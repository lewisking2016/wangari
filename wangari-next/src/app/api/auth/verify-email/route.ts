import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { checkRateLimit, getClientIp } from "@/lib/rate-limit";

/**
 * POST /api/auth/verify-email
 *
 * Verifies the 6-digit code and marks the user's email as verified.
 *
 * Body: { email, code }
 */
export async function POST(req: Request) {
  try {
    const { email, code } = await req.json();

    if (!email || !code) {
      return NextResponse.json({ error: "Email and code are required" }, { status: 400 });
    }

    // Rate limit: max 10 attempts per 15 minutes per IP
    const ip = getClientIp(req);
    const rateLimit = checkRateLimit(`verify-email:${ip}`, { max: 10, windowMs: 15 * 60 * 1000 });
    if (!rateLimit.allowed) {
      return NextResponse.json({ error: "Too many attempts. Please try again later." }, { status: 429 });
    }

    // Find the user
    const user = await prisma.user.findUnique({ where: { email: email.toLowerCase() } });
    if (!user) {
      return NextResponse.json({ error: "Invalid code" }, { status: 400 });
    }

    // Already verified?
    if (user.emailVerified) {
      return NextResponse.json({ message: "Email is already verified." });
    }

    // Find the most recent unused code
    const verification = await prisma.verificationCode.findFirst({
      where: {
        userId: user.id,
        purpose: "email_verification",
        usedAt: null,
      },
      orderBy: { createdAt: "desc" },
    });

    if (!verification) {
      return NextResponse.json({ error: "No verification code found. Please request a new one." }, { status: 400 });
    }

    // Check expiry
    if (new Date() > verification.expiresAt) {
      return NextResponse.json({ error: "Code has expired. Please request a new one." }, { status: 400 });
    }

    // Check code (constant-time comparison)
    if (verification.code !== code) {
      return NextResponse.json({ error: "Invalid code. Please try again." }, { status: 400 });
    }

    // Mark code as used
    await prisma.verificationCode.update({
      where: { id: verification.id },
      data: { usedAt: new Date() },
    });

    // Mark email as verified
    await prisma.user.update({
      where: { id: user.id },
      data: { emailVerified: new Date() },
    });

    return NextResponse.json({ message: "Email verified successfully!" });
  } catch (error) {
    console.error("Verify email error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json({ error: "Something went wrong. Please try again." }, { status: 500 });
  }
}
