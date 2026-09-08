import { prisma } from "../db.js";

/**
 * Transactional email via Resend (https://resend.com) — plain fetch, no SDK
 * dependency. Behavior:
 *  - RESEND_API_KEY unset  → send is skipped, EmailLog row records "failed"
 *    with a clear error. App never breaks because email is down.
 *  - RESEND_API_KEY set    → send via Resend REST API; log sent/failed with
 *    the provider id for support lookups.
 * Every attempt is logged to email_logs (the admin Email Ops module reads it).
 */

const RESEND_API = "https://api.resend.com/emails";
const FROM = process.env.EMAIL_FROM || "Wangari <noreply@imeantech.com>";

export interface EmailInput {
  to: string;
  subject: string;
  html: string;
  template: string; // receipt | ticket_reply | announcement | oneoff
  userId?: number | null;
}

function wrapHtml(title: string, bodyHtml: string): string {
  return `<!doctype html><html><body style="margin:0;padding:24px;background:#f6f8f6;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
  <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
    <div style="background:#166534;padding:20px 28px;">
      <span style="color:#ffffff;font-weight:700;font-size:18px;letter-spacing:0.5px;">🌾 Wangari</span>
    </div>
    <div style="padding:28px;">
      <h2 style="margin:0 0 12px;font-size:18px;color:#0f172a;">${title}</h2>
      ${bodyHtml}
    </div>
    <div style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e5e7eb;color:#94a3b8;font-size:12px;">
      Wangari — Farm Management · wangari.imeantech.com
    </div>
  </div>
</body></html>`;
}

export const emailTemplates = {
  receipt(amountKes: number, planName: string, expiresAt: Date): { subject: string; html: string } {
    return {
      subject: `Payment receipt — ${planName} (KES ${amountKes.toLocaleString()})`,
      html: wrapHtml(
        "Payment received",
        `<p style="color:#334155;font-size:14px;line-height:1.6;">Thanks for subscribing to <strong>${planName}</strong>.</p>
         <table style="width:100%;font-size:14px;color:#334155;margin:12px 0;">
           <tr><td style="padding:6px 0;color:#64748B;">Amount</td><td style="text-align:right;font-weight:700;">KES ${amountKes.toLocaleString()}</td></tr>
           <tr><td style="padding:6px 0;color:#64748B;">Plan</td><td style="text-align:right;font-weight:700;">${planName}</td></tr>
           <tr><td style="padding:6px 0;color:#64748B;">Valid until</td><td style="text-align:right;font-weight:700;">${expiresAt.toLocaleDateString()}</td></tr>
         </table>
         <p style="color:#64748B;font-size:13px;">You can view receipts anytime in your subscription page.</p>`
      ),
    };
  },

  ticketReply(ticketId: number, subject: string, replyBody: string): { subject: string; html: string } {
    return {
      subject: `Re: [Ticket #${ticketId}] ${subject}`,
      html: wrapHtml(
        `New reply on ticket #${ticketId}`,
        `<p style="color:#334155;font-size:14px;line-height:1.6;">Support replied to your request <strong>"${subject}"</strong>:</p>
         <blockquote style="margin:12px 0;padding:12px 16px;background:#f0fdf4;border-left:3px solid #166534;color:#334155;font-size:14px;line-height:1.6;">${replyBody}</blockquote>
         <p style="color:#64748B;font-size:13px;">Reply from your dashboard's Help section to continue the conversation.</p>`
      ),
    };
  },
};

export async function sendEmail(input: EmailInput): Promise<void> {
  const apiKey = process.env.RESEND_API_KEY;
  let status = "failed";
  let providerId: string | null = null;
  let error: string | null = null;

  if (!apiKey) {
    error = "RESEND_API_KEY not configured";
  } else {
    try {
      const res = await fetch(RESEND_API, {
        method: "POST",
        headers: { Authorization: `Bearer ${apiKey}`, "Content-Type": "application/json" },
        body: JSON.stringify({ from: FROM, to: [input.to], subject: input.subject, html: input.html }),
      });
      const data: any = await res.json().catch(() => ({}));
      if (res.ok && data?.id) {
        status = "sent";
        providerId = data.id;
      } else {
        error = data?.message || `HTTP ${res.status}`;
      }
    } catch (e: any) {
      error = e?.message || "network error";
    }
  }

  try {
    await prisma.emailLog.create({
      data: {
        to: input.to,
        subject: input.subject,
        template: input.template,
        status,
        provider: apiKey ? "resend" : null,
        providerId,
        error,
        userId: input.userId ?? null,
      },
    });
  } catch (e) {
    console.error("[email] failed to log:", e);
  }
  if (status === "failed") console.warn(`[email] ${input.template} to ${input.to} failed: ${error}`);
}
