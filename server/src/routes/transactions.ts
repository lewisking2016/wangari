import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/transactions
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.transaction.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { date: "desc" },
      take: 100,
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/transactions
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.transaction.create({
      data: {
        farmId: req.user!.farmId!,
        type: req.body.type,
        category: req.body.category || null,
        amount: Number(req.body.amount),
        description: req.body.description || null,
        date: new Date(req.body.date),
        paymentMethod: req.body.paymentMethod || "cash",
        createdBy: req.user!.userId,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/transactions/:id
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const result = await prisma.transaction.update({
      where: { id: Number(req.params.id) },
      data: {
        type: req.body.type,
        category: req.body.category,
        amount: Number(req.body.amount),
        description: req.body.description,
        paymentMethod: req.body.paymentMethod,
      },
    });
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/transactions/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.transaction.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
