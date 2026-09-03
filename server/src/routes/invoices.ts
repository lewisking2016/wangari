import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// Generate invoice number: INV-YYYYMM-XXXX
function generateInvoiceNumber(): string {
  const now = new Date();
  const ym = now.getFullYear().toString() + String(now.getMonth() + 1).padStart(2, "0");
  const rand = Math.floor(Math.random() * 9000 + 1000);
  return `INV-${ym}-${rand}`;
}

// GET /api/invoices
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.invoice.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { createdAt: "desc" },
      take: 100,
      include: { customer: { select: { name: true, phone: true } } },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/invoices — create invoice (optionally linked to a sale)
router.post("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const result = await prisma.invoice.create({
      data: {
        farmId,
        saleId: req.body.saleId ? Number(req.body.saleId) : null,
        customerId: req.body.customerId ? Number(req.body.customerId) : null,
        invoiceNumber: generateInvoiceNumber(),
        items: req.body.items || [],
        totalAmount: Number(req.body.totalAmount),
        amountPaid: Number(req.body.amountPaid || 0),
        paymentStatus: req.body.paymentStatus || "pending",
        dueDate: req.body.dueDate ? new Date(req.body.dueDate) : null,
        notes: req.body.notes || null,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/invoices/from-sale/:saleId — auto-generate invoice from an existing sale
router.post("/from-sale/:saleId", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const sale = await prisma.sale.findFirst({
      where: { id: Number(req.params.saleId), farmId },
      include: { customer: { select: { name: true, phone: true } } },
    });
    if (!sale) return res.status(404).json({ error: "Sale not found" });

    // Check if invoice already exists for this sale
    const existing = await prisma.invoice.findFirst({ where: { saleId: sale.id } });
    if (existing) return res.json(existing);

    const result = await prisma.invoice.create({
      data: {
        farmId,
        saleId: sale.id,
        customerId: sale.customerId,
        invoiceNumber: generateInvoiceNumber(),
        items: sale.items,
        totalAmount: Number(sale.totalAmount),
        amountPaid: Number(sale.amountPaid),
        paymentStatus: sale.paymentStatus,
        dueDate: null,
        notes: null,
      },
      include: { customer: { select: { name: true, phone: true } } },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/invoices/:id — record payment or update
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const invoice = await prisma.invoice.findFirst({ where: { id: Number(req.params.id), farmId: req.user!.farmId! } });
    if (!invoice) return res.status(404).json({ error: "Not found" });

    const updateData: any = {};
    if (req.body.amountPaid !== undefined) {
      const newPaid = Number(invoice.amountPaid) + Number(req.body.amountPaid);
      updateData.amountPaid = newPaid;
      updateData.paymentStatus = newPaid >= Number(invoice.totalAmount) ? "paid" : "partial";
    }
    if (req.body.notes !== undefined) updateData.notes = req.body.notes;
    if (req.body.dueDate !== undefined) updateData.dueDate = req.body.dueDate ? new Date(req.body.dueDate) : null;

    const updated = await prisma.invoice.update({ where: { id: invoice.id }, data: updateData });
    res.json(updated);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/invoices/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.invoice.deleteMany({ where: { id: Number(req.params.id), farmId: req.user!.farmId! } });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
