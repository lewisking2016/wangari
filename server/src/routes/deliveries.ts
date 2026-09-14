import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { requireOwner } from "../middleware/requireOwner.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware, requireOwner);

const COMMODITIES = ["milk", "coffee_cherry", "maize", "other"];

// GET /api/deliveries?commodity=milk — recent deliveries
router.get("/", async (req: Request, res: Response) => {
  try {
    const commodity = req.query.commodity as string | undefined;
    const data = await prisma.delivery.findMany({
      where: { farmId: req.user!.farmId!, ...(commodity ? { commodity } : {}) },
      orderBy: { date: "desc" },
      take: 60,
      include: { deductions: true },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed to fetch deliveries" });
  }
});

// POST /api/deliveries — log a delivery (the daily 30-second action)
router.post("/", async (req: Request, res: Response) => {
  try {
    const { date, commodity, quantity, unit, buyer, receiptRef, unitPrice, notes, deductions } = req.body;
    if (!quantity || !buyer || !COMMODITIES.includes(commodity)) {
      return res.status(400).json({ error: "quantity, buyer and a valid commodity are required" });
    }
    const qty = Number(quantity);
    const price = unitPrice != null ? Number(unitPrice) : null;
    const expectedPay = price != null ? qty * price : null;

    const delivery = await prisma.delivery.create({
      data: {
        farmId: req.user!.farmId!,
        date: date ? new Date(date) : new Date(),
        commodity,
        quantity: qty,
        unit: unit || (commodity === "milk" ? "litres" : "kg"),
        buyer,
        receiptRef: receiptRef || null,
        unitPrice: price,
        expectedPay,
        createdBy: req.user!.userId,
        deductions: {
          create: (Array.isArray(deductions) ? deductions : [])
            .filter((d: any) => d && d.label && Number(d.amount) > 0)
            .map((d: any) => ({ label: String(d.label), amount: Number(d.amount) })),
        },
      },
      include: { deductions: true },
    });
    res.status(201).json(delivery);
  } catch (error) {
    console.error("Create delivery error:", error);
    res.status(500).json({ error: "Failed to record delivery" });
  }
});

// PATCH /api/deliveries/:id — mark paid / disputed / edit price
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const existing = await prisma.delivery.findFirst({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    if (!existing) return res.status(404).json({ error: "Not found" });

    const data: Record<string, any> = {};
    if (req.body.status && ["pending", "paid", "disputed"].includes(req.body.status)) data.status = req.body.status;
    if (req.body.paidAmount !== undefined) data.paidAmount = Number(req.body.paidAmount);
    if (req.body.unitPrice !== undefined) {
      const price = Number(req.body.unitPrice);
      data.unitPrice = price;
      data.expectedPay = Number(existing.quantity) * price;
    }
    if (req.body.notes !== undefined) data.notes = req.body.notes || null;

    const updated = await prisma.delivery.update({ where: { id: existing.id }, data, include: { deductions: true } });
    res.json(updated);
  } catch (error) {
    res.status(500).json({ error: "Failed to update delivery" });
  }
});

// DELETE /api/deliveries/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.delivery.deleteMany({ where: { id: Number(req.params.id), farmId: req.user!.farmId! } });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed to delete delivery" });
  }
});

// GET /api/deliveries/statement?month=2026-09 — the payout statement
// Gross deliveries − deductions − input expenses (from farm finance records)
// = the net the farmer should expect. This is the dispute-proof number.
router.get("/statement", async (req: Request, res: Response) => {
  try {
    const now = new Date();
    const month = typeof req.query.month === "string" && /^\d{4}-\d{2}$/.test(req.query.month as string)
      ? req.query.month as string
      : `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
    const [y, m] = month.split("-").map(Number);
    const start = new Date(Date.UTC(y, m - 1, 1));
    const end = new Date(Date.UTC(y, m, 1));
    const farmId = req.user!.farmId!;

    const [deliveries, transactions] = await Promise.all([
      prisma.delivery.findMany({
        where: { farmId, date: { gte: start, lt: end } },
        include: { deductions: true },
        orderBy: { date: "asc" },
      }),
      prisma.transaction.findMany({
        where: { farmId, type: "expense", date: { gte: start, lt: end } },
        select: { category: true, amount: true, description: true, date: true },
      }),
    ]);

    const gross = deliveries.reduce((s, d) => s + Number(d.expectedPay ?? 0), 0);
    const deductionTotal = deliveries.reduce((s, d) => s + d.deductions.reduce((x, dd) => x + Number(dd.amount), 0), 0);
    const inputExpenses = transactions.reduce((s, t) => s + Number(t.amount), 0);
    const paid = deliveries.reduce((s, d) => s + Number(d.paidAmount ?? 0), 0);

    // Per-commodity breakdown so mixed farms see each line
    const byCommodity: Record<string, { quantity: number; gross: number; deliveries: number }> = {};
    for (const d of deliveries) {
      const c = (byCommodity[d.commodity] ??= { quantity: 0, gross: 0, deliveries: 0 });
      c.quantity += Number(d.quantity);
      c.gross += Number(d.expectedPay ?? 0);
      c.deliveries += 1;
    }

    res.json({
      month,
      deliveries: deliveries.length,
      gross,
      deductions: deductionTotal,
      inputExpenses,
      net: gross - deductionTotal - inputExpenses,
      paid,
      outstanding: gross - deductionTotal - paid,
      byCommodity,
      deliveryList: deliveries.map((d) => ({
        id: d.id, date: d.date, commodity: d.commodity, quantity: Number(d.quantity),
        unit: d.unit, buyer: d.buyer, expectedPay: d.expectedPay ? Number(d.expectedPay) : null,
        status: d.status,
        deductions: d.deductions.map((dd) => ({ label: dd.label, amount: Number(dd.amount) })),
      })),
      expenseList: transactions.map((t) => ({ category: t.category, amount: Number(t.amount), description: t.description, date: t.date })),
    });
  } catch (error) {
    console.error("Statement error:", error);
    res.status(500).json({ error: "Failed to build statement" });
  }
});

export default router;
