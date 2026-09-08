import crypto from "node:crypto";

/**
 * Minimal RFC 6238 TOTP (SHA-1, 6 digits, 30s step) + base32 — enough for
 * Google Authenticator / Authy / 1Password compatibility, zero dependencies.
 * Used exclusively by the admin MFA flow (lib scope, not exported to routes
 * that don't need it).
 */

const B32_ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";

export function generateSecret(byteLen = 20): string {
  const buf = crypto.randomBytes(byteLen);
  let bits = 0, value = 0, out = "";
  for (const byte of buf) {
    value = (value << 8) | byte;
    bits += 8;
    while (bits >= 5) {
      out += B32_ALPHABET[(value >>> (bits - 5)) & 31];
      bits -= 5;
    }
  }
  if (bits > 0) out += B32_ALPHABET[(value << (5 - bits)) & 31];
  return out;
}

function base32Decode(secret: string): Buffer {
  const clean = secret.toUpperCase().replace(/[^A-Z2-7]/g, "");
  let bits = 0, value = 0;
  const bytes: number[] = [];
  for (const ch of clean) {
    value = (value << 5) | B32_ALPHABET.indexOf(ch);
    bits += 5;
    if (bits >= 8) {
      bytes.push((value >>> (bits - 8)) & 255);
      bits -= 8;
    }
  }
  return Buffer.from(bytes);
}

export function totpAt(secret: string, timeMs: number, stepSec = 30, digits = 6): string {
  const counter = Math.floor(timeMs / 1000 / stepSec);
  const buf = Buffer.alloc(8);
  buf.writeUInt32BE(Math.floor(counter / 2 ** 32), 0);
  buf.writeUInt32BE(counter % 2 ** 32, 4);
  const hmac = crypto.createHmac("sha1", base32Decode(secret)).update(buf).digest();
  const offset = hmac[hmac.length - 1] & 0x0f;
  const bin =
    ((hmac[offset] & 0x7f) << 24) |
    (hmac[offset + 1] << 16) |
    (hmac[offset + 2] << 8) |
    hmac[offset + 3];
  return String(bin % 10 ** digits).padStart(digits, "0");
}

/** Verify a code allowing ±1 step of clock drift. Constant-time compare. */
export function verifyTotp(secret: string, code: string): boolean {
  const clean = String(code || "").replace(/\D/g, "");
  if (clean.length !== 6) return false;
  const now = Date.now();
  for (const drift of [-1, 0, 1]) {
    const expected = totpAt(secret, now + drift * 30_000);
    const a = Buffer.from(expected);
    const b = Buffer.from(clean);
    if (a.length === b.length && crypto.timingSafeEqual(a, b)) return true;
  }
  return false;
}

/** otpauth:// URI for QR encoding (rendered client-side). */
export function otpauthUri(secret: string, account: string, issuer = "Wangari Admin"): string {
  const label = encodeURIComponent(`${issuer}:${account}`);
  const params = new URLSearchParams({ secret, issuer, algorithm: "SHA1", digits: "6", period: "30" });
  return `otpauth://totp/${label}?${params}`;
}

/** Generate a batch of one-time recovery codes (returned once, stored hashed). */
export function generateRecoveryCodes(count = 8): { plain: string[]; hashed: string[] } {
  const plain: string[] = [];
  const hashed: string[] = [];
  for (let i = 0; i < count; i++) {
    const code = crypto.randomBytes(5).toString("hex"); // 10 hex chars
    plain.push(code);
    hashed.push(crypto.createHash("sha256").update(code).digest("hex"));
  }
  return { plain, hashed };
}

export function hashCode(code: string): string {
  return crypto.createHash("sha256").update(String(code).trim().toLowerCase()).digest("hex");
}
