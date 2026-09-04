import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";
import { prisma } from "@/lib/db";
import { checkRateLimit, getClientIp } from "@/lib/rate-limit";

export async function POST(req: Request) {
  try {
    // Rate limit: max 10 login attempts per 15 minutes per IP
    const ip = getClientIp(req);
    const rateLimit = checkRateLimit(`login:${ip}`, { max: 10, windowMs: 15 * 60 * 1000 });
    if (!rateLimit.allowed) {
      return NextResponse.json({ error: "Too many login attempts. Please try again later." }, { status: 429 });
    }

    const body = await req.json();

    const res = await proxyToBackend("/api/auth/login", {
      method: "POST",
      body: JSON.stringify(body),
    });

    const data = await res.json();

    // After successful login, check email verification status
    if (res.ok && data.user?.email) {
      const user = await prisma.user.findUnique({
        where: { email: data.user.email.toLowerCase() },
        select: { emailVerified: true },
      });
      data.emailVerified = user?.emailVerified ?? null;
    }

    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("Login proxy error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json(
      { error: "Something went wrong. Please try again." },
      { status: 500 }
    );
  }
}
