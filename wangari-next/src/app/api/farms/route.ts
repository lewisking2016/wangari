import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET() {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const farms = await prisma.farm.findMany({
    where: { id: user.farmId! },
  });
  return NextResponse.json(farms[0] || null);
}
