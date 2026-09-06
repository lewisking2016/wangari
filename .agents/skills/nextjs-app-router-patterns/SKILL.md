---
name: nextjs-app-router-patterns
description: >
  Next.js 15 App Router patterns, Server vs Client Components, data fetching,
  routing, layouts, metadata, and performance optimization. Use when building
  or debugging Next.js App Router pages, layouts, APIs, and middleware.
---
# Next.js App Router Patterns

Full skill source: `../../skills/antigravity/nextjs-app-router-patterns/`

## This Project's App Router Setup
- **Version:** Next.js 15 with App Router
- **Root layout:** `src/app/layout.tsx`
- **Dashboard group:** `src/app/(dashboard)/layout.tsx` — has Sidebar + Topbar
- **Auth group:** `src/app/(auth)/`
- **Marketing:** `src/app/(marketing)/`
- **API routes:** `src/app/api/`
- **Middleware:** `src/middleware.ts` — handles auth redirects

## Key Patterns

### "use client" Boundary
```tsx
// Server component (default) — no hooks, no browser APIs
export default async function Page() {
  const data = await fetch(...); // direct in server component
  return <div>{data}</div>;
}

// Client component — must declare at top
"use client";
import * as React from "react";
export default function Page() {
  const [state, setState] = React.useState(null);
  // ...
}
```

**Rule:** Most dashboard pages are `"use client"` because they use `useAuth`, `useFarm`, and API calls via `api-client`.

### Data Fetching (Client Components)
```tsx
React.useEffect(() => {
  api.get("/api/resource").then(setData).catch(() => {});
}, []);
```

### Route Groups
```
(dashboard)  → authenticated app pages, shares sidebar layout
(auth)       → login/register pages, no sidebar
(marketing)  → public pages, different layout
```

### Metadata
```tsx
// In server components/page.tsx
export const metadata = {
  title: "Page Title | Wangari",
  description: "Description",
};
```

### Next.js API Routes
Located in `src/app/api/`. These proxy to the Express backend at `server/`:
```tsx
// src/app/api/resource/route.ts
export async function GET(request: Request) { ... }
export async function POST(request: Request) { ... }
```

### Dynamic Routes
```
src/app/(dashboard)/flocks/[id]/page.tsx  →  /flocks/123
```

### Loading & Error
```
src/app/(dashboard)/page/loading.tsx  →  automatic loading UI
src/app/(dashboard)/page/error.tsx    →  error boundary
```

## Performance Rules
- Keep Client Components as deep/small as possible
- Server Components for static content (marketing pages)
- `React.Suspense` for async boundaries
- `next/image` for all images
- `next/font` for fonts
