import { Router, Request, Response } from "express";
import crypto from "crypto";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";
import { auditMoneyMutation } from "../lib/audit.js";

const router = Router();
const PAYSTACK_SECRET = process.env.PAYSTACK_SECRET_KEY || "";
const PAYSTACK_API = "https://api.paystack.co";

const PLANS = {
  starter_monthly: { name: "Starter Monthly", amount: 150000, description: "Starter plan - KES 1,500/month", days: 30 },
  starter_annual: { name: "Starter Annual", amount: 1200000, description: "Starter plan - KES 12,000/year", days: 365 },
  growth_monthly: { name: "Growth Monthly", amount: 450000, description: "Growth plan - KES 4,500/month", days: 30 },
  growth_annual: { name: "Growth Annual", amount: 3600000, description: "Growth plan - KES 36,000/year", days: 365 },
};

// Sorted by amount so annual is matched before monthly when both equal is impossible,
// but we match exact amount to the plan the user was initialized with.
function planFromAmount(amount: number) {
  return Object.values(PLANS).find((p) => p.amount === amount);
}

// POST /api/paystack - Initialize transaction
router.post("/", authMiddleware, async (req: Request, res: Response) => {
  try {
    const userId = req.user?.userId;
    let { email, plan, callback_url, phone } = req.body;

    if (!userId) {
      return res.status(401).json({ error: "Unauthorized" });
    }

    const user = await prisma.user.findUnique({ where: { id: userId }, select: { email: true, phone: true } });
    if (!email) email = user?.email;
    if (!phone) phone = user?.phone;

    if (!email || !plan) {
      return res.status(400).json({ error: "Email and plan are required" });
    }

    const planConfig = PLANS[plan as keyof typeof PLANS];
    if (!planConfig) {
      return res.status(400).json({ error: "Invalid plan" });
    }

    const payload: any = {
      email,
      amount: planConfig.amount,
      currency: "KES",
      channels: ["card", "mobile_money"],
      callback_url: callback_url || `https://wangari.imeantech.com/subscription?payment=success`,
      metadata: {
        purpose: "subscription",
        userId,
        plan,
        plan_name: planConfig.name,
      },
    };

    if (phone) {
      payload.phone = phone;
    }

    const response = await fetch(`${PAYSTACK_API}/transaction/initialize`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${PAYSTACK_SECRET}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const data = (await response.json()) as any;

    if (!data.status) {
      return res.status(400).json({ error: data.message || "Payment initialization failed" });
    }

    return res.json({
      status: true,
      authorization_url: data.data.authorization_url,
      access_code: data.data.access_code,
      reference: data.data.reference,
    });
  } catch (error) {
    console.error("Paystack error:", error);
    return res.status(500).json({ error: "Internal server error" });
  }
});

// POST /api/paystack/webhook — server-to-server payment confirmation (no auth; signature-verified)
router.post("/webhook", async (req: Request, res: Response) => {
  try {
    // Verify event origin via HMAC SHA512 of the raw request body (Paystack docs).
    const signature = req.headers["x-paystack-signature"] as string | undefined;
    const rawBody = Buffer.isBuffer(req.body) ? req.body : Buffer.from(JSON.stringify(req.body));
    const expected = crypto.createHmac("sha512", PAYSTACK_SECRET).update(rawBody).digest("hex");

    if (!signature || signature !== expected) {
      return res.status(401).json({ error: "Invalid signature" });
    }

    const event = Buffer.isBuffer(req.body) ? JSON.parse(req.body.toString("utf8")) : req.body;
    const eventType = event?.event;

    if (eventType === "charge.success") {
      const data = event?.data || {};
      const reference = data.reference;
      const paidAmount = Number(data.amount || 0); // Paystack amounts are in the currency's minor unit (cents).

      // Two payload shapes are supported:
      // 1. Express initialize (this file): metadata.userId + metadata.plan
      // 2. Vercel Next.js webhook contract: metadata.purpose === "subscription" + customer.email
      const userId = data?.metadata?.userId;
      let plan = data?.metadata?.plan;

      // If no userId in metadata, resolve the user by customer email (Vercel-webhook contract).
      if (!userId && data?.metadata?.purpose === "subscription") {
        const email = String(data?.customer?.email || "").toLowerCase();
        const user = email ? await prisma.user.findUnique({ where: { email } }) : null;
        if (user) {
          // Activate now (Express flow has no trial-start deferral)
          const startsAt = new Date();
          const expiresAt = new Date(startsAt.getTime() + (PLANS[plan as keyof typeof PLANS]?.days || 30) * 24 * 60 * 60 * 1000);
          const existing = reference ? await prisma.subscription.findFirst({ where: { reference } }) : null;
          if (!existing) {
            await prisma.subscription.create({
              data: {
                userId: user.id,
                plan: plan || "starter_monthly",
                planName: PLANS[plan as keyof typeof PLANS]?.name || plan || "Starter",
                amount: paidAmount / 100,
                status: "active",
                reference,
                startsAt,
                expiresAt,
              },
            });
          }
          return res.json({ received: true });
        }
      }

      if (!userId || !plan) {
        return res.json({ received: true }); // no-op, can't activate without metadata
      }

      const planConfig = PLANS[plan as keyof typeof PLANS];
      if (!planConfig) {
        return res.json({ received: true });
      }

      const amountKes = paidAmount / 100;
      if (amountKes !== planConfig.amount / 100) {
        console.error(`Paystack webhook amount mismatch for ${reference}: paid ${amountKes}, expected ${planConfig.amount / 100}`);
        return res.status(400).json({ error: "Amount mismatch" });
      }

      const startsAt = new Date();
      const expiresAt = new Date(startsAt.getTime() + planConfig.days * 24 * 60 * 60 * 1000);

      // Idempotent: reference is not unique in the schema, so find-first then create/update.
      const existing = reference
        ? await prisma.subscription.findFirst({ where: { reference } })
        : null;

      if (existing) {
        await prisma.subscription.update({
          where: { id: existing.id },
          data: { status: "active", planName: planConfig.name, expiresAt },
        });
      } else {
        await prisma.subscription.create({
          data: {
            userId: Number(userId),
            plan,
            planName: planConfig.name,
            amount: planConfig.amount / 100,
            status: "active",
            reference,
            startsAt,
            expiresAt,
          },
        });
      }
      auditMoneyMutation({
        userId: Number(userId),
        farmId: null,
        action: "subscription.activate",
        entityType: "Subscription",
        entityId: existing?.id || null,
        details: { plan, reference, amountKes: planConfig.amount / 100, expiresAt: expiresAt.toISOString() },
      });
    }

    res.json({ received: true });
  } catch (error) {
    console.error("Paystack webhook error:", error);
    res.status(500).json({ error: "Webhook processing failed" });
  }
});

// GET /api/paystack/verify?reference=xxx - Verify transaction (auth required)
router.get("/verify", authMiddleware, async (req: Request, res: Response) => {
  try {
    const reference = req.query.reference as string;
    if (!reference) {
      return res.status(400).json({ error: "Reference is required" });
    }

    const response = await fetch(`${PAYSTACK_API}/transaction/verify/${reference}`, {
      headers: { Authorization: `Bearer ${PAYSTACK_SECRET}` },
    });

    const data = (await response.json()) as any;

    if (!data.status) {
      return res.status(400).json({ error: "Verification failed" });
    }

    return res.json({
      status: true,
      data: {
        reference: data.data.reference,
        amount: data.data.amount,
        currency: data.data.currency,
        status: data.data.status,
        customer: data.data.customer,
        metadata: data.data.metadata,
      },
    });
  } catch (error) {
    console.error("Paystack verify error:", error);
    return res.status(500).json({ error: "Internal server error" });
  }
});

export default router;
