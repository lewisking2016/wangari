import { prisma } from "../db.js";

export interface PlanConfig {
  id: string;
  name: string;
  description: string | null;
  amount: number; // pesewas
  days: number;
}

// Compile-time fallback ONLY used when the DB has no plans (fresh deploys
// before seeding). Once seeded, the DB is the single source of truth.
const FALLBACK: Record<string, PlanConfig> = {
  starter_monthly: { id: "starter_monthly", name: "Starter Monthly", description: "Starter plan - KES 1,500/month", amount: 150000, days: 30 },
  starter_annual: { id: "starter_annual", name: "Starter Annual", description: "Starter plan - KES 12,000/year", amount: 1200000, days: 365 },
  growth_monthly: { id: "growth_monthly", name: "Growth Monthly", description: "Growth plan - KES 4,500/month", amount: 450000, days: 30 },
  growth_annual: { id: "growth_annual", name: "Growth Annual", description: "Growth plan - KES 36,000/year", amount: 3600000, days: 365 },
};

export async function getPlan(planId: string): Promise<PlanConfig | null> {
  try {
    const row = await prisma.plan.findFirst({ where: { id: planId, active: true } });
    if (row) {
      return { id: row.id, name: row.name, description: row.description, amount: row.amount, days: row.days };
    }
  } catch (err) {
    console.error("Plan lookup failed, using fallback:", err instanceof Error ? err.message : err);
  }
  return FALLBACK[planId] || null;
}

export async function getActivePlans(): Promise<PlanConfig[]> {
  try {
    const rows = await prisma.plan.findMany({
      where: { active: true },
      orderBy: { sortOrder: "asc" },
    });
    if (rows.length > 0) {
      return rows.map((r) => ({ id: r.id, name: r.name, description: r.description, amount: r.amount, days: r.days }));
    }
  } catch (err) {
    console.error("Plan list failed, using fallback:", err instanceof Error ? err.message : err);
  }
  return Object.values(FALLBACK);
}
