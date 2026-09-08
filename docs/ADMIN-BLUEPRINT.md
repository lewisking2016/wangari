# Wangari Super Admin Dashboard — Blueprint & Architecture

> Research-backed design for the internal "mission control" of the Wangari platform.
> Sources: SaaS admin-panel architecture write-ups (Yaro Labs, ShipSolid checklist, Voxire internal-tools guide),
> coupon/affiliate data-model guides (dev.to Stripe attribution), email-ops deliverability practice (Cadence),
> and boxyhq saas-starter-kit community feature requests. Community platforms requiring login
> (Reddit/X raw feeds) were blocked, so research leaned on curated engineering write-ups that summarize
> practitioner discussion.

---

## 0. Ground rules from the research (what not to get wrong)

1. **Separate admin identity from customer identity.** Never reuse the customer JWT with an added claim.
   Admin auth = separate token type, shorter expiry, separate middleware chain (Voxire: "these are different identity systems").
2. **Separate API surface.** Admin routes live under their own mount with their own middleware stack
   (`/api/admin/*`), never sprinkled as `role: admin` checks on customer routes. Prefer a dedicated server/port
   later; a strictly-segregated router + auth chain is the pragmatic v1.
3. **Audit log from day one.** Every admin write logs actor, entity, before/after, timestamp — immutable. (We already
   have `AuditLog` + `auditMoneyMutation`; extend it into a general-purpose audit helper, not just money-path.)
4. **RBAC granularity, not one "admin" role.** Research is explicit: support-read, support-full, billing, super-admin.
   A tier-1 support agent should apply credits but not change plan tiers; finance shouldn't impersonate.
5. **Impersonation is the highest-value, most dangerous feature.** Do it last among the power tools, heavily audited,
   banner-visible, time-boxed.
6. **Admin panel ≠ customer UI.** It shows internal states (raw subscription rows, references, provider event traces),
   not marketing views.

---

## 1. Current system inventory (what exists to build on)

| Area | Status |
|---|---|
| Auth (email/password, Google, verification codes) | ✅ `User` (role default `farm_owner`), `VerificationCode` |
| Tenancy | ✅ `Farm` (owner, code, name, location) — our "tenant" |
| Billing | ✅ `Subscription`, `Plan` (DB-driven pricing, seeded), Paystack init/webhook/verify, audit on activation |
| Finance audit | ✅ `AuditLog` (money-path today) |
| Users' ops data | ✅ Workers, attendance, flocks, crops, sales, transactions, invoices, inventory… (tenant-scoped) |
| Marketing site | ✅ `(marketing)` group: home, about, pricing, features, onboarding |
| Support contact | ❌ Only `mailto:` links — no tickets, no inbox |
| Email sending | ❌ No ESP integration at all (no nodemailer/resend in deps) |
| Coupons / partnerships | ❌ Nothing |
| Admin role | ❌ `User.role` is a string but nothing uses an admin path; no admin API, no admin UI |

---

## 2. Module blueprint (the dashboard's map)

M0 **Overview** — platform KPIs: signups, active farms, MRR (from `Plan.amount` × active subs), trial conversions,
7-day activity chart, recent signups, recent payments, open tickets count. The "see what is happening" screen.

M1 **Tenants (Farms) & Accounts (Users)** — unified lookup (name/email/phone/farm-code), full operational context in one view:
farm profile, owner, plan/subscription state, worker count, last activity, verification status. Controlled actions:
suspend/activate farm, force password reset, verify email manually, extend trial, change plan, apply credit, deprovision user.
Every action audited.

M2 **Billing & Payments** — subscription table (all farms, filterable by status/plan), Paystack transaction references,
webhook event trace list (we can store raw webhook events), manual plan override, comp/credit notes, revenue by plan chart.
Money reads come from `Subscription` + a new `PaymentEvent` table.

M3 **Pricing & Plans** — CRUD over the existing `Plan` table (it's already DB-driven — this dashboard is the reason we built that).
Edit name/amount/days/active/sort; instant effect on checkout + webhook validation. Change history audited.

M4 **Coupons & Partnership codes** — one `PromoCode` model powering three code types:
`discount` (public offers), `partnership` (affiliate/partner codes with attribution), `credit` (applies account balance).
Fields: code, type, discountType (percent/fixed), value, maxRedemptions, timesRedeemed, expiresAt, planScope (planId or any),
partnerName, active. Redemption recorded server-side at checkout/webhook (`PromoRedemption`), partner conversions reportable.
Registration/checkout endpoints accept a code; webhook stores attribution.

M5 **CRM** — `Contact` + `ContactNote` models: leads (from marketing contact form), partner prospects, existing-customer records.
Pipeline stages: `lead → contacted → demo → trial → customer → churned`. Owner (admin), source, next-follow-up date.
This is the "manage the customers" ask beyond in-app users.

M6 **Support / Tickets** — `Ticket` + `TicketMessage` models. Created from (a) in-app "Help" widget (new endpoint),
(b) admin manual entry, (c) email inbox later. Fields: subject, status (`open/pending/solved/closed`), priority, farm/user linkage, assignee.
Conversation thread inline; status changes audited. SLA-lite: aging indicator (open > 48h highlighted).

M7 **Email / Outbound** — `EmailLog` model (to, subject, template, status: queued/sent/failed/bounced, providerId, error).
ESP integration (Resend or SMTP via nodemailer — decision in build phase) with:
- transactional sends (verify email, password reset, payment receipt, ticket replies) all logged
- `noreply@imeantech.com` (or better: `mail.imeantech.com` subdomain per deliverability research) as verified sender
- admin can browse the log, resend failures, and compose one-off announcements (marketing later phase; subdomain split first)

M8 **Marketing site / Content ops** — manage pricing-page copy (reads from `Plan.description`),
announcements banner (`Announcement` model: message, link, active window — shown in-app), and the marketing
contact-form inbox (feeds M5 as leads). Blog/CMS explicitly deferred (YAGNI until content ops is real).

M9 **Admins & RBAC** — manage `AdminUser` entries (or `User.role` values) with four roles:
`super_admin` (everything), `billing` (M2/M3), `support` (M1 read + M6/M7 full), `support_read` (read-only).
Permission map is code-defined and checked by `requireAdmin(role)`. All logins via separate admin login page + MFA-ready.

M10 **System / Health** — service status (API health, DB latency), webhook health (last event age), background job list,
feature flags per farm (deferred to phase 2 unless needed earlier).

**Explicitly deferred:** bulk operations UI, advanced BI/reporting exports, in-app chat widget, blog CMS, warm-up cron tooling.

---

## 3. Data model additions (Prisma)

```prisma
model PromoCode {
  id             String   @id @default(cuid())
  code           String   @unique
  type           String   // discount | partnership | credit
  discountType   String?  @map("discount_type") // percent | fixed
  value          Int?     // percent (1-100) or fixed KES
  planId         String?  @map("plan_id")       // scope; null = any plan
  maxRedemptions Int?     @map("max_redemptions")
  timesRedeemed  Int      @default(0) @map("times_redeemed")
  partnerName    String?  @map("partner_name")
  expiresAt      DateTime? @map("expires_at")
  active         Boolean  @default(true)
  createdBy      Int?     @map("created_by")
  createdAt      DateTime @default(now()) @map("created_at")
  redemptions    PromoRedemption[]
  @@map("promo_codes")
}

model PromoRedemption {
  id          Int      @id @default(autoincrement())
  promoCodeId String   @map("promo_code_id")
  userId      Int      @map("user_id")
  farmId      Int?     @map("farm_id")
  reference   String?  // paystack reference when tied to payment
  amountKes   Decimal? @map("amount_kes") @db.Decimal(10, 2)
  createdAt   DateTime @default(now()) @map("created_at")
  promoCode   PromoCode @relation(fields: [promoCodeId], references: [id])
  @@index([promoCodeId])
  @@map("promo_redemptions")
}

model Contact {           // CRM
  id          Int      @id @default(autoincrement())
  name        String
  email       String?
  phone       String?
  company     String?  // farm/org
  type        String   @default("lead") // lead | partner | customer
  stage       String   @default("lead") // lead | contacted | demo | trial | customer | churned
  source      String?  // contact_form | manual | referral
  ownerId     Int?     @map("owner_id") // admin user
  notes       ContactNote[]
  createdAt   DateTime @default(now()) @map("created_at")
  updatedAt   DateTime @updatedAt @map("updated_at")
  @@map("crm_contacts")
}

model ContactNote {
  id        Int      @id @default(autoincrement())
  contactId Int      @map("contact_id")
  body      String
  authorId  Int?     @map("author_id")
  createdAt DateTime @default(now()) @map("created_at")
  contact   Contact  @relation(fields: [contactId], references: [id], onDelete: Cascade)
  @@map("crm_contact_notes")
}

model Ticket {
  id          Int      @id @default(autoincrement())
  subject     String
  status      String   @default("open") // open | pending | solved | closed
  priority    String   @default("normal") // low | normal | high
  userId      Int?     @map("user_id")
  farmId      Int?     @map("farm_id")
  assigneeId  Int?     @map("assignee_id")
  messages    TicketMessage[]
  createdAt   DateTime @default(now()) @map("created_at")
  updatedAt   DateTime @updatedAt @map("updated_at")
  @@index([status])
  @@map("tickets")
}

model TicketMessage {
  id       Int      @id @default(autoincrement())
  ticketId Int      @map("ticket_id")
  authorType String @map("author_type") // user | admin
  authorId Int?     @map("author_id")
  body     String
  createdAt DateTime @default(now()) @map("created_at")
  ticket   Ticket   @relation(fields: [ticketId], references: [id], onDelete: Cascade)
  @@map("ticket_messages")
}

model EmailLog {
  id        Int      @id @default(autoincrement())
  to        String
  subject   String
  template  String?  // verify_email | reset | receipt | ticket | announcement | oneoff
  status    String   @default("queued") // queued | sent | failed | bounced
  provider  String?
  providerId String? @map("provider_id")
  error     String?
  userId    Int?     @map("user_id")
  createdAt DateTime @default(now()) @map("created_at")
  @@index([status])
  @@map("email_logs")
}

model PaymentEvent {      // webhook trace for billing ops
  id        Int      @id @default(autoincrement())
  provider  String   @default("paystack")
  eventType String   @map("event_type")
  reference String?
  payload   Json
  processed Boolean  @default(false)
  error     String?
  createdAt DateTime @default(now()) @map("created_at")
  @@index([reference])
  @@map("payment_events")
}

model Announcement {
  id        Int      @id @default(autoincrement())
  message   String
  link      String?
  active    Boolean  @default(true)
  startsAt  DateTime @default(now()) @map("starts_at")
  endsAt    DateTime? @map("ends_at")
  createdAt DateTime @default(now()) @map("created_at")
  @@map("announcements")
}
```

Plus: `User.role` gains `"super_admin" | "billing" | "support" | "support_read"` values (existing string column — no migration
beyond usage). `VerificationCode` stays as-is.

---

## 4. Backend architecture (Express server)

```
server/src/
  lib/
    admin-auth.ts        # requireAdmin(roles[]) middleware — separate JWT type { adminId, role }, 4h expiry
    audit.ts             # extend: auditAdminAction(actor, entity, before, after) — general purpose
    email.ts             # ESP client: send(to, template, data) → writes EmailLog, handles failure
    promo.ts             # validateCode(code, planId) → discount calc; redeem(code, userId, ref) 
  routes/
    admin/
      index.ts           # admin router: mounts everything below, applies adminAuth + adminAudit to ALL
      overview.ts        # M0 KPIs
      farms.ts           # M1 tenant list/detail/actions
      users.ts           # M1 user list/detail/actions (reset, verify, deprovision)
      billing.ts         # M2 subscriptions, payment events, credits/overrides
      plans.ts           # M3 plan CRUD
      promos.ts          # M4 promo CRUD + redemption stats
      crm.ts             # M5 contacts + notes + pipeline moves
      tickets.ts         # M6 ticket CRUD + replies
      emails.ts          # M7 email log browse/resend + announcement send
      announcements.ts   # M8
      admins.ts          # M9 admin user management (super_admin only)
      system.ts          # M10 health
  scripts/
    create-admin.ts      # CLI: node scripts/create-admin.js <email> — bootstrap first super admin
```

**Cross-cutting rules enforced in code:**
- No admin route may call customer middleware. `adminAuth` verifies `admin` token type only.
- `adminAudit` middleware logs every mutating request (method, path, adminId, body-summary) into `AuditLog`.
- Money actions (plan override, credit) also call `auditMoneyMutation` for the finance trail.
- Public endpoints extended, not duplicated: checkout (`/api/paystack`) accepts optional `promoCode`;
  webhook writes `PaymentEvent` rows for every event (trace) and applies promo attribution.

**Auth bootstrap:** first super admin via CLI script on the VPS (no self-signup — ever). Admin login page is a
separate route (`/admin/login`) issuing the short-lived admin JWT. MFA: phase 2 (TOTP), flagged in research as
"enforced ideally" — we'll gate by making admin login require a second factor stored per admin.

---

## 5. Frontend architecture (wangari-next)

```
wangari-next/src/app/
  (admin)/                      # separate route group — its own layout, no farm chrome
    admin/login/page.tsx        # dedicated admin login
    admin/layout.tsx            # admin shell: sidebar (modules M0–M10), topbar, RBAC-aware nav
    admin/page.tsx              # M0 overview
    admin/farms/page.tsx        # M1 + [id]/page.tsx detail
    admin/users/page.tsx        # M1
    admin/billing/page.tsx      # M2
    admin/plans/page.tsx        # M3
    admin/promos/page.tsx       # M4
    admin/crm/page.tsx          # M5 (+ [id] detail, kanban-lite stage board)
    admin/tickets/page.tsx      # M6 (+ [id] thread view)
    admin/emails/page.tsx       # M7
    admin/content/page.tsx      # M8 announcements + contact-form inbox
    admin/admins/page.tsx       # M9 (super_admin only)
    admin/system/page.tsx       # M10
  lib/admin-client.ts           # fetch wrapper: admin token storage key, 401 → /admin/login
  components/admin/             # shared admin UI: DataTable, StatusBadge, KpiCard, ActionDialog,
                                # AuditTrail, TicketThread, PipelineBoard
```

**Conventions:** server components for data fetching where possible; client components only for interactivity
(tables with filters, forms). `middleware.ts` guards `/admin/*` (redirect to `/admin/login` without admin token —
but *authorization* is always re-checked server-side per request; middleware is UX only).
Route group keeps the admin visually and structurally separate from the farm app (research: admin is an
internal product, not a copy of the customer UI).

**In-app touchpoints that feed the admin dashboard:**
- Marketing contact form → `POST /api/contact` → CRM lead + email to admin
- In-app Help widget (small) → `POST /api/support/tickets` → M6
- Announcement banner rendered in the farm dashboard shell from `GET /api/announcements`

---

## 6. Build phases (each independently shippable)

**Phase 1 — Foundation (the skeleton + the money)**
Admin auth (separate JWT, CLI bootstrap), admin layout + login, Overview (M0),
Plans CRUD (M3), Billing (M2 read + overrides), audit middleware. *This alone replaces SQL access for pricing/billing.*

**Phase 2 — Users & support**
Farms/Users management with controlled actions (M1), Tickets (M6) + in-app contact form, EmailLog + ESP wiring (M7).

**Phase 3 — Growth tooling**
Promo codes (M4) end-to-end (create → redeem at checkout → attribution in webhook → partner report), CRM (M5),
announcements (M8).

**Phase 4 — Power & polish**
RBAC roles beyond super_admin, impersonation (audited, banner, time-boxed), system/health (M10), feature flags, bulk ops.

---

## 7. Open decisions (need your input before build)

1. **ESP choice:** Resend (modern API, generous free tier) vs nodemailer+SMTP (works with your existing host).
   Affects `lib/email.ts` implementation only.
2. **Admin domain:** serve admin from `wangari.imeantech.com/admin` (simpler) vs `admin.imeantech.com`
   (cleaner separation, needs DNS). v1 recommendation: same domain, separate route group.
3. **First admin:** your account gets promoted via CLI script — confirm which email is yours.
