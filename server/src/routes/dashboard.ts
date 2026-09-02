import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/dashboard
router.get("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const today = new Date();
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const weekAgo = new Date(today.getTime() - 7 * 86400000);
    const nextWeek = new Date(today.getTime() + 7 * 86400000);

    const [
      flocks,
      allFlocks,
      todayProd,
      monthIncome,
      monthExpense,
      recentTx,
      recentProd,
      lowStock,
      upcomingVax,
      recentMortality,
    ] = await Promise.all([
      prisma.flock.count({ where: { farmId, status: "active" } }),
      prisma.flock.findMany({ where: { farmId, status: "active" }, select: { currentCount: true, mortality: true, name: true } }),
      prisma.dailyProduction.findMany({ where: { farmId, date: today } }),
      prisma.transaction.aggregate({ where: { farmId, type: "income", date: { gte: monthStart } }, _sum: { amount: true } }),
      prisma.transaction.aggregate({ where: { farmId, type: "expense", date: { gte: monthStart } }, _sum: { amount: true } }),
      prisma.transaction.findMany({ where: { farmId }, orderBy: { createdAt: "desc" }, take: 5 }),
      prisma.dailyProduction.findMany({ where: { farmId }, orderBy: { date: "desc" }, take: 14, select: { date: true, eggsCollected: true, mortality: true } }),
      prisma.inventory.findMany({ where: { farmId, quantity: { lte: prisma.inventory.fields?.reorderLevel ?? 10 } } }).catch(() => []),
      prisma.vaccination.findMany({
        where: { flockId: { in: (await prisma.flock.findMany({ where: { farmId }, select: { id: true } })).map(f => f.id) }, status: "pending", scheduledDate: { gte: today, lte: nextWeek } },
        orderBy: { scheduledDate: "asc" },
        take: 5,
        include: { flock: { select: { name: true } } },
      }).catch(() => []),
      prisma.dailyProduction.findMany({
        where: { farmId, date: { gte: weekAgo } },
        select: { date: true, mortality: true },
        orderBy: { date: "asc" },
      }).catch(() => []),
    ]);

    const totalBirds = allFlocks.reduce((s, f) => s + f.currentCount, 0);
    const eggsToday = todayProd.reduce((s, p) => s + p.eggsCollected, 0);
    const mortalityToday = todayProd.reduce((s, p) => s + p.mortality, 0);

    // Mortality alerts — check for spikes in last 3 days
    const recentMortTotal = recentMortality.slice(-3).reduce((s, r) => s + r.mortality, 0);
    const avgMort = recentMortality.length > 0
      ? recentMortality.reduce((s, r) => s + r.mortality, 0) / recentMortality.length
      : 0;
    const mortalityAlerts = recentMortTotal > avgMort * 2 && recentMortTotal > 3
      ? [{ message: `High mortality: ${recentMortTotal} deaths in last 3 days`, severity: "high" as const }]
      : [];

    res.json({
      totalFlocks: flocks,
      totalBirds,
      eggsToday,
      mortalityToday,
      monthlyRevenue: Number(monthIncome._sum.amount || 0),
      monthlyExpenses: Number(monthExpense._sum.amount || 0),
      recentTransactions: recentTx,
      recentProduction: recentProd.reverse(),
      lowStock,
      upcomingVaccinations: upcomingVax,
      mortalityAlerts,
    });
  } catch (error) {
    console.error("Dashboard error:", error);
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
