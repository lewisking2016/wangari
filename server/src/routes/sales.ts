import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

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
    const newPaid = Number(sale.amountPaid) + Number(req.body.amountPaid || 0);
    const status = newPaid >= Number(sale.totalAmount) ? "paid" : "partial";
    const updated = await prisma.sale.update({ where: { id: sale.id }, data: { amountPaid: newPaid, paymentStatus: status } });
    res.json(updated);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/sales/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.sale.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
