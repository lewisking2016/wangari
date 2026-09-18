import { Router, Request, Response } from "express";
import { PrismaClient } from "@prisma/client";
import { captureBackendEvent } from "../lib/posthog-server.js";

/**
 * Email click tracking — POSTHOG-POWERED CAMPAIGN ANALYTICS.
 *
 * Marketing emails wrap their links as:
 *   /api/track/click?c=<campaign>&r=<recipientKey>&u=<base64url destination>
 *
 * On click we:
 *   1. Capture an `email_link_clicked` event to PostHog (distinct_id is the
 *      recipient key when it's an email address, so funnel/insight analysis
 *      lands on the same person as the browser SDK).
 *   2. Log a row in email_logs (template "email_click") for SQL analysis.
 *   3. 302-redirect to the destination — the user never notices.
 *
 * The `u` parameter is base64url of the destination. Only https? URLs are
 * allowed (no open-redirect to javascript:, data:, etc.).
 */

const prisma = new PrismaClient();
const router = Router();

function decodeTarget(raw: string): string | null {
  try {
    const url = Buffer.from(raw, "base64url").toString("utf8");
    if (!/^https?:\/\//i.test(url)) return null;
    return url;
  } catch {
    return null;
  }
}

router.get("/click", async (req: Request, res: Response) => {
  const campaign = String(req.query.c ?? "unknown").slice(0, 64);
  const recipient = String(req.query.r ?? "anonymous").slice(0, 200);
  const target = decodeTarget(String(req.query.u ?? ""));

  if (!target) {
    return res.status(400).send("Invalid tracking link");
  }

  // 1. PostHog event — person-level when recipient is an email address
  const distinctId = recipient.includes("@") ? recipient : `campaign:${campaign}`;
  captureBackendEvent("email_link_clicked", {
    campaign,
    recipient: recipient.includes("@") ? recipient : undefined,
    destination: target,
    ua: String(req.headers["user-agent"] ?? "").slice(0, 200),
  });

  // 2. SQL-side log (fire-and-forget; tracking must never block the redirect)
  prisma.emailLog
    .create({
      data: {
        to: recipient,
        subject: `click: ${campaign} → ${target}`.slice(0, 250),
        template: "email_click",
        status: "sent",
        provider: "track",
      },
    })
    .catch(() => {});

  // 3. Redirect
  return res.redirect(302, target);
});

export default router;
