/**
 * WhatsApp Webhook — receives messages from Meta Cloud API.
 * GET: verification handshake
 * POST: process incoming messages
 */
import { NextRequest, NextResponse } from "next/server";
import { verifyWebhook, parseMessage, executeCommand, sendWhatsAppMessage } from "@/lib/whatsapp";
import { prisma } from "@/lib/db";

// ─── GET: Webhook verification (Meta setup) ────────────────
export async function GET(req: NextRequest) {
  const url = req.nextUrl;
  const mode = url.searchParams.get("hub.mode");
  const token = url.searchParams.get("hub.verify_token");
  const challenge = url.searchParams.get("hub.challenge");

  if (mode && token && challenge) {
    const result = verifyWebhook(mode, token, challenge);
    if (result) {
      return new NextResponse(result, { status: 200 });
    }
  }

  return new NextResponse("Forbidden", { status: 403 });
}

// ─── POST: Incoming message ────────────────────────────────
export async function POST(req: NextRequest) {
  try {
    const body = await req.json();

    // Meta sends entries → changes → messages
    const entries = body.entry || [];
    for (const entry of entries) {
      const changes = entry.changes || [];
      for (const change of changes) {
        const messages = change.value?.messages || [];
        for (const msg of messages) {
          if (msg.type !== "text") continue;

          const from = msg.from; // phone number
          const text = msg.text?.body || "";

          // Find user by phone number (match last 9 digits)
          const phoneDigits = from.replace(/\D/g, "").slice(-9);
          const user = await prisma.user.findFirst({
            where: { phone: { contains: phoneDigits } },
          });

          if (!user) {
            await sendWhatsAppMessage(from, "❌ Number not registered. Sign up at wangari.imeantech.com");
            continue;
          }

          // Get user's farm
          const farmMember = await prisma.farmMember.findFirst({
            where: { userId: user.id },
          });
          const farmId = farmMember?.farmId;
          if (!farmId) {
            await sendWhatsAppMessage(from, "❌ No farm found. Complete setup in the app.");
            continue;
          }

          // Parse and execute
          const command = parseMessage(text);
          const response = await executeCommand(command, user.id, farmId, prisma);
          await sendWhatsAppMessage(from, response);

          // Log to audit
          await prisma.auditLog.create({
            data: {
              farmId,
              userId: user.id,
              action: "whatsapp_received",
              entityType: "whatsapp",
              details: {
                from,
                message: text,
                action: command.action,
                value: command.value,
                response: response.slice(0, 200),
              },
            },
          }).catch(() => {}); // non-critical
        }
      }
    }

    return NextResponse.json({ status: "ok" });
  } catch (err) {
    console.error("WhatsApp webhook error:", err);
    return NextResponse.json({ status: "ok" }); // always 200 to Meta
  }
}
