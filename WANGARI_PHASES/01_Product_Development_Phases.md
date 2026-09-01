# WANGARI — Product Development Phases
## From Current State to Launch-Ready (100% Ready for Selling)

**Document Owner:** Lewis (Founder, iMeanTech)
**Last Updated:** September 2026
**Target:** 1,000 Active Users by November 2026

---

## CURRENT STATE ASSESSMENT

### What Exists (Phase 0 — Complete)
| Module | Status | Maturity |
|--------|--------|----------|
| Poultry Hub (Flocks, Daily Records, FCR, Vaccination) | ✅ Built | 80% — Needs usability testing |
| Livestock Hub (Individual animals, Milk, Breeding) | ✅ Built | 80% — Needs usability testing |
| Crops Hub (Fields, Planting, Harvest, Cost-per-kg) | ✅ Built | 70% — Needs field validation |
| Feed Hub (Formulas, Batch Production, Cost-per-bag) | ✅ Built | 85% — Strong differentiator |
| Finance Hub (Cashbook, P&L, M-Pesa reconciliation) | ✅ Built | 75% — Needs M-Pesa API integration |
| CRM Hub (Customers, Orders, LPOs, Credit tracking) | ✅ Built | 75% — Needs invoice PDF generation |
| Reports Hub (KPI Dashboard, Trends, AI insights) | ✅ Built | 70% — AI needs training data |
| Web App (Responsive, Multi-user, Role-based) | ✅ Built | 85% — Fast, works on mobile |
| Landing Page (wangari.imeantech.com) | ✅ Built | 50% — Needs complete rewrite |

### What's Missing for Launch-Readiness
| Gap | Priority | Phase |
|-----|----------|-------|
| WhatsApp Bot for data entry | 🔴 CRITICAL | Phase 1 |
| Offline mode | 🔴 CRITICAL | Phase 1 |
| Pricing page on website | 🟡 HIGH | Phase 1 |
| Onboarding wizard (show 1 module, not 12) | 🔴 CRITICAL | Phase 1 |
| AI trained on Kenyan farm data | 🟡 HIGH | Phase 2 |
| Swahili language support | 🟡 HIGH | Phase 2 |
| Push notifications (vaccination alerts, low stock) | 🟡 HIGH | Phase 2 |
| M-Pesa direct API integration | 🟠 MEDIUM | Phase 2 |
| USSD data entry | 🟠 MEDIUM | Phase 3 |
| Ear-tag camera scanner | 🟢 LOW | Phase 3 |
| Mobile apps (Android/iOS) | 🟢 LOW | Phase 3 |
| Bank loan integration | 🟢 LOW | Phase 4 |

---

## PHASE 1: FOUNDATION (Weeks 1-4: Sept 1 - Sept 30)
### Theme: "Make It Work for ONE Farmer"

**Goal:** A single farmer can sign up, enter data in under 5 minutes, see their profit, and come back tomorrow.

### Week 1 (Sept 1-7): The Onboarding Wizard

**Problem:** Currently, a new user sees 12 modules and freezes. Choice overload kills adoption.

**Build: "The Goal Picker"**

```
SCREEN 1: Welcome to Wangari!
"What's the #1 thing you want to track?"

[🐔 My Poultry]    [🐄 My Livestock]
[🌾 My Crops]      [📦 My Inventory]
[💰 My Money]      [🤝 My Customers]

User picks ONE → System shows ONLY that module's dashboard.
Other 11 modules are HIDDEN (grayed out or invisible).
```

**After 14 days of usage**, show a subtle prompt:
> "Great job! You've tracked 14 days of poultry data. Want to see how your feed costs connect to your profit? [Connect Feed Hub →]"

This unlocks Module 2. Repeat at Day 30 for Module 3.

**Technical Requirements:**
- [ ] Frontend: Build Goal Picker component (React/Vue — match existing stack)
- [ ] Backend: User preference storage (which modules are active)
- [ ] Dashboard: Dynamic rendering based on active modules only
- [ ] Onboarding wizard: Step-by-step setup for the chosen module (add first flock, add first customer, etc.)
- [ ] Day 14 & Day 30 unlock prompts (in-app notification)

**Acceptance Criteria:**
- New user can complete setup in under 5 minutes
- First data entry (e.g., today's egg count) takes under 60 seconds
- User sees a meaningful report within 24 hours of first entry

---

### Week 1 (Parallel): Website Rewrite

**Problem:** Current site leads with "12+ Integrated Modules" — features. Must lead with PAIN.

**Rewrite the hero section:**

BEFORE:
> "One System. Every Farm. Smart Farming Technology"

AFTER:
> "Last Month, Your Farm Made KES _____ or Lost KES _____. Wangari Tells You in 30 Seconds."

Or:
> "Your Feed Manager Stole 180,000 KES. Wangari Stops That."

**Add pricing section:**
```
START FREE. UPGRADE WHEN YOU SEE RESULTS.

Starter:     KES 500/month    — Cashbook + Inventory (1 hub)
Pro:         KES 1,500/month  — Any 3 hubs + Reports
Enterprise:  KES 3,000/month  — All 7 hubs + AI + Priority Support

🎯 First 1,000 Farmers: 50% OFF FOREVER
   Limited spots. Claim yours before November.
```

**Add social proof section (placeholder for now):**
```
WHAT FARMERS ARE SAYING
"I used to guess my profit. Now I know exactly." — [Name], Kiambu
"Wangari showed me I was losing KES 15,000/month on feed waste." — [Name], Nakuru
```
(These will be filled with real testimonials from Phase 1 pilot farmers)

**Technical Requirements:**
- [ ] New hero section with pain-first headline
- [ ] Pricing table section (visible on homepage)
- [ ] Case study template (empty, ready for pilot farmer data)
- [ ] FAQ section (keep existing, update pricing Q&A)
- [ ] Mobile-optimized (80%+ of Kenyan users are mobile)

---

### Week 2 (Sept 8-14): WhatsApp Bot — MVP

**Problem:** Data entry is the #1 reason farmers quit. They won't type into a form every day. But they WILL send a WhatsApp message.

**Build: "Wangari Bot" — WhatsApp Integration (FREE via Evolution API)**

**Version 1 (MVP) — Simple Data Entry:**

```
Farmer sends: "eggs 40, mortality 2, feed 3 bags"
Bot responds: "✅ Recorded! Batch B4 Layers:
   Eggs: 40 (crate 6.7)
   Mortality: 2 (total: 12/500)
   Feed: 3 bags (remaining: 47 bags)
   Today's revenue: KES 2,400
   This week's profit: KES 12,800"
```

**Commands (V1):**
| Farmer Types | Bot Action |
|---|---|
| `eggs 40` | Logs 40 eggs for active batch |
| `mortality 2` | Logs 2 deaths, updates flock count |
| `feed 3 bags` | Deducts 3 bags from inventory, logs cost |
| `sold 10 crates @ 400` | Logs sale, updates revenue |
| `summary` | Returns today's summary (eggs, mortality, feed, revenue, profit) |
| `week` | Returns this week's summary |
| `stock` | Returns current inventory levels |

**Technical Requirements:**
- [ ] **Evolution API** (free, self-hosted) — install on Contabo VPS via Docker
- [ ] NLP parser for free-text commands (regex-based for V1, upgrade to Ollama + Mistral later)
- [ ] Backend: Map parsed commands to Wangari API endpoints
- [ ] Daily summary auto-send (configurable time, default 6pm)
- [ ] Error handling: "Sorry, I didn't understand. Try: eggs 40, mortality 2, feed 3 bags"

**Cost: $0/month.** Evolution API is free and self-hosted on your Contabo VPS. Zero per-message costs.

See 04_Business_Funding_Financial_Model.md for full free stack details.

---

### Week 2 (Parallel): Offline Mode — Critical Path

**Problem:** 35% of Kenyan farmers have unreliable internet. If the app doesn't work offline, you lose 35% of your market.

**Build: "Offline-First" Architecture**

**How it works:**
1. User opens Wangari in browser → app downloads a Service Worker + cached assets
2. User enters data while offline → data stored in IndexedDB/localStorage
3. When connection returns → data syncs to server automatically
4. Conflict resolution: latest entry wins, with timestamp

**Offline Mode Features (V1):**
- ✅ Enter daily records (eggs, mortality, feed, milk)
- ✅ View cached reports and dashboards
- ✅ Send WhatsApp messages (WhatsApp works on 2G, so bot works offline-adjacent)
- ❌ AI queries (needs internet — show "Connect to internet for AI")
- ❌ Real-time multi-user sync (conflicts resolved on reconnect)

**Technical Requirements:**
- [ ] Service Worker registration and caching strategy
- [ ] IndexedDB schema for offline data storage
- [ ] Sync queue with conflict resolution
- [ ] Visual indicator: "Offline — data will sync when connected"
- [ ] Background sync API (where supported)
- [ ] Test on 2G connection simulation

---

### Week 3 (Sept 15-21): The "First 50" Agent Toolkit

**Problem:** You can't scale to 1,000 users alone. You need field agents. But agents need tools.

**Build: "Agent Dashboard"**

A simple admin panel where field agents can:
1. **Create farm accounts** on behalf of farmers (farmer doesn't need to sign up themselves)
2. **Enter data** for farmers who can't type (agent visits farm, enters data on farmer's behalf)
3. **Track onboarding progress** (which farmers are active, which haven't logged in for 3+ days)
4. **Generate reports** to show farmers their data during visits

**Technical Requirements:**
- [ ] Admin panel with agent role
- [ ] "Create Account on Behalf" flow (agent enters farm name, type, modules)
- [ ] "Quick Entry" screen (agent selects farmer → enters data → saves)
- [ ] Agent leaderboard (farmers onboarded, active rate)
- [ ] Export: Agent performance report (for commission calculation)

---

### Week 4 (Sept 22-30): Pilot Launch — 5 Farmers

**Not a tech task. A human task.**

**Find 5 farmers:**
- 3 poultry farmers (layers or broilers)
- 1 dairy farmer
- 1 mixed farmer

**Where to find them:**
- Your personal network
- Local agrovets (ask the shop owner: "Which farmers here are struggling with records?")
- Facebook farming groups (post in "Kenya Poultry Farmers" group)
- Church/community groups in Kiambu or Nakuru

**Onboarding process:**
1. Visit farm in person
2. Set up their Wangari account (use Agent Dashboard)
3. Enter their current data (flock size, current stock, recent sales)
4. Show them the WhatsApp bot (send first message together)
5. Leave a printed "Quick Reference Card" with WhatsApp commands
6. Schedule weekly check-in call/visit

**What to track for each farmer:**
| Data Point | How | Frequency |
|---|---|---|
| Login/app usage | Analytics | Daily |
| WhatsApp messages sent | Bot logs | Daily |
| Data accuracy | Agent verification during visits | Weekly |
| Farmer satisfaction | 1-5 rating question | Weekly |
| Profit/loss calculation | Finance Hub | Weekly |
| Time spent on records | Self-reported | Weekly |

---

## PHASE 2: VALIDATION (Weeks 5-8: Oct 1 - Oct 31)
### Theme: "Prove It Works with Real Numbers"

**Goal:** 100 active users. 5 pilot farmers with documented before/after results. First case study video.

### Week 5-6 (Oct 1-14): Scale to 100 Users

**Distribution channels:**

| Channel | Strategy | Expected Users |
|---|---|---|
| Field agents (2 hired) | Visit 5 farms/day each, onboard on the spot | 50 users |
| 1 cooperative partnership | Present at cooperative meeting, bulk signup | 30 users |
| Facebook farming groups | Post case study from pilot farmers | 10 users |
| WhatsApp group ("Wangari Farmers") | Community building, tips, referrals | 10 users |

**Agent commission structure:**
- KES 500 per farmer successfully onboarded (farmer uses app for 7+ consecutive days)
- KES 200 bonus if farmer completes 30-day streak
- Target: 2 agents × 5 farms/day × 20 working days = 200 potential, 100 realistic

### Week 5-6 (Parallel): AI Training

**Download and integrate:**
1. GROW-Africa dataset (535K crop yield observations) — for yield benchmarking
2. FAO poultry enterprise budgets for Kenya — for cost-per-bird calculations
3. Lacuna Fund Kenya dataset — for pest/disease identification

**Build AI features:**
- "Your FCR is 2.8. Top farms in Kiambu average 2.1. Here's how to improve."
- "Based on your data, you'll run out of feed in 12 days. Reorder now."
- "Your mortality rate (4%) is above the 2% average for layers in your region."

**Technical Requirements:**
- [ ] Download and preprocess GROW-Africa dataset
- [ ] Build benchmarking engine (compare user data to regional averages)
- [ ] Build anomaly detection (flag unusual mortality, feed consumption, production drops)
- [ ] Build prediction models (feed depletion, expected egg production based on flock age)
- [ ] AI response generation (rule-based for V1, LLM-upgradable later)

---

### Week 7-8 (Oct 15-31): The Hero Case Study

**This is your most important marketing asset.**

**Process:**
1. Select the best-performing pilot farmer (most consistent data, most engaged)
2. Document their "BEFORE" state:
   - "I used notebooks. I never knew my real profit. I suspected feed theft but couldn't prove it."
   - Take photos of their old notebooks, their farm, their face
3. Document their "AFTER" state (30 days of Wangari data):
   - "Wangari showed me I was spending KES 3,000 more on feed than I thought."
   - "I can see exactly how many eggs each batch produces."
   - "My manager can't lie about mortality anymore."
4. Calculate their REAL numbers:
   - Cost savings from waste reduction
   - Revenue increase from better tracking
   - Time saved (hours per week not doing manual calculations)
5. Record a 60-90 second video testimonial
6. Publish on:
   - Wangari website (case study section)
   - YouTube
   - Facebook (farming groups)
   - Twitter/X

---

## PHASE 3: ACCELERATION (Weeks 9-10: Nov 1 - Nov 15)
### Theme: "1,000 Users in 14 Days"

**Goal:** Hit 1,000 active users. Launch referral program. Secure first cooperative partnerships at scale.

### Week 9 (Nov 1-7): Cooperative Blitz

**Partner with 3-5 cooperatives:**

| Cooperative Type | Value Proposition | Deal Structure |
|---|---|---|
| Dairy cooperatives | Track milk per cow, detect theft, prove quality to buyers | KES 300/member/month (bulk rate) |
| Poultry cooperatives | Track flock performance, feed costs, vaccination compliance | KES 300/member/month |
| SACCOs (savings & credit) | Farmers with Wangari data get faster loan approvals | Wangari provides data, SACCO refers farmers |

**How to approach cooperatives:**
1. Request a 15-minute slot at their next members' meeting
2. Demo the WhatsApp bot live (have a farmer send a message in real-time)
3. Show the case study video (your pilot farmer's story)
4. Offer: "First 30 days free for all cooperative members"
5. Sign a Memorandum of Understanding (MOU)

**Target: 3 cooperatives × 100 active members each = 300 users**

### Week 9 (Parallel): Referral Program

**"Bring 5 Farmers, Get 1 Month Free"**

```
YOUR REFERRAL CODE: [FARMER-NAME-XXXX]

Share this code with 5 farming friends.
When they sign up and use Wangari for 7 days:
→ YOU get 1 month free
→ THEY get 1 month free
→ Top referrers win a free upgrade to Enterprise for 3 months
```

**Technical Requirements:**
- [ ] Referral code generation (unique per user)
- [ ] Referral tracking (who invited whom)
- [ ] Automatic credit application (when referral is confirmed active)
- [ ] Leaderboard (show top referrers — social proof + competition)

### Week 10 (Nov 8-15): Push Notifications Launch

**Build and deploy:**
- Vaccination reminders: "Batch B4: Newcastle vaccine due in 2 days"
- Low stock alerts: "Feed stock: 8 bags remaining. At current usage, you'll run out in 5 days."
- Payment reminders: "Customer John Kamau owes KES 12,400 (30 days overdue)"
- Weekly summary push: "This week: 280 eggs, KES 16,800 revenue, KES 4,200 profit. +12% vs last week."

**Technical Requirements:**
- [ ] Push notification service (Firebase Cloud Messaging for web)
- [ ] Notification scheduling engine (cron-based for recurring alerts)
- [ ] User notification preferences (opt-in/out by category)
- [ ] Delivery tracking (sent, delivered, opened)

---

## PHASE 4: POLISH (Weeks 11-12: Nov 16 - Nov 30)
### Theme: "Make It Stick"

**Goal:** 1,000 active users RETAINED. Revenue flowing. System stable. Ready for investor conversations.

### Week 11 (Nov 16-22): Retention Engine

**The #1 metric isn't signups — it's WEEK 2 RETENTION.**

If a farmer uses Wangari for 7+ consecutive days, they're 5x more likely to stay for 6 months.

**Retention features to build:**
1. **"Streak" counter** (like Duolingo): "🔥 14-day streak! You've entered data every day for 2 weeks!"
2. **Weekly email/SMS report**: Auto-sent every Monday morning with key metrics
3. **"Win of the week" notification**: "This week you saved KES 2,100 compared to last week!"
4. **Inactivity alerts to agents**: If a farmer hasn't logged in for 3 days, agent gets notified to follow up
5. **Gamification**: "You're in the top 20% of Wangari farmers in Kiambu county!"

### Week 11 (Parallel): USSD Data Entry (Light Version)

**For farmers with basic phones (no smartphone, no WhatsApp):**

```
Dial *123# (or USSD short code)
1. Enter Production
   → Eggs: ___
   → Mortality: ___
   → Feed used: ___
2. View Summary
   → Today: Eggs 40, Profit KES 1,200
3. View Stock
   → Feed: 47 bags, Vaccines: 12 bottles
```

**Technical Requirements:**
- [ ] USSD gateway integration (Africa's Talking or similar)
- [ ] USSD session management (menu tree)
- [ ] Backend API mapping (USSD inputs → Wangari database)
- [ ] USSD response formatting (max 160 characters per screen)

---

### Week 12 (Nov 23-30): System Hardening

**Before you can sell, the system must be bulletproof:**

| Task | Priority | Owner |
|---|---|---|
| Load testing (1,000 concurrent users) | 🔴 CRITICAL | Dev |
| Security audit (user data encryption, role-based access) | 🔴 CRITICAL | Dev |
| Backup & disaster recovery testing | 🔴 CRITICAL | Dev |
| Bug bash (invite 10 power users to break the system) | 🟡 HIGH | QA + Users |
| Performance optimization (page load < 3 seconds on 3G) | 🟡 HIGH | Dev |
| Documentation (user guide, agent training manual) | 🟠 MEDIUM | Content |
| Terms of Service & Privacy Policy | 🟠 MEDIUM | Legal |
| Customer support SOP (WhatsApp support channel) | 🟠 MEDIUM | Support |

---

## PHASE 5: SCALE (Dec 2026 — Q1 2027)
### Theme: "From 1,000 to 10,000"

**Goal:** Product-market fit confirmed. Revenue sustainable. Ready for Series Seed round.

### Features to Build
| Feature | Why | Priority |
|---|---|---|
| M-Pesa direct API (auto-reconcile payments) | Eliminates manual payment tracking | 🔴 HIGH |
| Ear-tag camera scanner | Instant livestock identification | 🟡 HIGH |
| Android app (PWA or native) | Better offline experience | 🟡 HIGH |
| iOS app | Smaller market in Kenya, but needed for expansion | 🟠 MEDIUM |
| Multi-language (Kalenjin, Kikuyu, Luo) | Reach beyond Swahili speakers | 🟠 MEDIUM |
| Bank integration (loan applications via Wangari data) | THE MOAT — credit scoring for farmers | 🔴 CRITICAL |
| Cooperative admin dashboard | Bulk management for cooperative leaders | 🟡 HIGH |
| Marketplace integration (connect farmers to buyers) | Additional revenue stream | 🟢 LOW |
| Feed mill B2B ordering | Connect feed manufacturers to farmers | 🟢 LOW |

---

## PHASE 6: DEFENSE (Q2 2027+)
### Theme: "Build the Moat Nobody Can Cross"

**The features that make Wangari INDESCRIBABLE to leave:**

1. **Credit Score System**: Farmers build a "Wangari Credit Score" based on their farm data. Banks use this score to issue loans. If a farmer leaves Wangari, they lose their credit history. **This is the ultimate lock-in.**

2. **Cooperative Network Effects**: If 300 members of a cooperative use Wangari, leaving means losing connection to the cooperative's data, reports, and marketplace.

3. **AI That Knows YOUR Farm**: After 6 months of data, Wangari's AI knows this specific farmer's patterns. "Your Batch B5 typically lays 38 eggs/day at week 24. You're at 32. Something is wrong." Generic tools can't do this.

4. **Insurance Integration**: Partner with Kenya Agricultural Insurance programs. Farmers with Wangari data get lower premiums because their risk is quantifiable.

---

## TECHNICAL ARCHITECTURE (Reference)

```
┌─────────────────────────────────────────────────────┐
│                    WANGARI SYSTEM                     │
├─────────────────────────────────────────────────────┤
│                                                       │
│  FRONTEND          BACKEND           DATA LAYER      │
│  ─────────         ───────           ──────────      │
│  Web App           API Server        PostgreSQL       │
│  (React/Vue)       (Node/Python)     (Primary DB)     │
│                                                       │
│  WhatsApp Bot      Auth Service      Redis            │
│  (Twilio/360dialog)(JWT/RBAC)       (Cache/Sessions)  │
│                                                       │
│  USSD Gateway      AI Engine         IndexedDB        │
│  (Africa's Talking) (LLM + Rules)    (Offline Storage) │
│                                                       │
│  PWA/Offline       Notification      S3/Cloudflare    │
│  (Service Worker)  Service           (Static Assets)  │
│                                                       │
│  Agent Dashboard   Analytics         Backup           │
│  (Admin Panel)     (Events/Usage)    (Daily Snapshots) │
│                                                       │
└─────────────────────────────────────────────────────┘

EXTERNAL INTEGRATIONS:
├── M-Pesa API (Safaricom Daraja)
├── WhatsApp Business API (Twilio/360dialog)
├── USSD Gateway (Africa's Talking)
├── Weather API (OpenWeatherMap / Kenya Met)
├── Market Prices API (FAO / Local data)
├── Email Service (SendGrid / AWS SES)
└── SMS Service (Africa's Talking)
```

---

## MILESTONE TRACKER

| Milestone | Target Date | Status | Success Criteria |
|---|---|---|---|
| Onboarding wizard live | Sept 14 | ⬜ | New user completes setup in <5 min |
| WhatsApp bot MVP | Sept 21 | ⬜ | Farmer can send "eggs 40" and see it in dashboard |
| Website rewrite | Sept 14 | ⬜ | Pain-first headline, pricing visible |
| Offline mode (basic) | Sept 30 | ⬜ | App works without internet for data entry |
| 5 pilot farmers onboarded | Sept 30 | ⬜ | 5 farmers with 7+ day streaks |
| 100 active users | Oct 31 | ⬜ | 100 farmers with 3+ day/week usage |
| Hero case study published | Oct 31 | ⬜ | Video + blog post with real before/after numbers |
| AI benchmarking live | Oct 31 | ⬜ | Farmers see "Your FCR vs regional average" |
| 1,000 active users | Nov 30 | ⬜ | 1,000 farmers with 1+ usage/week |
| Revenue flowing | Nov 30 | ⬜ | 100+ paying subscribers at KES 500+/month |
| System hardened | Nov 30 | ⬜ | Load test passed, security audit clean |
| Grant applications submitted | Nov 15 | ⬜ | 3+ applications (AYuTe, WFP, others) |

---

*This document is a living roadmap. Update weekly as milestones are hit or adjusted.*
