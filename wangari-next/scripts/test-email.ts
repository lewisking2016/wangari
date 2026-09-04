/**
 * Standalone SMTP test script.
 *
 * Usage:
 *   npx tsx scripts/test-email.ts                        # verify connection only
 *   npx tsx scripts/test-email.ts you@example.com        # send a test email
 *
 * Reads SMTP config from .env.local (or .env).
 */

import * as dotenv from "dotenv";
dotenv.config({ path: ".env.local" });
import nodemailer from "nodemailer";

const SMTP_HOST = process.env.SMTP_HOST || "my.mailbux.com";
const SMTP_PORT = Number(process.env.SMTP_PORT) || 587;
const SMTP_USER = process.env.SMTP_USER || "noreply@imeantech.com";
const SMTP_PASSWORD = process.env.SMTP_PASSWORD || "";
const SMTP_FROM = process.env.SMTP_FROM || "Wangari <noreply@imeantech.com>";

const transporter = nodemailer.createTransport({
  host: SMTP_HOST,
  port: SMTP_PORT,
  secure: false,
  auth: {
    user: SMTP_USER,
    pass: SMTP_PASSWORD,
  },
  tls: { rejectUnauthorized: true },
});

async function main() {
  const targetEmail = process.argv[2];

  console.log("📧 Mailbux SMTP Test");
  console.log("─".repeat(40));
  console.log(`Host:     ${SMTP_HOST}:${SMTP_PORT}`);
  console.log(`User:     ${SMTP_USER}`);
  console.log(`Password: ${SMTP_PASSWORD ? "•".repeat(SMTP_PASSWORD.length) : "(empty)"}`);
  console.log(`From:     ${SMTP_FROM}`);
  console.log();

  // Step 1: Verify connection
  console.log("1️⃣  Verifying SMTP connection...");
  try {
    await transporter.verify();
    console.log("   ✅ SMTP connection successful!");
  } catch (err: any) {
    console.log("   ❌ SMTP connection failed:");
    console.log(`   ${err.message}`);
    process.exit(1);
  }

  // Step 2: Send test email (if target provided)
  if (!targetEmail) {
    console.log();
    console.log("ℹ️  Connection works! To send a test email, run:");
    console.log(`   npx tsx scripts/test-email.ts your@email.com`);
    process.exit(0);
  }

  console.log();
  console.log(`2️⃣  Sending test email to ${targetEmail}...`);

  try {
    const info = await transporter.sendMail({
      from: SMTP_FROM,
      to: targetEmail,
      subject: "🌿 Test email from Wangari",
      html: `
<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
  <tr><td style="background:#166534;padding:20px 24px;text-align:center;color:#fff;font-size:20px;font-weight:700;">🌿 Wangari</td></tr>
  <tr><td style="padding:24px;">
    <h2 style="margin:0 0 8px;font-size:18px;color:#334155;">SMTP is working! 🎉</h2>
    <p style="margin:0 0 16px;font-size:14px;color:#64748b;">This is a test email sent from your Wangari app. If you're seeing this, your email system is fully operational.</p>
    <div style="background:#f0fdf4;border-radius:8px;padding:16px;text-align:center;margin-bottom:16px;">
      <span style="font-size:28px;font-weight:700;letter-spacing:4px;color:#166534;font-family:monospace;">123456</span>
    </div>
    <p style="margin:0;font-size:12px;color:#94a3b8;">Sent at ${new Date().toLocaleString("en-KE", { timeZone: "Africa/Nairobi", dateStyle: "medium", timeStyle: "short" })}</p>
  </td></tr>
  <tr><td style="padding:12px 24px;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#64748b;">
    © 2026 Wangari · imeantech.com
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>`,
      text: `Wangari SMTP Test — If you're reading this, your email system works!\n\nSent at ${new Date().toLocaleString("en-KE", { timeZone: "Africa/Nairobi", dateStyle: "medium", timeStyle: "short" })}`,
    });

    console.log("   ✅ Email sent successfully!");
    console.log(`   Message ID: ${info.messageId}`);
    console.log(`   Response:   ${info.response}`);
  } catch (err: any) {
    console.log("   ❌ Failed to send email:");
    console.log(`   ${err.message}`);
    process.exit(1);
  }
}

main();
