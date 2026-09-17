import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";

/** POST /api/auth/mfa/setup — proxied to the Express backend. */
export async function POST(req: Request) {
  try {
    const headers: Record<string, string> = { "Content-Type": "application/json" };
    const auth = req.headers.get("authorization");
    if (auth) headers["Authorization"] = auth;

    const res = await proxyToBackend("/api/auth/mfa/setup", { method: "POST", headers });
    const data = await res.json().catch(() => ({}));
    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("MFA setup proxy error:", error);
    return NextResponse.json({ error: "Something went wrong. Please try again." }, { status: 500 });
  }
}
