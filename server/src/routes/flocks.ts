import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/flocks
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.flock.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { createdAt: "desc" },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/flocks
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.flock.create({
      data: {
        farmId: req.user!.farmId!,
        name: req.body.name,
        breed: req.body.breed || null,
        type: req.body.type || "layers",
        initialCount: Number(req.body.initialCount),
        currentCount: Number(req.body.initialCount),
        hatchDate: req.body.hatchDate ? new Date(req.body.hatchDate) : null,
        createdBy: req.user!.userId,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/flocks/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.flock.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
