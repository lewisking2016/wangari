import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
  if (!member) {
    return NextResponse.json({
      totalFlocks: 0, totalBirds: 0, eggsToday: 0, mortalityToday: 0,
      monthlyRevenue: 0, monthlyExpenses: 0, pendingVaccinations: 0, lowStockItems: 0,
      recentTransactions: [], upcomingVaccinations: [],
    });
  }

  const farmId = member.farmId;
  const today = new Date();
  const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);

  const [totalFlocks, flocks, todayProduction, monthlyIncome, monthlyExpenses, pendingVax, recentTx] = await Promise.all([
    prisma.flock.count({ where: { farmId, status: "active" } }),
    prisma.flock.findMany({ where: { farmId, status: "active" }, select: { currentCount: true } }),
    prisma.dailyProduction.findMany({ where: { farmId, date: today } }),
    prisma.transaction.aggregate({ where: { farmId, type: "income", date: { gte: monthStart } }, _sum: { amount: true } }),
    prisma.transaction.aggregate({ where: { farmId, type: "expense", date: { gte: monthStart } }, _sum: { amount: true } }),
    prisma.vaccination.count({ where: { flock: { farmId }, status: "pending" } }),
    prisma.transaction.findMany({ where: { farmId }, orderBy: { createdAt: "desc" }, take: 5 }),
  ]);

  const totalBirds = flocks.reduce((sum: number, f: { currentCount: number }) => sum + f.currentCount, 0);
  const eggsToday = todayProduction.reduce((sum: number, p: { eggsCollected: number }) => sum + p.eggsCollected, 0);
  const mortalityToday = todayProduction.reduce((sum: number, p: { mortality: number }) => sum + p.mortality, 0);

  return NextResponse.json({
    totalFlocks,
    totalBirds,
    eggsToday,
    mortalityToday,
    monthlyRevenue: Number(monthlyIncome._sum.amount || 0),
    monthlyExpenses: Number(monthlyExpenses._sum.amount || 0),
    pendingVaccinations: pendingVax,
    lowStockItems: 0,
    recentTransactions: recentTx,
    upcomingVaccinations: [],
  });
}
