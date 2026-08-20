# Wangari System — Persona Testing Findings

Walkthrough of the system as 5 farmer personas (Aug 14, 2026).
Each persona: registered, logged in, navigated every module, performed real actions.

## Personas
1. **Joseph Kimani** (jkdairy) — dairy farmer, cows only
2. **Grace Wanjiru** (gracelivestock) — cows + sheep
3. **Peter Ochieng** (peterpoultry) — chickens only
4. **Amina Hassan** (aminasheep) — sheep only
5. **Kipchoge Rono** (kipchogefarm) — small-scale mixed (a few of each)

## Errors Found

### BLOCKER — registration completely broken
- `Frontend/pages/register.php` INSERTs into `users(phone)` but the column is `phone_number`. Every new registration silently failed ("Registration failed. Please try again."). **No farmer can create an account.** → FIXED (phone → phone_number, verified: jkdairy created).

### CRITICAL — customer accounts can't use the system
- Public registration creates role `customer`, but the admin panel only accepts `super_admin`/`farm_manager`/`stock_manager`/`sales_staff`. A registered farmer is bounced to the marketing homepage and has zero access to any module (admin panel 302 → login, APIs 403). Customers are only usable as "shop customers" inside CRM.
- → Finding: either the public registration should create a farm_manager account (or an "owner" role), or the register page should be removed entirely for a private system.

### HIGH — Animals module broken (schema mismatch)
- `hub_operations.php` writes `animals(tag_id, species, date_of_birth)` but the table has `tag, type, birth_date`. Every Add/Edit Animal silently failed. Display columns also referenced wrong keys. → FIXED (tag, type, birth_date; verified Bessie the Friesian saves + displays).

### HIGH — Health Records module broken (schema mismatch)
- Code writes `health_records(animal_id, record_date, diagnosis, treatment, vet_name, cost, notes)`; the table has `subject, type, product, date, next_date, status, notes`. Add health record failed. → FIXED (mapped fields; vet/cost folded into notes since no columns exist).

### HIGH — Breeding Records module broken (schema mismatch)
- Code wrote `breeding_records(sire, dam, breeding_date, expected_birth)`; table has `subject, type, male_parent, date, due_date`. → FIXED.

### CRITICAL — NO DATA ISOLATION BETWEEN FARMS — FOUND BY PERSONA 4
- The core farm tables (`animals`, `herds`, `flocks`, `batches`, `production_records`, `farm_equipment`) have **no `user_id`/`owner_id` column and no row filtering**. Every farm_manager sees every other farmer's data: Amina (sheep) sees Joseph's cow Bessie and Grace's herds, and sees all 5 flocks including Peter's Layer Run B (which she didn't create).
- For a system sold as a *private per-farm* install, this is the biggest issue: if two farmers share one deployment they see each other's animals, stock, and sales.
- → Fix options: (a) add `user_id` to these tables + filter every query; or (b) since the product is sold as one-farm-per-install, at minimum hide cross-user rows. Needs a product decision.

### HIGH — Add Herd form broken (schema mismatch) — FOUND BY PERSONA 2
- `hub_operations.php` save_herd wrote `herds(type, head_count)` but the table has `species, size`. Every Add Herd form POST failed with "Unknown column 'type'". → FIXED (species, size; verified via real UI form POST — 'Ram Breeding Pen' saved).

### MEDIUM — Bulk Export miscounts customers — FOUND BY PERSONA 5
- Bulk Import/Export dashboard shows "CUSTOMERS: 1" and exports only `users WHERE role IN ('customer','demo')` — it never includes `walk_in_customers`. The CRM hub shows 13 customers (users + walk-ins). Exporting "Customers" silently drops all walk-in buyers. Also `expenses` maps to `financial_records`, which may differ from the expenses module's table.

### HIGH — Farm Equipment module broken (schema mismatch) — FOUND BY PERSONA 2
- The V2 hub's equipment form writes `farm_items(name, category, quantity, condition_status, purchase_date, cost, notes)` but `farm_items` is a *sellable products* catalog (`item_type, species, price, stock_quantity`). Every Add/Edit Equipment POST failed with "Unknown column 'category'".
- → FIXED: created a proper `farm_equipment` table (added to migration_v2_business.sql so it auto-creates on installs) and pointed the hub at it. Verified: John Deere tractor + water troughs + milking machine display.

### HIGH — Credit page shows "Network error" — FOUND BY PERSONA 2
- `credit.php` calls `updateKpis([])` but the function was **never defined**; the ReferenceError was caught by the generic catch and displayed as "Network error". → FIXED (added updateKpis). Verified: page now shows "No credit sales yet" + KES 0 KPIs.

### MEDIUM — Production is flock-only
- Daily Production form entity selector lists ONLY flocks (poultry batches). A dairy/goat farmer with no flocks cannot log milk at all — "Milk Yield (Litres)" field exists (placeholder "For Cows/Goats") but there's no way to attach it to a cow or a herd. Production records FK to `flocks`, so milk for livestock is impossible.

### MEDIUM — modal Save buttons sometimes don't submit
- The Add Animal / Add Health / Add Breeding modals: clicking "Save" via browser sometimes does nothing (JS interception or missing submit wiring). Worked when submitting the form directly. Needs testing/cleanup.

### MEDIUM — "AI assistant" is rule-based, not AI
- `ai_assistant.php` answers via a keyword matcher (~6 canned handlers: sales, credit, stock, production count, profit, upcoming). Queries it can't map (e.g. "How much milk did I record this week?") fall back to a generic greeting. Production queries read egg-only `production_records`.

### NOTE — full page sweep
- All 55 admin pages load cleanly as farm_manager (no 500s, no PHP warnings). The broken behavior is in form *actions*, not page loads.

### NOTE — Reminders
- Works: title/details/datetime, channel picker (In-app / WhatsApp / SMS / Email), phone field. Saved a reminder OK. Date/time uses Month/Day/Year/Hour/Minute spinboxes (slow UX).

### NOTE — Persona 3 (Peter, poultry): core poultry flows work
- Flocks (save_flock ✓ verified: 'Layer Run B'), egg production (save_production ✓ verified), hatchery (add_hatch ✓ verified), daily sales (egg-crate reconciliation ✓ verified). Feed stock + stock dashboard fine. The poultry-centric modules are the *only* fully-working vertical.

### NOTE — Persona 4 (Amina, sheep only)
- Herds/Animals accept Sheep ✓ (verified: Amina Flock 1 + ewe SHP-001 display). But: no wool-shearing records, no lambing calendar, no flock-weight tracking, and flocks module is chicken-only (documented above).

### NOTE — Persona 5 (Kipchoge, small mixed)
- Crops & Fields (save_field ✓ verified: Maize Plot A), Labour & Workers (save_worker ✓ verified: John Kamau), Bulk Import/Export (works, but customer count/export wrong — see finding above), Settings (✓ M-Pesa/COD/currency/timezone).

### NOTE — Messages/Team hub
- Team & Messages hub loads (Staff Accounts, Customer List, Assigned Tasks, Team Messages tabs).

## Missing / Needed Features

- **Milk production logging per cow/herd** (kg/litres per animal per day, SCC later) — production is poultry-eggs only in practice.
- **Lactation / dry-off / calving tracking** for dairy cows.
- **Sheep flock records** (the "flocks" module is chicken-centric: batch_type enum broiler/layer/kienyeji/dual_purpose — a sheep or cattle farmer cannot create a flock/batch for their animals).
- **Cattle weight/health growth records** beyond generic animal card.
- **Dairy-specific dashboard** (milk/day, cows in milk, dry cows).
- **Species-aware production** (eggs vs milk vs wool vs weight gain in one module).
- **Real AI/RAG assistant** — current one is canned keyword replies; milk/animal-specific questions go unanswered.
- **Register should create an owner/farm_manager account** (or remove public register for private sales).
- **Per-species data isolation / multi-farm scoping** (see CRITICAL finding) — the top blocker for selling this as a private per-farm system.
- **Walk-in customers in exports** — bulk export only covers `users` accounts.
- **Sheep/goat-specific modules**: wool records, lambing/kidding calendar, weight-gain tracking.
- **Cattle-specific modules**: lactation curve, dry-off, calving, SCC/mastitis log.
- **Offline/mobile/WhatsApp-SMS channel** (Phase 3-4 in research doc) — reminders have channel pickers but no real SMS/WhatsApp gateway.
- **Double-entry accounting** (Phase 3-4) — cashbook exists but no chart of accounts / trial balance.

## Fixes applied during this walkthrough
1. `register.php` — `phone` → `phone_number` (registration was 100% broken).
2. `hub_operations.php` — animals (tag_id/species/date_of_birth → tag/type/birth_date), health records (animal_id/record_date/diagnosis → subject/type/date), breeding (sire/dam/breeding_date → subject/type/male_parent/date/due_date), herds (type/head_count → species/size).
3. `hub_inventory.php` + `migration_v2_business.sql` — created real `farm_equipment` table; equipment form now works.
4. `credit.php` — added the missing `updateKpis()` function (page showed "Network error" permanently).

## Verification state (Aug 14, 2026)
- All 55 admin pages serve 200 as a farm_manager (no 500s, no PHP warnings on load).
- Verified working actions this session: register, login, herd add, animal add, health add, breeding add, equipment add, daily sales, CRM follow-up, flock add, egg production, hatchery add, field add, worker add.
- Files changed (lint-clean): `Frontend/admin/hub_operations.php`, `Frontend/admin/hub_inventory.php`, `Frontend/admin/credit.php`, `Backend/config/migration_v2_business.sql`, `Frontend/pages/register.php`.
- NOT committed yet — review with the user first (includes a schema migration file).
