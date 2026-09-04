import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const authHeader = req.headers.get("authorization");

    const headers: Record<string, string> = {
      "Content-Type": "application/json",
    };
    if (authHeader) {
      headers["Authorization"] = authHeader;
    }

    const res = await proxyToBackend("/api/auth/link-google", {
      method: "POST",
      headers,
      body: JSON.stringify(body),
    });

    const data = await res.json();

    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("Link Google proxy error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json({ error: "Failed to link Google account", details: message }, { status: 500 });
  }
}
