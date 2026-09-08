/**
 * Bootstrap a super admin: promote an existing user (by email) or create one.
 * Usage: node scripts/create-admin.mjs <email> [password]
 * Password only needed when creating a brand-new user. Prints a generated
 * password when creating without one.
 */
import bcrypt from "bcryptjs";
import pg from "pg";
import { randomBytes } from "node:crypto";
import { readFileSync } from "node:fs";

function loadDatabaseUrl() {
  if (process.env.DATABASE_URL) return process.env.DATABASE_URL;
  const env = readFileSync(new URL("../.env", import.meta.url), "utf8");
  const line = env.split("\n").find((l) => l.startsWith("DATABASE_URL="));
  if (!line) throw new Error("DATABASE_URL not found");
  return line.slice("DATABASE_URL=".length).trim().replace(/^["']|["']$/g, "");
}

const email = process.argv[2]?.toLowerCase().trim();
if (!email) {
  console.error("Usage: node scripts/create-admin.mjs <email> [password]");
  process.exit(1);
}

const ADMIN_ROLES = ["super_admin", "billing", "support", "support_read"];
const requestedRole = process.argv[3] && ADMIN_ROLES.includes(process.argv[3]) ? process.argv[3] : "super_admin";

const client = new pg.Client({ connectionString: loadDatabaseUrl() });
await client.connect();

try {
  const existing = await client.query("SELECT id, name, role FROM users WHERE email = $1", [email]);
  let password = process.argv[4] || null;
  if (existing.rows.length > 0) {
    const user = existing.rows[0];
    await client.query("UPDATE users SET role = $1 WHERE id = $2", [requestedRole, user.id]);
    console.log(`✓ Promoted existing user #${user.id} (${user.name}, ${email}) to ${requestedRole}`);
    if (!password) console.log("  Password unchanged — use their existing password to log in at /waadmin");
  } else {
    password = password || randomBytes(9).toString("base64url");
    const hash = await bcrypt.hash(password, 12);
    const name = email.split("@")[0].replace(/[._-]+/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
    const inserted = await client.query(
      "INSERT INTO users (name, email, password, role, email_verified) VALUES ($1, $2, $3, $4, NOW()) RETURNING id",
      [name, email, hash, requestedRole]
    );
    console.log(`✓ Created super admin #${inserted.rows[0].id}: ${email} (${requestedRole})`);
    if (!process.argv[4]) console.log(`  Password: ${password}\n  (Store it now — it is not shown again)`);
  }
} finally {
  await client.end();
}
