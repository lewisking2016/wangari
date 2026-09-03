import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/export — aggregated farm data for reports
router.get("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;

    const [farm, flocks, crops, production, transactions, sales, customers, inventory, workers, invoices] = await Promise.all([
      prisma.farm.findUnique({ where: { id: farmId } }),
      prisma.flock.findMany({ where: { farmId, status: "active" }, include: { vaccinations: true, production: { orderBy: { date: "desc" }, take: 30 } } }),
      prisma.crop.findMany({ where: { farmId }, include: { harvests: true, health: true, applications: true } }),
      prisma.dailyProduction.findMany({ where: { farmId }, orderBy: { date: "desc" }, take: 90, include: { flock: { select: { name: true, type: true } } } }),
      prisma.transaction.findMany({ where: { farmId }, orderBy: { date: "desc" }, take: 365 }),
      prisma.sale.findMany({ where: { farmId }, orderBy: { saleDate: "desc" }, take: 100, include: { customer: { select: { name: true, phone: true } } } }),
      prisma.customer.findMany({ where: { farmId } }),
      prisma.inventory.findMany({ where: { farmId } }),
      prisma.worker.findMany({ where: { farmId } }),
      prisma.invoice.findMany({ where: { farmId }, orderBy: { createdAt: "desc" }, take: 100 }),
    ]);

    // Compute financials
    const income = transactions.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
    const expenses = transactions.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);

    // Production totals
    const totalEggs = production.reduce((s: number, r: any) => s + (r.eggsCollected || 0), 0);
    const totalMilk = production.reduce((s: number, r: any) => s + Number(r.milkCollected || 0), 0);
    const totalMeat = production.reduce((s: number, r: any) => s + Number(r.weightGain || 0), 0);
    const totalFeedUsed = production.reduce((s: number, r: any) => s + Number(r.feedUsed || 0), 0);
    const totalMortality = production.reduce((s: number, r: any) => s + (r.mortality || 0), 0);

    // Inventory value
    const inventoryValue = inventory.reduce((s: number, i: any) => s + Number(i.quantity) * Number(i.unitCost), 0);

    // Livestock value
    const livestockValue = flocks.reduce((s: number, f: any) => s + Number(f.initialCount) * Number(f.costPerAnimal || 0), 0);

    // Monthly financials
    const monthlyData: Record<string, { income: number; expense: number }> = {};
    transactions.forEach((t: any) => {
      const d = new Date(t.date);
      const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
      if (!monthlyData[key]) monthlyData[key] = { income: 0, expense: 0 };
      if (t.type === "income") monthlyData[key].income += Number(t.amount);
      else monthlyData[key].expense += Number(t.amount);
    });

    // Daily wages
    const totalDailyWages = workers.reduce((s: number, w: any) => s + Number(w.dailyWage || 0), 0);

    // Outstanding credit
    const outstandingCredit = sales.reduce((s: number, sale: any) => s + (Number(sale.totalAmount) - Number(sale.amountPaid)), 0);

    res.json({
      farm: { name: farm?.name, location: farm?.location, county: farm?.county, farmType: farm?.farmType },
      livestock: {
        total: flocks.reduce((s: number, f: any) => s + (f.currentCount || 0), 0),
        groups: flocks.length,
        speciesBreakdown: flocks.reduce((acc: Record<string, number>, f: any) => { acc[f.type || "other"] = (acc[f.type || "other"] || 0) + (f.currentCount || 0); return acc; }, {}),
        value: livestockValue,
      },
      crops: {
        total: crops.length,
        types: [...new Set(crops.map((c: any) => c.cropType))],
        value: crops.reduce((s: number, c: any) => s + c.harvests.reduce((hs: number, h: any) => hs + Number(h.quantityKg) * Number(h.salePricePerKg || 0), 0), 0),
      },
      production: { totalEggs, totalMilk, totalMeat, totalFeedUsed, totalMortality, records: production.length },
      financials: {
        totalIncome: income,
        totalExpenses: expenses,
        netProfit: income - expenses,
        monthlyData,
        outstandingCredit,
      },
      sales: { total: sales.length, totalRevenue: sales.reduce((s: number, sale: any) => s + Number(sale.totalAmount), 0), customers: customers.length },
      inventory: { totalItems: inventory.length, totalValue: inventoryValue },
      workers: { total: workers.length, active: workers.filter((w: any) => w.status === "active").length, dailyWages: totalDailyWages, monthlyWages: totalDailyWages * 30 },
      invoices: { total: invoices.length, outstanding: invoices.reduce((s: number, i: any) => s + (Number(i.totalAmount) - Number(i.amountPaid)), 0) },
    });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
