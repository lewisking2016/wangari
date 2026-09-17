import { NextResponse } from "next/server";

/**
 * Vercel Cron trigger for the weekly DMARC / SPF / DKIM health check.
 *
 * The DNS lookups and alert email run on the Express backend (where the
 * mail credentials live); this route just calls it with CRON_SECRET.
 *
 * Schedule: Mondays 09:00 EAT (see vercel.json).
 */

const CRON_SECRET = process.env.CRON_SECRET || "";
const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || "https://api.wangari.imeantech.com";

export async function GET(req: Request) {
  const authHeader = req.headers.get("authorization");
  if (CRON_SECRET && authHeader !== `Bearer ${CRON_SECRET}`) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 30_000);
    const res = await fetch(`${BACKEND_URL}/api/cron/dmarc-check`, {
      headers: { Authorization: `Bearer ${CRON_SECRET}` },
      signal: controller.signal,
    }).finally(() => clearTimeout(timer));
    const data = await res.json().catch(() => ({}));
    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("DMARC check trigger error:", error);
    return NextResponse.json({ error: "DMARC check failed" }, { status: 500 });
  }
}
