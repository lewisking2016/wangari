/**
 * Wangari Learn Library — the content layer for the in-app document library.
 *
 * Every learning resource is a first-class document on OUR platform (no
 * external redirects): farmers read inside the app, in a book-like reader.
 * Docs flagged `memberOnly` render as teasers on the public /learn page and
 * in full inside the dashboard — the "free taste, full library inside" play.
 *
 * `live` blocks are fetched fresh from live APIs at read time (weather from
 * Open-Meteo, news from the farm-advisory RSS pipeline) so documents update
 * with time — a guide read today shows this season's data.
 */

export interface DocSection {
  heading: string;
  /** Simple paragraph text, or `list:` prefixed lines separated by \n */
  body: string;
}

export interface LearnDoc {
  slug: string;
  title: string;
  emoji: string;
  category: "growing-guide" | "farm-type" | "rights" | "official";
  /** short summary for cards */
  summary: string;
  memberOnly: boolean;
  readMinutes: number;
  updatedAt: string;
  source: string;
  /** section slugs of live blocks to inject (weather, news) */
  live?: ("weather" | "news")[];
  sections: DocSection[];
}

export const LEARN_DOCS: LearnDoc[] = [
  // ───────────────────────── GROWING GUIDES ─────────────────────────
  {
    slug: "maize",
    title: "Maize — Kenya's Staple Cash Crop",
    emoji: "🌽",
    category: "growing-guide",
    summary: "Varieties, spacing, top-dressing timing, aflatoxin-safe storage and market windows.",
    memberOnly: false,
    readMinutes: 6,
    updatedAt: "2026-09-10",
    source: "KALRO & Infonet-Biovision, adapted for Kenya",
    live: ["weather"],
    sections: [
      { heading: "Why maize pays", body: "Maize is Kenya's staple — demand never disappears, and the National Cereals and Produce Board (NCPB) buys at set prices in good seasons. A well-managed acre under hybrid seed can yield 25–35 bags of 90kg; poorly managed, fewer than 10. The difference is almost never the seed — it is timing." },
      { heading: "Choose the right variety", body: "list:Highlands (Kitale, Nandi, Uasin Gishu): H614, H628 — long season, high yield.\nMid-altitude (Central, Rift lower zones): H513, H520 — 120–135 days.\nDrylands (Eastern, Machakos, Kitui): Katumani Composite, DH02 — drought-escape, 90–110 days.\nCoastal belt: PH1, PH4 — tolerant of coastal humidity.\nAlways buy certified seed from a registered agrovet — fake seed is the #1 silent yield killer." },
      { heading: "Land preparation & planting", body: "list:Plough at the first rains, harrow to a fine tilth.\nSpacing: 75cm between rows × 25cm between holes, one seed per hole (2 for Katumani, thin later).\nDepth: 2–3cm in moist soil; slightly deeper in sandy soils.\nPlant within the first 2 weeks of the rains — every week of delay costs measurable yield.\nBasal fertilizer (DAP/NPK 10-26-10): one teaspoon per hole, mixed with soil so seed never touches fertilizer directly." },
      { heading: "Top-dressing — the money moment", body: "Top-dress with CAN when maize is knee-high (about 4–6 weeks after emergence), and again at tasseling for long-season varieties. Right before or right after a rain, never onto dry ground. One 50kg bag of CAN per acre is the common rate. Miss this window and no later input recovers the yield." },
      { heading: "Weeding & pests", body: "list:Weed twice: 3 weeks after emergence and 6 weeks — competition in these windows is what cuts yields.\nFall armyworm: scout leaf whorls weekly for 'window pane' damage and wet frass. Act at the first sign — hand-pick larvae in small plots, or use recommended pesticides at dusk when larvae feed.\nStalk borer: crop residue destruction after harvest breaks its cycle." },
      { heading: "Harvest & safe storage (aflatoxin)", body: "Harvest when husks are dry and black layer forms at the kernel base. Dry on cobs to 13% moisture before shelling — storing wet maize causes aflatoxin, which can make an entire shipment (or family) sick. Treat with approved storage insecticide, use hermetic bags (PICS/ZeroFly) where possible, and keep cobs off the ground." },
      { heading: "Selling smart", body: "Prices crash at peak harvest and climb in the dry months. If storage is safe, holding 2–3 months after harvest often earns 30–60% more. Log your harvest in Wangari and the cost-per-bag calculator shows your true break-even before you sell." },
    ],
  },
  {
    slug: "beans",
    title: "Beans — the Fast Rotation Cash Crop",
    emoji: "🫘",
    category: "growing-guide",
    summary: "Varieties per season, intercropping with maize, rust management and harvest timing.",
    memberOnly: false,
    readMinutes: 5,
    updatedAt: "2026-09-10",
    source: "KALRO & Infonet-Biovision, adapted for Kenya",
    sections: [
      { heading: "Why beans", body: "Beans mature in 65–90 days, fix nitrogen for the next crop, and sell steadily all year. For most smallholders they are the shortest cash cycle on the farm." },
      { heading: "Varieties that win", body: "list:Long rains: Rosecoco, Wairimu (Mwitemania) — higher yield, 75–90 days.\nShort rains: KK8, KK22, KAT B1 — 60–70 days, escape the rains' end.\nDry areas: KAT X56, KAT X69 — drought tolerant.\nCertified seed pays: beans from the open market carry rust and halo blight into your shamba." },
      { heading: "Planting", body: "45cm rows × 15cm, two seeds per hole, 2–5cm deep. In maize intercropping, plant beans in the same hole as maize or in alternate rows — the maize becomes a living trellis for climbing types." },
      { heading: "Disease watch", body: "list:Bean rust: orange-brown pustules under leaves — widest plant spacing and avoiding evening irrigation reduce it; spray a recommended fungicide at first sign.\nHalo blight: water-soaked spots — use certified seed, rotate out of beans for 2 seasons.\nAphids: vector viruses — scout young plants; neem-based sprays work early." },
      { heading: "Harvest before the shatter", body: "Harvest when 80% of pods are dry and yellow. Waiting for 100% loses seed to shattering — losses start fast at this stage. Dry on tarpaulins, never on bare soil, and thresh gently to avoid bruising (bruised beans discount at the market)." },
    ],
  },
  {
    slug: "tomatoes",
    title: "Tomatoes — High Value, High Attention",
    emoji: "🍅",
    category: "growing-guide",
    summary: "Staking, watering discipline, blight prevention and grading for the best prices.",
    memberOnly: false,
    readMinutes: 6,
    updatedAt: "2026-09-10",
    source: "KALRO & Infonet-Biovision, adapted for Kenya",
    live: ["weather"],
    sections: [
      { heading: "The opportunity and the trap", body: "Tomatoes can return more per acre than almost any common crop — and lose everything to blight in one humid week. The winners are farmers who prevent, not react." },
      { heading: "Varieties", body: "list:Open field determinate: Anna F1, Rio Grande, Cal-J — 75–90 days.\nGreenhouse indeterminate: Tylka F1, Prostar — longer harvest window, staked training.\nCoastal/humid areas: choose late-blight tolerant lines (e.g. Kilele F1)." },
      { heading: "Planting & staking", body: "Nursery for 4–6 weeks, then transplant at 60cm × 60cm. Stake at transplanting — staking later breaks roots. Prune suckers weekly and remove the lowest leaves to keep airflow under the canopy." },
      { heading: "Watering discipline", body: "Consistent water 2–3× per week. Irregular watering cracks fruit and invites blossom-end rot. Drip with mulch is the single biggest quality upgrade you can make. Water the soil, never the leaves — wet leaves are how blight travels." },
      { heading: "Blight — prevent, don't react", body: "High humidity for 3+ days means late blight risk. Spray preventively before humid spells (not after symptoms). Check the live weather panel on this page — a humid week ahead is your spray signal. Remove and burn infected plants; never compost them." },
      { heading: "Grading and selling", body: "Grade into Fancy / Standard / Reject at harvest. Fancy-grade alone often earns 40–60% more than the mixed crate buyers expect to bargain down. Harvest in the early morning, keep crates in shade, and sell the same day if you can — tomatoes wait for no one." },
    ],
  },
  {
    slug: "kales",
    title: "Kales (Sukuma Wiki) — the Weekly Income Crop",
    emoji: "🥬",
    category: "growing-guide",
    summary: "Continuous picking, diamondback moth scouting and the nursery-to-bed system.",
    memberOnly: false,
    readMinutes: 4,
    updatedAt: "2026-09-10",
    source: "KALRO & Infonet-Biovision, adapted for Kenya",
    sections: [
      { heading: "Why every farm grows them", body: "Sukuma wiki is Kenya's weekly-cash vegetable: first picking 60–75 days after transplant, then every week for 3+ months from the same plants. It smooths income between big-crop harvests." },
      { heading: "The nursery system", body: "Sow in a shaded nursery bed, water daily, and transplant at 4–6 weeks with 4–5 true leaves. Stagger a new nursery every month and you never run out of picking beds." },
      { heading: "Spacing & feeding", body: "45cm × 30cm in the bed. Feed with well-rotted manure at planting and top-dress with CAN every 3 weeks — kales are hungry, and pale leaves mean the market sees it too. Mulch heavily to hold moisture between watering." },
      { heading: "Pick low, pick few", body: "Pick only the 2–3 lowest leaves per plant per week, letting the crown keep producing. Ripping a plant bare gives you one good bunch and then nothing." },
      { heading: "The #1 pest", body: "Diamondback moth — scout the leaf undersides weekly for small green larvae and holes. Spraying works best early; rotate active ingredients so resistance doesn't build. A row of onions or coriander between beds helps confuse the moths." },
    ],
  },
  {
    slug: "potatoes",
    title: "Irish Potatoes — Highlands Gold",
    emoji: "🥔",
    category: "growing-guide",
    summary: "Certified seed, hilling, late blight prevention and storage that avoids greening.",
    memberOnly: false,
    readMinutes: 5,
    updatedAt: "2026-09-10",
    source: "KALRO & Infonet-Biovision, adapted for Kenya",
    sections: [
      { heading: "Know your zones", body: "Potatoes thrive at altitude — Nyandarua, Elgeyo Marakwet, Meru highlands, Nandi. An acre of Shangi can produce 80–120 bags of 50kg under good management; a blight-hit crop can produce nothing." },
      { heading: "Certified seed or nothing", body: "Farm-saved seed degenerates with viruses every season. Certified seed (from KALRO-licensed multipliers) costs more and returns multiples. Plant whole egg-sized tubers — cut seed carries rot into wet soil." },
      { heading: "Planting & hilling", body: "75cm rows × 30cm, 10–15cm deep. Hill soil around plants twice: at 15cm height and again 3 weeks later — exposed tubers turn green and toxic, and hilled plants set more tubers." },
      { heading: "Late blight — the killer", body: "Cool, humid highland weather is exactly what late blight wants. Preventive fungicide before humid spells is the only reliable strategy; once lesions show, sprays only slow it. Never plant near tomatoes — they share the disease." },
      { heading: "Harvest & storage", body: "Harvest when vines yellow and die back, 90–120 days. Cure in a dark, ventilated shed for 10–14 days so skins harden. Store in darkness with airflow — light turns potatoes green (solanine) and warm stores sprout them. Never wash before storage." },
    ],
  },
  {
    slug: "green-grams",
    title: "Green Grams (Ndengu) — the Short-Rains Cash Crop",
    emoji: "🌱",
    category: "growing-guide",
    summary: "Drought-tolerant, low-input and priced at premium — the perfect second-season crop.",
    memberOnly: false,
    readMinutes: 4,
    updatedAt: "2026-09-10",
    source: "KALRO & Infonet-Biovision, adapted for Kenya",
    sections: [
      { heading: "Built for the short rains", body: "Ndengu mature in 75–90 days on 300–400mm of rain — exactly what the October–December season offers. Low input, nitrogen-fixing, and selling at KES 120–200/kg, they are the smart short-rains bet in semi-arid areas." },
      { heading: "Varieties", body: "list:KAT N1, KAT N2 — early maturing, drought tolerant (the standard for Eastern/Kenya).\nK26/8 — traditional, aromatic, fetches a premium in local markets.\nN26 — larger seed, popular for export-grade produce." },
      { heading: "Planting & care", body: "45cm × 15cm, one to two seeds per hole. Minimal inputs: a light DAP dose at planting and one weeding at 3 weeks usually suffices. Over-fertilizing gives you leaves, not pods." },
      { heading: "Harvest & the premium trick", body: "Harvest when 80% of pods turn black — pick in two rounds rather than uprooting. The premium trick: clean, unbroken, single-variety lots graded free of stones sell to traders at the top of the range. Log your production cost in Wangari before selling — ndengu margins reward farmers who know their numbers." },
    ],
  },

  // ───────────────────────── FARM-TYPE HUBS ─────────────────────────
  {
    slug: "poultry",
    title: "Poultry Farming — Kienyeji & Layers",
    emoji: "🐔",
    category: "farm-type",
    summary: "Vaccination calendar, kienyeji economics, feed cost control and egg grading.",
    memberOnly: false,
    readMinutes: 7,
    updatedAt: "2026-09-12",
    source: "KALRO Non-Ruminant Institute & leading Kenyan practitioners",
    sections: [
      { heading: "Kienyeji vs layers — pick a lane", body: "Kienyeji (improved indigenous) sells at KES 500–900 per bird and eggs at KES 15–25 — slower growth, premium prices, lower input. Layers produce 280+ eggs per hen per year at industrial feed cost. Mixing the two models without a plan is the most common way beginners lose money." },
      { heading: "The vaccination calendar (copy this)", body: "list:Day 1: Marek's (hatchery).\nDay 7: Newcastle + IB (eye drop or water).\nDay 14: Gumboro (drinking water).\nDay 21: Gumboro booster.\nDay 28: Newcastle booster (LaSota, water).\nWeek 8: Fowl pox (wing web).\nWeek 16: Newcastle + IB + EDS booster before lay.\nNever vaccinate sick birds, and finish a vaccine course within 2 hours of mixing in water." },
      { heading: "Feed is 65–70% of your cost", body: "Chick mash (0–8 weeks), growers mash (9–18 weeks), layers mash (from lay). Mixing your own with a tested formulation can cut feed cost 20–30% — but only with correctly weighed ingredients; guessing formulation is worse than buying commercial. Log every feed purchase in Wangari and the cost-per-egg number tells you if your mix is actually saving money." },
      { heading: "Housing that pays", body: "list:3 birds per m² for layers, 4 for kienyeji.\nEast–west orientation, half-walls with wire mesh on the breeze side.\n1 nest box per 4 hens, 8cm of clean dry litter, turned weekly.\nWaterers on wire stands so litter stays dry — wet litter breeds coccidiosis." },
      { heading: "Egg economics", body: "Graded, clean eggs earn more: collect 3× daily, wipe (never wash) before storage, grade by weight if selling to shops. Track lay rate weekly — a drop below 70% in healthy-looking birds means feed, water or stress needs investigating. Wangari's production log graphs the lay rate automatically." },
    ],
  },
  {
    slug: "dairy",
    title: "Dairy Farming — Feeding for Yield",
    emoji: "🐄",
    category: "farm-type",
    summary: "Dry-matter feeding, calving management, mastitis prevention and milk recording.",
    memberOnly: false,
    readMinutes: 7,
    updatedAt: "2026-09-12",
    source: "KALRO Dairy Research Institute practices, Kenya",
    sections: [
      { heading: "The yield formula", body: "A cow gives milk from what you feed her, not from her breed alone. Rule of thumb: a 300kg Friesian cross producing 20L/day needs roughly 3% of bodyweight in dry matter, plus 1kg of dairy meal per 1.5–2L above 7L. Underfeeding dairy meal is why 'my cow is a good breed but gives 8L'." },
      { heading: "Feeding program", body: "list:Baseline: 50–70kg of quality forage per day (Napier, Boma Rhodes) — chop it, don't graze zero-grazed cows on long grass.\nConcentrate: scale with yield as above, split morning/evening.\nMineral block or lick available at all times — silent phosphorus deficiency wrecks fertility.\nClean water 60–80L/day; a milking cow drinking dirty water is a mastitis case waiting to happen." },
      { heading: "The calving calendar", body: "Gestation is ~283 days. Dry the cow off 60 days before calving — every day of missed dry period costs the next lactation. Log the insemination date in Wangari and the calving countdown and dry-off reminders generate themselves. Prepare the maternity pen 2 weeks early: clean bedding, iodine for the navel, colostrum within 6 hours of birth." },
      { heading: "Mastitis prevention", body: "list:Wash and dry the udder before milking — one cloth per cow, never shared.\nStrip into a cup to check for clots before full milking.\nTeat-dip after every milking.\nMilk withers first (young, clean cows), oldest cows last, mastitis cows separately and last.\nCull chronic cases — they cost more than they give." },
      { heading: "Records are the profit map", body: "Milk weights per cow per day reveal the truth: which cows pay for their feed and which coast. Log daily in Wangari, and the per-cow production trends identify your culls and your champions." },
    ],
  },
  {
    slug: "avocado-macadamia",
    title: "Avocado & Macadamia — Perennial Cash Trees",
    emoji: "🥑",
    category: "farm-type",
    summary: "Hass spacing, grafting, export-grade harvest rules and contract caution.",
    memberOnly: false,
    readMinutes: 6,
    updatedAt: "2026-09-12",
    source: "KALRO horticulture guides & export-industry practice",
    sections: [
      { heading: "Hass avocado — the export standard", body: "Hass bears from year 3–4, peaks from year 7, and exports pay multiples of local prices. Spacing 6m × 6m (about 100 trees per acre for Hass; Fuerte tolerates 7m × 7m). Buy grafted certified seedlings — never seedlings of unknown origin." },
      { heading: "Young tree care (years 1–3)", body: "list:Water 2–3× per week for the first two dry seasons — most young-tree deaths are drought deaths.\nMulch generously; avocado roots are shallow and hate exposure.\nTrain a single strong leader, remove branches below 1m.\nTop-dress with CAN from year 1 (light), increasing as canopy grows.\nRemove any fruit set in years 1–2 — let the tree build structure first." },
      { heading: "Harvest the export way", body: "Hass is ready when the fruit's stem-end skin turns yellowish and fruit reach 220g+ — maturity is dry-matter based, not color. Cut the stem with secateurs leaving a button; a fruit without its stem is rejected at the packhouse. Pick dry, deliver within 24 hours." },
      { heading: "Macadamia", body: "Spacing 8m × 8m, first nuts year 4–5, full bearing year 8–10. Kirinyaga and Embu lead production. Grafted varieties (Kirinyaga, Meru selections) out-yield ungrafted massively. Nuts are ready when husks split; dry to 10% moisture in-shell before selling." },
      { heading: "Contracts — read before you sign", body: "Exporters often offer contract buying. A fair contract states the price basis (per kg, minimum grade), the weighing method on-site, payment window, and who pays transport. Anything less leaves you exposed — read the 'Know your contract rights' document in this library before signing." },
    ],
  },
  {
    slug: "legumes",
    title: "Legumes & Pulses — Soil Builders That Pay",
    emoji: "🫘",
    category: "farm-type",
    summary: "Cowpeas, pigeon peas, soybeans: dryland options that fix nitrogen and sell.",
    memberOnly: false,
    readMinutes: 5,
    updatedAt: "2026-09-12",
    source: "KALRO & dryland farming practice, Kenya",
    sections: [
      { heading: "Why legumes belong in every rotation", body: "Legumes fix 30–100kg of nitrogen per hectare — free fertilizer for the maize that follows. They also open dryland markets: cowpeas and pigeon peas thrive where maize fails." },
      { heading: "Cowpeas", body: "list:Dual purpose: leaves (kunde) for the vegetable market, grain for dry sales.\n60cm × 20cm, thrives on 300mm of rain.\nFirst leaf picking 40 days, grain 70–90 days.\nAphids are the main enemy — scout seedlings weekly." },
      { heading: "Pigeon peas", body: "list:Deep-rooted — survives droughts that kill everything else.\nPlant with the long rains, harvest after 6–9 months (perennial varieties give a second season).\nIntercrops beautifully with maize and sorghum.\nMargins: low input, and market demand in Eastern Kenya is constant." },
      { heading: "Soybeans", body: "list:Needs the longer growing season — plant with the long rains.\nInoculate seed with rhizobium (cheap at agrovets) — uninoculated soybean on new land fixes little nitrogen.\nValue-add: processing into soya pieces/beans oil multiplies the price — a real agribusiness angle.\nRotates perfectly ahead of maize." },
    ],
  },

  // ───────────────────────── RIGHTS / CIVIC ─────────────────────────
  {
    slug: "contract-rights",
    title: "Know Your Contract Rights",
    emoji: "📜",
    category: "rights",
    summary: "What a fair produce contract must include — and what you can refuse.",
    memberOnly: false,
    readMinutes: 5,
    updatedAt: "2026-09-12",
    source: "Plain-language summary of Kenyan contract practice",
    sections: [
      { heading: "You already have rights", body: "Most smallholders sign produce contracts (avocado, macadamia, French beans, honey) without reading them, assuming the buyer's paper is standard and final. It is not. A contract is negotiable before you sign, and the law of contract protects you after." },
      { heading: "What a fair contract must state", body: "list:Price basis: per kg, per grade, and whether the price is fixed or pegged to a published reference.\nWeighing: weighed on-site, in your presence, with your right to witness the scale reading.\nGrading: objective, stated criteria — not 'quality acceptable to buyer'.\nPayment: a specific date or window (e.g. 7 days after delivery), not 'upon sale'.\nTransport: who provides and who pays.\nDeductions: every allowable deduction listed — anything else is an unlawful charge." },
      { heading: "What you can refuse", body: "You cannot legally be forced to sell at 'gate price' if your contract states otherwise. Vague oral promises about bonuses have no weight — get every promise written. If a buyer changes terms after delivery, that is a breach; keep your delivery notes (the Wangari documents vault works) and demand written confirmation of any change." },
      { heading: "Before you sign — the 5-point check", body: "list:1. Is the price or price formula written?\n2. Is the payment date written?\n3. Do you keep a signed copy?\n4. Is there a clause letting the buyer change terms unilaterally? Ask for it removed.\n5. Dispute resolution: county agriculture office or cooperative mediation is a legitimate, cheap first step." },
    ],
  },
  {
    slug: "subsidies",
    title: "Subsidies & Programs Checklist",
    emoji: "🏛️",
    category: "rights",
    summary: "E-voucher, KCEP-CRAL, NCPB and county programs — what you're entitled to and how to ask.",
    memberOnly: false,
    readMinutes: 5,
    updatedAt: "2026-09-12",
    source: "Public program information, Kenya",
    sections: [
      { heading: "These programs are free — they only reach farmers who ask", body: "Billions of shillings of agricultural support go unclaimed every season because farmers don't know the programs exist or where to walk in. This checklist is your starting script." },
      { heading: "National programs", body: "list:KCEP-CRAL (Kenya Cereal Enhancement Programme): free/subsidized certified seed and fertilizer for smallholders in target counties — ask at your county agriculture office before planting season.\nE-voucher input subsidy: register with your ward agricultural officer; redeemed at participating agrovets at reduced cost.\nNCPB: buys maize at set prices in declared seasons — registered farmers with proper records are prioritized.\nAFLATOxin / quality programs: free testing at some county depots before sale." },
      { heading: "County programs", body: "list:Free training days and field days — every county agriculture office runs them, word-of-mouth is the only advertising.\nSubsidized breeding services (AI), chick distribution and seedling programs in many counties.\nBursaries and agri-youth funds for young farmers.\nWalk in with your ID and farm records — counties prioritize farmers who can show production. Your Wangari records are exactly that proof." },
      { heading: "Cooperatives", body: "A registered co-op unlocks shared transport, better prices, credit and exporter access. Registration needs 10+ members through the County Co-operative Officer. See the 'Cooperatives' document in this library." },
    ],
  },
  {
    slug: "land-agreements",
    title: "Land & Written Agreements",
    emoji: "📝",
    category: "rights",
    summary: "Title vs lease, why a written shamba agreement protects both sides, succession basics.",
    memberOnly: false,
    readMinutes: 5,
    updatedAt: "2026-09-12",
    source: "Plain-language summary of Kenyan land practice",
    sections: [
      { heading: "Written or it didn't happen", body: "In Kenyan land practice, a verbal agreement is legal but nearly impossible to prove. Whether you lease out or lease in, a one-page written agreement signed by both sides (with a witness) prevents the disputes that destroy neighbor relationships and investment." },
      { heading: "What a shamba lease must say", body: "list:Parties: full names and ID numbers.\nThe land: parcel number (title deed or allotment number) and boundaries.\nRent and payment schedule.\nDuration: seasons or years, with renewal terms.\nWhat may be planted: perennial trees (avocado, macadamia) outlive leases — state who owns trees planted during the lease.\nNotice period for ending early.\nBoth signatures + one witness each." },
      { heading: "Title vs lease — know what you hold", body: "list:Title deed: absolute ownership — verify it at the lands registry before any purchase; fake titles exist.\nLeasehold: ownership for a term (state the expiry!).\nAllotment letters and customary holdings: weaker — get agreements in writing and keep every payment receipt.\nSuccession: land without a written will goes through succession courts — expensive and slow for your family. A simple written will costs little and saves years." },
    ],
  },
  {
    slug: "cooperatives",
    title: "Cooperatives — Strength in Numbers",
    emoji: "🤝",
    category: "rights",
    summary: "What a co-op gives you, how to register one, and how to avoid the bad ones.",
    memberOnly: false,
    readMinutes: 4,
    updatedAt: "2026-09-12",
    source: "Plain-language summary of Kenyan co-operative practice",
    sections: [
      { heading: "Why co-ops matter", body: "A registered co-op gives smallholders shared transport (costs split), collective bargaining (exporters and processors pay better for volume), credit access, and a voice in county decisions. The dairy and coffee success stories in Kenya are almost all co-op stories." },
      { heading: "How to register", body: "list:10+ members with a common interest (one crop, one area).\nDraft simple by-laws (the County Co-operative Officer has templates).\nRegister through the County Co-operative Office — costs are modest.\nFirst annual meeting elects a committee; minutes matter for compliance." },
      { heading: "Choosing an existing co-op — the warning signs", body: "list:Delayed payments with no written explanation.\nNo published accounts or annual meeting minutes.\nCommittee members who have been in office far beyond the by-law term.\nDeductions you can't see explained.\nA healthy co-op publishes its prices, pays on a stated schedule, and welcomes member questions." },
    ],
  },
];

export const CATEGORIES = [
  { id: "growing-guide", label: "Growing Guides", emoji: "🌱" },
  { id: "farm-type", label: "Farming Types", emoji: "🐔" },
  { id: "rights", label: "Your Rights", emoji: "📜" },
] as const;

export function getDoc(slug: string): LearnDoc | undefined {
  return LEARN_DOCS.find((d) => d.slug === slug);
}

export function docsByCategory(cat: string): LearnDoc[] {
  return LEARN_DOCS.filter((d) => d.category === cat);
}
