import { Router, Request, Response } from "express";
import { randomBytes } from "crypto";
import { prisma } from "../db.js";
import { requireOwner } from "../middleware/requireOwner.js";
import { authMiddleware } from "../middleware/auth.js";
import { auditMoneyMutation } from "../lib/audit.js";
import { sendEmail } from "../lib/email.js";

/**
 * Quotes — create, send, track, and convert to invoices.
 *
 * Lifecycle:  draft → sent → accepted | declined → converted (accepted only)
 * The farmer marks a quote sent (WhatsApp/SMS share), the customer responds
 * (via the farmer, or self-serve on the public /q/<token> page), and an
 * accepted quote converts into a real invoice in one tap — the new invoice
 * keeps the quote's items and carries the quote number in its notes for a
 * clean audit trail. Conversion is idempotent and transactional.
 */
const router = Router();

function newResponseToken(): string {
  return randomBytes(18).toString("base64url"); // 24 chars, ~144 bits of entropy
}

// Authenticated farm-owner routes below; the two public ones re-declare theirs.
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
        // Every quote gets a token at creation so it's ready the moment it's sent.
        responseToken: newResponseToken(),
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
      // Safety net: guarantee a token exists when the quote goes out.
      if (!quote.responseToken) update.responseToken = newResponseToken();
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

// ══════════════════════════════════════════════════════════════════
//  PUBLIC customer-facing endpoints — NO auth. Access is gated solely
//  by the unguessable 24-char token in the link (≈144 bits, same class
//  as password-reset links). Responses only reveal what the customer
//  already received on the quote itself, and only while it's actionable.
// ══════════════════════════════════════════════════════════════════

export const quotesPublic = Router();

// GET /api/quotes-public/:token — quote details for the response page
quotesPublic.get("/:token", async (req: Request, res: Response) => {
  try {
    const quote = await prisma.quote.findUnique({
      where: { responseToken: String(req.params.token) },
      select: {
        quoteNumber: true, items: true, totalAmount: true, status: true,
        validUntil: true, notes: true, sentAt: true, respondedAt: true,
        farm: { select: { name: true, location: true, county: true } },
        customer: { select: { name: true } },
      },
    });
    if (!quote) return res.status(404).json({ error: "Quote not found or link is invalid" });
    // Minimal projection for unknown viewers: don't leak contact details.
    res.json({
      quoteNumber: quote.quoteNumber,
      farmName: quote.farm.name,
      farmLocation: [quote.farm.location, quote.farm.county].filter(Boolean).join(", ") || null,
      customerName: quote.customer?.name || null,
      items: quote.items,
      totalAmount: quote.totalAmount,
      status: quote.status,
      validUntil: quote.validUntil,
      notes: quote.notes,
    });
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/quotes-public/:token/respond — customer accept/decline
quotesPublic.post("/:token/respond", async (req: Request, res: Response) => {
  try {
    const decision = req.body?.decision === "accept" ? "accept" : req.body?.decision === "decline" ? "decline" : null;
    if (!decision) return res.status(400).json({ error: "Decision must be accept or decline" });

    const quote = await prisma.quote.findUnique({
      where: { responseToken: String(req.params.token) },
      select: { id: true, farmId: true, quoteNumber: true, status: true, validUntil: true },
    });
    if (!quote) return res.status(404).json({ error: "Quote not found or link is invalid" });
    if (quote.status === "accepted") return res.json({ status: "accepted", alreadyDone: true });
    if (quote.status === "declined") return res.json({ status: "declined", alreadyDone: true });
    if (quote.status !== "sent") return res.status(400).json({ error: "This quote can no longer be responded to" });
    if (quote.validUntil && new Date(quote.validUntil) < new Date()) {
      return res.status(400).json({ error: "This quote has expired — contact the farm for a fresh quote" });
    }

    const newStatus = decision === "accept" ? "accepted" : "declined";
    await prisma.quote.update({ where: { id: quote.id }, data: { status: newStatus, respondedAt: new Date(), updatedAt: new Date() } });
    auditMoneyMutation({
      action: `quote.${newStatus}`,
      entityType: "Quote",
      entityId: quote.id,
      details: { quoteNumber: quote.quoteNumber, from: "sent", to: newStatus, via: "public_link" },
    });

    // ── Notify the farmer: in-app banner + email ──
    try {
      const farm = await prisma.farm.findUnique({
        where: { id: quote.farmId },
        select: { name: true, owner: { select: { name: true, email: true } } },
      });
      if (farm) {
        // In-app: farm-scoped announcement banner on the farmer's dashboard.
        await prisma.announcement.create({
          data: {
            farmId: quote.farmId,
            active: true,
            message: newStatus === "accepted"
              ? `🎉 Customer accepted quote ${quote.quoteNumber} — open Quotes to convert it into an invoice.`
              : `Customer declined quote ${quote.quoteNumber}. Open Quotes to follow up or send a revised offer.`,
            link: "/quotes",
          },
        });
        // Email: instant ping so the farmer hears about it even out of the app.
        const ownerEmail = farm.owner?.email;
        if (ownerEmail) {
          const subject = newStatus === "accepted"
            ? `🎉 Quote ${quote.quoteNumber} accepted — ready to invoice`
            : `Quote ${quote.quoteNumber} was declined`;
          const html = newStatus === "accepted" ? `<!DOCTYPE html>
<html><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /></head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;padding:40px 20px;"><tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
      <tr><td style="background-color:#166534;padding:24px 32px;text-align:center;"><span style="font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">🌿 Wangari</span></td></tr>
      <tr><td style="padding:32px;">
        <h2 style="margin:0 0 8px;font-size:20px;color:#166534;">🎉 Your quote was accepted!</h2>
        <p style="margin:0 0 16px;font-size:14px;color:#334155;">Quote <strong>${quote.quoteNumber}</strong> was accepted by the customer via their quote link.</p>
        <p style="margin:0 0 24px;font-size:13px;color:#64748b;">Open Wangari and convert it into an invoice with one tap — the customer is waiting to pay.</p>
        <div style="text-align:center;margin-top:8px;"><a href="${process.env.FRONTEND_URL || "https://wangari.imeantech.com"}/quotes" style="display:inline-block;background-color:#166534;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:8px;">Convert to Invoice →</a></div>
        <p style="margin:20px 0 0;font-size:11px;color:#64748b;text-align:center;">© ${new Date().getFullYear()} Wangari · imeantech.com</p>
      </td></tr>
    </table>
  </td></tr></table>
</body></html>` : `<!DOCTYPE html>
<html><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /></head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;padding:40px 20px;"><tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
      <tr><td style="background-color:#166534;padding:24px 32px;text-align:center;"><span style="font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">🌿 Wangari</span></td></tr>
      <tr><td style="padding:32px;">
        <h2 style="margin:0 0 8px;font-size:20px;color:#b45309;">Quote ${quote.quoteNumber} was declined</h2>
        <p style="margin:0 0 16px;font-size:14px;color:#334155;">The customer declined via their quote link.</p>
        <p style="margin:0 0 24px;font-size:13px;color:#64748b;">It's not personal — price and timing are the usual reasons. Consider sending a revised quote with a small adjustment or a payment plan.</p>
        <div style="text-align:center;margin-top:8px;"><a href="${process.env.FRONTEND_URL || "https://wangari.imeantech.com"}/quotes" style="display:inline-block;background-color:#b45309;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:8px;">Open Quotes →</a></div>
        <p style="margin:20px 0 0;font-size:11px;color:#64748b;text-align:center;">© ${new Date().getFullYear()} Wangari · imeantech.com</p>
      </td></tr>
    </table>
  </td></tr></table>
</body></html>`;
          await sendEmail({ to: ownerEmail, subject, html, template: "oneoff" }).catch(() => {});
        }
      }
    } catch (notifyErr: any) {
      // Never fail the customer's response because the notification hit a snag.
      console.error("Quote response notification failed:", notifyErr?.message);
    }

    res.json({ status: newStatus });
  } catch {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
