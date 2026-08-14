# Wangari — AI & Open-Source Integration Research

> Deep research: what farmers want from AI, and the best open-source tools to install and
> integrate to make Wangari world-class. Sources: MorganMyers/Ag Access 2026 farmer AI study,
> NC State AI-in-Agriculture conference 2026, Bushel 2026 State of the Farm report, Precision
> Farming Dealer farmer discussion group, GitHub star data (live), Exa web research, Kenya
> agritech vendors (Synnefa FarmShield, NuaSense). Date: Aug 2026.
> Nothing has been coded — this is the research for your approval.

---

## PART 1 — What farmers actually want from AI

### 1.1 Hard numbers (MorganMyers AI & Agriculture Report 2026, US farmers)

**What farmers use AI for today (in order):**
1. Personal research and drafting — 49%
2. Crop planning and planting decisions — 40%
3. Livestock nutrition / health insights — 35%
4. Business management — 32%

**What they get from it:** 38% say AI saves them time; 38% say it increases confidence in
decisions. 69% expect to **increase** AI use in the next 1–2 years.

**Their top concerns (trust barriers):**
- Accuracy of recommendations — **72%** worried
- Data privacy / ownership — **57%**
- Bias or brand influence coloring results — **51%**

**The killer quote (a farmer/tech founder, HTS Ag):**
> "My biggest frustration with AI is its ability to confidently lie to you. Today's AI models
> are hard-coded to be helpful and enthusiastic — sometimes to a fault."

**What this means for Wangari:** farmers will only trust AI answers that are **grounded in
their own records** (your Ask Wangari AI, which answers from the DB) and that **show their
work**. Never present AI guesses as facts. Always let the human make the final call.

### 1.2 NC State AI-in-Agriculture conference 2026 (460 growers, innovators, investors)

Four consensus themes:

1. **"The juice has to be worth the squeeze"** — AI must show clear ROI to be adopted.
   The tech that has actually taken hold on farms is the **back-office AI**:
   > "Farmers want AI tools that help with inventory, invoices and pay schedules. They are
   > torn in so many directions. They don't want the AI to control their money. They want
   > their AI to make their lives simpler." — investor Frank Klemens

2. **The farmer stays in control** — ultimate decision-making power must remain with the
   producer. AI is a helper, not a replacement ("AI is not going to be accountable. People
   are accountable.").

3. **Chatbots are just waystations — farmers want proactive systems** (Syngenta Cropwise AI):
   > "Farmers need systems that automatically alert them to issues, rather than just
   > answering questions — flagging that 'based on last year's yield data and spring soil
   > moisture, three fields need a different hybrid.'"

4. **Data stays with the farmer** — no silos, no lock-in. Federated learning / local-first
   is the architectural direction they trust.

### 1.3 Bushel 2026 State of the Farm report

- ~14% of producers already use AI for administrative tasks and strategic planning; adoption
  is "experimental" but growing fast.
- Younger farmers (18–30) find integrations easiest; older farmers struggle with data silos —
  **the #1 frustration is re-entering data across multiple platforms** (55% on large farms).
- Farmer preference: **text-based communication, mobile-first**. In-person-only services are
  becoming a bottleneck.

### 1.4 What farmers asked for directly (from prior Reddit + field research)

The single most-upvoted farmer wish (r/RegenerativeAg):
> "A RAG-enabled bot/agent that can look up species interactions, remember and use
> information about your site (location, climate, weather, soil, pests, water, species) and
> my specific crops, and help me set up, anticipate and prioritize regular actions, while
> prompting me for information and helping me journal what I do."

Plus: photo disease detection, voice logging ("60 eggs today, 2 deaths"), calendar that
auto-populates from plantings, WhatsApp/USSD/SMS channels (not just one app), and
Excel import/export that works perfectly.

### 1.5 AI feature priority for Wangari (ranked by all evidence above)

| Priority | Feature | Evidence |
|---|---|---|
| 1 | **Ask Wangari AI** — answers from farm's own records (done, local-first) | RAG wish + "grounded answers" trust need |
| 2 | **Proactive alerts** — system flags issues BEFORE you ask | Syngenta: "chatbots are waystations" |
| 3 | **Back-office automation** — invoices, pay schedules, inventory help | Investor Klemens, MorganMyers 32% |
| 4 | **Voice input (Swahili + English)** | ICTWorks reality #9 (low literacy) |
| 5 | **Photo disease/pest detection** | M-Shamba proof in Kenya (97% claimed) |
| 6 | **AI report summaries** — "what changed this week?" in plain language | "reports too complicated" complaint |
| 7 | **Smart reminders via right channel** (WhatsApp/SMS/USSD per farmer pref) | 36–49% want reminders; multi-modal reality |

---

## PART 2 — Open-source tools to integrate (the world-class stack)

### 2.1 AI MESSAGING & CUSTOMER COMMUNICATION

| Tool | Stars | What it is | Why for Wangari |
|---|---|---|---|
| **n8n** | 200k+ | Fair-code workflow automation with native AI (visual builder, 400+ integrations, MySQL/SQL nodes, webhooks) | THE glue. Automate: low-stock → WhatsApp/email alert; order → SMS; reminder → channel. Community edition is free and self-hostable. |
| **Typebot** | ~60k | Open-source visual chatbot builder (self-host, drag-drop flows, embed anywhere) | Build WhatsApp/web chat flows for customer orders, FAQs, lead capture. ManyChat alternative, free self-hosted. |
| **Chatwoot** | ~22k | Open-source customer support platform (live chat, email, WhatsApp/Telegram/SMS inboxes) | One inbox for all customer messages; ties directly to your CRM module. |
| **Evolution API** | 9.3k | Open-source WhatsApp integration API (self-host, QR connect) | Lets n8n/Typebot send+receive WhatsApp from a normal number — no Meta approval needed to start. |
| **WPPConnect** | 3.4k | Older WhatsApp connector (JS community) | Alternative if Evolution API doesn't fit. |
| **ChatbotX** | 605 | Open-source ManyChat alternative, omnichannel marketing (WhatsApp, TikTok, IG) | Marketing broadcasts + campaigns. |
| **Africa's Talking** (not OSS, but the Kenya rail) | — | SMS, USSD, voice, airtime APIs | THE Kenyan channel: bulk SMS + USSD menus reach feature phones. Direct API, PHP SDK. |

**Recommended integration order:** n8n (core automation) → Evolution API + Typebot
(WhatsApp bots) → Chatwoot (unified inbox) → Africa's Talking (SMS/USSD fallback for
farmers without smartphones).

### 2.2 EMAIL

| Tool | Stars | What it is | Why |
|---|---|---|---|
| **Mailpit** / **MailHog** | ~6k | Dev/test email catcher | Test email workflows locally before shipping. |
| **BillionMail** | 15.4k | Self-hosted mail server + newsletter + marketing, all-in-one | Full email in one box on your cPanel server. |
| **SendPortal** | 2.2k | Self-hosted email marketing (newsletters, lists, campaigns) | For marketing emails / invoices / statements to customers. |
| **Mailcoach** (spatie) | 409 | Self-hosted email list manager, modern UI | Lighter alternative to SendPortal. |
| **Postfix/Dovecot** (or cPanel built-in mail) | — | Standard MTA | You already get mail on cPanel; use it as the SMTP relay for all of the above. |

**Note for cPanel:** you don't need a separate mail server — cPanel has mail built in.
Use it as SMTP for transactional email (invoices, alerts) and add SendPortal/BillionMail
only if you want marketing newsletters with lists/campaigns.

### 2.3 CALENDAR MANAGEMENT

| Tool | Stars | What it is | Why |
|---|---|---|---|
| **Radicale** | ~3.5k | Lightweight Python CalDAV/CardDAV server (single file, SQLite) | Simplest self-hosted calendar backend; phone/PC calendar apps sync to it. |
| **Baïkal** | ~3k | PHP CalDAV/CardDAV server (PHP — matches your stack!) | Best fit: it's PHP, runs on cPanel, no Docker needed. |
| **Nextcloud Calendar** | 1.2k (app) | Full suite calendar with UI | If you go Nextcloud for files/docs, calendar comes free. |
| **Cal.com** | ~35k | Open-source Calendly alternative (scheduling) | Let customers book demo/training slots with you; webhooks into Wangari. |

**Recommended:** Baïkal (PHP, cPanel-friendly) as the CalDAV backbone + your existing
calendar.php admin view stays as the farm-wide view. Cal.com for customer-facing booking.

### 2.4 DOCUMENT / KNOWLEDGE MANAGEMENT ("Notion-like")

| Tool | Stars | What it is | Why |
|---|---|---|---|
| **AppFlowy** | 75k | AI collaborative workspace, Notion alternative, offline-first (Rust/Flutter) | Closest Notion clone; databases, boards, calendar views; has AI built in. |
| **Outline** | ~28k | Team wiki/knowledge base (used by Stripe, Coinbase) | Clean docs, real-time collab, permissions, Slack. Needs Postgres+Redis+S3. |
| **AFFiNE** | ~40k | Notion + Miro hybrid (docs + whiteboard, local-first) | Beautiful modern alternative with whiteboards. |
| **Docmost** | 21k | Collaborative wiki/documentation | Lighter, simpler deployment than Outline. |
| **APITable** | 15.4k | Airtable/Notion databases, API-first | Spreadsheet-style records that expose APIs — great for farm data tables. |

**Recommended:** **Outline** or **Docmost** for a farm documentation wiki (SOPs, training
manuals, input guides) if you want team docs. **AppFlowy** if you want personal/offline
notes with AI. For pure "farm records as tables," **APITable** is the API-friendly pick.
Note: Outline/AppFlowy need Docker + Postgres — heavier than cPanel shared hosting; Docmost
and APITable are lighter.

### 2.5 IOT — SENSORS, CONNECTORS, PHYSICAL ATTENDANCE, SOLAR

| Tool | Stars | What it is | Why |
|---|---|---|---|
| **ThingsBoard** | 22k | Full IoT platform (device mgmt, data collection, dashboards, rules) | The standard open-source IoT backend. MQTT/HTTP device API, alerts, telemetry. |
| **ThingsBoard Gateway** | 2.2k | Bridges Modbus, OPC-UA, BLE devices into ThingsBoard | Connect existing farm hardware. |
| **Home Assistant** | ~70k | Local-first home/device automation hub (huge device support) | Easiest way to ingest ESP32/sensor devices locally, then push data to Wangari. |
| **Node-RED** | ~20k | Visual flow editor for wiring devices, APIs, DBs | Quick glue between sensors → DB → alerts; pairs with n8n or replaces it for IoT. |
| **ESPHome** | ~6k | Flash ESP32/8266 with YAML — sensors done in minutes | Cheap hardware (≈$5–15 per node) for soil moisture, temp/humidity, egg counting, door sensors. |
| **Tasmota** | ~20k | Open firmware for cheap smart plugs/switches | Power monitoring on lights, heaters, incubators. |
| **ChirpStack / LoRaWAN** | ~6k | Long-range low-power radio network | Farm-wide sensor coverage without WiFi — ideal for Kenyan fields. |
| **ZKTeco SDK + zkteco-php** | — | Physical fingerprint/face attendance machines (industry standard in Kenya) | Real attendance hardware; zkteco-php lets PHP talk to the device and push punches to your Labour module. |

**Physical attendance:** Two paths — (a) ZKTeco machines with `zkteco-php` integration
(what Kenyan agribusinesses already use, punch → Wangari labour module), or (b) a
webcam facial-recognition flow (open-source, e.g. facenox-style) if you want zero hardware.

**Solar systems:** 
- **SolarAssistant** (~self-hosted) / **solar** (Solectrus, 161★) for inverter dashboards
  (Growatt, Deye, etc.) via Modbus → MQTT → dashboard.
- Simplest world-class path: **Home Assistant + ESPHome** reading your inverter's Modbus,
  then a Node-RED/n8n flow pushes production data into Wangari's expenses/reports.

**Kenya market reality (what farmers can actually buy):**
- **Synnefa FarmShield** — Kenyan-built IoT farm monitor: soil moisture, NPK, air temp/
  humidity, light, CO2 sensors + solar + battery + SMS/email/app alerts. Pricing:
  rent-to-own from KSH 11,100–14,200/month, or own from KSH 65,000–275,000. They have a
  FarmCloud dashboard API-able.
- **NuaSense** — Kenyan IoT soil/water/weather sensors (LoRaWAN + weather stations).
- **Arinifu** — Kenyan smart chicken brooders.
- These vendors mean: **don't build your own hardware** — build the software layer that
  ingests their data (via their APIs or MQTT/Modbus) so Wangari becomes the brain behind
  the sensors. That's the world-class play: hardware exists; nobody has connected it to a
  farm ERP with costing, CRM, and AI.

### 2.6 WEATHER (free, no key)

| Tool | What it is | Why |
|---|---|---|
| **Open-Meteo** | Free open-source weather API, no key required, 7-day + hourly forecasts, historical data | Direct integration into your Weather module — real forecasts replace manual alerts. |
| **Tomorrow.io** | Freemium API, agriculture layer | If you later need more precision. |

### 2.7 THE REFERENCE ARCHITECTURE (how it all fits together)

```
                    ┌──────────────────────────────┐
                    │   Wangari (PHP, cPanel)       │
                    │  system of record + API       │
                    └──────────┬───────────────────┘
                               │ webhooks / SQL / API
        ┌──────────────────────┼─────────────────────────┐
        │                      │                         │
┌───────▼───────┐      ┌───────▼───────┐         ┌───────▼────────┐
│  n8n          │      │  Typebot      │         │  Chatwoot      │
│  automation   │      │  chatbots     │         │  unified inbox │
│  (alerts,     │      │  (WhatsApp    │         │  (live chat,   │
│  invoices,    │      │  via Evolution│         │  email, SMS)   │
│  workflows)   │      │  API)         │         └────────────────┘
└───────┬───────┘      └───────────────┘
        │
┌───────▼────────┐   ┌──────────────┐   ┌───────────────────┐
│ Africa's       │   │ Baïkal/      │   │ ThingsBoard /     │
│ Talking        │   │ Nextcloud    │   │ Home Assistant +  │
│ SMS/USSD/voice │   │ calendar     │   │ ESPHome + Node-RED│
└────────────────┘   └──────────────┘   │  (sensors, ZKTeco │
                                        │   attendance,     │
                                        │   solar inverters)│
                                        └───────────────────┘
```

**The world-class pitch:** Wangari stays the system of record (PHP, cPanel, your data).
n8n is the automation brain connecting it to WhatsApp/SMS/email. ThingsBoard/Home
Assistant ingest sensors, ZKTeco attendance, and solar — and feed Wangari. Farmers get
alerts, reminders, and answers on the channel they already use. Nobody in East Africa
ships this combination today (research confirmed: Kenyan apps solve one slice each; no one
combines farm ERP + costing + CRM + AI + IoT).

---

## PART 3 — Recommended phased integration plan

**Phase A — Pure software, no hardware (1–2 weeks):**
1. **n8n** self-hosted + connect to Wangari MySQL (read-only user)
2. **Evolution API** + **Typebot** — WhatsApp order-taking + FAQ bot
3. **Open-Meteo** weather integration into the Weather module (real forecasts)
4. **Baïkal** calendar (PHP, cPanel) + sync admin calendar
5. **Mailpit** for email testing; wire transactional email via cPanel SMTP

**Phase B — Documentation & CRM depth (2–4 weeks):**
6. **Docmost** or **APITable** for farm knowledge base / SOPs
7. **Chatwoot** unified inbox tied to the CRM module
8. n8n workflows: low-stock → WhatsApp; order → email invoice; follow-up → reminder

**Phase C — Physical world (hardware, 1–3 months):**
9. **ZKTeco attendance** integration (zkteco-php) → Labour module
10. **Home Assistant + ESPHome** starter kit (temp/humidity in brooder houses) → alerts
11. **Solar inverter monitoring** via Modbus → MQTT → dashboard (if user has solar)
12. **Synnefa/NuaSense** API ingestion for soil/weather (partnership or white-label)

**Phase D — AI deepening (ongoing):**
13. Proactive AI alerts ("flock X is underperforming vs last week")
14. Voice input (Swahili/English) into Ask Wangari
15. Photo disease detection (upload photo → analysis)
16. AI report summaries + AI-generated invoices

*Every phase keeps data in your DB, exports free, and works on cheap phones.*

---

## Bottom line

1. **Farmers want AI that's grounded in THEIR data, proactive, back-office first, and
   never "confidently wrong"** — your local-first Ask Wangari is exactly the right shape;
   the next step is proactive alerts + voice + photo.
2. **The open-source stack is ready and mostly cPanel-friendly:** n8n (automation),
   Evolution API + Typebot + Chatwoot (messaging), cPanel SMTP + SendPortal (email),
   Baïkal (calendar), Docmost/APITable (Notion-style docs), ThingsBoard/Home Assistant +
   ESPHome (sensors), ZKTeco (attendance), Open-Meteo (weather).
3. **Don't build hardware** — Kenya vendors (Synnefa, NuaSense, Arinifu) already sell
   sensors; build the integration so Wangari is the brain, not the box.
4. **The moat:** no one in East Africa combines farm ERP + costing + CRM + AI + IoT + the
   channels farmers actually use. That combination is now architecturally mapped — ready
   for your go-ahead on any phase.

---

## PART 4 — THE FREE-FIRST STACK (zero/low cost, deeply connected)

> Follow-up research, Aug 2026: which tools are **free** (not just open-source), and exactly
> how each one plugs into Wangari. "Free" here = genuinely usable without paying, including
> free SaaS tiers and free self-hosted editions.

### 4.1 The headline discovery — WhatsApp service messages are now FREE

Meta changed its pricing model in **July 2025**:
- **Service conversations (customer-initiated) are FREE** within a 24-hour response window.
- You only pay for **business-initiated** marketing messages (~$0.025–0.14 each) — which you
  can avoid entirely by only replying inside the 24h window.
- This means: a WhatsApp ordering/FAQ bot for Wangari can cost **$0 in message fees**.

Path to $0: **Evolution API** (free, open-source, self-hosted, connects a normal WhatsApp
number via QR) OR **WhatsApp Business API via a BSP**. Evolution API needs zero Meta
approval and zero per-message cost, and n8n + Typebot both integrate with it natively.
(This was researched earlier — now confirmed as the free path.)

### 4.2 Free-tier tool matrix + how it connects to Wangari

| Need | Free tool | Free how much | Connection into Wangari |
|---|---|---|---|
| **Automation glue** | **n8n Community** | Free forever, self-hosted (or free cloud 2.5k executions/mo) | MySQL node reads/writes your DB; webhook node hits your PHP API; schedule triggers |
| **WhatsApp bot** | **Evolution API** (free OSS) + **Typebot** (free self-hosted) | 100% free | Typebot webhook → your API; bot answers from your orders/stock tables |
| **Support inbox** | **Chatwoot Community** | Free, self-hosted (unlimited agents) | Inbox webhooks → your CRM; agent sees order history from your API |
| **SMS/USSD** | **Africa's Talking** | Free sandbox + ~KES 0.30–0.60/message real | PHP SDK directly in your code — SMS alerts, USSD menu for feature phones |
| **Email sending** | **Brevo free** (300/day) or **Resend free** (100/day), or cPanel SMTP | 0 – 300 emails/day free | PHP `mail()`→SMTP or their API; invoices, alerts, newsletters |
| **Calendar** | **Google Calendar API** (free) or **Baïkal** (free PHP) | Free with usage limits | Google Calendar free tier; sync via API; or Baïkal CalDAV on cPanel |
| **Notion-style docs** | **Notion Free** (personal) or **Docmost**/**APITable** (free OSS) | Free up to 10 guests / unlimited | Embed or link; APITable has an API that can sync with your DB |
| **Weather** | **Open-Meteo** | Free for non-commercial; very low cost commercial | Direct HTTP fetch in PHP — real forecasts replace manual alerts |
| **AI answers** | **Ask Wangari local engine** (built) | Already free | Queries your own DB; optional LLM key later |
| **Sensors** | **Home Assistant** (free OSS) + **ESPHome** (free) | Free software; hardware $5–15/node | MQTT → HA → webhook → your API; or ThingsBoard Community |
| **Attendance** | **ZKTeco machines + zkteco-php** (free lib) | Hardware ~KES 8–20k one-time | PHP library pulls punches → Labour module |
| **Solar** | **ESPHome/Home Assistant Modbus** | Free software | Inverter Modbus → MQTT → your API → reports |
| **Live chat on site** | **Chatwoot widget** (free) | Free | Embed widget on shop pages; conversations logged to CRM |
| **Forms/surveys** | **Tally/Google Forms free** | Free | Webhook → your API (e.g. order intake, demo requests) |

### 4.3 The "deeply connected" integration pattern (each tool → Wangari)

Every tool above connects through ONE of four simple mechanisms, so it's cheap to build
and consistent to maintain:

1. **Direct MySQL (read)** — n8n connects to your DB with a read-only user. Triggers on
   new rows: new order → WhatsApp/email alert; low stock → SMS to farm manager; due
   follow-up → reminder.
2. **Webhook into your PHP API** — tools POST to `/Backend/api/integrations.php`
   (to be created): Typebot answers, Chatwoot conversations, Evolution API messages,
   HA sensor readings, ZKTeco punches. One endpoint, one auth token, one log table.
3. **Webhook out of Wangari** — your PHP code fires `http_post(webhook)` on events
   (order placed, stock low, harvest recorded) so n8n/Typebot/HA can react.
4. **Iframe/embed** — Chatwoot widget, calendar embed, docs embed, directly in pages.

This means ONE integration layer (a small PHP file + one DB table for outbound webhooks)
unlocks every free tool. No per-tool custom code buried in pages.

### 4.4 What costs money (be honest) vs what's free

**Truly free:** n8n Community, Evolution API, Typebot, Chatwoot Community, Baïkal, Docmost,
APITable, Open-Meteo (non-commercial), Google Calendar free tier, Brevo/Resend free tiers,
Home Assistant, ESPHome, ThingsBoard Community, zkteco-php, Tally forms, the Ask Wangari
local engine.

**Small real costs (choose later):**
- WhatsApp business-initiated marketing messages (~$0.025+/msg) — avoid by only replying
  inside the 24h service window.
- SMS on Africa's Talking (~KES 0.30–0.60/msg) — still the cheapest reach to feature phones.
- Hardware: ESP32 sensor nodes ($5–15 each), ZKTeco attendance machine (KES 8–20k once),
  optional Synnefa FarmShield (KES 11k/mo rent-to-own) if you want professional soil/NPK.
- A commercial Open-Meteo licence if you monetize (their pricing is modest).
- cPanel hosting (you already pay for this).

### 4.5 Recommended free-first Phase A (order of build)

1. **Webhook integration layer** in Wangari (one endpoint + outbound webhook table) — the
   foundation that makes ALL tools "well connected."
2. **n8n Community** self-hosted + read-only DB user + first 3 workflows:
   new order → WhatsApp (Evolution API) + email (Brevo); low stock → SMS (Africa's Talking);
   due follow-up → in-app + WhatsApp reminder.
3. **Open-Meteo** weather fetch → Weather module real forecasts.
4. **Chatwoot** widget on shop pages → conversations logged to CRM.
5. **Google Calendar** sync or Baïkal for the admin calendar.
6. Later: Evolution API + Typebot WhatsApp bot, ZKTeco attendance, ESPHome sensors,
   APITable docs — all via the same webhook layer.

*Total software cost for Phase A: KES 0. Hardware stays optional.*

---

## Bottom line (free-first addendum)

1. **The whole messaging/email/automation/weather layer is free** — WhatsApp service
   messages (July 2025 pricing), n8n Community, Evolution API, Typebot, Chatwoot, Brevo/
   Resend free tiers, Open-Meteo, Google Calendar.
2. **One integration layer (webhook in + webhook out + DB table) makes every tool "well
   connected"** — build it once, plug in anything.
3. **Only real costs are hardware and optional SMS volumes** — choose them later, on
   evidence, not upfront.
