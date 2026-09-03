import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/audit — list activity logs
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.auditLog.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { createdAt: "desc" },
      take: 100,
      include: { user: { select: { name: true } } },
    });
    const result = data.map((log: any) => ({
      ...log,
      userName: log.user?.name || "System",
    }));
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/audit — record an activity (internal use)
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.auditLog.create({
      data: {
        userId: req.user!.userId,
        farmId: req.user!.farmId!,
        action: req.body.action,
        entityType: req.body.entityType || null,
        entityId: req.body.entityId || null,
        details: req.body.details || null,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
