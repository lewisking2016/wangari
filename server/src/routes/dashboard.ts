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

    // Get farm info
    const farm = await prisma.farm.findUnique({
      where: { id: farmId },
      select: { name: true },
    });

    const [
      activeFlockCount,
      flocks,
      todayProd,
      monthIncome,
      monthExpense,
      recentTx,
      recentProd,
      inventory,
      upcomingVax,
      recentMortality,
    ] = await Promise.all([
      prisma.flock.count({ where: { farmId, status: "active" } }),
      prisma.flock.findMany({
        where: { farmId, status: "active" },
        select: { id: true, name: true, currentCount: true, mortality: true, totalCount: true },
      }),
      prisma.dailyProduction.findMany({ where: { farmId, date: today } }),
      prisma.transaction.aggregate({
        where: { farmId, type: "income", date: { gte: monthStart } },
        _sum: { amount: true },
      }),
      prisma.transaction.aggregate({
        where: { farmId, type: "expense", date: { gte: monthStart } },
        _sum: { amount: true },
      }),
      prisma.transaction.findMany({
        where: { farmId },
        orderBy: { createdAt: "desc" },
        take: 5,
      }),
      prisma.dailyProduction.findMany({
        where: { farmId },
        orderBy: { date: "desc" },
        take: 14,
        select: { date: true, eggsCollected: true, mortality: true },
      }),
      prisma.inventory.findMany({
        where: { farmId },
        select: { id: true, name: true, quantity: true, unit: true, reorderLevel: true },
      }).catch(() => []),
      prisma.vaccination.findMany({
        where: {
          flockId: { in: flocks.map((f) => f.id) },
          status: "pending",
          scheduledDate: { gte: today, lte: nextWeek },
        },
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

    const totalBirds = flocks.reduce((s, f) => s + f.currentCount, 0);
    const eggsToday = todayProd.reduce((s, p) => s + p.eggsCollected, 0);
    const mortalityToday = todayProd.reduce((s, p) => s + p.mortality, 0);

    // ─── Mortality Alerts ──────────────────────────────────
    const mortalityAlerts = flocks
      .filter((f) => f.totalCount > 0 && (f.mortality / f.totalCount) * 100 > 3)
      .map((f) => ({
        flockName: f.name,
        mortalityRate: Number(((f.mortality / f.totalCount) * 100).toFixed(1)),
        totalMortality: f.mortality,
      }));

    // ─── Low Stock Alerts ──────────────────────────────────
    const stockAlerts = inventory
      .filter((item) => item.reorderLevel > 0 && item.quantity <= item.reorderLevel)
      .map((item) => ({
        itemName: item.name,
        currentStock: item.quantity,
        unit: item.unit,
        reorderLevel: item.reorderLevel,
      }));

    // ─── Vaccination Alerts ────────────────────────────────
    const vaccinationAlerts = upcomingVax.map((v: any) => ({
      flockName: v.flock?.name || "Unknown",
      vaccineName: v.vaccineName || v.type || "Vaccination",
      dueDate: v.scheduledDate?.toISOString() || new Date().toISOString(),
    }));

    // ─── Weather (placeholder — integrate with API later) ──
    const weather = {
      temperature: 24,
      humidity: 65,
      windSpeed: 12,
      condition: "Partly Cloudy",
      icon: "cloud" as const,
    };

    res.json({
      farmName: farm?.name || "Your Farm",
      totalFlocks: activeFlockCount,
      totalBirds,
      eggsToday,
      mortalityToday,
      monthlyRevenue: Number(monthIncome._sum.amount || 0),
      monthlyExpenses: Number(monthExpense._sum.amount || 0),
      recentTransactions: recentTx,
      recentProduction: recentProd.reverse(),
      flocks: flocks.map((f) => ({
        id: f.id,
        name: f.name,
        totalBirds: f.currentCount,
      })),
      mortalityAlerts,
      stockAlerts,
      vaccinationAlerts,
      weather,
    });
  } catch (error) {
    console.error("Dashboard error:", error);
    res.status(500).json({ error: "Failed to load dashboard" });
  }
});

export default router;
