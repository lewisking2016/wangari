import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET(req: Request) {
  const user = await getCurrentUser(req);
  if (!user || !user.farmId) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const data = await prisma.attendance.findMany({
    where: { farmId: user.farmId },
    orderBy: { date: "desc" },
    take: 100,
    include: { worker: { select: { name: true, role: true } } },
  });
  return NextResponse.json(data);
}

export async function POST(req: Request) {
  const user = await getCurrentUser(req);
  if (!user || !user.farmId) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Check if attendance already exists for this worker today
    const existing = await prisma.attendance.findFirst({
      where: { workerId: Number(body.workerId), date: today },
    });

    if (existing && body.action === "checkout") {
      // Update check-out
      const result = await prisma.attendance.update({
        where: { id: existing.id },
        data: { checkOut: body.time || new Date().toTimeString().slice(0, 5) },
      });
      return NextResponse.json(result);
    }

    if (existing) {
      return NextResponse.json({ error: "Already checked in today" }, { status: 400 });
    }

    // Create check-in
    const result = await prisma.attendance.create({
      data: {
        workerId: Number(body.workerId),
        farmId: user.farmId,
        date: today,
        checkIn: body.time || new Date().toTimeString().slice(0, 5),
        status: body.status || "present",
      },
    });
    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
