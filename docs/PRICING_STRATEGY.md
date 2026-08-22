# Wangari Pricing Strategy

## Research Summary

### Global Farm Management SaaS Benchmarks

| Platform | Target | Free Tier | Paid Plans | Key Insight |
|---|---|---|---|---|
| **FarmLogs** (Bushel) | US row crop farms | Limited features | $150-500/mo | Free for basic, premium for analytics |
| **Granular** (Corteva) | Large US farms | No free tier | $200-1,000/mo | Enterprise-focused |
| **Agworld** | Global mixed farms | 30-day trial | $35-200/mo | Per-farm pricing |
| **Fieldview** (Bayer) | US/row crop | Free basic | $500+/yr | Data-driven upsell |
| **AgriWebb** | Livestock farms | No free tier | $100-300/mo | Per-head pricing |
| **MooCall** | Dairy farms | Limited | $10-50/mo | Per-animal pricing |
| **FarmERP** | Indian/African farms | Trial only | $50-500/mo | Modular pricing |

### African SaaS Pricing Benchmarks

| Platform | Market | Pricing Model | Price Point |
|---|---|---|---|
| **M-KOPA** | Kenya (energy) | Daily micro-payments | KES 50-150/day |
| **Twiga Foods** | Kenya (B2B) | Transaction fee | 5-10% commission |
| **M-Farm** | Kenya (agri) | Subscription | KES 500-2,000/mo |
| **iCow** | Kenya (dairy) | Freemium | Free basic, KES 100/mo premium |
| **DigiFarm** | Kenya (Kenya Power) | Subsidized | Free for small farmers |
| **Apollo Agriculture** | Kenya | Input financing | Revenue from inputs |

### Key Insights for Kenya/East Africa

1. **Daily micro-payments work** — M-KOPA proved Kenyan farmers prefer daily KES 50-100 over monthly KES 2,000+
2. **Free tier is essential** — Small farmers won't pay without trying first
3. **Value must be immediate** — Farmers see ROI within days, not months
4. **M-Pesa integration is mandatory** — Credit card adoption is <5% in rural Kenya
5. **Per-head/per-animal pricing resonates** — Farmers think in units, not abstract features

---

## Recommended Pricing Architecture

### 4-Tier Model

```
┌─────────────────────────────────────────────────────────────────────┐
│  FREE          GROW           SCALE           ENTERPRISE           │
│  KES 0/mo      KES 999/mo     KES 2,999/mo    Custom              │
│  (Freemium)    (Small farm)   (Large farm)     (Management)        │
│                                                                     │
│  Target:        Target:        Target:         Target:             │
│  Individual     Small-scale    Large-scale     Farm management     │
│  subsistence    commercial     commercial      companies/coops     │
│  farmers        farmers        farmers                             │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Tier Details

### 🌱 FREE (KES 0/month)
**Target:** Individual subsistence farmers, hobby farmers, those trying the platform
**Goal:** Get them hooked, show value, create data they can't leave behind

**What they GET:**
- 1 farm
- 1 flock/batch/herd (up to 100 animals)
- Basic production tracking (daily entries)
- Simple expense tracking
- 1 team member (solo use)
- Mobile app access
- Basic reports (this month only)
- Community support (forum)
- **AI Assistant: 5 questions/day** (this is the hook)

**What they DON'T get (upgrade triggers):**
- ❌ No multiple flocks/batches
- ❌ No customer/CRM features
- ❌ No invoicing or LPO generation
- ❌ No feed formula management
- ❌ No historical reports (only current month)
- ❌ No data export (CSV/PDF)
- ❌ No team collaboration
- ❌ No vaccination/medication schedules
- ❌ No AI beyond 5 questions/day
- ❌ No M-Pesa integration
- ❌ "Powered by Wangari" watermark on exported data
- ❌ Data only retained for 30 days (vs 90 days for paid)

**Psychology — Why they'll upgrade:**
1. **Data lock-in** — After 3 months of recording data, they don't want to lose it
2. **AI limit** — 5 questions/day is enough to get addicted, not enough to be useful
3. **Month blindness** — Can't see last month's data = can't compare performance = frustration
4. **Solo limit** — Farm grows, needs help, can't add team = upgrade
5. **No invoicing** — Selling to customers without invoices = unprofessional = upgrade

---

### 🌿 GROW (KES 999/month ≈ $7.70 USD)
**Target:** Small-scale commercial farmers (500-5,000 animals, 1-5 acres crops)
**Goal:** Core revenue driver, 60% of paying users should be here

**Everything in FREE, plus:**
- 1 farm with up to 3 flocks/batches/herds
- Up to 2,000 animals total
- Customer management (up to 50 customers)
- Basic invoicing & LPO generation
- M-Pesa payment recording
- Feed formula management (up to 3 formulas)
- Vaccination/medication schedules
- 30-day history (not just current month)
- Data export (CSV only)
- 3 team members
- Email support (24-hour response)
- **AI Assistant: 50 questions/day**
- Basic P&L reports

**Psychology — Why they'll stay and upgrade to SCALE:**
1. **Customer limit** — 50 customers sounds like a lot until you have 60
2. **Flock limit** — 3 flocks works until you expand to 4
3. **History limit** — 30 days isn't enough for annual planning
4. **Team limit** — 3 members works until you hire a 4th
5. **AI limit** — 50 questions/day is productive but not unlimited

---

### 🌳 SCALE (KES 2,999/month ≈ $23 USD)
**Target:** Large-scale commercial farmers (5,000+ animals, 10+ acres, multiple locations)
**Goal:** High-value customers, 25% of revenue

**Everything in GROW, plus:**
- Unlimited flocks/batches/herds
- Unlimited animals
- Unlimited customers
- Advanced invoicing with PDF generation
- Full credit management & aging reports
- Unlimited feed formulas with cost optimization
- Multi-location support (up to 3 farms)
- Full history (all time)
- Data export (CSV + PDF)
- 10 team members
- Priority email + phone support (4-hour response)
- **AI Assistant: Unlimited questions**
- Advanced analytics & trend charts
- Weekly email summaries
- Stock alerts & reorder points
- Budget vs actual tracking

**Psychology — Why they'll stay or upgrade to ENTERPRISE:**
1. **Multi-farm limit** — 3 farms works until you have 4
2. **Team limit** — 10 members works until the organization grows
3. **Need for API** — Want to integrate with accounting software
4. **Need for white-label** — Coops want their own branding

---

### 🏢 ENTERPRISE (Custom pricing — KES 15,000-50,000+/month)
**Target:** Farm management companies, cooperatives, agribusinesses, government programs
**Goal:** High-value contracts, 15% of revenue but 40% of profit

**Everything in SCALE, plus:**
- Unlimited farms & locations
- Unlimited team members
- White-label branding (your logo, your domain)
- API access for custom integrations
- Custom reports & dashboards
- Dedicated account manager
- On-site training & setup
- SLA guarantee (99.9% uptime)
- Data retention: 7 years
- Custom AI training on your data
- Multi-enterprise support
- Priority phone + WhatsApp support (1-hour response)
- **Volume discounts for annual billing**

**Pricing structure:**
- Base: KES 15,000/month for up to 5 farms
- Additional farms: KES 2,500/month each
- Additional users: KES 500/month each
- Custom development: Quoted per project

---

## The "Freeium Trap" — Strategic Conversion Design

### How the Free Tier Forces Upgrades

```
Week 1-2:   "This is great! I can track my flock."
             → User enters data daily, builds habit

Week 3-4:   "I want to see last month's performance..."
             → Can't. Only current month on free tier.
             → Frustration begins

Month 2:    "My flock grew, I need to add Batch 2..."
             → Can't. Only 1 flock on free tier.
             → Must upgrade to GROW

Month 3:    "I sold 50 birds to a restaurant, need invoice..."
             → Can't. No invoicing on free tier.
             → Must upgrade to GROW

Month 4:    "I hired a worker, need to add them..."
             → Can't. Only 1 user on free tier.
             → Must upgrade to GROW

Month 6:    "I have 4 flocks now..."
             → Can't. GROW only allows 3.
             → Must upgrade to SCALE

Year 1:     "I have 2 farms now..."
             → Can't. SCALE only allows 3 locations.
             → Must upgrade to ENTERPRISE
```

### The AI Hook

The AI assistant is the most powerful conversion tool:

```
FREE:     5 questions/day
          → Farmer asks: "What was my profit last month?"
          → AI: "You've used all 5 free questions today. 
                  Upgrade to GROW for 50 questions/day."
          → Farmer is frustrated — they NEED that answer

GROW:     50 questions/day  
          → Farmer asks: "Which batch had the worst FCR?"
          → AI answers fully
          → Farmer asks 49 more questions
          → At question 50: "Upgrade to SCALE for unlimited"

SCALE:    Unlimited
          → Farmer uses AI daily
          → AI becomes indispensable
          → They'll never go back to spreadsheets
```

### Data Lock-in Strategy

```
FREE users:
  - Data retained for 30 days only
  - After 30 days, old data is archived (not deleted, but inaccessible)
  - Must upgrade to access archived data

GROW users:
  - Data retained for 1 year
  - After 1 year, archived

SCALE users:
  - Data retained forever
  - Full history always accessible

ENTERPRISE:
  - Data retained forever
  - Backup guarantee
  - Data portability guarantee
```

### Feature Gating Summary

| Feature | FREE | GROW (999) | SCALE (2,999) | ENTERPRISE |
|---|---|---|---|---|
| Farms | 1 | 1 | 3 | Unlimited |
| Flocks/batches | 1 | 3 | Unlimited | Unlimited |
| Animals | 100 | 2,000 | Unlimited | Unlimited |
| Customers | 0 | 50 | Unlimited | Unlimited |
| Team members | 1 | 3 | 10 | Unlimited |
| History | Current month | 30 days | All time | All time |
| AI questions/day | 5 | 50 | Unlimited | Unlimited + custom |
| Invoicing | ❌ | Basic | Advanced + PDF | White-label |
| M-Pesa integration | ❌ | Recording only | Full | Full + API |
| Feed formulas | ❌ | 3 | Unlimited | Unlimited |
| Vaccination schedules | ❌ | ✅ | ✅ | ✅ |
| Data export | ❌ | CSV | CSV + PDF | All formats + API |
| Support | Community | Email 24h | Phone 4h | Dedicated 1h |
| Data retention | 30 days | 1 year | Forever | Forever + backup |
| Reports | Basic | P&L | Advanced | Custom |
| Multi-location | ❌ | ❌ | 3 farms | Unlimited |
| White-label | ❌ | ❌ | ❌ | ✅ |
| API access | ❌ | ❌ | ❌ | ✅ |

---

## Payment Models

### Monthly Billing (default)
- GROW: KES 999/month
- SCALE: KES 2,999/month
- ENTERPRISE: Custom

### Annual Billing (20% discount)
- GROW: KES 799/month (KES 9,588/year — save KES 2,400)
- SCALE: KES 2,399/month (KES 28,788/year — save KES 7,200)
- ENTERPRISE: Custom with volume discount

### Daily Micro-payment Option (M-Pesa)
Inspired by M-KOPA's model:
- GROW: KES 33/day via M-Pesa auto-debit
- SCALE: KES 100/day via M-Pesa auto-debit

**Why this works:**
- KES 33/day = price of a cup of chai ☕
- KES 100/day = price of a newspaper + chai
- Farmers think in daily costs, not monthly subscriptions
- M-Pesa STK push makes auto-debit seamless

### Pay-per-Animal Option (for large farms)
For farms that want to pay based on size:
- KES 2/animal/month (minimum KES 999)
- Example: 5,000 animals × KES 2 = KES 10,000/month
- Automatically scales with farm size

---

## Revenue Projections

### Conservative (Year 1-3)

| Metric | Year 1 | Year 2 | Year 3 |
|---|---|---|---|
| Free users | 500 | 2,000 | 5,000 |
| GROW subscribers | 50 | 200 | 500 |
| SCALE subscribers | 10 | 40 | 100 |
| ENTERPRISE contracts | 2 | 5 | 15 |
| **Monthly revenue** | **KES 79,990** | **KES 359,960** | **KES 1,049,850** |
| **Annual revenue** | **KES 959,880** | **KES 4,319,520** | **KES 12,598,200** |
| **USD equivalent** | **$7,380** | **$33,227** | **$96,909** |

### Conversion Funnel

```
1,000 free signups
    ↓ 10% activate (enter data for 7+ days)
100 active free users
    ↓ 15% convert to GROW after 3 months
15 GROW subscribers
    ↓ 20% upgrade to SCALE after 6 months
3 SCALE subscribers
    ↓ 10% upgrade to ENTERPRISE after 1 year
0.3 ENTERPRISE contracts
```

---

## Competitive Pricing Positioning

### vs. International Tools
| Tool | Price | Wangari Advantage |
|---|---|---|
| FarmLogs | $150+/mo | Wangari is 95% cheaper |
| Granular | $200+/mo | Wangari is 97% cheaper |
| Agworld | $35+/mo | Wangari is 50% cheaper + free tier |

### vs. Local Alternatives
| Alternative | Price | Wangari Advantage |
|---|---|---|
| Spreadsheets | Free | Wangari adds AI, mobile, team features |
| M-Farm | KES 500-2,000/mo | Wangari has more modules, free tier |
| iCow | KES 100/mo (dairy only) | Wangari covers all farm types |
| Manual notebooks | Free | Wangari adds reports, alerts, AI |

### Pricing Psychology for Kenya

1. **Anchor high, sell low** — Show ENTERPRISE first (KES 15,000+), then GROW (KES 999) feels cheap
2. **Daily framing** — "Less than KES 33/day" sounds better than "KES 999/month"
3. **Social proof** — "Join 200+ farms already using Wangari"
4. **Risk reversal** — "14-day money-back guarantee" or "Cancel anytime"
5. **Urgency** — "Early bird pricing — lock in KES 999 before it goes to KES 1,499"

---

## Implementation Recommendations

### Phase 1 (Launch)
- Start with FREE + GROW only
- GROW at KES 999/month
- Focus on getting 500 free users
- Collect feedback, iterate

### Phase 2 (3 months)
- Add SCALE tier at KES 2,999/month
- Add annual billing option
- Add daily micro-payment option

### Phase 3 (6 months)
- Add ENTERPRISE tier
- Add pay-per-animal option
- Add white-label for cooperatives

### Phase 4 (12 months)
- Add marketplace commission model
- Add insurance partnerships
- Add financial services integration

---

*This strategy is designed for the Kenyan/East African market. Adjust pricing based on actual user feedback and willingness to pay.*
