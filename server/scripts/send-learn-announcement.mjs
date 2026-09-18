/**
 * One-off marketing email: announcing the Wangari Learn Center
 * (https://wangari.imeantech.com/learn) to all real, verified users.
 *
 * Recipients: every user with a verified email, EXCLUDING security-probe
 * and test accounts (probe-*, *@wangari.test, test@wangari.com) AND anyone
 * already emailed this same subject (dedupe guard — re-running never
 * double-sends).
 *
 * Click tracking: links are wrapped through /api/track/click which captures
 * `email_link_clicked` to PostHog per recipient, so you can see exactly who
 * clicked through from the email in the PostHog dashboard.
 *
 * Run on the VPS:  cd /var/www/wangari/server && node scripts/send-learn-announcement.mjs
 * Dry-run first:   DRY_RUN=1 node scripts/send-learn-announcement.mjs
 */

import { readFileSync } from "fs";

// ── load .env (script runs outside PM2) ──
for (const line of readFileSync(new URL("../.env", import.meta.url), "utf8").split("\n")) {
  const m = line.match(/^([A-Z_]+)=(.*)$/);
  if (m && !process.env[m[1]]) process.env[m[1]] = m[2].trim().replace(/^"|"$/g, "");
}

const DRY_RUN = process.env.DRY_RUN === "1";
const SITE = "https://wangari.imeantech.com";
const CAMPAIGN = "learn-center-launch";
const SUBJECT = "📚 New & free: the Wangari Learn Center — farming knowledge for Kenya";
const API = "https://api.wangari.imeantech.com";

/** Wrap a destination URL with click tracking for a specific recipient. */
function tracked(url, recipientEmail) {
  const u = Buffer.from(url).toString("base64url");
  const r = Buffer.from(recipientEmail).toString("base64url");
  return `${API}/api/track/click?c=${CAMPAIGN}&r=${r}&u=${u}`;
}

const { PrismaClient } = await import("@prisma/client");
const prisma = new PrismaClient();

function emailHtml(name, email) {
  const first = (name || "Farmer").split(" ")[0];
  const learn = tracked(`${SITE}/learn`, email);
  return `<!DOCTYPE html><html><body style="margin:0;background:#f6f7f4;font-family:-apple-system,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;"><tr><td align="center">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
    <tr><td style="background:linear-gradient(135deg,#065f46,#0f766e);padding:32px 36px;text-align:center;">
      <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:2px;color:#6ee7b7;">NEW &amp; FREE FOR EVERY FARMER</p>
      <h1 style="margin:0;font-size:26px;font-weight:800;color:#ffffff;">📚 The Wangari Learn Center is here</h1>
      <p style="margin:8px 0 0;font-size:14px;color:#d1fae5;">A free farming library, written for Kenya</p>
    </td></tr>
    <tr><td style="padding:32px 36px;">
      <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#1e293b;">Habari ${first},</p>
      <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;">
        We just launched something we think you'll love: the
        <strong>Wangari Learn Center</strong> — a free digital library of farming
        knowledge you can read right on your phone, no app install and no
        account needed to start.</p>

      <p style="margin:0 0 10px;font-size:14px;font-weight:700;color:#065f46;">What's inside (14 documents, more coming):</p>
      <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr><td style="padding:5px 0;font-size:14px;line-height:1.5;color:#334155;">🌱 <strong>Growing guides</strong> — maize, beans, tomatoes, sukuma wiki, potatoes, ndengu: varieties, spacing, top-dressing timing, safe storage</td></tr>
        <tr><td style="padding:5px 0;font-size:14px;line-height:1.5;color:#334155;">🐔 <strong>Farming handbooks</strong> — poultry (full vaccination calendar), dairy (feeding for 20L+), avocado &amp; macadamia, legumes</td></tr>
        <tr><td style="padding:5px 0;font-size:14px;line-height:1.5;color:#334155;">📜 <strong>Your rights</strong> — what a fair produce contract must include, subsidies you're entitled to, written land agreements, cooperatives</td></tr>
        <tr><td style="padding:5px 0;font-size:14px;line-height:1.5;color:#334155;">🔴 <strong>Live alerts</strong> — guides update with the week's rain outlook and flag risky farm news (aflatoxin, armyworm, drought)</td></tr>
      </table>

      <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#334155;">
        Every document is written for Kenyan conditions, with KALRO-adjusted
        advice — and members reading inside the dashboard also get live weather
        woven into every guide, plus reminders generated from their own farm records.</p>

      <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:6px 0 22px;">
        <a href="${learn}" style="display:inline-block;background:#059669;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;padding:14px 36px;border-radius:999px;">Read the library free →</a>
      </td></tr></table>

      <p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:#64748b;">
        Know another farmer who needs this? The Learn Center is free for
        everyone — forward them this email.</p>
    </td></tr>
    <tr><td style="background:#f8fafc;padding:18px 36px;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:11px;line-height:1.5;color:#94a3b8;text-align:center;">
        You're receiving this because you have a Wangari Farm OS account.<br>
        Wangari Farm OS · by iMeanTech · ${SITE}</p>
    </td></tr>
  </table></td></tr></table></body></html>`;
}

function emailText(name) {
  const first = (name || "Farmer").split(" ")[0];
  return `Habari ${first},

We just launched the Wangari Learn Center — a FREE digital library of farming knowledge for Kenyan farmers, readable on any phone:

- Growing guides: maize, beans, tomatoes, sukuma wiki, potatoes, ndengu
- Farming handbooks: poultry (full vaccination calendar), dairy, avocado & macadamia, legumes
- Your rights: fair produce contracts, subsidies you're entitled to, land agreements, cooperatives
- Live alerts: rain outlooks and risky farm-news flags updated as things change

Read it free, no account needed: ${SITE}/learn

Know another farmer who needs this? Forward them this email — it's free for everyone.

— Wangari Farm OS, by iMeanTech`;
}

// ── recipients: verified, real users, minus anyone already emailed this subject ──
const candidates = await prisma.user.findMany({
  where: {
    AND: [
      { emailVerified: { not: null } },
      { email: { not: { contains: "probe" } } },
      { email: { not: { contains: "wangari.test" } } },
      { email: { not: "test@wangari.com" } },
    ],
  },
  select: { email: true, name: true },
});

// ── DEDUPE GUARD: skip anyone already sent this exact subject ──
const alreadySent = new Set(
  (await prisma.emailLog.findMany({
    where: { subject: SUBJECT, status: "sent", template: { not: "email_click" } },
    select: { to: true },
  })).map((r) => r.to.toLowerCase())
);
const users = candidates.filter((u) => !alreadySent.has(u.email.toLowerCase()));

console.log(`Recipients: ${users.length} (${candidates.length} candidates, ${alreadySent.size} skipped by dedupe)`);
for (const u of users) console.log("  -", u.email);

if (DRY_RUN) {
  console.log("DRY_RUN=1 — no emails sent. Subject preview:");
  console.log(`  ${SUBJECT}`);
  process.exit(0);
}

const { sendEmail } = await import("../dist/lib/email.js");

let sent = 0, failed = 0;
for (const u of users) {
  const res = await sendEmail({
    to: u.email,
    subject: SUBJECT,
    html: emailHtml(u.name, u.email),
    text: emailText(u.name),
    template: "oneoff",
  });
  if (res?.ok ?? res?.status === "sent") sent++;
  else { failed++; console.warn(`  ✗ ${u.email}:`, res?.error ?? "unknown"); }
  // throttle: keep the SMTP provider happy
  await new Promise((r) => setTimeout(r, 1200));
}

console.log(`Done. Sent: ${sent}, failed: ${failed}`);
await prisma.$disconnect();
