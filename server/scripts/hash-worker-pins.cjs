/**
 * One-time migration: bcrypt-hash all plaintext worker PINs in place.
 * Idempotent — PINs already hashed (start with $2a$/$2b$/$2y$) are skipped.
 * Run: node scripts/hash-worker-pins.cjs
 */
const bcrypt = require("bcryptjs");
const { PrismaClient } = require("@prisma/client");
const p = new PrismaClient();

(async () => {
  const workers = await p.worker.findMany({ where: { pin: { not: null } }, select: { id: true, pin: true } });
  let hashed = 0;
  for (const w of workers) {
    if (/^\$2[aby]\$/.test(w.pin)) continue;
    await p.worker.update({ where: { id: w.id }, data: { pin: await bcrypt.hash(w.pin, 10) } });
    hashed++;
  }
  console.log(`✓ hashed ${hashed} of ${workers.length} worker PINs`);
  await p.$disconnect();
})().catch((e) => { console.error(e); process.exit(1); });
