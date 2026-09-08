#!/usr/bin/env node
/**
 * Wangari platform regression suite — runs against a BASE URL with three
 * real sessions. Usage:
 *   BASE=https://api.wangari.imeantech.com node scripts/flow-test.mjs
 * Env: ADMIN_EMAIL/ADMIN_PASS (staff login), WORKER_FARM/WORKER_PIN,
 *       and optionally TEST_USER_EMAIL/TEST_USER_PASS for the owner session.
 * Creates are deleted; safe to re-run.
 */
const BASE = process.env.BASE || "http://localhost:3001";
let passed = 0, failed = 0;
const results = [];

function check(name, ok, detail = "") {
  if (ok) { passed++; results.push(`  ✓ ${name}`); }
  else { failed++; results.push(`  ✗ ${name}${detail ? ` — ${detail}` : ""}`); }
}

async function req(method, path, token, body) {
  const res = await fetch(`${BASE}${path}`, {
    method,
    headers: {
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(body ? { "Content-Type": "application/json" } : {}),
    },
    ...(body ? { body: JSON.stringify(body) } : {}),
  });
  let json = null;
  try { json = await res.json(); } catch {}
  return { status: res.status, json };
}

async function main() {
  const ADMIN_EMAIL = process.env.ADMIN_EMAIL;
  const ADMIN_PASS = process.env.ADMIN_PASS;
  const WORKER_FARM = process.env.WORKER_FARM;
  const WORKER_PIN = process.env.WORKER_PIN;

  console.log(`\nWangari regression suite → ${BASE}\n`);

  // ── public ──
  const health = await req("GET", "/health");
  check("health", health.status === 200);
  const plans = await req("GET", "/api/plans");
  check("public plans", plans.status === 200 && Array.isArray(plans.json) && plans.json.length > 0);

  // ── admin session ──
  let admin = null;
  if (ADMIN_EMAIL && ADMIN_PASS) {
    const login = await req("POST", "/api/admin/login", null, { email: ADMIN_EMAIL, password: ADMIN_PASS });
    check("admin login", login.status === 200 && !!login.json.token);
    admin = login.json?.token;
    if (admin) {
      for (const ep of ["me", "overview", "plans", "billing", "audit", "system"]) {
        const r = await req("GET", `/api/admin/${ep}`, admin);
        check(`admin ${ep}`, r.status === 200);
      }
      // plan write round-trip
      const before = plans.json.find((p) => p.id === plans.json[0].id);
      const upd = await req("PATCH", `/api/admin/plans/${before.id}`, admin, { description: `regression-${Date.now()}` });
      check("admin plan PATCH", upd.status === 200);
      const revert = await req("PATCH", `/api/admin/plans/${before.id}`, admin, { description: before.description ?? null });
      check("admin plan revert", revert.status === 200);
    }
  }

  // ── worker session + flows ──
  if (WORKER_FARM && WORKER_PIN) {
    const wlogin = await req("POST", "/api/worker/login", null, { farmCode: WORKER_FARM, pin: WORKER_PIN });
    check("worker login", wlogin.status === 200 && !!wlogin.json.token);
    const wt = wlogin.json?.token;
    if (wt) {
      for (const ep of ["tasks", "me", "my-attendance", "my-activity"]) {
        const r = await req("GET", `/api/worker/${ep}`, wt);
        check(`worker ${ep}`, r.status === 200);
      }
      // wrong current PIN rejected
      const bad = await req("POST", "/api/worker/change-pin", wt, { currentPin: "0000", newPin: "9999" });
      check("worker change-pin wrong rejected", bad.status === 401);

      // isolation
      const s = await req("GET", "/api/settings", wt);
      check("worker blocked from owner settings", s.status === 403);
      const t = await req("POST", "/api/transactions", wt, { type: "income", amount: 1 });
      check("worker blocked from money writes", t.status === 403);
      const a = await req("GET", "/api/admin/overview", wt);
      // 403 (type mismatch) or 401 (different signing secret) — both rejections.
      check("worker blocked from admin", a.status === 403 || a.status === 401);
    }
  }

  // ── anon isolation ──
  const anonDash = await req("GET", "/api/dashboard");
  check("anon blocked from farm data", anonDash.status === 401);
  const anonAdmin = await req("GET", "/api/admin/overview");
  check("anon blocked from admin", anonAdmin.status === 401);

  console.log(results.join("\n"));
  console.log(`\n${passed} passed, ${failed} failed\n`);
  process.exit(failed > 0 ? 1 : 0);
}

main().catch((e) => { console.error(e); process.exit(1); });
