---
name: mobile-design
description: >
  Mobile-first, touch-first responsive design guidance for web apps. Use when
  improving mobile/phone views, adding responsive layouts, fixing touch targets,
  implementing bottom navigation, or auditing mobile UX. Enforces 44px min
  touch targets, thumb-zone CTAs, no horizontal scroll, safe-area insets.
---
# Mobile Design System

**(Mobile-First · Touch-First · Platform-Respectful)**

> **Core Law:** Mobile is NOT a small desktop. Think constraints first, aesthetics second.

## Key Rules for Web Responsive Design

### Touch Targets
- Min `44px` height/width for all interactive elements
- Use `min-h-[44px]` Tailwind class on buttons and nav items
- Primary CTAs in thumb zone (bottom half of screen)

### Layout Patterns
- Mobile bottom navigation bar for apps with 4–6 main sections
- Sidebar becomes drawer (slide-in from left) on mobile
- Stack columns vertically: `flex-col md:flex-row`
- `min-height: 100dvh` not `100vh` (iOS Safari bug)
- Add `pb-safe` or `padding-bottom: env(safe-area-inset-bottom)` for iPhone home indicator

### Typography
- Base font 16px min (prevents iOS zoom on input focus)
- Line height 1.5–1.6 for body
- Headings scale down: `text-xl sm:text-2xl lg:text-3xl`

### No-Horizontal-Scroll Rule
- All tables wrapped in `overflow-x-auto`
- No fixed pixel widths wider than viewport
- Use `max-w-full` on images and media

### Performance
- No heavy animations on scroll on mobile
- Use `transform` and `opacity` only (GPU-accelerated)
- Lazy load images below fold

### Anti-Patterns (Hard Bans)
| ❌ Never | ✅ Always |
|---------|---------|
| Touch targets < 44px | `min-h-[44px]` on all buttons |
| No loading state | Skeleton / spinner |
| Notification dropdown `right-0` overflow | Center or full-width on mobile |
| Fixed sidebar always visible | Slide-in drawer on mobile |
| `100vh` for full-screen | `100dvh` |
| Inline overflow without scroll | `overflow-x-auto` wrapper |

### Breakpoints (Tailwind)
- `sm` = 640px (large phones landscape)
- `md` = 768px (tablets)
- `lg` = 1024px (small laptop — show sidebar)
- `xl` = 1280px (desktop)

### Mobile Bottom Nav Pattern
```tsx
// 5 key tabs: visible only on mobile (lg:hidden)
// Fixed bottom, safe area aware
<nav className="fixed bottom-0 left-0 right-0 z-50 lg:hidden bg-white border-t border-gray-200
                pb-[env(safe-area-inset-bottom)]">
  <div className="grid grid-cols-5 h-16">
    {tabs.map(tab => (
      <Link key={tab.href} href={tab.href}
        className="flex flex-col items-center justify-center gap-1 min-h-[44px]
                   text-xs font-medium">
        {tab.icon}
        <span>{tab.label}</span>
      </Link>
    ))}
  </div>
</nav>
```

### Content Padding for Bottom Nav
```tsx
// Main content needs bottom padding on mobile to not be hidden under nav
<main className="flex-1 p-4 sm:p-6 pb-20 lg:pb-6">
```
