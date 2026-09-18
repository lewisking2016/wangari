import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { requireOwner } from "../middleware/requireOwner.js";
import { authMiddleware } from "../middleware/auth.js";
import { auditMoneyMutation } from "../lib/audit.js";

/**
 * Quotes — create, send, track, and convert to invoices.
 *
 * Lifecycle:  draft → sent → accepted | declined → converted (accepted only)
 * The farmer marks a quote sent (WhatsApp/SMS share), the customer responds,
 * and an accepted quote converts into a real invoice in one tap — the new
 * invoice keeps the quote's items and carries the quote number in its notes
 * for a clean audit trail. Conversion is idempotent and transactional.
 */
const router = Router();
router.use(authMiddleware, requireOwner);

async function nextQuoteNumber(farmId: number): Promise<string> {
  const { nextDocCode } = await import("../lib/doc-codes.js");
  return prisma.$transaction((tx: any) => nextDocCode(tx, farmId, "quote"));
}

// GET /api/quotes?status= — list quotes with conversion status
router.get("/", async (req: Request, res: Response) => {
  try {
    const status = String(req.query.status || "");
    const data = await prisma.quote.findMany({
      where: {
        farmId: req.user!.farmId!,
        ...(status && status !== "all" ? { status } : {}),
      },
      orderBy: { createdAt: "desc" },
      take: 200,
      include: { customer: { select: { name: true, phone: true } } },
    });
    res.json(data);
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

// GET /api/quotes/stats — pipeline summary for the dashboard strip
router.get("/stats", async (req: Request, res: Response) => {
  try {
    const quotes = await prisma.quote.findMany({
      where: { farmId: req.user!.farmId! },
      select: { status: true, totalAmount: true },
    });
    const stats = { total: quotes.length, draft: 0, sent: 0, accepted: 0, declined: 0, converted: 0, expired: 0, acceptedValue: 0, conversionRate: 0 };
    for (const q of quotes) {
      const s = q.status as keyof typeof stats;
      if (typeof stats[s] === "number") (stats[s] as number) += 1;
      if (q.status === "accepted" || q.status === "converted") stats.acceptedValue += Number(q.totalAmount);
    }
    const decided = stats.accepted + stats.declined + stats.converted;
    stats.conversionRate = decided ? Math.round(((stats.accepted + stats.converted) / decided) * 100) : 0;
    res.json(stats);
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/quotes — create quote (status: draft or sent directly)
router.post("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const items = Array.isArray(req.body.items) ? req.body.items : [];
    if (!items.length) return res.status(400).json({ error: "At least one line item is required" });
    const quoteNumber = await nextQuoteNumber(farmId);
    const result = await prisma.quote.create({
      data: {
        farmId,
        customerId: req.body.customerId ? Number(req.body.customerId) : null,
        quoteNumber,
        items,
        totalAmount: Number(req.body.totalAmount),
        status: req.body.status === "sent" ? "sent" : "draft",
        sentAt: req.body.status === "sent" ? new Date() : null,
        validUntil: req.body.validUntil ? new Date(req.body.validUntil) : null,
        notes: req.body.notes || null,
      },
      include: { customer: { select: { name: true, phone: true } } },
    });
    auditMoneyMutation({
      userId: req.user!.userId,
      farmId,
      action: "quote.create",
      entityType: "Quote",
      entityId: result.id,
      details: { quoteNumber, totalAmount: Number(result.totalAmount), status: result.status },
    });
    res.status(201).json(result);
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/quotes/:id — edit draft, mark sent, or record the customer's response
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const quote = await prisma.quote.findFirst({ where: { id: Number(req.params.id), farmId: req.user!.farmId! } });
    if (!quote) return res.status(404).json({ error: "Not found" });
    if (quote.status === "converted") return res.status(400).json({ error: "Converted quotes are locked" });

    const update: any = { updatedAt: new Date() };
    const action = String(req.body.action || "");

    if (action === "mark-sent") {
      if (quote.status !== "draft") return res.status(400).json({ error: "Only drafts can be marked sent" });
      update.status = "sent";
      update.sentAt = new Date();
    } else if (action === "accept" || action === "decline") {
      if (quote.status !== "sent") return res.status(400).json({ error: "Only sent quotes can be accepted or declined" });
      update.status = action === "accept" ? "accepted" : "declined";
      update.respondedAt = new Date();
    } else {
      // plain edit — drafts only for money fields
      if (quote.status !== "draft") return res.status(400).json({ error: "Only drafts can be edited" });
      if (req.body.items !== undefined) update.items = req.body.items;
      if (req.body.totalAmount !== undefined) update.totalAmount = Number(req.body.totalAmount);
      if (req.body.customerId !== undefined) update.customerId = req.body.customerId ? Number(req.body.customerId) : null;
      if (req.body.validUntil !== undefined) update.validUntil = req.body.validUntil ? new Date(req.body.validUntil) : null;
      if (req.body.notes !== undefined) update.notes = req.body.notes;
    }

    const updated = await prisma.quote.update({ where: { id: quote.id }, data: update, include: { customer: { select: { name: true, phone: true } } } });
    if (update.status) {
      auditMoneyMutation({
        userId: req.user!.userId,
        farmId: req.user!.farmId,
        action: `quote.${update.status}`,
        entityType: "Quote",
        entityId: quote.id,
        details: { quoteNumber: quote.quoteNumber, from: quote.status, to: update.status },
      });
    }
    res.json(updated);
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/quotes/:id/convert — accepted quote → invoice (idempotent, transactional)
router.post("/:id/convert", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const quote = await prisma.quote.findFirst({ where: { id: Number(req.params.id), farmId } });
    if (!quote) return res.status(404).json({ error: "Not found" });
    if (quote.status !== "accepted") return res.status(400).json({ error: "Only accepted quotes can be converted" });

    const invoice = await prisma.$transaction(async (tx: any) => {
      if (quote.convertedInvoiceId) {
        return tx.invoice.findUnique({ where: { id: quote.convertedInvoiceId } });
      }
      const { nextDocCode } = await import("../lib/doc-codes.js");
      const invoiceNumber = await nextDocCode(tx, farmId, "invoice");
      const created = await tx.invoice.create({
        data: {
          farmId,
          customerId: quote.customerId,
          invoiceNumber,
          items: quote.items as any,
          totalAmount: quote.totalAmount,
          amountPaid: 0,
          paymentStatus: "pending",
          dueDate: quote.validUntil,
          notes: `Converted from quote ${quote.quoteNumber}`,
        },
      });
      await tx.quote.update({ where: { id: quote.id }, data: { status: "converted", convertedInvoiceId: created.id, updatedAt: new Date() } });
      return created;
    });

    auditMoneyMutation({
      userId: req.user!.userId,
      farmId,
      action: "quote.convert",
      entityType: "Quote",
      entityId: quote.id,
      details: { quoteNumber: quote.quoteNumber, invoiceNumber: invoice.invoiceNumber, totalAmount: Number(invoice.totalAmount) },
    });
    res.status(201).json(invoice);
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/quotes/:id — drafts only
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    const existing = await prisma.quote.findFirst({ where: { id: Number(req.params.id), farmId: req.user!.farmId! } });
    if (!existing) return res.status(404).json({ error: "Not found" });
    if (existing.status !== "draft") return res.status(400).json({ error: "Only drafts can be deleted — decline or convert it instead" });
    await prisma.quote.deleteMany({ where: { id: existing.id, farmId: req.user!.farmId! } });
    res.json({ success: true });
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
