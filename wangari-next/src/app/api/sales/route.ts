import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
  if (!member) return NextResponse.json([]);

  const sales = await prisma.sale.findMany({
    where: { farmId: member.farmId },
    orderBy: { saleDate: "desc" },
    include: { customer: { select: { name: true } } },
  });

  return NextResponse.json(sales);
}

export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    const body = await req.json();
    const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
    if (!member) return NextResponse.json({ error: "No farm" }, { status: 400 });

    const sale = await prisma.sale.create({
      data: {
        farmId: member.farmId,
        customerId: body.customerId || null,
        items: body.items || [],
        totalAmount: Number(body.totalAmount),
        paymentStatus: body.paymentStatus || "paid",
        amountPaid: Number(body.amountPaid || body.totalAmount),
        createdBy: Number(session.user.id),
      },
    });

    return NextResponse.json(sale, { status: 201 });
  } catch (error) {
    console.error("Sale error:", error);
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
