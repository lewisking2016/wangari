import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function GET() {
  try {
    const farmId = 1;
    const sales = await prisma.sale.findMany({
      where: { farmId },
      include: { customer: true },
      orderBy: { saleDate: "desc" },
      take: 100,
    });

    const invoices = sales.map((sale, i) => ({
      id: sale.id,
      invoiceNumber: `INV-${String(sale.id).padStart(4, "0")}`,
      customerName: sale.customer?.name || "Walk-in Customer",
      customerPhone: sale.customer?.phone || "",
      items: sale.items,
      totalAmount: Number(sale.totalAmount),
      amountPaid: Number(sale.amountPaid),
      balance: Number(sale.totalAmount) - Number(sale.amountPaid),
      paymentStatus: sale.paymentStatus,
      date: sale.saleDate,
      createdAt: sale.createdAt,
    }));

    return NextResponse.json(invoices);
  } catch (error) {
    console.error("Invoices API error:", error);
    return NextResponse.json([]);
  }
}

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const sale = await prisma.sale.create({
      data: {
        farmId: body.farmId || 1,
        customerId: body.customerId || null,
        items: body.items || [],
        totalAmount: body.totalAmount,
        amountPaid: body.amountPaid || 0,
        paymentStatus: body.paymentStatus || "pending",
        saleDate: new Date(body.date || Date.now()),
        createdBy: body.userId || null,
      },
      include: { customer: true },
    });

    return NextResponse.json({
      id: sale.id,
      invoiceNumber: `INV-${String(sale.id).padStart(4, "0")}`,
      ...sale,
    }, { status: 201 });
  } catch (error) {
    console.error("Invoice create error:", error);
    return NextResponse.json({ error: "Failed to create invoice" }, { status: 500 });
  }
}
