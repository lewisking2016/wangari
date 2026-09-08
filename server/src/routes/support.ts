import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

/**
 * Customer-facing support surface:
 * - GET  /api/announcements/active — the current banner (public within app)
 * - POST /api/support/tickets      — logged-in user opens a ticket
 * - GET  /api/support/tickets      — user's own tickets
 */
const router = Router();

// GET /api/announcements/active
router.get("/announcements/active", async (_req: Request, res: Response) => {
  try {
    const banner = await prisma.announcement.findFirst({
      where: { active: true },
      select: { id: true, message: true, link: true, createdAt: true },
    });
    res.json(banner || { id: null });
  } catch (error) {
    console.error("Active announcement error:", error);
    res.status(500).json({ error: "Failed" });
  }
});

// All ticket routes require a login.
router.use("/support/tickets", authMiddleware);

// POST /api/support/tickets — open a ticket with a first message.
router.post("/support/tickets", async (req: Request, res: Response) => {
  try {
    const userId = req.user!.userId;
    const { subject, body, priority } = req.body || {};
    if (!subject || !String(subject).trim()) return res.status(400).json({ error: "Subject is required" });
    if (!body || !String(body).trim()) return res.status(400).json({ error: "Message is required" });
    if (String(body).length > 5000) return res.status(400).json({ error: "Message too long (max 5000 chars)" });

    const ticket = await prisma.ticket.create({
      data: {
        subject: String(subject).trim().slice(0, 200),
        priority: ["low", "normal", "high"].includes(priority) ? priority : "normal",
        userId,
        farmId: req.user!.farmId ?? null,
        messages: {
          create: { authorType: "user", authorId: userId, body: String(body).trim() },
        },
      },
      include: { messages: true },
    });
    res.status(201).json({ id: ticket.id, subject: ticket.subject, status: ticket.status });
  } catch (error) {
    console.error("Ticket create error:", error);
    res.status(500).json({ error: "Failed to create ticket" });
  }
});

// GET /api/support/tickets — the user's own tickets (never anyone else's).
router.get("/support/tickets", async (req: Request, res: Response) => {
  try {
    const rows = await prisma.ticket.findMany({
      where: { userId: req.user!.userId },
      select: {
        id: true, subject: true, status: true, priority: true, createdAt: true, updatedAt: true,
        _count: { select: { messages: true } },
      },
      orderBy: { updatedAt: "desc" },
      take: 30,
    });
    res.json(rows.map((t) => ({ ...t, messageCount: t._count.messages, _count: undefined })));
  } catch (error) {
    console.error("Ticket list error:", error);
    res.status(500).json({ error: "Failed to load tickets" });
  }
});

export default router;
