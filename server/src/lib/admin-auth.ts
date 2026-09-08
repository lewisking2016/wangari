import { Request, Response, NextFunction } from "express";
import jwt from "jsonwebtoken";
import bcrypt from "bcryptjs";
import { prisma } from "../db.js";
import { JWT_SECRET } from "../middleware/auth.js";
import { auditMoneyMutation } from "./audit.js";

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

export function signAdminToken(payload: AdminTokenPayload): string {
  return jwt.sign({ ...payload, type: "admin" }, JWT_SECRET, { expiresIn: "4h" });
}

/** Login with email + password. Only users with an admin role may log in here. */
export async function adminLogin(email: string, password: string) {
  const user = await prisma.user.findUnique({ where: { email: String(email).toLowerCase().trim() } });
  if (!user || !user.password) return null;

  const adminRoles: string[] = ["super_admin", "billing", "support", "support_read"];
  if (!adminRoles.includes(user.role)) return null; // not an admin — reject silently

  const valid = await bcrypt.compare(password, user.password);
  if (!valid) return null;

  return {
    token: signAdminToken({ adminId: user.id, role: user.role as AdminRole, name: user.name }),
    admin: { id: user.id, name: user.name, email: user.email, role: user.role },
  };
}

export function requireAdmin(roles?: AdminRole[]) {
  return (req: Request, res: Response, next: NextFunction) => {
    const token = req.headers.authorization?.replace("Bearer ", "");
    if (!token) return res.status(401).json({ error: "Unauthorized" });

    try {
      const decoded = jwt.verify(token, JWT_SECRET) as AdminTokenPayload & { type?: string };
      if (decoded.type !== "admin" || !decoded.adminId) {
        return res.status(403).json({ error: "Admin token required" });
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
