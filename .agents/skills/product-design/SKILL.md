---
name: product-design
description: >
  Product design thinking for user-centered features: user flows, information
  architecture, feature scoping, UX writing, onboarding, and progressive
  disclosure. Use when designing new features, evaluating UX quality, or
  planning user journeys for the Wangari farm management platform.
---
# Product Design

Full skill source: `../../skills/antigravity/product-design.md`

## When to Use
- Designing new features or pages from scratch
- Evaluating whether a feature solves the right problem
- Planning user flows and information architecture
- Writing UX copy (labels, error messages, empty states, tooltips)
- Deciding what to build vs. what to skip

## ⚠️ Wangari User Context — Non-Negotiable

**Primary users:** Smallholder farmers in Kenya/Africa. Many are **not formally educated**.

Assume the user:
- Is a **first-time or low-experience smartphone user**
- May be **semi-literate or non-English-fluent** — Swahili is often primary language
- Is checking the app **outdoors, one-handed, in sunlight**, on a budget Android phone
- Has **no patience for error messages they don't understand**
- Has never used software like Excel, QuickBooks, or similar — this may be their first business tool
- Will **stop using the app** if confused twice in a row

### Absolute UX Laws (Never Break)
| Law | ❌ Wrong | ✅ Right |
|-----|---------|---------|
| Icons always have labels | 🐔 (no text) | 🐔 My Animals |
| Status uses icon + color + word | Red dot | 🔴 Low Stock |
| Labels are plain words | "Register Livestock" | "Add Animal" |
| KPIs have sub-labels | "FCR: 2.1" | "Feed Score: 2.1 — how well animals use feed" |
| Errors are human | "Error 503" | "Couldn't save. Check your internet." |
| Deletes confirm in plain language | Silent delete | "Delete this sale? It cannot be undone." |
| Empty states guide next step | Blank page | "No animals yet. Tap + to add your first." |
| Touch targets are large | 32px button | 48–56px minimum |
| Progress is always shown | Blank screen | Spinner + "Loading your farm data…" |
| One action per screen | Two forms open | One form, one primary button |

**Key workflows:**
1. Log daily production (eggs, milk, weight)
2. Record feed usage and mortality
3. Track sales and outstanding payments
4. Monitor inventory levels
5. View financial summaries

## Feature Scoping Rules
- **YAGNI:** Build only what's needed now. Farmers don't need enterprise features.
- **One action per screen:** Don't crowd multiple data-entry forms on one page.
- **Progressive disclosure:** Show summary → detail → edit. Don't show edit forms by default.
- **Mobile first:** Most farmers use phones. Design for 375px first.

## UX Writing Standards
| Situation | ❌ Avoid | ✅ Use |
|-----------|---------|-------|
| Empty state | Nothing | "No records yet. Add your first one." |
| Success | "Operation completed successfully!" | "Saved." or "Sale recorded." |
| Error | "An error occurred" | "Couldn't save. Check your connection." |
| Delete confirm | `window.confirm("Delete?")` | Custom inline confirm with undo |
| Button labels | "Submit", "OK", "Click here" | "Record Production", "Save Sale", "Add Animal" |

## Information Architecture (Dashboard Sections)
```
Home (Dashboard)
├── My Farm
│   ├── My Animals (Flocks)
│   ├── My Crops
│   ├── Daily Output (Production)
│   └── Health & Vaccines
├── Money & Workers
│   ├── Income & Expenses (Finances)
│   ├── Sales
│   ├── Store / Inventory
│   ├── My Workers
│   ├── Worker Attendance
│   └── Worker View
└── Tools
    ├── Feed Helper
    ├── Weather
    ├── Reports
    ├── AI Assistant
    └── WhatsApp & USSD
```

## Feature Checklist (Before Shipping)
- [ ] Empty state designed (not blank)
- [ ] Loading state designed (skeleton or spinner)
- [ ] Error state designed (with retry action)
- [ ] Mobile layout tested at 375px
- [ ] Touch targets ≥ 44px
- [ ] Labels in Swahili considered
- [ ] Action is reversible or has confirmation
