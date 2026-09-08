import bcrypt from "bcryptjs";

/**
 * Worker PIN hashing helpers.
 * PINs are 4 digits — low entropy by nature, so hashing is about protecting
 * the DB-at-rest case (dump/backup leak), not brute-force resistance online.
 * Cost 10 keeps login fast for a numeric compare.
 */
const HASH_RE = /^\$2[aby]\$/;

export function isHashed(pin: string | null | undefined): boolean {
  return !!pin && HASH_RE.test(pin);
}

export async function hashPin(pin: string): Promise<string> {
  return bcrypt.hash(pin, 10);
}

export async function verifyPin(plain: string, stored: string | null): Promise<boolean> {
  if (!stored) return false;
  if (isHashed(stored)) return bcrypt.compare(plain, stored);
  return plain === stored; // pre-migration plaintext fallback
}
