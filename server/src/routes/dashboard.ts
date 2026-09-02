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

    const [flocks, allFlocks, todayProd, monthIncome, monthExpense, recentTx, recentProd] =
      await Promise.all([
        prisma.flock.count({ where: { farmId, status: "active" } }),
        prisma.flock.findMany({ where: { farmId, status: "active" }, select: { currentCount: true } }),
        prisma.dailyProduction.findMany({ where: { farmId, date: today } }),
        prisma.transaction.aggregate({ where: { farmId, type: "income", date: { gte: monthStart } }, _sum: { amount: true } }),
        prisma.transaction.aggregate({ where: { farmId, type: "expense", date: { gte: monthStart } }, _sum: { amount: true } }),
        prisma.transaction.findMany({ where: { farmId }, orderBy: { createdAt: "desc" }, take: 5 }),
        prisma.dailyProduction.findMany({ where: { farmId }, orderBy: { date: "desc" }, take: 14, select: { date: true, eggsCollected: true, mortality: true } }),
      ]);

    const totalBirds = allFlocks.reduce((s, f) => s + f.currentCount, 0);
    const eggsToday = todayProd.reduce((s, p) => s + p.eggsCollected, 0);
    const mortalityToday = todayProd.reduce((s, p) => s + p.mortality, 0);

    res.json({
      totalFlocks: flocks,
      totalBirds,
      eggsToday,
      mortalityToday,
      monthlyRevenue: Number(monthIncome._sum.amount || 0),
      monthlyExpenses: Number(monthExpense._sum.amount || 0),
      recentTransactions: recentTx,
      recentProduction: recentProd.reverse(),
    });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
