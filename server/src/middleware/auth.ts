import { Request, Response, NextFunction } from "express";
import jwt from "jsonwebtoken";
import { prisma } from "../db.js";

// Fail hard without a real secret in production (CVE-2026-49352-style bypass)
export const JWT_SECRET = process.env.JWT_SECRET || (process.env.NODE_ENV === "production" ? (() => { throw new Error("JWT_SECRET must be set in production"); })() : "wangari-dev-secret-change-in-production");

export interface AuthUser {
  userId?: number;
  workerId?: number;
  farmId: number | null;
}

declare global {
  namespace Express {
    interface Request {
      user?: AuthUser;
    }
  }
}

export async function authMiddleware(req: Request, res: Response, next: NextFunction) {
  const token = req.headers.authorization?.replace("Bearer ", "");

  if (!token) {
    return res.status(401).json({ error: "Unauthorized" });
  }

  try {
    const decoded = jwt.verify(token, JWT_SECRET) as AuthUser & { role?: string };
    req.user = decoded;

    // A worker token carries workerId + role "worker", never userId.
    // Skip the subscription gate for workers (owner-gated endpoints already reject non-owners elsewhere).
    if (decoded.role === "worker" || decoded.workerId) {
      return next();
    }

    // Exempt endpoints: auth, trial status, paystack, subscriptions
    const url = req.originalUrl || req.url || "";
    const isExempt =
      url.includes("/api/auth") ||
      url.includes("/api/trial") ||
      url.includes("/api/paystack") ||
      url.includes("/api/subscriptions");

    if (isExempt) {
      return next();
    }

    const now = new Date();
    const user = await prisma.user.findUnique({
      where: { id: decoded.userId! },
      select: { trialEndsAt: true, createdAt: true },
    });

    if (!user) {
      return res.status(401).json({ error: "User not found" });
    }

    let trialActive = false;
    if (user.trialEndsAt) {
      trialActive = now < user.trialEndsAt;
    } else if (user.createdAt) {
      const fourteenDays = new Date(user.createdAt.getTime() + 14 * 24 * 60 * 60 * 1000);
      trialActive = now < fourteenDays;
    }

    if (trialActive) {
      return next();
    }

    const activeSub = await prisma.subscription.findFirst({
      where: {
        userId: decoded.userId!,
        status: "active",
        expiresAt: { gt: now },
      },
    });

    if (activeSub) {
      return next();
    }

    return res.status(403).json({
      error: "Your 14-day free trial has expired. Please subscribe to continue using Wangari.",
      trialExpired: true,
    });
  } catch (error: any) {
    if (error?.name === "JsonWebTokenError" || error?.name === "TokenExpiredError") {
      return res.status(401).json({ error: "Invalid or expired token" });
    }
    console.error("Auth middleware error:", error);
    return res.status(500).json({ error: "Authentication failed" });
  }
}

export function generateToken(userId: number, farmId: number | null): string {
  return jwt.sign({ userId, farmId }, JWT_SECRET, { expiresIn: "7d" });
}
