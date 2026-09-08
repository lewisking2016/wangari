import { NextRequest, NextResponse } from "next/server";

const PAYSTACK_SECRET = process.env.PAYSTACK_SECRET_KEY!;
const PAYSTACK_API = "https://api.paystack.co";
// Plan config (name/amount) lives in the backend DB — no price literals here.
const BACKEND_URL = process.env.BACKEND_URL || "https://api.wangari.imeantech.com";

interface PlanConfig {
  id: string;
  name: string;
  amount: number; // pesewas
}

async function fetchPlan(planId: string): Promise<PlanConfig | null> {
  try {
    const res = await fetch(`${BACKEND_URL}/api/plans`, { next: { revalidate: 60 } });
    if (!res.ok) return null;
    const plans: PlanConfig[] = await res.json();
    return plans.find((p) => p.id === planId) || null;
  } catch {
    return null;
  }
}

// POST /api/paystack - Initialize a payment
export async function POST(req: NextRequest) {
  try {
    const { email, plan, callback_url } = await req.json();

    if (!email || !plan) {
      return NextResponse.json({ error: "Email and plan are required" }, { status: 400 });
    }

    const planConfig = await fetchPlan(plan);
    if (!planConfig) {
      return NextResponse.json({ error: "Invalid plan" }, { status: 400 });
    }

    // Initialize Paystack transaction (one-time payment with metadata)
    const response = await fetch(`${PAYSTACK_API}/transaction/initialize`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${PAYSTACK_SECRET}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        email,
        amount: planConfig.amount,
        currency: "KES",
        channels: ["card", "mobile_money"],
        callback_url: callback_url || `${process.env.NEXTAUTH_URL || "https://wangari.imeantech.com"}/dashboard?payment=success`,
        metadata: {
          purpose: "subscription",
          plan,
          plan_name: planConfig.name,
        },
      }),
    });

    const data = await response.json();

    if (!data.status) {
      return NextResponse.json({ error: data.message || "Payment initialization failed" }, { status: 400 });
    }

    return NextResponse.json({
      status: true,
      authorization_url: data.data.authorization_url,
      access_code: data.data.access_code,
      reference: data.data.reference,
    });
  } catch (error) {
    console.error("Paystack error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}

// GET /api/paystack?reference=xxx - Verify a payment
export async function GET(req: NextRequest) {
  try {
    const reference = req.nextUrl.searchParams.get("reference");

    if (!reference) {
      return NextResponse.json({ error: "Reference is required" }, { status: 400 });
    }

    const response = await fetch(`${PAYSTACK_API}/transaction/verify/${reference}`, {
      headers: {
        Authorization: `Bearer ${PAYSTACK_SECRET}`,
      },
    });

    const data = await response.json();

    if (!data.status) {
      return NextResponse.json({ error: "Verification failed" }, { status: 400 });
    }

    return NextResponse.json({
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
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
