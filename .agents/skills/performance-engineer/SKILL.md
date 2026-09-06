---
name: performance-engineer
description: >
  Performance optimization for Next.js web apps: Core Web Vitals, bundle size,
  render performance, image optimization, API response times, and database query
  performance. Use when diagnosing slow pages, optimizing build output, or
  improving Lighthouse scores.
---
# Performance Engineer

Full skill source: `../../skills/antigravity/performance-engineer/`

## When to Use
- Page load is slow (> 2s LCP)
- Bundle size is large (> 300kb gzipped)
- Database queries are slow (> 200ms)
- Lighthouse score < 80
- User-reported sluggishness or jank

## Core Web Vitals Targets
| Metric | Target | Danger Zone |
|--------|--------|-------------|
| LCP (Largest Contentful Paint) | < 2.5s | > 4s |
| FID/INP (Interaction to Next Paint) | < 200ms | > 500ms |
| CLS (Cumulative Layout Shift) | < 0.1 | > 0.25 |
| TTFB (Time to First Byte) | < 800ms | > 1.8s |

## Next.js Optimizations

### Bundle Size
```bash
# Analyze bundle
npx @next/bundle-analyzer
ANALYZE=true npm run build
```

- Lazy load heavy components: `const Chart = dynamic(() => import("recharts"), { ssr: false })`
- Use `dynamic` imports for large page-specific deps
- Tree-shake imports: `import { X } from "lucide-react"` not `import * from`

### Images
```tsx
import Image from "next/image";
// Always use next/image — automatic WebP, sizing, lazy loading
<Image src="/logo.png" alt="Logo" width={48} height={48} priority />
```

### Fonts
```tsx
import { Inter } from "next/font/google";
const inter = Inter({ subsets: ["latin"], display: "swap" });
// Apply via className — eliminates FOUT
```

### API Response Caching
```typescript
// Add cache headers to API routes
res.setHeader("Cache-Control", "s-maxage=60, stale-while-revalidate=300");
```

## Database Query Optimization

### Identify N+1 Queries
```typescript
// ❌ N+1 — fetches flock for each production record
const records = await prisma.productionRecord.findMany({ where: { farmId } });
for (const r of records) {
  const flock = await prisma.flock.findUnique({ where: { id: r.flockId } });
}

// ✅ One query with include
const records = await prisma.productionRecord.findMany({
  where: { farmId },
  include: { flock: { select: { id: true, name: true } } },
});
```

### Index Missing Columns
```prisma
// Add indexes for common filter + sort patterns
@@index([farmId, createdAt])
@@index([farmId, type])
```

### Paginate Large Results
```typescript
// Never `findMany` without `take` on unbounded data
const items = await prisma.sale.findMany({
  where: { farmId },
  take: 50,
  orderBy: { createdAt: "desc" },
});
```

## React Render Performance

### Avoid Unnecessary Rerenders
```tsx
// Memo expensive list items
const SaleRow = React.memo(({ sale }: { sale: Sale }) => (
  <tr>...</tr>
));

// Stable callbacks
const handleDelete = React.useCallback((id: number) => {
  api.delete(`/api/sales/${id}`).then(load);
}, []);
```

### Virtualize Long Lists
If a list exceeds ~100 items, use virtual scrolling instead of rendering all DOM nodes.

## Quick Wins (Immediate Impact)
1. Add `loading.tsx` to dashboard routes → better perceived performance
2. Skeleton loaders instead of spinners → perceived speed improvement
3. `priority` prop on above-fold images
4. Reduce API payload size with Prisma `select`
5. Add `React.memo` to components in `.map()` loops
