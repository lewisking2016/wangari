import { Router, Request, Response } from "express";
import bcrypt from "bcryptjs";
import { prisma } from "../db.js";
import { adminLogin, requireAdmin, auditAdminAction, AdminRole } from "../lib/admin-auth.js";

/**
 * Super-admin API (Phase 1 of docs/ADMIN-BLUEPRINT.md).
 * Everything here is mounted at /api/admin and requires an admin JWT
 * (type:"admin") — customer and worker tokens are rejected by requireAdmin.
 * Every mutating request is audited with the acting admin identity.
 */
const router = Router();

// ─── Auth ─────────────────────────────────────────────────
// POST /api/admin/login — separate identity system from customer login.
router.post("/login", async (req: Request, res: Response) => {
  try {
    const { email, password } = req.body || {};
    if (!email || !password) {
      return res.status(400).json({ error: "Email and password required" });
    }
    const result = await adminLogin(email, password);
    if (!result) {
      return res.status(401).json({ error: "Invalid credentials" });
    }
    auditAdminAction(undefined, "admin.login", "admin", result.admin.id, { email: result.admin.email });
    res.json(result);
  } catch (error) {
    console.error("Admin login error:", error);
    res.status(500).json({ error: "Login failed" });
  }
});

// GET /api/admin/me — session check for the admin shell.
router.get("/me", requireAdmin(), (req: Request, res: Response) => {
  res.json({ admin: (req as any).admin });
});

// ─── M0: Overview ─────────────────────────────────────────
router.get("/overview", requireAdmin(["billing", "support", "support_read"]), async (_req: Request, res: Response) => {
  try {
    const now = new Date();
    const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);

    const [totalFarms, totalUsers, totalWorkers, activeSubs, recentUsers, recentPayments, openTickets] =
      await Promise.all([
        prisma.farm.count(),
        prisma.user.count({ where: { role: "farm_owner" } }),
        prisma.worker.count(),
        prisma.subscription.findMany({ where: { status: "active", expiresAt: { gt: now } }, select: { amount: true, planName: true } }),
        prisma.user.findMany({
          where: { role: "farm_owner", createdAt: { gte: sevenDaysAgo } },
          select: { id: true, name: true, email: true, createdAt: true },
          orderBy: { createdAt: "desc" },
          take: 8,
        }),
        prisma.subscription.findMany({
          where: { status: "active" },
          select: { id: true, userId: true, planName: true, amount: true, startsAt: true, reference: true, user: { select: { name: true, email: true } } },
          orderBy: { startsAt: "desc" },
          take: 8,
        }),
        // Tickets arrive in Phase 2 (model added then) — tolerate absence now.
        (async () => {
          const anyPrisma = prisma as any;
          if (!anyPrisma.ticket) return 0;
          return await anyPrisma.ticket.count({ where: { status: { in: ["open", "pending"] } } });
        })(),
      ]);

    // MRR: active subscription amounts (Decimal stored in KES major units)
    const mrr = activeSubs.reduce((sum, s) => sum + Number(s.amount), 0);
    const byPlan: Record<string, { count: number; revenue: number }> = {};
    for (const s of activeSubs) {
      const k = s.planName;
      byPlan[k] = byPlan[k] || { count: 0, revenue: 0 };
      byPlan[k].count += 1;
      byPlan[k].revenue += Number(s.amount);
    }

    // 7-day signup trend
    const signups: { date: string; count: number }[] = [];
    for (let i = 6; i >= 0; i--) {
      const day = new Date(now.getTime() - i * 24 * 60 * 60 * 1000);
      const start = new Date(day.getFullYear(), day.getMonth(), day.getDate());
      const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
      const count = await prisma.user.count({ where: { role: "farm_owner", createdAt: { gte: start, lt: end } } });
      signups.push({ date: start.toISOString().slice(0, 10), count });
    }

    res.json({
      totals: {
        farms: totalFarms,
        users: totalUsers,
        workers: totalWorkers,
        activeSubscriptions: activeSubs.length,
        mrrKes: Math.round(mrr),
        openTickets,
      },
      byPlan,
      signups,
      recentUsers,
      recentPayments,
    });
  } catch (error) {
    console.error("Admin overview error:", error);
    res.status(500).json({ error: "Failed to load overview" });
  }
});

// ─── M3: Plans CRUD ───────────────────────────────────────
router.get("/plans", requireAdmin(["billing", "support", "support_read"]), async (_req: Request, res: Response) => {
  try {
    const plans = await prisma.plan.findMany({ orderBy: { sortOrder: "asc" } });
    const counts = await prisma.subscription.groupBy({
      by: ["plan"],
      where: { status: "active", expiresAt: { gt: new Date() } },
      _count: true,
    });
    const countMap = Object.fromEntries(counts.map((c) => [c.plan, c._count]));
    res.json(plans.map((p) => ({ ...p, activeSubscriptions: countMap[p.id] || 0 })));
  } catch (error) {
    console.error("Admin plans list error:", error);
    res.status(500).json({ error: "Failed to load plans" });
  }
});

// Coerce to a finite number or undefined (rejects NaN from bad input).
function numOrUndef(v: unknown): number | undefined {
  if (v === undefined || v === null || v === "") return undefined;
  const n = Number(v);
  return Number.isFinite(n) ? n : undefined;
}

router.post("/plans", requireAdmin(["billing"]), async (req: Request, res: Response) => {
  try {
    const { id, name, description, amount, days, active, sortOrder } = req.body || {};
    const amt = numOrUndef(amount);
    const d = numOrUndef(days);
    if (!id || !name || amt === undefined || d === undefined) {
      return res.status(400).json({ error: "id, name, amount (pesewas) and days are required and must be numbers" });
    }
    const plan = await prisma.plan.create({
      data: { id: String(id).trim().toLowerCase(), name: String(name).trim(), description: description || null, amount: amt, days: d, active: active !== false, sortOrder: numOrUndef(sortOrder) || 0 },
    });
    auditAdminAction((req as any).admin, "admin.plan.create", "plan", plan.id, { name: plan.name, amount: plan.amount, days: plan.days });
    res.json(plan);
  } catch (error: any) {
    if (error?.code === "P2002") return res.status(409).json({ error: "A plan with that id already exists" });
    console.error("Admin plan create error:", error);
    res.status(500).json({ error: "Failed to create plan" });
  }
});

router.patch("/plans/:id", requireAdmin(["billing"]), async (req: Request, res: Response) => {
  try {
    const planId = String(req.params.id);
    const before = await prisma.plan.findUnique({ where: { id: planId } });
    if (!before) return res.status(404).json({ error: "Plan not found" });
    const { name, description, amount, days, active, sortOrder } = req.body || {};
    const amt = numOrUndef(amount);
    const d = numOrUndef(days);
    if ((amount !== undefined && amt === undefined) || (days !== undefined && d === undefined)) {
      return res.status(400).json({ error: "amount and days must be numbers" });
    }
    const plan = await prisma.plan.update({
      where: { id: planId },
      data: {
        ...(name !== undefined ? { name: String(name).trim() } : {}),
        ...(description !== undefined ? { description: description || null } : {}),
        ...(amt !== undefined ? { amount: amt } : {}),
        ...(d !== undefined ? { days: d } : {}),
        ...(active !== undefined ? { active: Boolean(active) } : {}),
        ...(sortOrder !== undefined ? { sortOrder: numOrUndef(sortOrder) || 0 } : {}),
      },
    });
    auditAdminAction((req as any).admin, "admin.plan.update", "plan", plan.id, {
      before: { name: before.name, amount: before.amount, days: before.days, active: before.active },
      after: { name: plan.name, amount: plan.amount, days: plan.days, active: plan.active },
    });
    res.json(plan);
  } catch (error) {
    console.error("Admin plan update error:", error);
    res.status(500).json({ error: "Failed to update plan" });
  }
});

// ─── M2: Billing & Subscriptions ──────────────────────────
router.get("/billing", requireAdmin(["billing", "support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const status = String(req.query.status || "all");
    const q = String(req.query.q || "").trim();
    const page = Math.max(1, Number(req.query.page) || 1);
    const pageSize = 20;
    const where: any = {};
    if (status !== "all") where.status = status;
    if (q) where.user = { OR: [{ name: { contains: q, mode: "insensitive" } }, { email: { contains: q, mode: "insensitive" } }] };

    const [rows, total] = await Promise.all([
      prisma.subscription.findMany({
        where,
        include: { user: { select: { id: true, name: true, email: true, role: true } } },
        orderBy: { startsAt: "desc" },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.subscription.count({ where }),
    ]);
    res.json({ rows, total, page, pageSize });
  } catch (error) {
    console.error("Admin billing list error:", error);
    res.status(500).json({ error: "Failed to load billing" });
  }
});

// Manual plan override / comp: create a compensated subscription (amount 0 = free comp).
router.post("/billing/override", requireAdmin(["billing"]), async (req: Request, res: Response) => {
  try {
    const { userId, plan, planName, days, amount } = req.body || {};
    if (!userId || !plan || !days) {
      return res.status(400).json({ error: "userId, plan and days are required" });
    }
    const startsAt = new Date();
    const expiresAt = new Date(startsAt.getTime() + Number(days) * 24 * 60 * 60 * 1000);
    const sub = await prisma.subscription.create({
      data: {
        userId: Number(userId),
        plan: String(plan),
        planName: String(planName || plan),
        amount: amount !== undefined ? Number(amount) : 0,
        status: "active",
        reference: `admin_override_${Date.now()}`,
        startsAt,
        expiresAt,
      },
    });
    auditAdminAction((req as any).admin, "admin.billing.override", "subscription", sub.id, { userId, plan, days, amount: sub.amount });
    res.json(sub);
  } catch (error) {
    console.error("Admin billing override error:", error);
    res.status(500).json({ error: "Failed to apply override" });
  }
});

// Cancel (deactivate) a subscription immediately.
router.post("/billing/:id/cancel", requireAdmin(["billing"]), async (req: Request, res: Response) => {
  try {
    const sub = await prisma.subscription.update({ where: { id: Number(req.params.id) }, data: { status: "cancelled" } });
    auditAdminAction((req as any).admin, "admin.billing.cancel", "subscription", sub.id, { userId: sub.userId, plan: sub.plan });
    res.json(sub);
  } catch (error) {
    console.error("Admin billing cancel error:", error);
    res.status(500).json({ error: "Failed to cancel subscription" });
  }
});

// ─── Audit trail ──────────────────────────────────────────
router.get("/audit", requireAdmin(["billing", "support", "support_read"]), async (req: Request, res: Response) => {
  try {
    const page = Math.max(1, Number(req.query.page) || 1);
    const pageSize = 30;
    const [rows, total] = await Promise.all([
      prisma.auditLog.findMany({
        include: { user: { select: { name: true, email: true } } },
        orderBy: { createdAt: "desc" },
        skip: (page - 1) * pageSize,
        take: pageSize,
      }),
      prisma.auditLog.count(),
    ]);
    res.json({ rows, total, page, pageSize });
  } catch (error) {
    console.error("Admin audit list error:", error);
    res.status(500).json({ error: "Failed to load audit log" });
  }
});

// ─── M10: System health ───────────────────────────────────
router.get("/system", requireAdmin(["billing", "support", "support_read"]), async (_req: Request, res: Response) => {
  try {
    const start = Date.now();
    await prisma.$queryRaw`SELECT 1`;
    const dbLatencyMs = Date.now() - start;
    const [farms, users, subs] = await Promise.all([prisma.farm.count(), prisma.user.count(), prisma.subscription.count()]);
    res.json({
      status: "ok",
      dbLatencyMs,
      counts: { farms, users, subs },
      uptimeSec: Math.round(process.uptime()),
      nodeVersion: process.version,
    });
  } catch (error) {
    console.error("Admin system error:", error);
    res.status(500).json({ error: "Health check failed" });
  }
});

export default router;
