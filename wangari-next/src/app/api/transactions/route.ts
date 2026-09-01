import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET() {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const data = await prisma.transaction.findMany({
    where: { farmId: user.farmId! },
    orderBy: { date: "desc" }, take: 100,
  });
  return NextResponse.json(data);
}

export async function POST(req: Request) {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const result = await prisma.transaction.create({
      data: {
        farmId: user.farmId!, type: body.type, category: body.category || null,
        amount: Number(body.amount), description: body.description || null,
        date: new Date(body.date), paymentMethod: body.paymentMethod || "cash",
        createdBy: user.userId,
      },
    });
    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
