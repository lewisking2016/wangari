import { NextResponse } from "next/server";

/**
 * Weekly cron trigger (Vercel Cron → Express backend where the DB lives).
 * Emails verified users without 2FA a one-click setup reminder.
 */
export async function GET() {
  const CRON_SECRET = process.env.CRON_SECRET || "";
  const backendUrl = process.env.BACKEND_INTERNAL_URL || "http://localhost:8925";

  try {
    const res = await fetch(`${backendUrl}/api/cron/mfa-nudge`, {
      headers: { Authorization: `Bearer ${CRON_SECRET}` },
      signal: AbortSignal.timeout(110_000),
    });
    const data = await res.json().catch(() => ({}));
    return NextResponse.json(data, { status: res.status });
  } catch (error: any) {
    return NextResponse.json({ ok: false, error: error?.message || "backend unreachable" }, { status: 502 });
  }
}
