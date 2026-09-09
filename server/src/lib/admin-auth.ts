import { Request, Response, NextFunction } from "express";
import jwt from "jsonwebtoken";
import bcrypt from "bcryptjs";
import { prisma } from "../db.js";
import { JWT_SECRET } from "../middleware/auth.js";
import { auditMoneyMutation } from "./audit.js";
import { verifyTotp, hashCode } from "./totp.js";

/**
 * Admin authentication — SEPARATE identity system from customers (per
 * docs/ADMIN-BLUEPRINT.md). Admin tokens carry { adminId, role } and a
 * short 4h expiry. Customer JWTs (userId/workerId) are rejected here,
 * and admin tokens are rejected by the customer middleware — no sharing.
 */

export type AdminRole = "super_admin" | "billing" | "support" | "support_read";

// What each admin role may do. Code-defined permission map (blueprint §2 M9).
export const ADMIN_PERMISSIONS: Record<AdminRole, string[]> = {
  super_admin: ["*"],
  billing: ["overview.read", "plans.write", "billing.read", "billing.write", "audit.read"],
  support: [
    "overview.read", "farms.read", "farms.write", "users.read", "users.write",
    "billing.read", "tickets.read", "tickets.write", "emails.read", "emails.write", "audit.read",
  ],
  support_read: [
    "overview.read", "farms.read", "users.read", "billing.read",
    "tickets.read", "emails.read", "audit.read",
  ],
};

export function can(role: AdminRole, permission: string): boolean {
  const perms = ADMIN_PERMISSIONS[role];
  if (!perms) return false;
  return perms.includes("*") || perms.includes(permission);
}

export interface AdminTokenPayload {
  adminId: number;
  role: AdminRole;
  name: string;
}

// Separate signing secret for admin tokens — a leaked farm-app JWT_SECRET
// cannot be used to mint super-admin tokens. Falls back to JWT_SECRET only in
// development; production must set ADMIN_JWT_SECRET.
export const ADMIN_JWT_SECRET = process.env.ADMIN_JWT_SECRET || (process.env.NODE_ENV === "production" ? (() => { throw new Error("ADMIN_JWT_SECRET must be set in production"); })() : JWT_SECRET);

export function signAdminToken(payload: AdminTokenPayload, tokenVersion = 0): string {
  // tv = tokenVersion. Bumping the user's tokenVersion instantly revokes every
  // issued admin token (password change, sign-out-everywhere).
  return jwt.sign({ ...payload, type: "admin", tv: tokenVersion }, ADMIN_JWT_SECRET, { expiresIn: "4h" });
}

export { verifyTotp };

/**
 * Login with email + password (+ TOTP token when MFA is enabled).
 * Only users with an admin role may log in here.
 *
 * MFA flow: correct password + MFA enabled + no code → returns
 * { mfaRequired: true } with NO token. Client re-posts with totpCode.
 * A code that was provided but is wrong → { mfaInvalid: true } (route maps
 * to 401 so the UI can show "invalid code"). A valid recovery code is
 * accepted once and consumed.
 */
export async function adminLogin(
  email: string,
  password: string,
  totpCode?: string
): Promise<
  | null
  | { token: string; admin: { id: number; name: string; email: string; role: string } }
  | { mfaRequired: true }
  | { mfaInvalid: true }
> {
  const user = await prisma.user.findUnique({ where: { email: String(email).toLowerCase().trim() } });
  if (!user || !user.password) return null;

  const adminRoles: string[] = ["super_admin", "billing", "support", "support_read"];
  if (!adminRoles.includes(user.role)) return null; // not an admin — reject silently

  const valid = await bcrypt.compare(password, user.password);
  if (!valid) return null;

  const full = await prisma.user.findUnique({ where: { id: user.id }, select: { tokenVersion: true } });

  const mfaEnabled = !!(user.totpEnabledAt && user.totpSecret);
  if (mfaEnabled) {
    const code = String(totpCode || "").trim();
    if (!code) return { mfaRequired: true };

    if (verifyTotp(user.totpSecret!, code)) {
      // TOTP OK — fall through to token issuance.
    } else {
      // Try a recovery code (single-use). Compare hashed.
      const hashes: string[] = user.recoveryCodes ? JSON.parse(user.recoveryCodes) : [];
      const h = hashCode(code);
      const idx = hashes.indexOf(h);
      if (idx === -1) return { mfaInvalid: true }; // code given but wrong → explicit rejection
      hashes.splice(idx, 1);
      await prisma.user.update({ where: { id: user.id }, data: { recoveryCodes: JSON.stringify(hashes) } });
    }
  }

  return {
    token: signAdminToken({ adminId: user.id, role: user.role as AdminRole, name: user.name }, full?.tokenVersion ?? 0),
    admin: { id: user.id, name: user.name, email: user.email, role: user.role },
  };
}

export function requireAdmin(roles?: AdminRole[]) {
  return async (req: Request, res: Response, next: NextFunction) => {
    const token = req.headers.authorization?.replace("Bearer ", "");
    if (!token) return res.status(401).json({ error: "Unauthorized" });

    try {
      const decoded = jwt.verify(token, ADMIN_JWT_SECRET) as AdminTokenPayload & { type?: string; tv?: number };
      if (decoded.type !== "admin" || !decoded.adminId) {
        return res.status(403).json({ error: "Admin token required" });
      }
      // Revocation check: token carries the tokenVersion at sign time; a bump
      // (password change / sign-out-everywhere) invalidates every old token.
      const current = await prisma.user.findUnique({ where: { id: decoded.adminId }, select: { tokenVersion: true } });
      if (!current || (decoded.tv ?? 0) !== (current.tokenVersion || 0)) {
        return res.status(401).json({ error: "Session revoked — sign in again" });
      }
      if (roles && !roles.includes(decoded.role) && decoded.role !== "super_admin") {
        return res.status(403).json({ error: "Insufficient admin role" });
      }
      (req as any).admin = decoded;
      next();
    } catch {
      return res.status(401).json({ error: "Invalid or expired admin token" });
    }
  };
}

/**
 * Immutable audit entry for every admin action (blueprint ground-rule #3).
 * Fire-and-forget: audit failure never blocks the admin operation.
 */
export function auditAdminAction(
  admin: AdminTokenPayload | undefined,
  action: string,
  entityType: string,
  entityId: string | number | null,
  details?: Record<string, unknown>
): void {
  auditMoneyMutation({
    userId: admin?.adminId ?? null,
    farmId: null,
    action,
    entityType,
    entityId: typeof entityId === "string" ? null : entityId,
    details: { ...(details || {}), _entityKey: entityType, _entityIdStr: entityId ?? undefined, _actor: `admin:${admin?.adminId}:${admin?.role}` },
  });
}
