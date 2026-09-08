# Wangari — Full System Security & Stability Audit

Date: 2026-09-08 · Skills applied: ponytail, caveman, security-auditor, api-security, auth-patterns, api-design, database-design, nextjs-best-practices, frontend-dev-guidelines + external research via agent-reach (Paystack docs, GHSA advisories, CVE-2026-49352, Stripe trial-abuse research).

---

## 1. The money path — what an attacker would do

### CRITICAL (fixed): payments never activated subscriptions
**The hole:** `/api/paystack` initialized Paystack checkout and users paid real money — but **nothing ever wrote to the `subscription` table**. No webhook existed. The frontend showed a success modal based on a URL query param (`?payment=success`), which anyone can type. Paying users stayed locked out; the only working path was the 14-day trial.

**How it hurts:** revenue leakage — users pay, get nothing, churn. And `?payment=success` is client-trusted UI, not authorization.

**The fix (matching Paystack's official docs):**
- New `POST /api/paystack/webhook` — Paystack server-to-server callback
- Verifies `x-paystack-signature` (HMAC-SHA512 of the **raw** body with your secret key) before processing
- Only `charge.success` activates; amount checked against the plan price; idempotent on `reference`
- Writes a real `Subscription` row (`status: active`, correct `expiresAt` for 30/365 days)
- `GET /api/paystack/verify` now requires auth (was public — anyone could probe references)
- Raw-body parser mounted for the webhook path only (HMAC breaks if express.json re-serializes)

**Action needed from you:** add the webhook URL in the Paystack dashboard → `https://<your-api>/api/paystack/webhook`.

### HIGH (fixed): cross-farm money edits (IDOR)
`PATCH /api/transactions/:id` updated by bare ID with **no farm check** — any authenticated user could edit another farm's financial records by iterating IDs. Fixed: find-first scoped to `farmId`, then update. Amounts validated (positive number; type/date required on create).

*(Sales PATCH/DELETE and invoices already scoped — verified, no change needed.)*

## 2. Getting in — authentication holes

### CRITICAL (fixed): AI endpoint was completely open
`POST /api/ai/chat` had **no authMiddleware** and took `farmId` from the request body. Any internet caller could read/write/delete **any farm's** data (the AI tools include deletes) — OWASP API #1, broken object level authorization.

**Fixed:** auth added, `farmId` from the verified token only, all AI delete tools scoped `deleteMany({ where: { id, farmId } })`, tool-call errors caught per-tool.

### CRITICAL (fixed): worker login was broken by a secret mismatch
`worker.ts` signed JWTs with fallback `"wangari-secret-key-2025"` but the middleware verified with `"wangari-dev-secret-change-in-production"` — **every worker login token was rejected**. Even with matching secrets, worker tokens carry `workerId` not `userId`, so the middleware's `prisma.user.findUnique({ id: undefined })` threw → 500. Both fixed: shared exported `JWT_SECRET`, middleware is worker-aware.

### CRITICAL (fixed): hardcoded JWT fallback = known CVE pattern
Both secrets were committed fallbacks — exactly **CVE-2026-49352** (9router): anyone reading the public repo forges valid tokens against deployments that didn't set `JWT_SECRET`. The server now **refuses to boot** in production without `JWT_SECRET`.

### HIGH (fixed): Google token validation incomplete
`/api/auth/google` never checked `aud` (issued-for-this-app) or `email_verified`. A token minted for a *different* OAuth client could be replayed here. Both checked now (aud active when `GOOGLE_CLIENT_ID` is set — add it to the VPS env).

### MEDIUM (fixed): pending subscription = free access
`GET /api/trial/status` treated any `pending` subscription as full access. "Pending" means *paid nothing yet*. Removed from the access decision (still shown in UI).

### MEDIUM (fixed): no brute-force protection on login
Strict limiter (20 req / 15 min) added on login, register, forgot-password, worker PIN login. PIN space is only 10,000 — without this, PIN brute-force is trivial.

## 3. Crash vectors — "can a button crash the system?"

Yes, in four places. Three fixed:

1. **(fixed) No error boundary anywhere** — any render error in any of 19+ pages = white screen. Added `(dashboard)/error.tsx` + `global-error.tsx` with retry.
2. **(fixed) AI tool calls** — one malformed tool call crashed the whole chat endpoint. Now caught per-tool.
3. **(fixed) Unguarded async writes** — e.g. `settings.ts` PUT had no try/catch; Express 4 does not catch async throws, so one DB hiccup = unhandled rejection.
4. **(remaining) Unhandled-rejection crash risk** — any future `await` without try/catch can kill the PM2 process. Add `process.on("unhandledRejection")` logging + PM2 restart policy. Cheap, do next.

**Other crash vectors found:**
- `JSON.parse(user.selectedHubs)` in trial.ts — corrupted value throws → 500 on every request. Wrap it.
- `new Date(req.body.date)` with garbage input → `Invalid Date` persisted or Prisma type error. Transactions create validates now; other routes still trust client dates.
- `Number(req.body.amount)` with `undefined` → `NaN` → silent bad data. Transactions validated.

## 4. Future-change blast radius ("will changes break the system?")

- **No migrations — only `db push`** (package.json has `db:migrate` but the VPS flow uses push). Schema changes are unversioned; drift between local/VPS is exactly how the stale-Prisma 500s you already hit happened. Adopt `prisma migrate` before adding tables.
- **Two Prisma schemas exist** (`wangari-next/prisma/schema.prisma`, `server/prisma/schema.prisma`) — they drift independently; a server-side field missing from the frontend copy compiles fine and fails at runtime. Sync them or generate the frontend client from the server schema.
- **`take: 100` everywhere, no pagination** — a farm with years of data silently loses older records in UI. Correctness trap, not a crash.
- **Invoice numbers from `Math.random()`** — collisions real at volume; no unique constraint on `invoiceNumber`, duplicates silently accepted. Add `@unique` + retry when you next touch invoices.
- **ZKTeco push endpoint** trusts device serial only (no shared secret) — a guessed serial forges attendance, which feeds wages. Add a per-device secret required in the push payload.

## 5. Checked and found OK
- Sale/invoice/customer/flock CRUD scoped to `farmId`
- Password reset: constant response, 1h expiry, token cleared on use
- bcrypt cost 12; upload type/size limits; helmet + CORS locked to frontend URL
- Multer write filenames are server-controlled

## 6. Priority list (in order)
1. **Add the webhook URL in Paystack dashboard** — without it, paid users never activate. (Dashboard action, only you can do it.)
2. **Set `JWT_SECRET` (strong random) and `GOOGLE_CLIENT_ID` on the VPS** before restart — production now refuses to boot without JWT_SECRET.
3. Deploy: `git pull && npx prisma generate && npm run build && pm2 restart wangari-server`
4. Switch to `prisma migrate` for all future schema changes; delete the duplicate frontend schema.
5. ZKTeco per-device secret; wrap `JSON.parse(selectedHubs)`; `unhandledRejection` handler.
6. Later: audit-log money mutations, rate-limit AI chat per farm, invoice-number uniqueness.
