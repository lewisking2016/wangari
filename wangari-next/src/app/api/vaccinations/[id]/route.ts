import { NextResponse } from "next/server";
import { getCurrentUser } from "@/lib/current-user";
import { prisma } from "@/lib/db";

export async function PATCH(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  const body = await req.json();
  const result = await prisma.vaccination.update({
    where: { id: Number(id) },
    data: {
      status: body.status,
      completedDate: body.completedDate ? new Date(body.completedDate) : undefined,
      notes: body.notes,
    },
  });
  return NextResponse.json(result);
}

export async function DELETE(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await params;
  await prisma.vaccination.delete({ where: { id: Number(id) } });
  return NextResponse.json({ success: true });
}
