import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();

const MODULE_HUB_MAP: Record<string, string> = {
  livestock: "poultry",
  production: "poultry",
  vaccinations: "poultry",
  crops: "crops",
  finances: "finance",
  sales: "sales",
  customers: "sales",
  invoices: "sales",
  workers: "team",
  attendance: "team",
  inventory: "_always",
  dashboard: "_always",
  "feed-calculator": "_always",
  weather: "_always",
  reports: "_always",
  settings: "_always",
};

// GET /api/trial/status
router.get("/status", authMiddleware, async (req: Request, res: Response) => {
  try {
    const userId = req.user?.userId;
    if (!userId) {
      return res.status(401).json({ error: "Unauthorized" });
    }

    const user = await prisma.user.findUnique({
      where: { id: userId },
      select: {
        id: true,
        trialStartsAt: true,
        trialEndsAt: true,
        selectedHubs: true,
        createdAt: true,
      },
    });

    if (!user) {
      return res.status(404).json({ error: "User not found" });
    }

    const now = new Date();

    const activeSub = await prisma.subscription.findFirst({
      where: {
        userId: user.id,
        status: "active",
        expiresAt: { gt: now },
      },
      orderBy: { expiresAt: "desc" },
    });

    const pendingSub = await prisma.subscription.findFirst({
      where: {
        userId: user.id,
        status: "pending",
      },
      orderBy: { startsAt: "asc" },
    });

    let trialStatus: "active" | "expired" | "no_trial" = "no_trial";
    let trialDaysLeft = 0;
    let trialEndsAt: string | null = null;

    // Resolve the canonical trial end date
    let resolvedTrialEndsAt: Date | null = user.trialEndsAt;

    // If trialEndsAt was never persisted, calculate from createdAt and LOCK it in the DB
    if (!resolvedTrialEndsAt && user.createdAt) {
      resolvedTrialEndsAt = new Date(user.createdAt.getTime() + 14 * 24 * 60 * 60 * 1000);
      // Persist so subsequent calls return a stable, unchanging end date
      await prisma.user.update({
        where: { id: user.id },
        data: {
          trialStartsAt: user.createdAt,
          trialEndsAt: resolvedTrialEndsAt,
        },
      });
    }

    if (resolvedTrialEndsAt) {
      if (now < resolvedTrialEndsAt) {
        trialStatus = "active";
        trialDaysLeft = Math.ceil((resolvedTrialEndsAt.getTime() - now.getTime()) / (24 * 60 * 60 * 1000));
        trialEndsAt = resolvedTrialEndsAt.toISOString();
      } else {
        trialStatus = "expired";
      }
    }

    let hasAccess = false;
    let accessReason: string = "none";

    if (activeSub) {
      hasAccess = true;
      accessReason = "subscription";
    } else if (trialStatus === "active") {
      hasAccess = true;
      accessReason = "trial";
    } else if (pendingSub) {
      hasAccess = true;
      accessReason = "trial";
    }

    const selectedHubs: string[] = user.selectedHubs
      ? JSON.parse(user.selectedHubs)
      : [];

    const moduleAccess: Record<string, boolean> = {};
    for (const [module, hub] of Object.entries(MODULE_HUB_MAP)) {
      if (hub === "_always" || hasAccess) {
        moduleAccess[module] = true;
      } else {
        moduleAccess[module] = false;
      }
    }

    const subscription = activeSub
      ? {
          plan: activeSub.plan,
          planName: activeSub.planName,
          expiresAt: activeSub.expiresAt.toISOString(),
          daysLeft: Math.ceil((activeSub.expiresAt.getTime() - now.getTime()) / (24 * 60 * 60 * 1000)),
        }
      : pendingSub
        ? {
            plan: pendingSub.plan,
            planName: pendingSub.planName,
            startsAt: pendingSub.startsAt.toISOString(),
            status: "pending_start",
          }
        : null;

    return res.json({
      trial: {
        status: trialStatus,
        daysLeft: trialDaysLeft,
        endsAt: trialEndsAt,
      },
      subscription,
      hasAccess,
      accessReason,
      selectedHubs,
      modules: moduleAccess,
    });
  } catch (error) {
    console.error("Trial status error:", error);
    return res.status(500).json({ error: "Failed to get trial status" });
  }
});

export default router;
