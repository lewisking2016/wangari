import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET(req: Request) {
  const user = await getCurrentUser(req);
  if (!user || !user.farmId) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const data = await prisma.vaccination.findMany({
    where: { flock: { farmId: user.farmId } },
    orderBy: { scheduledDate: "desc" },
    include: { flock: { select: { name: true } } },
  });
  return NextResponse.json(data);
}

export async function POST(req: Request) {
  const user = await getCurrentUser(req);
  if (!user || !user.farmId) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const result = await prisma.vaccination.create({
      data: {
        flockId: Number(body.flockId),
        vaccineName: body.vaccineName,
        scheduledDate: new Date(body.scheduledDate),
        completedDate: body.completedDate ? new Date(body.completedDate) : null,
        status: body.status || "pending",
        notes: body.notes || null,
      },
    });
    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
