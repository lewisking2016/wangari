import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const farms = await prisma.farm.findMany({
    where: {
      members: { some: { userId: Number(session.user.id) } },
    },
    include: { members: true },
  });

  return NextResponse.json(farms);
}
