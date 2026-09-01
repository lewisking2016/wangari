import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
  if (!member) return NextResponse.json([]);

  const workers = await prisma.worker.findMany({
    where: { farmId: member.farmId },
    orderBy: { createdAt: "desc" },
  });

  return NextResponse.json(workers);
}

export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
    if (!member) return NextResponse.json({ error: "No farm" }, { status: 400 });

    const worker = await prisma.worker.create({
      data: {
        farmId: member.farmId,
        name: body.name,
        phone: body.phone || null,
        role: body.role || null,
        dailyWage: body.dailyWage ? Number(body.dailyWage) : null,
        createdBy: Number(session.user.id),
      },
    });

    return NextResponse.json(worker, { status: 201 });
  } catch (error) {
    console.error("Worker error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
