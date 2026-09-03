import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/workers
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.worker.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { name: "asc" },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/workers
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.worker.create({
      data: {
        farmId: req.user!.farmId!,
        name: req.body.name,
        phone: req.body.phone || null,
        role: req.body.role || null,
        dailyWage: req.body.dailyWage ? Number(req.body.dailyWage) : null,
        createdBy: req.user!.userId,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/workers/:id — update worker
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const result = await prisma.worker.update({
      where: { id: Number(req.params.id) },
      data: { ...req.body, dailyWage: req.body.dailyWage ? Number(req.body.dailyWage) : undefined },
    });
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/workers/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.worker.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
