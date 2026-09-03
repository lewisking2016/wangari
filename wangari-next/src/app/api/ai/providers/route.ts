import { NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";

export async function GET() {
  try {
    const res = await proxyToBackend("/api/ai/providers");
    const data = await res.json();
    return NextResponse.json(data);
  } catch {
    return NextResponse.json([]);
  }
}
