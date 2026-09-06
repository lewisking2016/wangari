import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
const PAYSTACK_SECRET = process.env.PAYSTACK_SECRET_KEY || "";
const PAYSTACK_API = "https://api.paystack.co";

const PLANS = {
  starter_monthly: { name: "Starter Monthly", amount: 150000, description: "Starter plan - KES 1,500/month" },
  starter_annual: { name: "Starter Annual", amount: 1200000, description: "Starter plan - KES 12,000/year" },
  growth_monthly: { name: "Growth Monthly", amount: 450000, description: "Growth plan - KES 4,500/month" },
  growth_annual: { name: "Growth Annual", amount: 3600000, description: "Growth plan - KES 36,000/year" },
};

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

// GET /api/paystack/verify?reference=xxx - Verify transaction
router.get("/verify", async (req: Request, res: Response) => {
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
