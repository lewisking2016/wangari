# Wangari Project — Agent Rules

These rules apply to all agent interactions in this workspace.

## Always-Active Skills

### Communication Style (caveman)
- Drop articles (a/an/the), filler words (just/really/basically/actually)
- Drop pleasantries (sure/certainly/of course/happy to)
- Fragments OK. Direct answers. No tool-call narration.
- Code first, explanation after (3 lines max)
- Never add words to sound casual — compression only

### Minimal Code (ponytail)
Follow the YAGNI ladder before writing any code:
1. Does this need to exist at all? → Skip it
2. Already in this codebase? → Reuse it
3. Stdlib/native feature does it? → Use it
4. Already-installed dep solves it? → Use it (don't add new deps)
5. Can it be one line? → One line
6. Minimum code that works

Shortest working diff wins. Fewest files possible.

## ⚠️ Non-Negotiable UX Rule — Low-Literacy Users

Many Wangari users are **not formally educated** and may be:
- First-time smartphone users
- Unable to read English fluently (use plain Swahili-friendly labels)
- Unfamiliar with standard app conventions (tabs, modals, dropdowns)
- Checking data while working outdoors, one-handed, on a small screen
- Overwhelmed by too many options or long text

**Design must always follow these rules without exception:**

1. **Icons + words always.** Never icon-only. Label every button and nav item. No tooltips as the only explanation.
2. **One action per screen.** Never two forms or two primary CTAs on the same view.
3. **Largest possible touch targets.** Minimum 48px, prefer 56px for primary actions.
4. **Short, simple labels.** "Add Animal" not "Register Livestock Record". "Sales" not "Revenue Transactions". 2–3 words max.
5. **Color + icon + text for status.** Never rely on color alone (red/green). Always pair with an icon and a word.
6. **No jargon.** KPIs must have a plain-English sub-label. "FCR" → "Feed Score (how well animals use feed)".
7. **Confirm before delete.** Never silent deletes. Show what will be deleted, in plain language.
8. **Success/error in plain language.** "Saved!" not "Record persisted successfully". "Couldn't save, check your internet" not "Network error 503".
9. **Progress is always visible.** Loading spinners with text ("Loading..."), not blank screens.
10. **Empty states explain what to do next.** Never a blank page. Always: what it is + how to add the first item.

## Project-Specific Rules

### Tech Stack
- **Frontend:** Next.js 15 App Router, TypeScript, Tailwind CSS v4
- **Backend:** Express + Node.js in `server/` directory
- **Database:** PostgreSQL via Prisma ORM
- **Auth:** JWT tokens, `useAuth` hook on client

### Critical Security Rule
EVERY database query in the server MUST filter by `farmId` from the authenticated user. Never expose cross-farm data.

### Styling
- Use existing Wangari design tokens: `wangari-green-*`, `wangari-cream`, `wangari-border`, etc.
- Never add TailwindCSS v3 config syntax — this project uses Tailwind v4 (`@theme` block in globals.css)
- Follow the existing color system — do not introduce new accent colors

### Mobile Responsiveness
- All new UI must be tested at 375px (mobile phone) width
- Touch targets minimum 44px height
- Tables must have `overflow-x-auto` wrapper
- Use `min-h-[100dvh]` not `h-screen` for full-height sections

### Components
- Check `src/components/ui/` for existing primitives before creating new ones
- Existing: Card, Badge, Button, Input, Label, Avatar, Dialog, etc.
- Use `cn()` from `@/lib/utils` for conditional class merging

### No window.alert / window.confirm
Never use `window.alert()` or `window.confirm()`. Use `useToast` from `@/components/shared/toast` for notifications, and custom inline confirm UIs for destructive actions.

### API Calls
Always use `api` from `@/lib/api-client`. Never use raw `fetch()` in components.

## Available Skills (Use When Relevant)
- **mobile-design** — responsive design, touch targets, bottom nav
- **redesign-existing-projects** — UI audit and visual upgrades
- **high-end-visual-design** — premium agency-grade design
- **frontend-design** — design system and aesthetic direction
- **frontend-developer** — React/Next.js component patterns
- **frontend-dev-guidelines** — code style, file organization
- **nextjs-app-router-patterns** — App Router specific patterns
- **product-design** — UX, user flows, feature scoping
- **security-auditor** — security review checklist
- **database-design** — Prisma schema and query patterns
- **nodejs-backend-patterns** — Express route patterns
- **performance-engineer** — bundle, render, and DB optimization
