import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET() {
  const user = await getCurrentUser();
  if (!user || !user.farmId) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const farmId = user.farmId;
  const today = new Date();
  const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);

  const [flocks, allFlocks, todayProd, monthIncome, monthExpense, lowStock, recentTx] = await Promise.all([
    prisma.flock.count({ where: { farmId, status: "active" } }),
    prisma.flock.findMany({ where: { farmId, status: "active" }, select: { currentCount: true } }),
    prisma.dailyProduction.findMany({ where: { farmId, date: today } }),
    prisma.transaction.aggregate({ where: { farmId, type: "income", date: { gte: monthStart } }, _sum: { amount: true } }),
    prisma.transaction.aggregate({ where: { farmId, type: "expense", date: { gte: monthStart } }, _sum: { amount: true } }),
    prisma.inventory.findMany({ where: { farmId, quantity: { lte: prisma.inventory.fields ? 0 : 0 } } }).catch(() => []),
    prisma.transaction.findMany({ where: { farmId }, orderBy: { createdAt: "desc" }, take: 5 }),
  ]);

  const totalBirds = allFlocks.reduce((s: number, f: any) => s + f.currentCount, 0);
  const eggsToday = todayProd.reduce((s: number, p: any) => s + p.eggsCollected, 0);
  const mortalityToday = todayProd.reduce((s: number, p: any) => s + p.mortality, 0);

  return NextResponse.json({
    totalFlocks: flocks, totalBirds, eggsToday, mortalityToday,
    monthlyRevenue: Number(monthIncome._sum.amount || 0),
    monthlyExpenses: Number(monthExpense._sum.amount || 0),
    recentTransactions: recentTx,
  });
}
