import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET(req: Request) {
  const user = await getCurrentUser(req);
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const data = await prisma.inventory.findMany({
    where: { farmId: user.farmId! },
    orderBy: { itemName: "asc" },
  });
  return NextResponse.json(data);
}

export async function POST(req: Request) {
  const user = await getCurrentUser(req);
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const result = await prisma.inventory.create({
      data: {
        farmId: user.farmId!, itemName: body.itemName, category: body.category || null,
        quantity: Number(body.quantity), unit: body.unit || "bags",
        unitCost: Number(body.unitCost), reorderLevel: Number(body.reorderLevel || 0),
      },
    });
    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
