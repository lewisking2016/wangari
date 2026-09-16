import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { decodeToken } from "@/lib/jwt";

/**
 * Admin-only proxy to the PostHog Query API (EU Cloud).
 *
 * The Personal API Key never reaches the browser — the waadmin Analytics
 * page calls this route with its admin JWT, and we forward vetted HogQL
 * queries to PostHog here. Query `kind` is whitelisted so this can't become
 * an open proxy.
 */

const PH_HOST = process.env.POSTHOG_API_HOST || "https://eu.posthog.com";
const PH_KEY = process.env.POSTHOG_PERSONAL_API_KEY || "";
const PH_PROJECT_ID = process.env.POSTHOG_PROJECT_ID || "276436";

const ALLOWED_KINDS = new Set(["EventsQuery", "HogQLQuery", "TrendsQuery", "FunnelsQuery", "RetentionQuery"]);

export async function POST(req: NextRequest) {
  const payload = decodeToken(req.headers.get("authorization"));
  // Admin JWTs carry type:"admin" + adminId (see signAdminToken on the backend).
  // This route only READS product analytics — no user PII leaves PostHog.
  if (!payload?.adminId || (payload as any).type !== "admin") {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  if (!PH_KEY) {
    return NextResponse.json({ error: "POSTHOG_PERSONAL_API_KEY not configured" }, { status: 503 });
  }

  try {
    const body = await req.json();
    const query = body?.query;
    if (!query || typeof query !== "object" || !ALLOWED_KINDS.has(query.kind)) {
      return NextResponse.json({ error: "Invalid or disallowed query kind" }, { status: 400 });
    }

    const res = await fetch(`${PH_HOST}/api/projects/${PH_PROJECT_ID}/query/`, {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${PH_KEY}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ query }),
      // PostHog caches on their side; add a small Next cache too
      next: { revalidate: 60 },
    } as RequestInit);

    const data = await res.json();
    if (!res.ok) {
      console.error("PostHog query error:", data?.detail || data);
      return NextResponse.json({ error: data?.detail || "PostHog query failed" }, { status: res.status });
    }

    return NextResponse.json(data);
  } catch (e: any) {
    console.error("PostHog proxy error:", e?.message);
    return NextResponse.json({ error: "Query failed" }, { status: 500 });
  }
}
