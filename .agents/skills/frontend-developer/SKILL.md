---
name: frontend-developer
description: >
  Frontend development patterns, component architecture, state management, and
  code quality guidelines for React/Next.js projects. Covers hooks, performance,
  accessibility, TypeScript patterns, and reusable component design. Use when
  writing or reviewing frontend code.
---
# Frontend Developer Guidelines

Full skill source: `../../skills/antigravity/frontend-developer.md`

## Stack (Wangari Project)
- **Framework:** Next.js 15 App Router
- **Language:** TypeScript
- **Styling:** Tailwind CSS v4
- **State:** React hooks + API calls via `api` client (`@/lib/api-client`)
- **Animation:** Framer Motion (`motion`, `AnimatePresence`)
- **Icons:** Lucide React

## Component Patterns

### Standard Page Structure
```tsx
"use client";
import * as React from "react";
// imports...

export default function PageName() {
  const [data, setData] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => { load(); }, []);
  const load = () => api.get("/api/endpoint")
    .then(d => { setData(Array.isArray(d) ? d : []); setLoading(false); })
    .catch(() => setLoading(false));

  if (loading) return <LoadingState />;
  return <div>...</div>;
}
```

### API Client Usage
```tsx
import api from "@/lib/api-client";

// GET
const data = await api.get("/api/resource");

// POST
await api.post("/api/resource", { field: value });

// PATCH
await api.patch(`/api/resource/${id}`, { field: value });

// DELETE
await api.delete("/api/resource/" + id);
```

### Animation (Framer Motion)
```tsx
const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

<motion.div initial="hidden" animate="visible" variants={stagger}>
  <motion.div variants={fadeUp}>...</motion.div>
</motion.div>
```

## Code Quality Rules
- All `useEffect` deps explicit — no empty array suppression
- No `any` for API responses if shape is known — define minimal interface
- Custom hooks for data fetching repeated > 2 times
- `React.memo` on list item components
- `useCallback` on stable callbacks passed as props

## Imports (Path Aliases)
```
@/components/ui/...      → UI primitives (Card, Button, Badge, etc.)
@/components/shared/...  → Shared (PageHeader, EmptyState, Toast)
@/components/layout/...  → Layout (Sidebar, Topbar)
@/hooks/...              → Custom hooks (useAuth, useFarm)
@/lib/api-client         → API client
@/lib/utils              → cn() and utilities
```

## Forms Pattern
```tsx
const [form, setForm] = React.useState({ field: "" });
const handleChange = (key: string) => (e: React.ChangeEvent<HTMLInputElement>) =>
  setForm(f => ({ ...f, [key]: e.target.value }));
```

## Accessibility Minimums
- All buttons have `aria-label` if icon-only
- All inputs have associated `<Label>`
- `tabIndex` order logical
- Color contrast: text meets 4.5:1 ratio
- Focus visible: `:focus-visible` ring (already in globals.css)
