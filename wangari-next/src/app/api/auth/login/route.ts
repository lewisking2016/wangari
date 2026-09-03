import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";

export async function POST(req: Request) {
  try {
    const body = await req.json();

    const res = await proxyToBackend("/api/auth/login", {
      method: "POST",
      body: JSON.stringify(body),
    });

    const data = await res.json();

    return NextResponse.json(data, { status: res.status });
  } catch (error) {
    console.error("Login proxy error:", error);
    const message = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json(
      { error: "Login failed", details: message },
      { status: 500 }
    );
  }
}
