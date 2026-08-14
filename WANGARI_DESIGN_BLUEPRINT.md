# WANGARI — Design Blueprint

> Rebrand + redesign blueprint for the farming platform, named after **Prof. Wangari Maathai**.
> Website direction: **Growvi** organic-farm Framer template (by Mezario). Dashboard: full redesign.
> This document is design only — **no code has been changed.** For approval before implementation.

---

## 1. The Growvi template — what we're borrowing (analyzed live, Aug 2026)

**Live site:** https://growvi.framer.website/ (screenshots saved in /tmp/growvi_home.png, growvi_mid.png, growvi_bottom.png)

### Design system extracted from the live site
| Token | Value | Notes |
|---|---|---|
| Ink/Night (dark sections, hero, footer) | `#000B22` (rgb 0,11,34) | Deep navy — editorial, premium |
| Accent (CTAs, highlights) | `#D0F24C` (rgb 208,242,76) | Lime/acid green — fresh, energetic |
| Off-white (light sections) | `#F4F5F8` | Clean, airy |
| Surface | `#FFFFFF` | Cards |
| Earth tan | `#81754F` | Subtle organic warmth |
| Headings/body font | **Inter Tight** | Modern, tight, readable |
| Accent font | **Instrument Serif** | Editorial serif for emphasis words/numbers |

### Section inventory (homepage — a complete modern farm-brand site)
1. **Hero** — headline + subhead + 2 CTAs + big stat row (50K+ plants saved, 16+ languages, 26k+ joined, 95% satisfaction)
2. **About** — mission + stats strip + "Trusted by 200+ corporate partners"
3. **Services** — numbered list (01 Crop Management, 02 Soil Health & Analysis, 03 Irrigation Solutions, 04 Sustainable Farming) with product-ish names (FieldCare, HarvestBoost, FieldGuardian…)
4. **Why Choose Us** — 3 cards (Tailored Strategies, Advanced Techniques, Long-Term Vision)
5. **Our Projects** — project cards with location + results
6. **Working Process** — 3 steps (Consultation → Planning → Implementation)
7. **Testimonials** — 4.9/5.0 carousel
8. **CTA + Inquiry form** — Name / Phone / Email / Service type / Message
9. **FAQ** — 5-accordion
10. **Blogs** — CMS cards
11. **Footer** — contact, menus, email subscribe ("Stay Up To Date")

### What it gives us
- A **modern, nature-inspired, editorial layout** that instantly says "organic farm brand" — far more premium than the current Busia public site.
- **Proven section patterns** (stats, numbered services, process, projects, testimonials, FAQ, blogs) that we can map 1:1 onto the Wangari site and into the dashboard.
- A **high-contrast, fresh palette** that works beautifully on cheap phones (deep navy + bright lime = readable outdoors).

---

## 2. Wangari Maathai — the soul of the brand (research via agent-reach)

**Who she was (verified sources: NobelPrize.org, Britannica, Green Belt Movement, UN):**
- Born **Nyeri, Kenya, 1940** — died 2011. First woman in East & Central Africa to earn a **doctorate** (PhD, University of Nairobi, 1971). First woman to chair the Dept. of Veterinary Anatomy.
- Founder of the **Green Belt Movement (1977)** — grassroots tree-planting with women's groups; **20+ million trees** planted.
- **2004 Nobel Peace Prize** — first African woman and first environmentalist to win it, "for her contribution to sustainable development, democracy and peace."
- UN **Messenger of Peace**. Author of *Unbowed*, *The Challenge for Africa*, *Replenishing the Earth*, *The Green Belt Movement*.
- Core values: **sustainability, growth, empowerment, community, justice, African pride, action over talk.**

**Her voice (use in the brand):**
> "It's the little things citizens do. That's what will make the difference. My little thing is planting trees."

> "Until you dig a hole, you plant a tree, you water it and make it survive, you haven't done a thing. You are just talking."

> "I will be a hummingbird. I will do the best I can." (her Nobel lecture story)

**Why "Wangari" is a perfect product name:** she stands for *growing things, nurturing farms, empowering people, and sustainable African progress* — exactly what an African farm ERP/CRM/AI platform should mean. Every farm that uses Wangari is, in her spirit, planting and growing.

---

## 3. The Wangari brand identity

### 3.1 Name & story
- **Product name:** Wangari — "Smart Farming for a Sustainable Future"
- **Tagline options:** "Grow smarter. Rooted in Africa." / "The farm that grows itself." / "Where every farm grows."
- **Brand story (About page):** born in Kenya, inspired by Prof. Wangari Maathai — technology as the "green belt" for modern farms: record, grow, protect, and profit sustainably.

### 3.2 Logo & symbol
- **Primary mark:** a **hummingbird + leaf/tree** glyph in a circle — the hummingbird from her Nobel lecture ("I will do the best I can"), the leaf = growth. Green ring ("the green belt").
- **Wordmark:** "Wangari" in Inter Tight SemiBold, with "GROW" or a leaf in lime.
- **Favicon:** the leaf/hummingbird mark.

### 3.3 The Wangari design system (merging Growvi + Maathai's nature)
| Token | Value | Where used |
|---|---|---|
| **Canopy Green** (primary) | `#166534` (deep forest green) | Buttons primary, links, active nav, dashboard primary |
| **Nightingale Ink** (night) | `#0B1220` (Growvi navy, green-tinted) | Hero/footer/dashboard sidebar dark sections |
| **Acacia Lime** (accent) | `#D0F24C` (Growvi lime) | CTAs, highlights, badges, AI accents |
| **Humus Earth** | `#8A6D3B` | Subtle borders, icons, warmth |
| **Cream** (off-white) | `#FAF8F2` | Light section backgrounds |
| **Mist** | `#F4F5F8` | Table/surface backgrounds |
| Fonts | **Inter Tight** (UI/headings) + **Instrument Serif** (accent numerals, pull-quotes) | Global |
| Motifs | Green horizontal "belt" bands, leaf curves, growth-line charts | Section dividers, data viz |

### 3.4 Voice & microcopy
- Short, plain English (Grade-7-friendly per your expansion plan), Swahili-ready.
- Action verbs: *plant, grow, harvest, protect, track, sell.*
- Error/empty states phrased as growth language: "Nothing planted here yet — add your first flock."

---

## 4. Website redesign (public site → Growvi-style)

Map the current Busia pages onto Growvi's structure. All existing features (shop, cart, checkout, products, recipes, FAQs) are kept and restyled.

| Page | Growvi inspiration | Wangari content |
|---|---|---|
| Home V1 (default) | Hero + stats + about + services + projects + testimonials + FAQ + blogs + CTA | Hero: "Grow smarter with Wangari" + stats (farms onboarded, birds raised, KES saved); Featured products slider (keep); Trust words slider (keep); "Why Wangari" = Growvi's "Why Choose Us" |
| Home V2 (alt) | Same sections, different layout | Alternate hero with the hummingbird story |
| Shop | Growvi product showcase | Existing catalog, restyled cards (Inter Tight, lime price chips) |
| Products | CMS-style service cards | Categories: Poultry, Livestock, Crops, Feeds, Services |
| About | "Farm story" | **Wangari Maathai tribute** + company story + stats + certifications |
| Services | Numbered services (01–04) | Farm services: Consultation, Feed Production, Health & Vet, Training (maps to your admin modules!) |
| Recipes | Blogs CMS | Existing recipes as CMS cards |
| FAQ | 5-question accordion | Existing 20+ FAQs |
| Contact | Inquiry form (Name/Phone/Email/Service/Message) | Existing contact + WhatsApp/SMS options |
| Pricing | Pricing page | Future SaaS tiers or bulk pricing table |
| 404 | Template 404 | On-brand 404 ("This field is empty") |
| Login/Register/Checkout/Cart | Keep flows | Restyle to the new system; **fix the checkout fatal bug first** |

### Mobile-first + Africa-reality
- Keep everything **low-data, fast, PWA-ready**, works on entry-level phones (per the 12-reasons research).
- Add **Swahili toggle** (i18n-ready).
- WhatsApp/SMS contact CTAs.

---

## 5. Dashboard redesign (admin + customer)

### 5.1 Layout
- **Sidebar:** Ink navy (#0B1220), white text, **lime active-state** with a leaf marker. Sections kept from the hub: Dashboard, Operations, Inventory, Sales & Finance, Reports, Team, Settings — plus new **AI Assistant** entry (lime, with sparkle).
- **Topbar:** breadcrumb, global search, weather strip (small), notifications bell, user menu.
- **Content:** Cream background, white cards, Inter Tight, **Instrument Serif for big KPI numerals** (like Growvi's stats).

### 5.2 Dashboard (Operations Cockpit → "Wangari Home")
Redesign the current dashboard to Growvi's stat-strip style:
- **KPI strip (serif numerals):** Active flocks/herds/crops, eggs today, revenue this month, stock value, alerts count.
- **Revenue Trend** (keep Chart.js, restyle to brand colors).
- **"Growvi-style" sections:** System Overview cards (Platform Status, Top Moving Products, Raw Material Health) restyled; **What needs your attention today** (alerts, tasks, low stock) — the decision layer from the research.
- **AI Assistant panel:** chat box ("Ask Wangari anything about your farm") — stub UI now, engine in AI phase.
- **Weather + market strip** (phase 2 API).

### 5.3 Data tables & forms
- Tables: Cream header rows, hover rows, lime "live" pulses for stock alerts, serif numerals for amounts.
- Forms: consistent Growvi-style inputs (focus ring = lime), clear required-field markers, native date pickers (fix the M3 issue), inline validation messages.
- **Empty states** rewritten in growth language with a clear first action.

### 5.4 Customer dashboard (Frontend/pages/dashboard.php)
- Order history, recurring feed schedules, credit balance, invoices — restyled; add "Ask Wangari" chat stub.

---

## 6. Everything researched gets added (roadmap recap with design)

**Phase 0 — Rebrand shell + fixes** (design-first):
1. Apply Wangari design tokens (CSS variables) globally.
2. Fix **checkout fatal** (float cast) — restyle checkout to brand.
3. Fix GSAP ScrollTrigger registration; fix dead links, titles, cart label.
4. New logo/favicon, header/footer, fonts, colors across public site.

**Phase 1 — Website v1 (Growvi structure):** Home V1/V2, About (Wangari tribute), Services, Shop, Products, Recipes (CMS), FAQ, Contact, Pricing, 404 — all restyled + Swahili toggle + PWA.

**Phase 2 — Dashboard v1:** Wangari Home (KPI strip, attention panel), restyled hub pages, tables, forms, empty states; consolidate duplicate legacy pages into the hub.

**Phase 3 — Generalize + ERP depth:** crops/dairy/fish/bees data model, custom fields, multi-location, full accounting, CRM, labour, weather API.

**Phase 4 — Mobile + channels:** offline-first PWA, WhatsApp/SMS/USSD, voice-first.

**Phase 5 — AI (Wangari Assistant):** RAG chat over farm data → voice (Swahili/English) → photo disease detection → predictive alerts → AI reports → receipt auto-logging.

---

## 7. Approval checklist (what I need from you)
1. ✅/❌ Name **Wangari** and the tagline ("Grow smarter. Rooted in Africa." or your preferred).
2. ✅/❌ Palette (Canopy Green + Nightingale Ink + Acacia Lime + Cream) — or keep Growvi's pure navy+lime.
3. ✅/❌ Logo direction (hummingbird + leaf mark) — or you provide a logo file.
4. ✅/❌ Start with **Phase 0 + Phase 1** (rebrand shell + public site) first, then dashboard (Phase 2)?
5. ✅/❌ Confirm the checkout fix is part of the first build.

*Nothing coded yet — this blueprint is for your approval.*
