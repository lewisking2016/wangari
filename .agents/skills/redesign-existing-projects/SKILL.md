---
name: redesign-existing-projects
description: >
  Use when upgrading existing websites or apps by auditing generic UI patterns
  and applying premium design fixes without rewrites. Covers typography, color,
  layout, interactivity states, component patterns, and iconography audits.
  Works with the existing tech stack — no framework migrations.
---
# Redesign Skill

## When to Use
- Redesign, restyle, modernize, polish, or improve an existing website or app UI
- Audit current frontend code and make targeted visual improvements
- Design feels generic, AI-generated, poorly spaced, visually flat, or missing responsive/interactive/loading/empty/error states

## Limitations
- Upgrades existing UI — does not authorize framework migrations or product-scope expansion
- Preserve working behavior, routing, data flows, accessibility semantics
- Validate redesigned screens across supported browsers and viewport sizes

## How This Works
1. **Scan** — Read codebase. Identify framework, styling method, current design patterns.
2. **Diagnose** — Run through the audit below. List every generic pattern, weak point, and missing state.
3. **Fix** — Apply targeted upgrades with the existing stack. Do not rewrite from scratch.

## Fix Priority (Max Impact, Min Risk)
1. **Font swap** — biggest instant improvement, lowest risk
2. **Color palette cleanup** — remove clashing or oversaturated colors
3. **Hover and active states** — makes the interface feel alive
4. **Layout and spacing** — proper grid, max-width, consistent padding
5. **Replace generic components** — swap cliche patterns for modern alternatives
6. **Add loading, empty, and error states** — makes it feel finished
7. **Polish typography scale and spacing** — the premium final touch

## Design Audit Checklist

### Typography
- [ ] Browser default fonts → replace with Geist, Outfit, Cabinet Grotesk, or Satoshi
- [ ] Headlines lack presence → increase size, tighten letter-spacing
- [ ] Body text too wide → limit to ~65 chars, increase line-height
- [ ] Only Regular + Bold → introduce Medium (500) and SemiBold (600)
- [ ] Numbers in proportional font → use `font-variant-numeric: tabular-nums` for data
- [ ] Missing letter-spacing → negative for large headers, positive for labels
- [ ] Orphaned words → `text-wrap: balance` or `text-wrap: pretty`

### Color and Surfaces
- [ ] Oversaturated accent colors → keep saturation < 80%
- [ ] More than one accent color → pick one
- [ ] Mixed warm and cool grays → stick to one gray family
- [ ] Purple/blue "AI gradient" aesthetic → replace with neutral base + single accent
- [ ] Generic `box-shadow` → tint shadows to match background hue
- [ ] Flat design with zero texture → add subtle noise or micro-pattern
- [ ] Random dark section in light page → commit to consistent tone

### Layout
- [ ] Everything centered and symmetrical → break with offset margins
- [ ] Three equal card columns → replace with 2-col zig-zag or asymmetric grid
- [ ] `height: 100vh` → replace with `min-height: 100dvh`
- [ ] No max-width container → add ~1200-1440px container with auto margins
- [ ] Missing whitespace → double the spacing, let design breathe
- [ ] Buttons not bottom-aligned in card groups → pin to card bottom
- [ ] Inconsistent vertical rhythm → align shared elements across columns

### Interactivity and States
- [ ] No hover states on buttons → background shift, slight scale, or translate
- [ ] No active/pressed feedback → `scale(0.98)` or `translateY(1px)` on press
- [ ] Instant transitions → add 200–300ms smooth transitions
- [ ] Missing focus ring → visible focus indicators (accessibility required)
- [ ] No loading states → skeleton loaders matching layout shape
- [ ] No empty states → designed "getting started" view
- [ ] No error states → clear inline error messages, no `window.alert()`
- [ ] Dead links → real destinations or visually disabled
- [ ] No active page indicator in nav → style active link differently
- [ ] Animations using `top`/`left`/`width`/`height` → switch to `transform` + `opacity`

### Component Patterns
- [ ] Generic card (border + shadow + white) → remove border or use only bg color
- [ ] Modals for everything → inline editing, slide-over panels, or expandable sections
- [ ] 3-card carousel testimonials → masonry wall or single rotating quote
- [ ] Pricing table → highlight recommended tier with color + emphasis

### Code Quality
- [ ] Div soup → semantic HTML: `<nav>`, `<main>`, `<article>`, `<aside>`, `<section>`
- [ ] Hardcoded pixel widths → relative units (`%`, `rem`, `em`, `max-width`)
- [ ] Missing alt text → describe image content for screen readers
- [ ] Arbitrary z-index like `9999` → establish clean z-index scale
- [ ] Missing meta tags → `<title>`, `description`, `og:image`, social sharing

## Upgrade Techniques

### Surface Upgrades
- **True glassmorphism**: `backdrop-filter: blur` + 1px inner border + subtle inner shadow
- **Colored, tinted shadows**: shadows carry background hue, not generic black
- **Grain overlays**: fixed `pointer-events-none` noise overlay to break flatness

### Motion Upgrades
- **Staggered entry**: elements cascade in with slight delays (Y-axis translate + opacity)
- **Spring physics**: replace linear easing with spring-based motion
- **Active states**: `scale(0.98)` on button press, translate on hover

## Rules
- Work with existing tech stack. No framework migrations.
- Do not break existing functionality. Test after every change.
- Before importing any new library, check `package.json` first.
- If using Tailwind, check version (v3 vs v4) before modifying config.
- Keep changes reviewable and focused. Small, targeted improvements over big rewrites.
