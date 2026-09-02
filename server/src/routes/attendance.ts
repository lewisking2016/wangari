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
      take: 30,
      include: { worker: { select: { name: true } } },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/attendance
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.attendance.create({
      data: {
        workerId: Number(req.body.workerId),
        farmId: req.user!.farmId!,
        date: new Date(req.body.date),
        checkIn: req.body.checkIn || null,
        checkOut: req.body.checkOut || null,
        status: req.body.status || "present",
        notes: req.body.notes || null,
      },
    });
    res.status(201).json(result);
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
