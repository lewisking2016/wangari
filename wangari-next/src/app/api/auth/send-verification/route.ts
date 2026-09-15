import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";

/**
 * POST /api/auth/send-verification
 *
 * Proxied to the Express backend, which owns the DB connection and SMTP
 * credentials. Vercel can't reach Postgres directly, so this must run there.
 */
export async function POST(req: Request) {
  try {
    const res = await proxyToBackend("/api/auth/send-verification", {
      method: "POST",
      body: JSON.stringify(await req.json()),
    });

    const data = await res.json().catch(() => ({}));
    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("Send verification proxy error:", error);
    return NextResponse.json({ error: "Something went wrong. Please try again." }, { status: 500 });
  }
}
