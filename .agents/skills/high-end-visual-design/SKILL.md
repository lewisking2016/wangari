---
name: high-end-visual-design
description: >
  Use when designing expensive agency-grade, Awwwards-tier, Apple-like, or
  Linear-like interfaces with premium fonts, spatial rhythm, soft depth, nested
  bezel components, and fluid microinteractions. Covers layout archetypes,
  motion choreography, and haptic micro-aesthetics. Avoid for plain dashboards
  or constrained low-performance environments.
---
# High-End Visual Design

Full skill source: `../../skills/antigravity/high-end-visual-design.md`

## Core Directive
Engineer $150k+ agency-level digital experiences. Output must exude haptic depth, cinematic spatial rhythm, obsessive micro-interactions, and flawless fluid motion.

## Absolute Zero (Hard Bans)
- **Banned Fonts:** Inter, Roboto, Arial, Open Sans, Helvetica → use Geist, Clash Display, Plus Jakarta Sans
- **Banned Icons:** Thick Lucide/FontAwesome/Material → use Phosphor Light, Remix Line
- **Banned Borders:** Generic 1px solid gray, harsh dark shadows → hairline borders, colored ambient shadows
- **Banned Layouts:** Edge-to-edge sticky navbars, boring symmetrical 3-col grids
- **Banned Motion:** `linear` or `ease-in-out`, instant state changes

## Layout Archetypes (Pick 1 per design)
1. **Asymmetrical Bento** — masonry CSS Grid with varying card sizes (`col-span-8 row-span-2`). Mobile: single column `grid-cols-1`.
2. **Z-Axis Cascade** — cards stacked with depth, slight rotations. Mobile: remove all rotations below 768px.
3. **Editorial Split** — massive typography left, interactive content right. Mobile: vertical stack `w-full`.

**Universal Mobile Override:** Any asymmetric layout MUST fall back to `w-full px-4 py-8` below 768px. Use `min-h-[100dvh]` not `h-screen`.

## Component Mastery

### Double-Bezel / Nested Architecture
```tsx
// Outer shell
<div className="p-1.5 rounded-[2rem] bg-black/5 ring-1 ring-black/5">
  {/* Inner core */}
  <div className="rounded-[calc(2rem-0.375rem)] bg-white shadow-[inset_0_1px_1px_rgba(255,255,255,0.15)]">
    {/* Content */}
  </div>
</div>
```

### Spatial Rhythm
- Section padding: `py-24` to `py-40`
- Eyebrow tags: `rounded-full px-3 py-1 text-[10px] uppercase tracking-[0.2em] font-medium`

## Motion Choreography
- Custom cubic-bezier: `cubic-bezier(0.32,0.72,0,1)` for spring-like motion
- Duration: 500–800ms for layout transitions, 150–300ms for micro-interactions
- Staggered entry: children cascade with 60–100ms delays
- Never use `linear` easing

## Vibe Archetypes (Pick 1)
1. **Ethereal Glass** — OLED black `#050505`, radial mesh gradients, `backdrop-blur-2xl`, hairline borders
2. **Editorial Luxury** — warm cream `#FDFBF7`, CSS noise overlay `opacity-[0.03]`, variable serif headlines
3. **Soft Structuralism** — white/silver-grey, massive Grotesk type, diffused ambient shadows
