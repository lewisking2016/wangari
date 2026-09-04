import nodemailer from "nodemailer";

/**
 * Mailbux SMTP email utility for Wangari.
 * Sends emails from noreply@imeantech.com via Mailbux SMTP.
 */

// ─── Transporter Setup ────────────────────────────────────

const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || "my.mailbux.com",
  port: Number(process.env.SMTP_PORT) || 587,
  secure: false, // STARTTLS — upgraded on connection
  auth: {
    user: process.env.SMTP_USER || "noreply@imeantech.com",
    pass: process.env.SMTP_PASSWORD || "",
  },
  tls: {
    rejectUnauthorized: true,
  },
});

// ─── Types ────────────────────────────────────────────────

export interface EmailOptions {
  to: string;
  subject: string;
  html: string;
  text?: string;
  replyTo?: string;
}

export interface SendResult {
  success: boolean;
  messageId?: string;
  error?: string;
}

// ─── Core Send Function ───────────────────────────────────

export async function sendEmail(options: EmailOptions): Promise<SendResult> {
  const from = process.env.SMTP_FROM || "Wangari <noreply@imeantech.com>";

  try {
    const info = await transporter.sendMail({
      from,
      to: options.to,
      subject: options.subject,
      html: options.html,
      text: options.text || stripHtml(options.html),
      replyTo: options.replyTo,
    });

    console.log(`✉️  Email sent: ${info.messageId} → ${options.to}`);
    return { success: true, messageId: info.messageId };
  } catch (error) {
    console.error("Email send error:", error);
    return {
      success: false,
      error: error instanceof Error ? error.message : "Unknown email error",
    };
  }
}

// ─── Verification ─────────────────────────────────────────

export async function verifyConnection(): Promise<boolean> {
  try {
    await transporter.verify();
    console.log("✅ SMTP connection verified");
    return true;
  } catch (error) {
    console.error("SMTP connection failed:", error);
    return false;
  }
}

// ─── Helpers ──────────────────────────────────────────────

function stripHtml(html: string): string {
  return html
    .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, "")
    .replace(/<[^>]+>/g, "")
    .replace(/\s+/g, " ")
    .trim();
}
