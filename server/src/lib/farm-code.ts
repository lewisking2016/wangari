import crypto from "crypto";
import { prisma } from "../db.js";

const CODE_ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // no I, O, 0, 1 — avoids misreading

/** Random 3-char suffix like "K7Q" -> full code "WANGARI-K7Q". */
export function generateFarmCode(): string {
  let suffix = "";
  for (let i = 0; i < 3; i++) {
    suffix += CODE_ALPHABET[crypto.randomInt(CODE_ALPHABET.length)];
  }
  return `WANGARI-${suffix}`;
}

/** Create a unique farm code, retrying on the (rare) unique-constraint clash. */
export async function createUniqueFarmCode(): Promise<string> {
  for (let attempt = 0; attempt < 5; attempt++) {
    const code = generateFarmCode();
    const exists = await prisma.farm.findUnique({ where: { code } });
    if (!exists) return code;
  }
  // Practically unreachable; fall back to id-derived code
  return `WANGARI-${Date.now().toString(36).toUpperCase().slice(-4)}`;
}

/** Random 4-digit PIN as a string (keeps leading zeros, e.g. "0427"). */
export function generateWorkerPin(): string {
  return String(crypto.randomInt(10000)).padStart(4, "0");
}

/** Ensure a farm has a code; backfills older farms lazily. */
export async function ensureFarmCode(farmId: number): Promise<string> {
  const farm = await prisma.farm.findUnique({ where: { id: farmId }, select: { id: true, code: true } });
  if (!farm) throw new Error("Farm not found");
  if (farm.code) return farm.code;
  const code = await createUniqueFarmCode();
  await prisma.farm.update({ where: { id: farmId }, data: { code } });
  return code;
}
