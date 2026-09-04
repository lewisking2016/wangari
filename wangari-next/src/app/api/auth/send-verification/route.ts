import { NextResponse } from "next/server";
import { randomInt } from "crypto";
import { prisma } from "@/lib/db";
import { sendEmail } from "@/lib/email";
import { confirmationCodeEmail } from "@/lib/email-templates";
import { checkRateLimit, getClientIp } from "@/lib/rate-limit";

/**
 * POST /api/auth/send-verification
 *
 * Generates a 6-digit code, stores it in the DB with a 15-minute expiry,
 * and sends it to the user's email.
 *
 * Body: { email }
 */
export async function POST(req: Request) {
  try {
    const { email } = await req.json();

    if (!email || typeof email !== "string") {
      return NextResponse.json({ error: "Email is required" }, { status: 400 });
    }

    // Rate limit: max 5 requests per 15 minutes per IP
    const ip = getClientIp(req);
    const rateLimit = checkRateLimit(`send-verification:${ip}`, { max: 5, windowMs: 15 * 60 * 1000 });
    if (!rateLimit.allowed) {
      return NextResponse.json({ error: "Too many requests. Please try again later." }, { status: 429 });
    }

    // Find the user
    const user = await prisma.user.findUnique({ where: { email: email.toLowerCase() } });
    if (!user) {
      // Don't reveal whether the email exists
      return NextResponse.json({ message: "If that email is registered, a code has been sent." });
    }

    // Already verified?
    if (user.emailVerified) {
      return NextResponse.json({ message: "Email is already verified." });
    }

    // Generate 6-digit code (cryptographically secure)
    const code = randomInt(100000, 999999).toString();
    const expiresAt = new Date(Date.now() + 15 * 60 * 1000); // 15 minutes

    // Delete any old verification codes for this user
    await prisma.verificationCode.deleteMany({
      where: { userId: user.id, purpose: "email_verification" },
    });

    // Store the new code
    await prisma.verificationCode.create({
      data: {
        userId: user.id,
        code,
        purpose: "email_verification",
        expiresAt,
      },
    });

    // Send the email
    const result = await sendEmail({
      to: user.email,
      subject: "Verify your email — Wangari",
      html: confirmationCodeEmail(code, "email verification"),
    });

    if (!result.success) {
      console.error("Failed to send verification email:", result.error);
      return NextResponse.json({ error: "Failed to send email. Please try again." }, { status: 500 });
    }

    return NextResponse.json({ message: "Verification code sent to your email." });
  } catch (error) {
    console.error("Send verification error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json({ error: "Something went wrong. Please try again." }, { status: 500 });
  }
}
