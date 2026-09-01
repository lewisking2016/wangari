import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function GET() {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const data = await prisma.sale.findMany({
    where: { farmId: user.farmId! },
    orderBy: { saleDate: "desc" }, take: 100,
    include: { customer: { select: { name: true } } },
  });
  return NextResponse.json(data);
}

export async function POST(req: Request) {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const result = await prisma.sale.create({
      data: {
        farmId: user.farmId!, customerId: body.customerId ? Number(body.customerId) : null,
        saleDate: new Date(body.saleDate || new Date()), items: body.items || "",
        totalAmount: Number(body.totalAmount), amountPaid: Number(body.amountPaid || 0),
        paymentStatus: body.paymentStatus || "pending", createdBy: user.userId,
      },
    });
    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
