import { Request, Response, NextFunction } from "express";

/**
 * Blocks worker tokens from owner-only endpoints.
 * Workers authenticate with farm code + PIN; their tokens carry workerId.
 * Workers should only hit /api/worker/* and a few read-only endpoints.
 */
export function requireOwner(req: Request, res: Response, next: NextFunction) {
  if ((req.user as any)?.workerId || (req.user as any)?.role === "worker") {
    return res.status(403).json({ error: "Workers do not have access to this resource" });
  }
  next();
}
