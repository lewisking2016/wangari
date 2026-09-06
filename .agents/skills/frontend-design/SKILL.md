---
name: frontend-design
description: >
  Use when designing production-grade, distinctive web interfaces that avoid
  generic AI UI patterns. Covers intentional aesthetic direction, design systems,
  color theory, typography, component patterns, and visual memorability. Acts as
  a frontend designer-engineer, not a layout generator.
---
# Frontend Design (Distinctive, Production-Grade)

Full skill source: `../../skills/antigravity/frontend-design.md`

You are a **frontend designer-engineer**, not a layout generator. Create memorable, high-craft interfaces that avoid generic "AI UI" patterns, express a clear aesthetic point of view, and are fully functional and production-ready.

## Core Design Mandate
Every output must satisfy **all four**:
1. **Intentional Aesthetic Direction** — named, explicit design stance
2. **Technical Correctness** — real, working code, not mockups
3. **Visual Memorability** — at least one element remembered 24h later
4. **Cohesive Restraint** — no random decoration, every flourish serves the thesis

❌ No default layouts · ❌ No design-by-components · ❌ No "safe" palettes · ✅ Strong opinions, well executed

## Wangari Project Design System

### Existing Tokens (from globals.css)
```css
--color-wangari-green-50 through 900  /* Primary brand greens */
--color-wangari-cream: #FAFBFC        /* Page background */
--color-wangari-heading: #0F172A      /* Headings */
--color-wangari-text: #334155         /* Body text */
--color-wangari-muted: #64748B        /* Secondary text */
--font-sans: "Inter", system-ui       /* Body font */
--font-serif: "Instrument Serif"      /* Display/numbers */
```

### Design Stance: Agricultural Premium
- **Palette:** Deep forest greens + warm cream + slate neutrals
- **Typography:** Inter for UI, Instrument Serif for numbers/display
- **Components:** Rounded-xl (12px) cards, subtle border `border-wangari-border`
- **Motion:** Stagger children 60ms, fade+translateY(8px) entry

## Key Principles for This Project
1. Never break the green/cream color system — extend within it
2. Data cards use `font-serif` for numbers (premium feel)
3. Status badges: amber=warning, red=danger, green=success (consistent)
4. All interactive items: `transition-all duration-150` minimum
5. Forms: labeled inputs, clear error states, no `window.alert()`
6. Empty states: never blank — show illustration + CTA

## Anti-Patterns (Ban for Wangari)
- Pure white `#FFFFFF` backgrounds (use `bg-wangari-cream` or `bg-white` cards)
- Unstyled `<select>` elements (custom dropdown treatment already in globals.css)
- Modals for simple confirmations → use inline confirm or toast
- `onClick={() => confirm("Delete?")}` → use custom confirm UI
