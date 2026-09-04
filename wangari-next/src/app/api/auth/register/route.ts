import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";
import { checkRateLimit, getClientIp } from "@/lib/rate-limit";

export async function POST(req: Request) {
  try {
    // Rate limit: max 5 registrations per hour per IP
    const ip = getClientIp(req);
    const rateLimit = checkRateLimit(`register:${ip}`, { max: 5, windowMs: 60 * 60 * 1000 });
    if (!rateLimit.allowed) {
      return NextResponse.json({ error: "Too many registration attempts. Please try again later." }, { status: 429 });
    }

    const body = await req.json();

    const res = await proxyToBackend("/api/auth/register", {
      method: "POST",
      body: JSON.stringify(body),
    });

    const data = await res.json();

    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("Register proxy error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json(
      { error: "Something went wrong. Please try again." },
      { status: 500 }
    );
  }
}
