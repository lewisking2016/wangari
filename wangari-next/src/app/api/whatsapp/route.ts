import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function GET() {
  try {
    // Get recent WhatsApp messages from audit log
    const logs = await prisma.auditLog.findMany({
      where: { entityType: "whatsapp" },
      orderBy: { createdAt: "desc" },
      take: 50,
    });

    return NextResponse.json({
      connected: false,
      messages: logs.map((log) => ({
        id: log.id,
        action: log.action,
        details: log.details,
        createdAt: log.createdAt,
      })),
      templates: [
        { id: "daily_report", name: "Daily Report", description: "Send daily egg production summary", active: true },
        { id: "mortality_alert", name: "Mortality Alert", description: "Alert when mortality exceeds threshold", active: true },
        { id: "low_stock", name: "Low Stock Alert", description: "Alert when inventory runs low", active: true },
        { id: "vaccination_reminder", name: "Vaccination Reminder", description: "Remind about upcoming vaccinations", active: false },
        { id: "payment_received", name: "Payment Received", description: "Confirm payment received from customer", active: false },
        { id: "weekly_summary", name: "Weekly Summary", description: "Send weekly farm performance summary", active: false },
      ],
      stats: {
        sent: logs.filter((l) => l.action === "send").length,
        delivered: logs.filter((l) => l.action === "delivered").length,
        failed: logs.filter((l) => l.action === "failed").length,
      },
    });
  } catch (error) {
    console.error("WhatsApp API error:", error);
    return NextResponse.json({ connected: false, messages: [], templates: [], stats: { sent: 0, delivered: 0, failed: 0 } });
  }
}

export async function POST(request: Request) {
  try {
    const body = await request.json();

    // Log the WhatsApp message attempt
    await prisma.auditLog.create({
      data: {
        farmId: body.farmId || 1,
        action: "send",
        entityType: "whatsapp",
        details: {
          to: body.phoneNumber,
          template: body.templateId,
          message: body.message,
          timestamp: new Date().toISOString(),
        },
      },
    });

    // In production, this would call the WhatsApp Business API
    return NextResponse.json({
      success: true,
      message: "Message queued for delivery",
      note: "WhatsApp Business API integration pending. Messages logged for now.",
    });
  } catch (error) {
    console.error("WhatsApp send error:", error);
    return NextResponse.json({ success: false, error: "Failed to send message" }, { status: 500 });
  }
}
