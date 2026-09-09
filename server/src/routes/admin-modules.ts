import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { requireAdmin, auditAdminAction } from "../lib/admin-auth.js";
import { sendEmail, emailTemplates } from "../lib/email.js";

/**
 * Admin modules — farms/users management, promo codes, tickets, announcements.
 * Mounted under /api/admin alongside routes/admin.ts. Every mutation audited.
 */
const router = Router();

// ─── M1: Farms (tenants) ──────────────────────────────────
router.get("/farms", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const q = String(req.query.q || "").trim();
    const page = Math.max(1, Number(req.query.page) || 1);
    const status = String(req.query.status || "all");
    const pageSize = 20;
    const now = new Date();
    const weekAhead = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);

    const where: any = q
      ? {
          OR: [
            { name: { contains: q, mode: "insensitive" } },
            { code: { contains: q, mode: "insensitive" } },
            { owner: { email: { contains: q, mode: "insensitive" } } },
            { owner: { name: { contains: q, mode: "insensitive" } } },
          ],
        }
      : {};

    const [rows, total, allFarms] = await Promise.all([
      prisma.farm.findMany({
        where,
        include: {
          owner: { select: { id: true, name: true, email: true } },
          _count: { select: { workers: true, flocks: true } },
        },
        orderBy: { id: "desc" },
      }),
      prisma.farm.count({ where }),
      prisma.farm.findMany({
        select: { ownerId: true, owner: { select: { subscriptions: { where: { status: "active" }, select: { expiresAt: true } } } } },
      }) as unknown as Promise<{ ownerId: number; owner: { subscriptions: { expiresAt: Date }[] } }[]>,
    ]);

    // Latest subscription per farm (via owner) for the plan column.
    const ownerIds = rows.map((r) => r.ownerId);
    const subs = await prisma.subscription.findMany({
      where: { userId: { in: ownerIds }, status: "active" },
      orderBy: { expiresAt: "desc" },
    });
    const subByUser = new Map(subs.map((s) => [s.userId, s]));

    const enrich = (f: (typeof rows)[number]) => {
      const sub = subByUser.get(f.ownerId);
      return {
        id: f.id,
        name: f.name,
        code: f.code,
        location: f.location,
        county: f.county,
        owner: f.owner,
        workers: f._count.workers,
        flocks: f._count.flocks,
        plan: sub ? { name: sub.planName, status: sub.status, expiresAt: sub.expiresAt } : null,
        createdAt: f.createdAt,
      };
    };

    // Platform-wide summary (independent of search/filter)
    const summary = {
      total: allFarms.length,
      active: allFarms.filter((f) => (f.owner?.subscriptions ?? []).some((s) => s.expiresAt > now)).length,
      expiring: allFarms.filter((f) => (f.owner?.subscriptions ?? []).some((s) => s.expiresAt > now && s.expiresAt <= weekAhead)).length,
      trial: allFarms.filter((f) => !(f.owner?.subscriptions ?? []).some((s) => s.expiresAt > now)).length,
    };

    // Status filter applied after enrichment
    const statusOf = (f: (typeof rows)[number]) => {
      const sub = subByUser.get(f.ownerId);
      if (!sub) return "trial";
      if (sub.expiresAt <= weekAhead) return "expiring";
      return "active";
    };
    const filtered = status === "all" ? rows : rows.filter((f) => statusOf(f) === status);
    const paged = filtered.slice((page - 1) * pageSize, page * pageSize);

    res.json({
      rows: paged.map(enrich),
      total: filtered.length,
      page,
      pageSize,
      summary,
    });
  } catch (error) {
    console.error("Admin farms list error:", error);
    res.status(500).json({ error: "Failed to load farms" });
  }
});

// Farm detail: one screen with plan, workforce, flocks, subscription history, tickets.
router.get("/farms/:id", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const id = Number(req.params.id);
    const farm = await prisma.farm.findUnique({
      where: { id },
      include: {
        owner: { select: { id: true, name: true, email: true, phone: true, createdAt: true, emailVerified: true } },
        _count: { select: { workers: true, flocks: true } },
      },
    });
    if (!farm) return res.status(404).json({ error: "Farm not found" });

    const anyPrisma = prisma as any;
    const [workers, flocks, subs, tickets] = await Promise.all([
      anyPrisma.worker.findMany({
        where: { farmId: id },
        select: { id: true, name: true, role: true, status: true, createdAt: true },
        orderBy: { id: "desc" },
        take: 20,
      }).catch(() => []),
      anyPrisma.flock.findMany({
        where: { farmId: id },
        select: { id: true, name: true, species: true, birdCount: true, status: true },
        orderBy: { id: "desc" },
        take: 20,
      }).catch(() => []),
      prisma.subscription.findMany({
        where: { userId: farm.ownerId },
        orderBy: { startsAt: "desc" },
        take: 10,
        select: { id: true, planName: true, amount: true, status: true, reference: true, startsAt: true, expiresAt: true },
      }),
      anyPrisma.ticket.findMany({
        where: { userId: farm.ownerId },
        select: { id: true, subject: true, status: true, createdAt: true },
        orderBy: { id: "desc" },
        take: 5,
      }).catch(() => []),
    ]);

    res.json({
      farm: {
        id: farm.id, name: farm.name, code: farm.code, location: farm.location, county: farm.county, createdAt: farm.createdAt,
      },
      owner: farm.owner,
      workers: farm._count.workers,
      flocks: farm._count.flocks,
      workerList: workers,
      flockList: flocks,
      subscriptions: subs,
      tickets,
    });
  } catch (error) {
    console.error("Admin farm detail error:", error);
    res.status(500).json({ error: "Failed to load farm" });
  }
});

// Controlled farm action: extend the owner's subscription (audited).
router.post("/farms/:id/extend", requireAdmin(["support", "billing"]), async (req: Request, res: Response) => {
  try {
    const days = Number(req.body?.days);
    if (!Number.isFinite(days) || days <= 0 || days > 365) {
      return res.status(400).json({ error: "days must be 1-365" });
    }
    const farm = await prisma.farm.findUnique({ where: { id: Number(req.params.id) }, select: { ownerId: true, name: true } });
    if (!farm) return res.status(404).json({ error: "Farm not found" });

    const current = await prisma.subscription.findFirst({
      where: { userId: farm.ownerId, status: "active", expiresAt: { gt: new Date() } },
      orderBy: { expiresAt: "desc" },
    });
    const base = current ? current.expiresAt : new Date();
    const expiresAt = new Date(base.getTime() + days * 24 * 60 * 60 * 1000);

    const sub = current
      ? await prisma.subscription.update({ where: { id: current.id }, data: { expiresAt } })
      : await prisma.subscription.create({
          data: {
            userId: farm.ownerId,
            plan: "starter_monthly",
            planName: "Goodwill extension",
            amount: 0,
            status: "active",
            reference: `admin_extend_${Date.now()}`,
            startsAt: new Date(),
            expiresAt,
          },
        });

    auditAdminAction((req as any).admin, "admin.farm.extend", "farm", farm.name ? Number(req.params.id) : null, {
      farm: farm.name, days, newExpiresAt: expiresAt,
    });
    res.json(sub);
  } catch (error) {
    console.error("Admin farm extend error:", error);
    res.status(500).json({ error: "Failed to extend" });
  }
});

// ─── M1: Users (accounts) ─────────────────────────────────
router.get("/users", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const q = String(req.query.q || "").trim();
    const page = Math.max(1, Number(req.query.page) || 1);
    const pageSize = 20;
    const where: any = q
      ? { OR: [{ name: { contains: q, mode: "insensitive" } }, { email: { contains: q, mode: "insensitive" } }, { phone: { contains: q } }] }
      : {};

    const [rows, total] = await Promise.all([
      prisma.user.findMany({
        where,
        select: {
          id: true, name: true, email: true, phone: true, role: true,
          emailVerified: true, createdAt: true, tokenVersion: true,
          ownedFarms: { select: { id: true, name: true } },
        },
        orderBy: { id: "desc" },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.user.count({ where }),
    ]);
    res.json({ rows, total, page, pageSize });
  } catch (error) {
    console.error("Admin users list error:", error);
    res.status(500).json({ error: "Failed to load users" });
  }
});

// Force-logout a user (bump tokenVersion) — the "suspend session" control.
router.post("/users/:id/force-logout", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const user = await prisma.user.update({
      where: { id: Number(req.params.id) },
      data: { tokenVersion: { increment: 1 } },
      select: { id: true, email: true },
    });
    auditAdminAction((req as any).admin, "admin.user.force-logout", "user", user.id, { email: user.email });
    res.json({ success: true });
  } catch (error) {
    console.error("Admin force-logout error:", error);
    res.status(500).json({ error: "Failed" });
  }
});

// Manually verify a user's email (support control).
router.post("/users/:id/verify-email", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const user = await prisma.user.update({
      where: { id: Number(req.params.id) },
      data: { emailVerified: new Date() },
      select: { id: true, email: true },
    });
    auditAdminAction((req as any).admin, "admin.user.verify-email", "user", user.id, { email: user.email });
    res.json({ success: true });
  } catch (error) {
    console.error("Admin verify-email error:", error);
    res.status(500).json({ error: "Failed" });
  }
});

// ─── M4: Promo codes ──────────────────────────────────────
router.get("/promos", requireAdmin(["billing", "support", "support_read"]), async (_req: Request, res: Response) => {
  try {
    const rows = await prisma.promoCode.findMany({
      include: { _count: { select: { redemptions: true } } },
      orderBy: { createdAt: "desc" },
    });
    res.json(rows.map((p) => ({ ...p, redemptions: p._count.redemptions, _count: undefined })));
  } catch (error) {
    console.error("Admin promos list error:", error);
    res.status(500).json({ error: "Failed to load promo codes" });
  }
});

router.post("/promos", requireAdmin(["billing"]), async (req: Request, res: Response) => {
  try {
    const { code, type, discountType, value, maxRedemptions, partnerName, expiresAt } = req.body || {};
    const clean = String(code || "").trim().toUpperCase();
    if (!clean || !/^[A-Z0-9_-]{3,24}$/.test(clean)) {
      return res.status(400).json({ error: "Code must be 3-24 chars (A-Z, 0-9, dash, underscore)" });
    }
    if (!["discount", "partnership", "credit"].includes(type)) {
      return res.status(400).json({ error: "type must be discount, partnership or credit" });
    }
    const val = Number(value);
    if (!Number.isFinite(val) || val <= 0) {
      return res.status(400).json({ error: "value must be a positive number" });
    }
    if (discountType === "percent" && val > 100) {
      return res.status(400).json({ error: "percent discount cannot exceed 100" });
    }
    const promo = await prisma.promoCode.create({
      data: {
        code: clean,
        type,
        discountType: discountType === "percent" ? "percent" : "fixed",
        value: Math.round(val),
        maxRedemptions: maxRedemptions ? Number(maxRedemptions) : null,
        partnerName: partnerName || null,
        expiresAt: expiresAt ? new Date(expiresAt) : null,
        createdBy: (req as any).admin?.adminId,
      },
    });
    auditAdminAction((req as any).admin, "admin.promo.create", "promo", promo.id, { code: promo.code, type, value: promo.value });
    res.status(201).json(promo);
  } catch (error: any) {
    if (error?.code === "P2002") return res.status(409).json({ error: "That code already exists" });
    console.error("Admin promo create error:", error);
    res.status(500).json({ error: "Failed to create promo code" });
  }
});

router.patch("/promos/:id", requireAdmin(["billing"]), async (req: Request, res: Response) => {
  try {
    const { active, maxRedemptions, expiresAt } = req.body || {};
    const promo = await prisma.promoCode.update({
      where: { id: String(req.params.id) },
      data: {
        ...(active !== undefined ? { active: Boolean(active) } : {}),
        ...(maxRedemptions !== undefined ? { maxRedemptions: maxRedemptions ? Number(maxRedemptions) : null } : {}),
        ...(expiresAt !== undefined ? { expiresAt: expiresAt ? new Date(expiresAt) : null } : {}),
      },
    });
    auditAdminAction((req as any).admin, "admin.promo.update", "promo", promo.id, { active: promo.active, maxRedemptions: promo.maxRedemptions });
    res.json(promo);
  } catch (error) {
    console.error("Admin promo update error:", error);
    res.status(500).json({ error: "Failed to update promo code" });
  }
});

// ─── M6: Tickets ──────────────────────────────────────────
router.get("/tickets", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const status = String(req.query.status || "all");
    const where: any = status === "all" ? {} : { status };
    const rows = await prisma.ticket.findMany({
      where,
      include: { user: { select: { name: true, email: true } }, _count: { select: { messages: true } } },
      orderBy: { updatedAt: "desc" },
      take: 100,
    });
    res.json(rows.map((t) => ({ ...t, messageCount: t._count.messages, _count: undefined })));
  } catch (error) {
    console.error("Admin tickets list error:", error);
    res.status(500).json({ error: "Failed to load tickets" });
  }
});

router.get("/tickets/:id", requireAdmin(["support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const ticket = await prisma.ticket.findUnique({
      where: { id: Number(req.params.id) },
      include: {
        user: { select: { id: true, name: true, email: true } },
        messages: { orderBy: { createdAt: "asc" } },
      },
    });
    if (!ticket) return res.status(404).json({ error: "Ticket not found" });
    res.json(ticket);
  } catch (error) {
    console.error("Admin ticket detail error:", error);
    res.status(500).json({ error: "Failed to load ticket" });
  }
});

// Admin reply + status change in one call.
router.post("/tickets/:id/reply", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const { body, status } = req.body || {};
    if (!body || !String(body).trim()) return res.status(400).json({ error: "Reply body required" });
    const admin = (req as any).admin;
    const ticket = await prisma.ticket.findUnique({ where: { id: Number(req.params.id) }, select: { id: true, status: true } });
    if (!ticket) return res.status(404).json({ error: "Ticket not found" });

    const message = await prisma.ticketMessage.create({
      data: { ticketId: ticket.id, authorType: "admin", authorId: admin?.adminId, body: String(body).trim() },
    });
    const nextStatus = ["open", "pending", "solved", "closed"].includes(status) ? status : "pending";
    await prisma.ticket.update({ where: { id: ticket.id }, data: { status: nextStatus } });
    auditAdminAction(admin, "admin.ticket.reply", "ticket", ticket.id, { status: nextStatus });

    // Notify the ticket owner by email (if they have an address).
    const fullTicket = await prisma.ticket.findUnique({
      where: { id: ticket.id },
      select: { subject: true, user: { select: { id: true, email: true } } },
    });
    const userEmail = fullTicket?.user?.email;
    if (userEmail) {
      const tpl = emailTemplates.ticketReply(ticket.id, fullTicket.subject, String(body).trim());
      await sendEmail({ to: userEmail, subject: tpl.subject, html: tpl.html, template: "ticket_reply", userId: fullTicket.user?.id });
    }

    res.status(201).json(message);
  } catch (error) {
    console.error("Admin ticket reply error:", error);
    res.status(500).json({ error: "Failed to reply" });
  }
});

// ─── M8: Announcements ────────────────────────────────────
router.get("/announcements", requireAdmin(["support", "support_read"]), async (_req: Request, res: Response) => {
  try {
    res.json(await prisma.announcement.findMany({ orderBy: { createdAt: "desc" }, take: 50 }));
  } catch (error) {
    console.error("Admin announcements list error:", error);
    res.status(500).json({ error: "Failed to load announcements" });
  }
});

router.post("/announcements", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const { message, link } = req.body || {};
    if (!message || !String(message).trim()) return res.status(400).json({ error: "Message required" });
    // One active banner at a time — creating a new one retires the old.
    await prisma.announcement.updateMany({ where: { active: true }, data: { active: false } });
    const ann = await prisma.announcement.create({
      data: { message: String(message).trim(), link: link || null },
    });
    auditAdminAction((req as any).admin, "admin.announcement.create", "announcement", ann.id, { message: ann.message });
    res.status(201).json(ann);
  } catch (error) {
    console.error("Admin announcement create error:", error);
    res.status(500).json({ error: "Failed" });
  }
});

router.post("/announcements/:id/deactivate", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const ann = await prisma.announcement.update({ where: { id: Number(req.params.id) }, data: { active: false } });
    auditAdminAction((req as any).admin, "admin.announcement.deactivate", "announcement", ann.id, {});
    res.json(ann);
  } catch (error) {
    console.error("Admin announcement deactivate error:", error);
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
