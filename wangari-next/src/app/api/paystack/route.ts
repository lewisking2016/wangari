import { NextRequest, NextResponse } from "next/server";

const PAYSTACK_SECRET = process.env.PAYSTACK_SECRET_KEY!;
const PAYSTACK_API = "https://api.paystack.co";

// Plan amounts in KES (Paystack uses kobo/pesewas = amount * 100)
const PLANS = {
  growth_monthly: { name: "Growth Monthly", amount: 150000, description: "Growth plan - KES 1,500/month" },
  growth_annual: { name: "Growth Annual", amount: 1500000, description: "Growth plan - KES 15,000/year" },
  enterprise_monthly: { name: "Enterprise Monthly", amount: 500000, description: "Enterprise plan - KES 5,000/month" },
  enterprise_annual: { name: "Enterprise Annual", amount: 5000000, description: "Enterprise plan - KES 50,000/year" },
};

// POST /api/paystack - Initialize a payment
export async function POST(req: NextRequest) {
  try {
    const { email, plan, callback_url } = await req.json();

    if (!email || !plan) {
      return NextResponse.json({ error: "Email and plan are required" }, { status: 400 });
    }

    const planConfig = PLANS[plan as keyof typeof PLANS];
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
        callback_url: callback_url || `${process.env.NEXTAUTH_URL}/dashboard?payment=success`,
        metadata: {
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
