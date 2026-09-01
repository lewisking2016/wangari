# WANGARI — AI, WhatsApp Bot & Data Strategy
## Building the Intelligence Layer That Makes Farmers Smarter

**Document Owner:** Lewis (Founder, iMeanTech)
**Last Updated:** September 2026
**Vision:** "A farmer's pocket agronomist that speaks Swahili, knows their farm, and never sleeps."

---

## STRATEGIC OVERVIEW

### Why This Document Matters

Your 12 modules are the BODY of Wangari. The AI and WhatsApp bot are the BRAIN and VOICE. Without intelligence, Wangari is just a digital notebook. With it, Wangari becomes an indispensable advisor that farmers can't live without.

### The Evidence Base

| What Works | Source | Key Data |
|---|---|---|
| AI chatbot for farming advice | Farmer.Chat (Digital Green) | 300,000+ queries answered, 75%+ success rate, 8,800 users in Kenya |
| WhatsApp-based farm data collection | MooMe (Tunisia/Pan-Africa) | 3,000+ users, 10-15% milk yield increase, $300/cow/year revenue boost |
| AI fertilizer recommendations | Virtual Agronomist (iSDA/Gates Foundation) | Sammy Selim: 2.3 → 7.3 tonnes coffee yield (+217%) |
| AI pest diagnosis | PlantVillage Nuru | Voice-based, works offline, identifies fall armyworm with 87-94% accuracy |
| AI-powered farm lending | Apollo Agriculture | 400,000 farmers, 2.5x higher yields than average |
| Voice-based farming advice | NAFOORE (Senegal) | Works via radio, SMS, social media, voice calls in local languages |

### The Wangari AI Vision

```
LAYER 1: DATA COLLECTION (WhatsApp Bot + USSD + App)
  Farmer enters data → Wangari stores it → Database grows

LAYER 2: INTELLIGENCE ENGINE (AI Processing)
  Analyze farm data → Compare to benchmarks → Detect anomalies → Predict outcomes

LAYER 3: ADVICE DELIVERY (WhatsApp + App + SMS)
  Push actionable insights → Answer questions → Alert on problems → Suggest actions

LAYER 4: LEARNING LOOP (Continuous Improvement)
  Track which advice was followed → Measure outcomes → Improve recommendations
```

---

## WHATSAPP BOT — COMPLETE SPECIFICATION

### Architecture

```
┌──────────────────────────────────────────────────────┐
│                  WANGARI WHATSAPP BOT                  │
├──────────────────────────────────────────────────────┤
│                                                        │
│  FARMER'S PHONE          WHATSAPP API       WANGARI   │
│  ──────────────          ────────────       ───────   │
│  Sends message ──────→  Twilio/360dialog ──→ Backend  │
│                          │                    │        │
│                          │  NLP Parser        │        │
│                          │  (regex + LLM)     │        │
│                          │                    │        │
│  Receives reply ←──────  ←── Response Gen ←──┘        │
│                                                        │
│  AUTO-SEND (Scheduled):                               │
│  7:00 AM  → Daily reminder to enter data              │
│  6:00 PM  → Daily summary report                      │
│  Monday   → Weekly profit report                      │
│  As needed → Alerts (vaccination, low stock, credit)  │
│                                                        │
└──────────────────────────────────────────────────────┘
```

### Command Reference (V1)

#### Data Entry Commands

| Farmer Sends | Bot Action | Response Example |
|---|---|---|
| `eggs 40` | Log 40 eggs for active batch | "✅ Eggs: 40 recorded. Today's total: 40 (crate 6.7). Revenue: KES 2,400" |
| `mortality 2` | Log 2 deaths | "⚠️ Mortality: 2 recorded. Batch B4: 498/500 remaining. Rate: 0.4% today" |
| `feed 3 bags` | Deduct 3 bags from inventory, log cost | "📦 Feed: 3 bags used. Remaining: 47 bags. Cost today: KES 12,000" |
| `milk 15` | Log 15 litres milk yield | "✅ Milk: 15L recorded for Cow #23. This week: 98L. Average: 14L/day" |
| `sold 10 crates @ 400` | Log sale, update revenue | "💰 Sale recorded! 10 crates × KES 400 = KES 4,000. Customer: [default]" |
| `sold 10 crates @ 400 John Kamau` | Log sale to specific customer | "💰 Sale to John Kamau: KES 4,000. His outstanding balance: KES 12,400" |
| `buy feed 20 bags @ 500` | Log purchase, update inventory, log expense | "📥 Purchase: 20 bags × KES 500 = KES 10,000. New stock: 67 bags" |
| `expense 2000 transport` | Log miscellaneous expense | "💸 Expense logged: Transport KES 2,000. This month's total: KES 18,000" |

#### Query Commands

| Farmer Sends | Bot Action | Response Example |
|---|---|---|
| `summary` | Today's full summary | "📊 TODAY: Eggs 40, Mortality 2, Feed 3 bags, Revenue KES 2,400, Costs KES 12,000. Net: -KES 9,600 (feed day)" |
| `week` | This week's summary | "📊 THIS WEEK: Eggs 280, Revenue KES 16,800, Costs KES 42,000. Profit: KES -25,200. Note: 2 feed purchases this week" |
| `month` | This month's summary | "📊 NOVEMBER: Eggs 1,200, Revenue KES 72,000, Costs KES 58,000. Profit: KES 14,000 (+18% vs Oct)" |
| `stock` | Current inventory | "📦 STOCK: Feed 47 bags, Vaccines 12 bottles, Layers mash 8 bags. ⚠️ Low stock: Layers mash (reorder in 3 days)" |
| `profit` | Running profit calculation | "💰 PROFIT (this month): Revenue KES 72,000 - Costs KES 58,000 = KES 14,000. Cost/bird: KES 116. Revenue/bird: KES 144" |
| `fcr` | Feed conversion ratio | "📈 FCR (Batch B4): 2.1 (Target: 1.8-2.0). You're slightly above average. Check: feed quality, bird health" |
| `mortality` | Mortality summary | "⚠️ MORTALITY: This month: 12/500 (2.4%). Acceptable: <5%. Status: ✅ Good" |
| `credit` | Outstanding customer debts | "💳 CREDITS OWED: John Kamau KES 12,400 (30 days), Mary Wanjiku KES 5,600 (15 days), Peter Ochieng KES 8,200 (45 days ⚠️)" |

#### AI Advice Commands

| Farmer Sends | Bot Action | Response Example |
|---|---|---|
| `why low eggs?` | Analyze production data | "📊 ANALYSIS: Your egg production dropped 15% this week. Possible causes: 1) Feed quality (check FCR), 2) Disease (check mortality), 3) Weather (heat stress reduces laying). Recommendation: Check feed stock for moisture contamination" |
| `best price eggs?` | Market price query | "📈 EGG PRICES (Nakuru, this week): Wholesale KES 380-420/crate, Retail KES 450-500/crate. Trend: ↑ Rising (December demand spike). Recommendation: Hold stock for 1 week if possible" |
| `when vaccinate?` | Vaccination schedule | "💉 VACCINATION SCHEDULE (Batch B4 Layers): Week 8: Newcastle (due in 2 days!) ✅ Set reminder. Week 12: Fowl Pox. Week 16: Newcastle booster. ⚠️ Don't miss Week 8 — Newcastle kills 80% of unvaccinated flocks" |
| `help` | Show all commands | "📖 COMMANDS: Enter data → eggs 40, mortality 2, feed 3 bags. View → summary, week, stock, profit, fcr, credit. Ask → why low eggs?, best price eggs?, when vaccinate?. Help → this message" |

### NLP Parser Design (V1 — Rule-Based)

```python
# Pseudo-code for WhatsApp bot command parser

PATTERNS = {
    # Data entry
    r"eggs?\s+(\d+)": "log_eggs",
    r"mortality\s+(\d+)": "log_mortality",
    r"feed\s+(\d+)\s+bags?": "log_feed",
    r"milk\s+(\d+)": "log_milk",
    r"sold\s+(\d+)\s+(crates?|kg|bags?)\s*@\s*(\d+)(?:\s+(.+))?": "log_sale",
    r"buy\s+(\w+)\s+(\d+)\s+bags?\s*@\s*(\d+)": "log_purchase",
    r"expense\s+(\d+)\s+(.+)": "log_expense",
    
    # Queries
    r"summary|today": "get_daily_summary",
    r"week|weekly": "get_weekly_summary",
    r"month|monthly": "get_monthly_summary",
    r"stock|inventory": "get_inventory",
    r"profit|earnings": "get_profit",
    r"fcr|conversion": "get_fcr",
    r"mortality": "get_mortality",
    r"credit|debt|owing": "get_credits",
    
    # AI
    r"why\s+(low|drop|less|fewer)\s+(eggs?|production|milk)": "analyze_production",
    r"best\s+price": "get_market_price",
    r"vaccin(e|ation)": "get_vaccination_schedule",
    r"help|commands?": "show_help",
}
```

**Phase 2 Upgrade:** Replace regex with LLM-powered NLP (GPT-4 or open-source Llama) for natural language understanding:
- "My chickens are eating but not laying eggs" → Trigger production analysis
- "I think something is wrong with batch 3" → Trigger health check
- "How much feed do I need for next week?" → Trigger feed prediction

### WhatsApp Bot Cost Model

| Item | Cost | Notes |
|---|---|---|
| Twilio WhatsApp API | $0.005/message (KES 0.70) | Per message sent AND received |
| 360dialog (alternative) | $0.003/message (KES 0.42) | Cheaper, less features |
| Monthly estimate (1,000 users) | KES 42,000-84,000 | 2-4 messages/user/day |
| Revenue from 1,000 users | KES 500,000-1,500,000 | At KES 500-1,500/month/user |
| **Bot cost as % of revenue** | **3-17%** | Sustainable |

### WhatsApp Bot Implementation Timeline

| Week | Milestone | Details |
|---|---|---|
| Week 1 | API setup | Register WhatsApp Business account, connect Twilio/360dialog |
| Week 2 | MVP parser | Rule-based NLP for top 10 commands |
| Week 3 | Database integration | Bot reads/writes to Wangari database |
| Week 4 | Auto-send messages | Daily reminder (7am), daily summary (6pm) |
| Week 5 | AI queries | "Why low eggs?" → production analysis |
| Week 6 | Market prices | Integration with price data sources |
| Week 7 | LLM upgrade | Natural language understanding (optional) |
| Week 8 | Testing & optimization | Reduce parse errors, improve response quality |

---

## AI ENGINE — COMPLETE SPECIFICATION

### AI Feature Roadmap

#### V1: Rule-Based Intelligence (Phase 1-2)
No machine learning. Just smart calculations with predefined thresholds.

| Feature | How It Works | Example |
|---|---|---|
| **Profit Calculator** | Revenue - Costs = Profit (auto-calculated from entries) | "This month: KES 72,000 - KES 58,000 = KES 14,000 profit" |
| **FCR Calculator** | Feed consumed / Weight gain | "FCR: 2.1 (good is 1.8-2.0 for layers)" |
| **Mortality Tracker** | Deaths / Starting count × 100 | "Mortality: 2.4% (acceptable: <5%)" |
| **Cost-Per-Bird** | Total costs / Number of birds | "Cost per bird: KES 116/month" |
| **Revenue-Per-Bird** | Total revenue / Number of birds | "Revenue per bird: KES 144/month" |
| **Feed Depletion Predictor** | Stock / Daily usage = Days remaining | "Feed will run out in 15.7 days. Reorder now." |
| **Low Stock Alerts** | If stock < reorder point | "⚠️ Layers mash below minimum. 3 days until stockout." |
| **Vaccination Scheduler** | Predefined schedule by bird type/age | "Newcastle vaccine due in 2 days!" |
| **Credit Aging** | Days since invoice date | "John Kamau: KES 12,400 overdue by 30 days" |

#### V2: Benchmark Intelligence (Phase 2)
Compare user's data to regional averages from open datasets.

| Feature | Data Source | Example |
|---|---|---|
| **Yield Benchmarking** | GROW-Africa dataset (535K observations) | "Your maize yield: 1.2 tonnes/acre. Top farmers in Machakos: 2.1 tonnes/acre. Gap: 43%" |
| **FCR Benchmarking** | FAO poultry data + Kenya-specific data | "Your FCR: 2.8. Average for layers in Kiambu: 2.1. You're using 33% more feed than needed." |
| **Cost Benchmarking** | FAO enterprise budgets for Kenya | "Your feed cost/bird: KES 480. Recommended: KES 350. Savings possible: KES 130/bird" |
| **Production Benchmarking** | MooMe data + industry standards | "Your milk yield: 12L/cow/day. Average for Friesian in Central Kenya: 18L/cow. Potential: +50%" |

#### V3: Predictive Intelligence (Phase 3)
Machine learning models trained on accumulated user data.

| Feature | How It Works | Example |
|---|---|---|
| **Egg Production Forecast** | Time-series model on historical egg data | "Based on your flock's age and history, expect 38 eggs/day next week (±3)" |
| **Feed Consumption Prediction** | Regression model on feed usage patterns | "At current rate, you'll need 15 bags by Dec 15. Order by Dec 10." |
| **Mortality Risk Alert** | Anomaly detection on mortality patterns | "⚠️ Unusual: 3 deaths in 2 days vs your average 0.5/day. Check for disease." |
| **Profit Forecast** | Projection based on current trends | "At this rate, November profit: KES 14,000. To hit KES 20,000, reduce feed waste by 15%" |
| **Market Price Prediction** | Seasonal patterns + external data | "Egg prices typically rise 20% in December. Consider holding stock." |

---

## OPEN DATA INTEGRATION

### Dataset 1: GROW-Africa (Yield Benchmarking)

**Source:** Nature, 2025 — "An Africa-wide agricultural production database"
**URL:** https://www.nature.com/articles/s41597-025-05257-5

**What it contains:**
- 535,844 georeferenced observations of crop yields across Africa
- 25 key crops
- County/district level data
- Yield per hectare, input costs, practice data

**How Wangari uses it:**
```
1. User enters crop type + county during setup
2. Wangari queries GROW-Africa for that crop + county
3. Displays benchmark:
   "Your maize yield in Nakuru: 1.2 tonnes/acre
    Average for Nakuru: 1.6 tonnes/acre
    Top 25% in Nakuru: 2.1 tonnes/acre
    You're in the bottom 40% — here's how to improve"
4. Links to relevant AI advice
```

### Dataset 2: Lacuna Fund — Kenya Agriculture

**Source:** Lacuna Fund
**URL:** https://lacunafund.org/datasets/agriculture/

**What it contains:**
- ML dataset of smallholder farmer fields
- Georeferenced crop images + yields
- Collected across 8 Kenyan counties
- Perfect for training pest/disease detection models

**How Wangari uses it:**
```
1. Build pest/disease identification model
2. Farmer photographs sick plant/animal
3. Wangari AI compares to Lacuna Fund training data
4. Returns diagnosis + treatment recommendation
5. "This looks like Newcastle disease (87% confidence).
    Recommended action: Isolate affected birds, contact vet.
    Prevention: Vaccinate remaining flock within 24 hours."
```

### Dataset 3: FAO Poultry Enterprise Budgets

**Source:** FAO — "A case study of poultry producers in Egypt, Kenya and Uganda"
**URL:** https://openknowledge.fao.org/

**What it contains:**
- Detailed cost/revenue breakdowns for poultry farms
- Kenya-specific data on feed costs, labor, veterinary, housing
- Per-bird cost calculations
- Break-even analysis

**How Wangari uses it:**
```
1. User enters their costs during setup
2. Wangari compares to FAO Kenya benchmarks
3. Displays gaps:
   "YOUR COSTS vs. FAO KENYA BENCHMARK:
    Feed:        KES 480/bird (Benchmark: KES 350) ⚠️ +37%
    Labor:       KES 50/bird  (Benchmark: KES 45)  ✅ OK
    Veterinary:  KES 25/bird  (Benchmark: KES 30)  ✅ Good
    Housing:     KES 30/bird  (Benchmark: KES 25)  ⚠️ +20%
    TOTAL:       KES 585/bird (Benchmark: KES 450) ⚠️ +30%
    
    Your biggest opportunity: Feed costs.
    Top performers in Kiambu achieve KES 320/bird through better
    feed formulas. Want to see how?"
```

### Dataset 4: RHoMIS (Household Survey Data)

**Source:** Re-usable Household and farm Information Survey
**URL:** https://rhomis.org/

**What it contains:**
- Standardized survey data from smallholder farms across sub-Saharan Africa
- Farm size, crop types, livestock numbers, income, expenditures
- Enables cross-country and cross-region comparisons

**How Wangari uses it:**
```
1. Benchmark farm income: "Average smallholder in your county
   earns KES 8,000/month from farming. You earn KES 14,000.
   You're in the top 30%!"
2. Farm diversification advice: "Farms in your region that
   combine poultry + crops earn 40% more than poultry-only farms."
```

### Data Pipeline Architecture

```
┌──────────────────────────────────────────────────────┐
│                 WANGARI DATA PIPELINE                 │
├──────────────────────────────────────────────────────┤
│                                                        │
│  EXTERNAL DATA SOURCES                                 │
│  ├── GROW-Africa (crop yields)                        │
│  ├── Lacuna Fund (Kenya farm images)                  │
│  ├── FAO Enterprise Budgets (cost benchmarks)         │
│  ├── RHoMIS (household survey data)                   │
│  ├── OpenWeatherMap (weather data)                    │
│  ├── FAO Food Price Monitor (market prices)           │
│  └── Kenya Met Department (rainfall/temperature)      │
│                                                        │
│  ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓                │
│                                                        │
│  DATA PROCESSING LAYER                                 │
│  ├── ETL Pipeline (daily batch processing)            │
│  ├── Benchmark Database (pre-calculated averages)     │
│  ├── Anomaly Detection Engine                         │
│  └── Prediction Models                                │
│                                                        │
│  ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓                │
│                                                        │
│  WANGARI USER DATABASE                                 │
│  ├── Farmer profiles + farm data                      │
│  ├── Daily entries (eggs, mortality, feed, sales)     │
│  ├── Financial records (income, expenses, P&L)        │
│  ├── Inventory (stock levels, movements)              │
│  └── AI insights (benchmarks, alerts, predictions)    │
│                                                        │
│  ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓                │
│                                                        │
│  DELIVERY LAYER                                        │
│  ├── WhatsApp Bot (direct messages)                   │
│  ├── App Dashboard (visual reports)                   │
│  ├── Push Notifications (alerts)                      │
│  ├── Email Reports (weekly summary)                   │
│  └── SMS (for basic phones / USSD users)              │
│                                                        │
└──────────────────────────────────────────────────────┘
```

---

## VOICE AI — THE KILLER FEATURE

### Why Voice Matters

- 30% of Kenyan farmers have limited digital literacy
- Typing is slow and error-prone on small phone keyboards
- Farmers prefer speaking to typing (cultural norm)
- Voice works across all literacy levels

### Voice AI Implementation

**Phase 1: Voice-to-Text (Simple)**
```
Farmer holds phone, speaks: "Leo nimevua yai mirongo nne"
(Fourteen: "Today I collected forty eggs")
↓
Speech-to-text engine (Google Speech API / Whisper)
↓
NLP Parser (same as WhatsApp bot)
↓
Response: "✅ Yai 40 zimeandikwa. Leo: 40 yai. Mapato: KES 2,400"
```

**Phase 2: Voice AI Chatbot**
```
Farmer asks: "Kuku zangu zimepungua mayai. Kwa nini?"
("My chickens are laying fewer eggs. Why?")
↓
Speech-to-text → LLM processes question + farm data
↓
AI analyzes: egg production trend, mortality rate, feed consumption, weather
↓
Voice response: "Kulingana na data yako, uzalishaji wa yai umeshuka 15% wiki hii.
Sababu inaweza kuwa: 1) Ubora wa chakula, 2) Ukungu, 3) Magonjwa.
Pendekezo: Angalia ubora wa chakula na ongeza vitamini."
```

**Technical Requirements:**
- [ ] Google Speech-to-Text API (supports Swahili)
- [ ] OpenAI Whisper (open-source alternative, runs locally)
- [ ] Text-to-Speech for responses (Google TTS / ElevenLabs)
- [ ] Integration with WhatsApp voice messages
- [ ] Integration with USSD (record voice, transcribe, respond via text)

---

## USSD BOT — FOR BASIC PHONES

### For farmers without smartphones or WhatsApp

**USSD Menu Tree:**

```
*123# (Wangari Short Code)

MAIN MENU:
1. Enter Production
2. View Summary
3. View Stock
4. Get Advice
5. My Account

--- SUBMENU 1: Enter Production ---
1. Eggs → Enter number: ___
2. Mortality → Enter number: ___
3. Feed used → Enter bags: ___
4. Milk → Enter litres: ___
5. Sale → Crates: ___ @ KES: ___

--- SUBMENU 2: View Summary ---
1. Today → [shows today's numbers]
2. This Week → [shows week summary]
3. This Month → [shows month summary]

--- SUBMENU 3: View Stock ---
1. Feed → [shows remaining bags]
2. Vaccines → [shows remaining bottles]
3. All Stock → [shows everything]

--- SUBMENU 4: Get Advice ---
1. Vaccination Reminder → [shows next due]
2. Low Stock Alert → [shows items below minimum]
3. Profit Check → [shows running profit/loss]

--- SUBMENU 5: My Account ---
1. Change PIN
2. View Subscription
3. Contact Support
```

**Technical Requirements:**
- [ ] USSD gateway (Africa's Talking API)
- [ ] Short code registration with CA (Communications Authority of Kenya)
- [ ] Session management (USSD sessions timeout in 180 seconds)
- [ ] Response formatting (max 160 chars per USSD screen)
- [ ] PIN-based authentication

---

## AI TRAINING DATA STRATEGY

### Data You Already Have
- Your own farm data from pilot users
- FAO enterprise budgets (Kenya-specific)
- Industry benchmarks (FCR, mortality, cost-per-bird)

### Data You Need to Acquire

| Dataset | Source | Cost | Priority |
|---|---|---|---|
| GROW-Africa | Nature/open access | Free | 🔴 HIGH |
| Lacuna Fund Kenya | lacunafund.org | Free | 🔴 HIGH |
| FAO Poultry Budgets | FAO open knowledge | Free | 🔴 HIGH |
| RHoMIS | rhomis.org | Free | 🟡 MEDIUM |
| Kenya Met historical weather | Kenya Meteorological Department | Low cost | 🟡 MEDIUM |
| FAO Food Price Data | FAO FPMA | Free | 🟡 MEDIUM |
| User-generated data (your farmers) | Wangari platform | Free (you generate it) | 🔴 CRITICAL |

### Building Your Data Moat

**The long-term play:** Every farmer who uses Wangari generates data that makes the AI smarter for EVERY farmer.

```
Farmer A enters egg data → Wangari learns patterns for layers in Kiambu
Farmer B enters egg data → Wangari compares A vs B, finds insights
Farmer C enters egg data → Wangari detects a regional trend (disease?)
Farmers D-Z enter data → Wangari has the BEST poultry dataset in Kenya

When a new farmer signs up → Wangari already knows what "normal" looks like
  for their region, crop type, and farm size.
  → "Your FCR is 2.8. For layers in Nakuru, the average is 2.1.
     Here's what top performers do differently."

NO COMPETITOR CAN REPLICATE THIS without 1,000+ farmers
generating data for 6+ months.
```

**This is your ultimate moat.** Every day you delay getting farmers on the platform is a day your competitor could be building their own dataset. Speed to 1,000 users is critical not just for revenue — it's critical for DATA.

---

## AI SAFETY & TRUST

### Critical Rules for AI Recommendations

1. **Never recommend dangerous actions without vet verification**
   - "This looks like Newcastle disease. Contact a vet immediately."
   - NOT: "Give the birds [antibiotic name]"

2. **Always show confidence level**
   - "87% confidence: This is fall armyworm"
   - NOT: "This IS fall armyworm"

3. **Always cite the source**
   - "According to FAO Kenya data, average cost per bird is KES 350"
   - NOT: "Average cost is KES 350" (where did this come from?)

4. **Always allow the farmer to override**
   - "Based on your data, we recommend... [Farmer can accept or dismiss]"

5. **Localize everything**
   - Swahili + English
   - Currency in KES
   - Units in kg, litres, bags (not bushels, acres)
   - Weather for THEIR county, not national averages

---

## IMPLEMENTATION TIMELINE

| Week | AI Feature | Bot Feature | Data Feature |
|---|---|---|---|
| 1-2 | Profit calculator, FCR calculator | WhatsApp API setup, basic parser | — |
| 3-4 | Cost-per-bird, mortality tracker | Full command set (10 commands) | Download GROW-Africa |
| 5-6 | Feed depletion predictor, vaccination scheduler | Auto-send daily summary | Integrate benchmarks |
| 7-8 | Regional benchmarking | AI queries ("why low eggs?") | Download FAO data |
| 9-10 | Market price integration | Voice message support | Build benchmark DB |
| 11-12 | Anomaly detection, predictions | Natural language processing | Train models on pilot data |
| 13+ | Full AI chatbot (Swahili) | USSD bot | Continuous learning loop |

---

*This document defines Wangari's intelligence layer. The AI is what transforms Wangari from a recording tool into an indispensable farm advisor.*
