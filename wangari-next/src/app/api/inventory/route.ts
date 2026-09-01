import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
  if (!member) return NextResponse.json([]);

  const items = await prisma.inventory.findMany({
    where: { farmId: member.farmId },
    orderBy: { createdAt: "desc" },
  });

  return NextResponse.json(items);
}

export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
    if (!member) return NextResponse.json({ error: "No farm" }, { status: 400 });

    const item = await prisma.inventory.create({
      data: {
        farmId: member.farmId,
        itemName: body.itemName,
        category: body.category || null,
        quantity: Number(body.quantity || 0),
        unit: body.unit || "bags",
        unitCost: Number(body.unitCost || 0),
        reorderLevel: Number(body.reorderLevel || 0),
        supplier: body.supplier || null,
      },
    });

    return NextResponse.json(item, { status: 201 });
  } catch (error) {
    console.error("Inventory error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
