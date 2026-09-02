import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/production
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.dailyProduction.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { date: "desc" },
      take: 30,
      include: { flock: { select: { name: true } } },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/production
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.dailyProduction.upsert({
      where: {
        flockId_date: {
          flockId: Number(req.body.flockId),
          date: new Date(req.body.date),
        },
      },
      update: {
        eggsCollected: Number(req.body.eggsCollected || 0),
        mortality: Number(req.body.mortality || 0),
        feedUsed: Number(req.body.feedUsed || 0),
        notes: req.body.notes || null,
      },
      create: {
        flockId: Number(req.body.flockId),
        farmId: req.user!.farmId!,
        date: new Date(req.body.date),
        eggsCollected: Number(req.body.eggsCollected || 0),
        mortality: Number(req.body.mortality || 0),
        feedUsed: Number(req.body.feedUsed || 0),
        notes: req.body.notes || null,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
