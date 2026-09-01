import { NextResponse } from "next/server";
import bcrypt from "bcryptjs";
import { prisma } from "@/lib/db";

export async function POST(req: Request) {
  try {
    const { name, email, password, farmName } = await req.json();

    if (!name || !email || !password || !farmName) {
      return NextResponse.json(
        { error: "All fields are required" },
        { status: 400 }
      );
    }

    const existing = await prisma.user.findUnique({ where: { email } });
    if (existing) {
      return NextResponse.json(
        { error: "An account with this email already exists" },
        { status: 409 }
      );
    }

    const hashedPassword = await bcrypt.hash(password, 12);

    const result = await prisma.$transaction(async (tx) => {
      const user = await tx.user.create({
        data: { name, email, password: hashedPassword, role: "farm_owner" },
      });

      const farm = await tx.farm.create({
        data: { name: farmName, ownerId: user.id, farmType: "mixed" },
      });

      await tx.farmMember.create({
        data: { userId: user.id, farmId: farm.id, role: "owner" },
      });

      return { user: { id: user.id, name: user.name, email: user.email }, farm };
    });

    return NextResponse.json({ success: true, ...result }, { status: 201 });
  } catch (error) {
    console.error("Registration error:", error);
    return NextResponse.json(
      { error: "Registration failed. Please try again." },
      { status: 500 }
    );
  }
}
