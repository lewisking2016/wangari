import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET(req: Request) {
  const user = await getCurrentUser(req);
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const data = await prisma.flock.findMany({
    where: { farmId: user.farmId! },
    orderBy: { createdAt: "desc" },
  });
  return NextResponse.json(data);
}

export async function POST(req: Request) {
  const user = await getCurrentUser(req);
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const result = await prisma.flock.create({
      data: {
        farmId: user.farmId!, name: body.name, breed: body.breed || null,
        type: body.type || "layers", initialCount: Number(body.initialCount),
        currentCount: Number(body.initialCount),
        hatchDate: body.hatchDate ? new Date(body.hatchDate) : null,
        createdBy: user.userId,
      },
    });
    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
