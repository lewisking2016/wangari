import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const records = await prisma.dailyProduction.findMany({
    orderBy: { date: "desc" },
    take: 50,
    include: { flock: { select: { name: true } } },
  });

  return NextResponse.json(records);
}

export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await req.json();
    const { flockId, date, eggsCollected, mortality, feedUsed, notes } = body;

    if (!flockId || !date) {
      return NextResponse.json({ error: "Flock and date are required" }, { status: 400 });
    }

    const flock = await prisma.flock.findUnique({ where: { id: Number(flockId) } });
    if (!flock) {
      return NextResponse.json({ error: "Flock not found" }, { status: 404 });
    }

    const record = await prisma.dailyProduction.upsert({
      where: { flockId_date: { flockId: Number(flockId), date: new Date(date) } },
      update: {
        eggsCollected: Number(eggsCollected || 0),
        mortality: Number(mortality || 0),
        feedUsed: Number(feedUsed || 0),
        notes: notes || null,
      },
      create: {
        flockId: Number(flockId),
        farmId: flock.farmId,
        date: new Date(date),
        eggsCollected: Number(eggsCollected || 0),
        mortality: Number(mortality || 0),
        feedUsed: Number(feedUsed || 0),
        notes: notes || null,
      },
    });

    return NextResponse.json(record, { status: 201 });
  } catch (error) {
    console.error("Production log error:", error);
    return NextResponse.json({ error: "Failed to log production" }, { status: 500 });
  }
}
