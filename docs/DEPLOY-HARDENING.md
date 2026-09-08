# Hardening Deploy Guide — 2026-09-08

Four changes in this batch. Local builds pass (server tsc + Next.js build). DB steps must run on the VPS.

## 1. Crash safety (code only — nothing to run)
- `unhandledRejection` → logged, process keeps serving
- `uncaughtException` → logged, clean exit(1); PM2 restarts
- PM2 already restarts on crash. Optionally after deploy: `pm2 restart wangari-server --update-env` and confirm `↺` resets.

## 2. ZKTeco device secrets (schema change)
Every existing device gets a `device_secret` column. The push endpoint now **requires** it — devices that don't send it will be rejected (401).

VPS steps:
```bash
cd /var/www/wangari/server
npx prisma generate
# give existing devices a secret (run once):
sudo -u postgres psql -d wangari_db -c "UPDATE zkteco_devices SET device_secret = encode(gen_random_bytes(24), 'hex') WHERE device_secret IS NULL OR device_secret = '';"
```
Then in the Wangari UI, open each device's row to view its secret and configure it on the physical device (ADMS "device password" / snSecret field depends on model).

New devices registered via `POST /api/zkteco/devices` get a secret auto-generated and returned once in the response.

## 3. Migrations (one-time baseline)
The repo now has `server/prisma/migrations/0_init` reflecting the current schema. Tell Prisma the live DB already matches:

```bash
cd /var/www/wangari/server
npm run db:deploy        # applies 0_init — will no-op after resolve
npm run db:baseline      # marks 0_init as applied without running it
```
Order matters: run `deploy` first (it creates `_prisma_migrations` table), and if it errors on existing tables, run `baseline` then `deploy` again to confirm clean state.

All future schema changes: edit `server/prisma/schema.prisma` → `npx prisma migrate dev --name <change>` locally, commit the migration folder, `npm run db:deploy` on the VPS. Never `db push` again (kept only as an escape hatch).

The frontend schema (`wangari-next/prisma/schema.prisma`) is now a byte-copy of the server one — regenerate it after any server schema change (`cp server/prisma/schema.prisma wangari-next/prisma/schema.prisma`).

## 4. Money-mutation audit trail (code only)
Every create/update/delete on **transactions**, **sales**, **invoices** — plus **subscription activations** from the Paystack webhook — now writes an `audit_log` row:
`action` (e.g. `sale.payment`), entity type/id, before/after amounts, actor userId, farmId.
Audit failures never break the business operation (fire-and-forget with error logging). Visible on the existing `/audit` page.
