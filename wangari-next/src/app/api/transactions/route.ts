import { NextResponse } from "next/server";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function GET() {
  const session = await auth();
  if (!session?.user?.id) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const member = await prisma.farmMember.findFirst({
    where: { userId: Number(session.user.id) },
  });
  if (!member) return NextResponse.json([]);

  const transactions = await prisma.transaction.findMany({
    where: { farmId: member.farmId },
    orderBy: { date: "desc" },
    take: 100,
  });

  return NextResponse.json(transactions);
}

export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await req.json();
    const { type, category, amount, description, date, paymentMethod } = body;

    const member = await prisma.farmMember.findFirst({
      where: { userId: Number(session.user.id) },
    });
    if (!member) {
      return NextResponse.json({ error: "No farm found" }, { status: 400 });
    }

    const transaction = await prisma.transaction.create({
      data: {
        farmId: member.farmId,
        type,
        category: category || null,
        amount: Number(amount),
        description: description || null,
        date: new Date(date),
        paymentMethod: paymentMethod || "cash",
        createdBy: Number(session.user.id),
      },
    });

    return NextResponse.json(transaction, { status: 201 });
  } catch (error) {
    console.error("Transaction error:", error);
    return NextResponse.json({ error: "Failed to create transaction" }, { status: 500 });
  }
}
