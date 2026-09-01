import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";

export async function getCurrentUser() {
  const session = await auth();
  if (session?.user?.id) {
    const member = await prisma.farmMember.findFirst({ where: { userId: Number(session.user.id) } });
    return { userId: Number(session.user.id), farmId: member?.farmId || null };
  }
  if (process.env.NODE_ENV === "development") {
    const user = await prisma.user.findFirst();
    if (user) {
      const member = await prisma.farmMember.findFirst({ where: { userId: user.id } });
      return { userId: user.id, farmId: member?.farmId || null };
    }
  }
  return null;
}
