import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { requireAdmin, auditAdminAction } from "../lib/admin-auth.js";
import { sendEmail } from "../lib/email.js";

/**
 * Admin CRM (M5) + Email ops (M7).
 * CRM: leads/partners/customers pipeline with notes. Marketing contact form
 * feeds leads in via POST /api/contact.
 * Email: browsable log of every send, failed-send retry, one-off compose.
 */
const router = Router();

const STAGES = ["lead", "contacted", "demo", "trial", "customer", "churned"];

// ─── CRM: contacts ────────────────────────────────────────
router.get("/crm/contacts", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const stage = String(req.query.stage || "all");
    const type = String(req.query.type || "all");
    const q = String(req.query.q || "").trim();
    const where: any = {};
    if (STAGES.includes(stage)) where.stage = stage;
    if (["lead", "partner", "customer"].includes(type)) where.type = type;
    if (q) {
      where.OR = [
        { name: { contains: q, mode: "insensitive" } },
        { email: { contains: q, mode: "insensitive" } },
        { company: { contains: q, mode: "insensitive" } },
      ];
    }
    const rows = await prisma.crmContact.findMany({
      where,
      include: { _count: { select: { notes: true } } },
      orderBy: { updatedAt: "desc" },
      take: 200,
    });
    res.json(rows.map((c) => ({ ...c, noteCount: c._count.notes, _count: undefined })));
  } catch (error) {
    console.error("Admin CRM list error:", error);
    res.status(500).json({ error: "Failed to load contacts" });
  }
});

router.post("/crm/contacts", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const { name, email, phone, company, type, stage, source } = req.body || {};
    if (!name || !String(name).trim()) return res.status(400).json({ error: "Name required" });
    const contact = await prisma.crmContact.create({
      data: {
        name: String(name).trim(),
        email: email ? String(email).trim().toLowerCase() : null,
        phone: phone || null,
        company: company || null,
        type: ["lead", "partner", "customer"].includes(type) ? type : "lead",
        stage: STAGES.includes(stage) ? stage : "lead",
        source: source || "manual",
        ownerId: (req as any).admin?.adminId,
      },
    });
    auditAdminAction((req as any).admin, "admin.crm.create", "crm_contact", contact.id, { name: contact.name, type: contact.type });
    res.status(201).json(contact);
  } catch (error) {
    console.error("Admin CRM create error:", error);
    res.status(500).json({ error: "Failed to create contact" });
  }
});

// Move a contact through the pipeline + edit fields.
router.patch("/crm/contacts/:id", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const { stage, type, name, email, phone, company } = req.body || {};
    if (stage !== undefined && !STAGES.includes(stage)) {
      return res.status(400).json({ error: `stage must be one of: ${STAGES.join(", ")}` });
    }
    const before = await prisma.crmContact.findUnique({ where: { id: Number(req.params.id) }, select: { stage: true, type: true } });
    if (!before) return res.status(404).json({ error: "Contact not found" });
    const contact = await prisma.crmContact.update({
      where: { id: Number(req.params.id) },
      data: {
        ...(stage !== undefined ? { stage } : {}),
        ...(type !== undefined ? { type } : {}),
        ...(name !== undefined ? { name: String(name).trim() } : {}),
        ...(email !== undefined ? { email: email ? String(email).trim().toLowerCase() : null } : {}),
        ...(phone !== undefined ? { phone: phone || null } : {}),
        ...(company !== undefined ? { company: company || null } : {}),
      },
    });
    auditAdminAction((req as any).admin, "admin.crm.update", "crm_contact", contact.id, {
      stage: before.stage !== contact.stage ? `${before.stage} → ${contact.stage}` : undefined,
      type: before.type !== contact.type ? `${before.type} → ${contact.type}` : undefined,
    });
    res.json(contact);
  } catch (error) {
    console.error("Admin CRM update error:", error);
    res.status(500).json({ error: "Failed to update contact" });
  }
});

router.delete("/crm/contacts/:id", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    await prisma.crmContact.delete({ where: { id: Number(req.params.id) } });
    auditAdminAction((req as any).admin, "admin.crm.delete", "crm_contact", Number(req.params.id), {});
    res.json({ success: true });
  } catch (error) {
    console.error("Admin CRM delete error:", error);
    res.status(500).json({ error: "Failed to delete contact" });
  }
});

// Add a note (activity log on the contact).
router.post("/crm/contacts/:id/notes", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const { body } = req.body || {};
    if (!body || !String(body).trim()) return res.status(400).json({ error: "Note body required" });
    const note = await prisma.crmContactNote.create({
      data: { contactId: Number(req.params.id), body: String(body).trim(), authorId: (req as any).admin?.adminId },
    });
    res.status(201).json(note);
  } catch (error) {
    console.error("Admin CRM note error:", error);
    res.status(500).json({ error: "Failed to add note" });
  }
});

router.get("/crm/contacts/:id/notes", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const notes = await prisma.crmContactNote.findMany({
      where: { contactId: Number(req.params.id) },
      orderBy: { createdAt: "desc" },
      take: 50,
    });
    res.json(notes);
  } catch (error) {
    console.error("Admin CRM notes list error:", error);
    res.status(500).json({ error: "Failed to load notes" });
  }
});

// ─── Email ops ────────────────────────────────────────────
router.get("/emails", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const status = String(req.query.status || "all");
    const where: any = status === "all" ? {} : { status };
    const [rows, total] = await Promise.all([
      prisma.emailLog.findMany({ where, orderBy: { createdAt: "desc" }, take: 100 }),
      prisma.emailLog.count({ where }),
    ]);
    const failed = await prisma.emailLog.count({ where: { status: "failed" } });
    res.json({ rows, total, failed });
  } catch (error) {
    console.error("Admin email log error:", error);
    res.status(500).json({ error: "Failed to load email log" });
  }
});

// Resend a failed email by re-sending the same subject with a generic body note.
router.post("/emails/:id/resend", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const log = await prisma.emailLog.findUnique({ where: { id: Number(req.params.id) } });
    if (!log) return res.status(404).json({ error: "Email not found" });
    await sendEmail({
      to: log.to,
      subject: `${log.subject}`,
      html: `<p style="color:#334155;font-size:14px;">This is a re-send of an earlier message.</p><p style="color:#64748B;font-size:13px;">Original subject: ${log.subject}</p>`,
      template: log.template || "oneoff",
      userId: log.userId,
    });
    auditAdminAction((req as any).admin, "admin.email.resend", "email_log", log.id, { to: log.to });
    res.json({ success: true });
  } catch (error) {
    console.error("Admin email resend error:", error);
    res.status(500).json({ error: "Failed to resend" });
  }
});

// One-off announcement email to a single address (marketing sends later).
router.post("/emails/send", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const { to, subject, body } = req.body || {};
    if (!to || !subject || !body) return res.status(400).json({ error: "to, subject and body are required" });
    await sendEmail({
      to: String(to).trim().toLowerCase(),
      subject: String(subject).trim(),
      html: `<p style="color:#334155;font-size:14px;line-height:1.6;">${String(body).replace(/\n/g, "<br/>")}</p>`,
      template: "oneoff",
    });
    auditAdminAction((req as any).admin, "admin.email.send", "email_log", null, { to, subject });
    res.json({ success: true });
  } catch (error) {
    console.error("Admin email send error:", error);
    res.status(500).json({ error: "Failed to send" });
  }
});

export default router;
