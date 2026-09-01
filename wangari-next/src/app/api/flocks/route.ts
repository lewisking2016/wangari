import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const flocks = await prisma.flock.findMany({
    orderBy: { createdAt: "desc" },
  });

  return NextResponse.json(flocks);
}

export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await req.json();
    const { name, breed, type, initialCount, hatchDate } = body;

    if (!name || !initialCount) {
      return NextResponse.json(
        { error: "Name and count are required" },
        { status: 400 }
      );
    }

    // Get user's primary farm
    const member = await prisma.farmMember.findFirst({
      where: { userId: Number(session.user.id) },
    });

    if (!member) {
      return NextResponse.json(
        { error: "No farm found. Create a farm first." },
        { status: 400 }
      );
    }

    const flock = await prisma.flock.create({
      data: {
        farmId: member.farmId,
        name,
        breed: breed || null,
        type: type || "layers",
        initialCount: Number(initialCount),
        currentCount: Number(initialCount),
        hatchDate: hatchDate ? new Date(hatchDate) : null,
        createdBy: Number(session.user.id),
      },
    });

    return NextResponse.json(flock, { status: 201 });
  } catch (error) {
    console.error("Create flock error:", error);
    return NextResponse.json(
      { error: "Failed to create flock" },
      { status: 500 }
    );
  }
}
