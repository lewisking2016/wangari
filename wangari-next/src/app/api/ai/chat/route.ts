import { NextRequest, NextResponse } from "next/server";
import { proxyToBackend } from "@/lib/api-proxy";

// In-memory conversation store (per-session)
const conversations = new Map<string, Array<{ role: string; content: string }>>();

export async function POST(req: NextRequest) {
  try {
    const { message, sessionId } = await req.json();

    if (!message) {
      return NextResponse.json({ error: "Message is required" }, { status: 400 });
    }

    const sid = sessionId || "default";
    if (!conversations.has(sid)) {
      conversations.set(sid, []);
    }
    const history = conversations.get(sid)!;

    // Add user message
    history.push({ role: "user", content: message });

    // Build messages for the backend
    const messages = history.slice(-20);

    // Get auth token from request
    const authHeader = req.headers.get("authorization") || "";
    const token = authHeader.replace("Bearer ", "");

    // Get farmId from the JWT token (simplified - decode from token)
    const farmId = getFarmIdFromToken(token);

    // Call Express backend
    const res = await proxyToBackend("/api/ai/chat", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ messages, farmId }),
    });

    const data = await res.json();

    if (!res.ok) {
      return NextResponse.json({ error: data.error || "AI request failed" }, { status: res.status });
    }

    // Add assistant response to history
    if (data.message?.content) {
      history.push({ role: "assistant", content: data.message.content });
    }

    // Keep history manageable
    if (history.length > 40) {
      conversations.set(sid, history.slice(-20));
    }

    return NextResponse.json(data);
  } catch (error) {
    console.error("AI chat proxy error:", error);
    const msg = error instanceof Error ? error.message : "Unknown error";
    return NextResponse.json({ error: msg }, { status: 500 });
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const { sessionId } = await req.json();
    if (sessionId) {
      conversations.delete(sessionId);
    }
    return NextResponse.json({ ok: true });
  } catch {
    return NextResponse.json({ ok: true });
  }
}

/** Simple JWT decode to extract farmId (no verification needed here) */
function getFarmIdFromToken(token: string): number | null {
  try {
    if (!token) return null;
    const payload = token.split(".")[1];
    if (!payload) return null;
    const decoded = JSON.parse(Buffer.from(payload, "base64url").toString());
    return decoded.farmId || null;
  } catch {
    return null;
  }
}
