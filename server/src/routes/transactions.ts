import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { requireOwner } from "../middleware/requireOwner.js";
import { authMiddleware } from "../middleware/auth.js";
import { auditMoneyMutation } from "../lib/audit.js";

const router = Router();
router.use(authMiddleware, requireOwner);

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
    const amount = Number(req.body.amount);
    if (isNaN(amount) || amount <= 0) {
      return res.status(400).json({ error: "Amount must be a positive number" });
    }
    if (!req.body.type || !req.body.date) {
      return res.status(400).json({ error: "Type and date are required" });
    }

    const result = await prisma.transaction.create({
      data: {
        farmId: req.user!.farmId!,
        type: req.body.type,
        category: req.body.category || null,
        amount,
        description: req.body.description || null,
        date: new Date(req.body.date),
        paymentMethod: req.body.paymentMethod || "cash",
        createdBy: req.user!.userId,
      },
    });
    auditMoneyMutation({
      userId: req.user!.userId,
      farmId: req.user!.farmId,
      action: "transaction.create",
      entityType: "Transaction",
      entityId: result.id,
      details: { type: result.type, amount: Number(result.amount), category: result.category },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/transactions/:id
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const id = Number(req.params.id);
    const existing = await prisma.transaction.findFirst({ where: { id, farmId: req.user!.farmId! } });
    if (!existing) return res.status(404).json({ error: "Not found" });

    const amount = Number(req.body.amount);
    if (req.body.amount !== undefined && (isNaN(amount) || amount <= 0)) {
      return res.status(400).json({ error: "Amount must be a positive number" });
    }

    const result = await prisma.transaction.update({
      where: { id: existing.id },
      data: {
        type: req.body.type ?? existing.type,
        category: req.body.category ?? existing.category,
        amount: req.body.amount !== undefined ? amount : existing.amount,
        description: req.body.description ?? existing.description,
        paymentMethod: req.body.paymentMethod ?? existing.paymentMethod,
      },
    });
    auditMoneyMutation({
      userId: req.user!.userId,
      farmId: req.user!.farmId,
      action: "transaction.update",
      entityType: "Transaction",
      entityId: result.id,
      details: { before: { amount: Number(existing.amount), type: existing.type }, after: { amount: Number(result.amount), type: result.type } },
    });
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/transactions/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    const id = Number(req.params.id);
    const existing = await prisma.transaction.findFirst({ where: { id, farmId: req.user!.farmId! } });
    if (!existing) return res.status(404).json({ error: "Not found" });
    await prisma.transaction.deleteMany({ where: { id, farmId: req.user!.farmId! } });
    auditMoneyMutation({
      userId: req.user!.userId,
      farmId: req.user!.farmId,
      action: "transaction.delete",
      entityType: "Transaction",
      entityId: id,
      details: { deleted: { amount: Number(existing.amount), type: existing.type, description: existing.description } },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
