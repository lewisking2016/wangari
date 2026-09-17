import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";

/** GET /api/auth/mfa/status — proxied to the Express backend (owns the DB). */
export async function GET(req: Request) {
  try {
    const res = await proxyToBackend("/api/auth/mfa/status", {
      method: "GET",
      headers: req.headers.get("authorization")
        ? { Authorization: req.headers.get("authorization")! }
        : {},
    });
    const data = await res.json().catch(() => ({}));
    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("MFA status proxy error:", error);
    return NextResponse.json({ error: "Something went wrong. Please try again." }, { status: 500 });
  }
}
