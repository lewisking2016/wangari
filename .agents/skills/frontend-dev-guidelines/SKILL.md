---
name: frontend-dev-guidelines
description: >
  Frontend development guidelines: code style, file organization, TypeScript
  strictness, testing, git conventions, and team practices for the Wangari
  Next.js project. Use when onboarding, reviewing code, or setting up new
  features.
---
# Frontend Dev Guidelines

Full skill source: `../../skills/antigravity/frontend-dev-guidelines.md`

## Code Style

### TypeScript
- Prefer explicit types over `any` — define interfaces for API responses
- Use `type` for unions/primitives, `interface` for object shapes
- No `// @ts-ignore` — fix the type problem properly
- Strict null checks respected — guard undefined/null before access

### React
- Components: PascalCase named functions, not arrow functions at export level
- Hooks: camelCase, prefix with `use`
- Event handlers: `handle` prefix (`handleSubmit`, `handleDelete`)
- State: descriptive names (`isLoading` not `loading2`)

### File Organization
```
src/
  app/
    (dashboard)/
      [feature]/
        page.tsx        ← main page (default export)
  components/
    [feature]/          ← feature-scoped components
    shared/             ← cross-feature reusables
    ui/                 ← primitive UI components
    layout/             ← sidebar, topbar, footer
  hooks/                ← custom React hooks
  lib/                  ← utilities, api client
  types/                ← shared TypeScript types
```

### Import Order
1. React/Next
2. Third-party (framer-motion, lucide-react, etc.)
3. Internal `@/components/...`
4. Internal `@/hooks/...`
5. Internal `@/lib/...`

## Naming Conventions
| Thing | Convention | Example |
|-------|-----------|---------|
| Component | PascalCase | `FlockCard` |
| Hook | camelCase with `use` | `useFarm` |
| Utility | camelCase | `formatCurrency` |
| Type | PascalCase | `FlockData` |
| API route | kebab-case | `/api/flocks` |
| CSS class | kebab-case | `kpi-card` |

## State Management Rules
- **Local state** for UI (modals open, form values, loading flags)
- **No global store** — use React Context only for auth (`useAuth`) and farm (`useFarm`)
- **No prop drilling** > 2 levels — extract context or pass callback down

## Performance Guidelines
- `React.memo` on components rendered in lists
- `useCallback` on event handlers passed as props
- Avoid anonymous functions in JSX: `onClick={() => fn()}` → `onClick={fn}`
- Split large page components into sub-components > 100 lines
- Use `React.Suspense` for lazy-loaded sections

## Error Handling
```tsx
// Always catch API errors
api.get("/api/resource")
  .then(setData)
  .catch(() => showToast("Failed to load. Please try again.", "error"))
  .finally(() => setLoading(false));
```

## Git Conventions
- Commits: `feat:`, `fix:`, `style:`, `refactor:`, `chore:`
- Branch names: `feature/feature-name`, `fix/bug-description`
- PRs: describe what changed and why, not how
