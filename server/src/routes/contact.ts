import { Router, Request, Response } from "express";
import { prisma } from "../db.js";

/**
 * POST /api/contact — marketing site contact form → CRM lead.
 * Public but rate-limited (global /api limiter applies). Never leaks whether
 * an email already exists; always answers the same.
 */
const router = Router();

router.post("/contact", async (req: Request, res: Response) => {
  try {
    const { name, email, phone, message, company } = req.body || {};
    if (!name || !String(name).trim()) return res.status(400).json({ error: "Name is required" });
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email))) {
      return res.status(400).json({ error: "A valid email is required" });
    }
    if (!message || !String(message).trim()) return res.status(400).json({ error: "Message is required" });
    if (String(message).length > 3000) return res.status(400).json({ error: "Message too long (max 3000 chars)" });

    const cleanEmail = String(email).trim().toLowerCase();
    await prisma.crmContact.create({
      data: {
        name: String(name).trim().slice(0, 120),
        email: cleanEmail,
        phone: phone ? String(phone).trim().slice(0, 30) : null,
        company: company ? String(company).trim().slice(0, 120) : null,
        type: "lead",
        stage: "lead",
        source: "contact_form",
      },
    });
    // Store the message itself as the first note.
    const contact = await prisma.crmContact.findFirst({ where: { email: cleanEmail, source: "contact_form" }, orderBy: { id: "desc" } });
    if (contact) {
      await prisma.crmContactNote.create({
        data: { contactId: contact.id, body: `Contact form message:\n${String(message).trim()}` },
      });
    }

    res.json({ success: true });
  } catch (error) {
    console.error("Contact form error:", error);
    res.status(500).json({ error: "Failed to send message" });
  }
});

export default router;
