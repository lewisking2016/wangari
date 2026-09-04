import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { decodeToken } from "@/lib/jwt";

export async function GET(req: Request) {
  try {
    const user = decodeToken(req.headers.get("authorization"));
    const farmId = user?.farmId;
    if (!farmId) return NextResponse.json([]);
    const logs = await prisma.auditLog.findMany({
      where: { farmId },
      include: { user: { select: { name: true } } },
      orderBy: { createdAt: "desc" },
      take: 100,
    });

    return NextResponse.json(logs.map((log) => ({
      id: log.id,
      action: log.action,
      entityType: log.entityType,
      entityId: log.entityId,
      details: log.details,
      userName: log.user?.name || "System",
      createdAt: log.createdAt,
    })));
  } catch (error) {
    console.error("Audit log error:", error);
    return NextResponse.json([]);
  }
}

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const log = await prisma.auditLog.create({
      data: {
        farmId: body.farmId || 1,
        userId: body.userId || null,
        action: body.action,
        entityType: body.entityType,
        entityId: body.entityId || null,
        details: body.details || null,
      },
    });
    return NextResponse.json(log, { status: 201 });
  } catch (error) {
    console.error("Audit log create error:", error);
    return NextResponse.json({ error: "Failed to create audit log" }, { status: 500 });
  }
}
