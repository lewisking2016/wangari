import { Request, Response, NextFunction } from "express";

/**
 * Blocks worker tokens from owner-only endpoints, and rejects farm-owner
 * sessions that have no farm attached (fresh registrations before onboarding
 * completes). Without this, every farm-scoped route crashes with
 * PrismaClientValidationError (`id: null`) instead of one clear message.
 */
export function requireOwner(req: Request, res: Response, next: NextFunction) {
  const user = req.user as any;
  if (user?.workerId || user?.role === "worker") {
    return res.status(403).json({ error: "Workers do not have access to this resource" });
  }
  if (user?.userId && (user.farmId === null || user.farmId === undefined)) {
    return res.status(403).json({
      error: "No farm found for this account. Create a farm or accept a farm invite first.",
      needsFarm: true,
    });
  }
  next();
}
