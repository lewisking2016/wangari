import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { requireOwner } from "../middleware/requireOwner.js";
import { authMiddleware } from "../middleware/auth.js";
import { auditMoneyMutation } from "../lib/audit.js";

const router = Router();
router.use(authMiddleware, requireOwner);

// GET /api/sales
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.sale.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { saleDate: "desc" },
      take: 100,
      include: { customer: { select: { name: true } } },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/sales
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.sale.create({
      data: {
        farmId: req.user!.farmId!,
        customerId: req.body.customerId ? Number(req.body.customerId) : null,
        saleDate: new Date(req.body.saleDate || new Date()),
        items: req.body.items || "",
        totalAmount: Number(req.body.totalAmount),
        amountPaid: Number(req.body.amountPaid || 0),
        paymentStatus: req.body.paymentStatus || "pending",
        createdBy: req.user!.userId,
      },
    });

    // Auto-generate invoice for this sale
    try {
      const ym = new Date().getFullYear().toString() + String(new Date().getMonth() + 1).padStart(2, "0");
      const rand = Math.floor(Math.random() * 9000 + 1000);
      await prisma.invoice.create({
        data: {
          farmId: req.user!.farmId!,
          saleId: result.id,
          customerId: result.customerId,
          invoiceNumber: `INV-${ym}-${rand}`,
          items: result.items as any,
          totalAmount: Number(result.totalAmount),
          amountPaid: Number(result.amountPaid),
          paymentStatus: result.paymentStatus,
        },
      });
    } catch { /* invoice creation failed, not critical */ }

    auditMoneyMutation({
      userId: req.user!.userId,
      farmId: req.user!.farmId,
      action: "sale.create",
      entityType: "Sale",
      entityId: result.id,
      details: { totalAmount: Number(result.totalAmount), amountPaid: Number(result.amountPaid), paymentStatus: result.paymentStatus, customerId: result.customerId },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/sales/:id — record partial payment
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const sale = await prisma.sale.findFirst({ where: { id: Number(req.params.id), farmId: req.user!.farmId! } });
    if (!sale) return res.status(404).json({ error: "Not found" });
    const payment = Number(req.body.amountPaid || 0);
    if (isNaN(payment) || payment <= 0) return res.status(400).json({ error: "Amount must be a positive number" });
    const newPaid = Number(sale.amountPaid) + payment;
    const status = newPaid >= Number(sale.totalAmount) ? "paid" : "partial";
    const updated = await prisma.sale.update({ where: { id: sale.id }, data: { amountPaid: newPaid, paymentStatus: status } });
    auditMoneyMutation({
      userId: req.user!.userId,
      farmId: req.user!.farmId,
      action: "sale.payment",
      entityType: "Sale",
      entityId: sale.id,
      details: { payment, newPaid, status },
    });
    res.json(updated);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/sales/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    const id = Number(req.params.id);
    const existing = await prisma.sale.findFirst({ where: { id, farmId: req.user!.farmId! } });
    if (!existing) return res.status(404).json({ error: "Not found" });
    await prisma.sale.deleteMany({ where: { id, farmId: req.user!.farmId! } });
    auditMoneyMutation({
      userId: req.user!.userId,
      farmId: req.user!.farmId,
      action: "sale.delete",
      entityType: "Sale",
      entityId: id,
      details: { deleted: { totalAmount: Number(existing.totalAmount), amountPaid: Number(existing.amountPaid), paymentStatus: existing.paymentStatus } },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
