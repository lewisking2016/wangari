import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/attendance
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.attendance.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { date: "desc" },
      take: 100,
      include: { worker: { select: { name: true, role: true, dailyWage: true } } },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/attendance — clock in or create record
router.post("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const workerId = Number(req.body.workerId);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const now = new Date().toTimeString().slice(0, 5);

    // Check if worker already has a record today
    const existing = await prisma.attendance.findFirst({
      where: { workerId, farmId, date: today },
    });

    if (existing) {
      // Already clocked in today — this is a clock out
      const updated = await prisma.attendance.update({
        where: { id: existing.id },
        data: { checkOut: now, status: "present" },
      });
      return res.json(updated);
    }

    // New clock in
    const result = await prisma.attendance.create({
      data: {
        workerId,
        farmId,
        date: today,
        checkIn: now,
        status: "present",
        notes: req.body.notes || null,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/attendance/:id — update status or notes
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const result = await prisma.attendance.update({
      where: { id: Number(req.params.id) },
      data: { ...req.body },
    });
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/attendance/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.attendance.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
