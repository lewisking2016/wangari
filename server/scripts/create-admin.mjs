/**
 * Bootstrap a super admin: promote an existing user (by email) or create one.
 * Usage: node scripts/create-admin.mjs <email> [password]
 * Password only needed when creating a brand-new user. Prints a generated
 * password when creating without one.
 */
import bcrypt from "bcryptjs";
import { PrismaClient } from "@prisma/client";
import { randomBytes } from "node:crypto";

const prisma = new PrismaClient();

const email = process.argv[2]?.toLowerCase().trim();
if (!email) {
  console.error("Usage: node scripts/create-admin.mjs <email> [password]");
  process.exit(1);
}

const ADMIN_ROLES = ["super_admin", "billing", "support", "support_read"];
const requestedRole = process.argv[3] && ADMIN_ROLES.includes(process.argv[3]) ? process.argv[3] : "super_admin";

try {
  const existing = await prisma.user.findUnique({ where: { email }, select: { id: true, name: true, role: true } });
  const password = process.argv[4] || null;
  if (existing) {
    await prisma.user.update({ where: { id: existing.id }, data: { role: requestedRole } });
    console.log(`✓ Promoted existing user #${existing.id} (${existing.name}, ${email}) to ${requestedRole}`);
    if (!password) console.log("  Password unchanged — use their existing password to log in at /waadmin");
  } else {
    const plain = password || randomBytes(9).toString("base64url");
    const hash = await bcrypt.hash(plain, 12);
    const name = email.split("@")[0].replace(/[._-]+/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
    const created = await prisma.user.create({
      data: { name, email, password: hash, role: requestedRole, emailVerified: new Date() },
      select: { id: true },
    });
    console.log(`✓ Created ${requestedRole} #${created.id}: ${email}`);
    if (!password) console.log(`  Password: ${plain}\n  (Store it now — it is not shown again)`);
  }
} finally {
  await prisma.$disconnect();
}
