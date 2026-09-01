import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET() {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const data = await prisma.dailyProduction.findMany({
    where: { farmId: user.farmId! },
    orderBy: { date: "desc" }, take: 50,
    include: { flock: { select: { name: true } } },
  });
  return NextResponse.json(data);
}

export async function POST(req: Request) {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const flock = await prisma.flock.findUnique({ where: { id: Number(body.flockId) } });
    if (!flock) return NextResponse.json({ error: "Flock not found" }, { status: 404 });
    const result = await prisma.dailyProduction.upsert({
      where: { flockId_date: { flockId: Number(body.flockId), date: new Date(body.date) } },
      update: { eggsCollected: Number(body.eggsCollected || 0), mortality: Number(body.mortality || 0), feedUsed: Number(body.feedUsed || 0), notes: body.notes || null },
      create: { flockId: Number(body.flockId), farmId: user.farmId!, date: new Date(body.date), eggsCollected: Number(body.eggsCollected || 0), mortality: Number(body.mortality || 0), feedUsed: Number(body.feedUsed || 0), notes: body.notes || null },
    });
    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
