import { NextResponse } from "next/server";
import { sendEmail, verifyConnection } from "@/lib/email";
import { decodeToken } from "@/lib/jwt";

/**
 * POST /api/email/send
 * General-purpose email sending endpoint.
 *
 * Body: { to, subject, html, text? }
 *
 * Protected: requires a valid JWT token with userId.
 */
export async function POST(req: Request) {
  try {
    // ── Auth check ──────────────────────────────────────────
    const user = decodeToken(req.headers.get("authorization"));
    if (!user?.userId) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await req.json();
    const { to, subject, html, text } = body;

    if (!to || !subject || !html) {
      return NextResponse.json(
        { error: "Missing required fields: to, subject, html" },
        { status: 400 }
      );
    }

    const result = await sendEmail({ to, subject, html, text });

    if (!result.success) {
      return NextResponse.json(
        { error: "Failed to send email", details: result.error },
        { status: 500 }
      );
    }

    return NextResponse.json({ success: true, messageId: result.messageId });
  } catch (error) {
    console.error("Email API error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json({ error: "Something went wrong. Please try again." }, { status: 500 });
  }
}

/**
 * GET /api/email/send
 * Health check — verifies SMTP connection is alive.
 */
export async function GET() {
  const ok = await verifyConnection();
  return NextResponse.json({ smtp: ok ? "connected" : "failed" }, { status: ok ? 200 : 500 });
}
