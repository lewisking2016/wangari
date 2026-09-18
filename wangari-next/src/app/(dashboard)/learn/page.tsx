"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  GraduationCap, Sprout, Droplets, Bug, Sun, Wind,
  ExternalLink, Play, BookOpen, Leaf, Coins, Lock, Video,
} from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

/**
 * Learn (dashboard) — the FULL members-only library.
 *
 * The public /learn page is the free taste; this is the complete version:
 * farming-type video hubs, complete growing guides, county resource
 * checklists, deeper civic guides. Framing reinforces the membership value.
 */

interface Guide {
  crop: string;
  emoji: string;
  season: string;
  spacing: string;
  water: string;
  companions: string;
  maturity: string;
  tip: string;
}

// Distilled from OpenFarm-style growing guides + KALRO recommendations for Kenya
const GROWING_GUIDES: Guide[] = [
  {
    crop: "Maize (Hybrid)", emoji: "🌽", season: "Long rains (Mar–May)",
    spacing: "75cm rows × 25cm, 1 seed per hole", water: "600–800mm total; critical at knee-high & flowering",
    companions: "Beans (intercrop), pumpkins between rows", maturity: "120–150 days",
    tip: "Top-dress with CAN when knee-high. Dry to 13% moisture before storing to stop aflatoxin.",
  },
  {
    crop: "Beans", emoji: "🫘", season: "Long & short rains",
    spacing: "45cm rows × 15cm, 2 seeds per hole", water: "350–450mm; avoid waterlogging",
    companions: "Maize (intercrop), potatoes", maturity: "65–90 days",
    tip: "90-day varieties win in short rains. Harvest before pods shatter — losses start fast.",
  },
  {
    crop: "Tomatoes", emoji: "🍅", season: "Year-round (best Jun–Sep under irrigation)",
    spacing: "60cm × 60cm; stake early", water: "Consistent 2–3x/week; irregular watering cracks fruit",
    companions: "Basil, onions, marigold (pests)", maturity: "75–90 days",
    tip: "High humidity 3+ days = blight risk. Spray preventively and prune lower leaves for airflow.",
  },
  {
    crop: "Kales (Sukuma Wiki)", emoji: "🥬", season: "Year-round",
    spacing: "45cm × 30cm; nursery 6 weeks first", water: "2–3x/week, mulch heavily",
    companions: "Onions, coriander; avoid near strawberries", maturity: "First pick 60–75 days, then weekly 3+ months",
    tip: "Pick lower leaves only, 2–3 per plant. Diamondback moth is the #1 pest — scout undersides weekly.",
  },
  {
    crop: "Irish Potatoes", emoji: "🥔", season: "Long rains; highland areas",
    spacing: "75cm rows × 30cm; certified seed only", water: "500–600mm; critical at tuber formation",
    companions: "Beans, maize edges; avoid tomato (blight)", maturity: "90–120 days",
    tip: "Late blight is the killer — preventive spray before humid spells, never after symptoms show.",
  },
  {
    crop: "Green Grams (Ndengu)", emoji: "🌱", season: "Short rains (Oct–Dec)",
    spacing: "45cm × 15cm", water: "300–400mm; drought-tolerant once established",
    companions: "Maize, sorghum", maturity: "75–90 days",
    tip: "Perfect short-rains cash crop — low input, fixes nitrogen, and sells at KES 120–200/kg.",
  },
];

// Farming-type video hubs — curated free YouTube, embedded as topic pages
const FARM_TYPE_HUBS = [
  {
    type: "Poultry (Kienyeji & Layers)", emoji: "🐔", videos: [
      { title: "Improved Kienyeji Chicken Farming in Kenya", url: "https://www.youtube.com/results?search_query=improved+kienyeji+chicken+farming+kenya" },
      { title: "How to Build the Best Chicken House", url: "https://www.youtube.com/results?search_query=chicken+house+design+kenya+kienyeji" },
      { title: "Poultry Vaccination Schedule Explained", url: "https://www.youtube.com/results?search_query=poultry+vaccination+schedule+kenya" },
      { title: "Feed Mixing — Cut Costs, Raise Profits", url: "https://www.youtube.com/results?search_query=poultry+feed+formulation+kenya" },
    ],
    bonus: "Log your flock in Wangari and the vaccination reminders build themselves.",
  },
  {
    type: "Dairy Farming", emoji: "🐄", videos: [
      { title: "Dairy Success Stories & 250-Cow Setups", url: "https://www.youtube.com/results?search_query=dairy+farming+kenya+success" },
      { title: "Feeding for 20L+ per Day", url: "https://www.youtube.com/results?search_query=dairy+cow+feeding+program+kenya" },
      { title: "Calving — What to Prepare", url: "https://www.youtube.com/results?search_query=cow+calving+management+kenya" },
      { title: "Mastitis Prevention That Works", url: "https://www.youtube.com/results?search_query=mastitis+prevention+dairy+kenya" },
    ],
    bonus: "Breeding records + Wangari = automatic calving countdowns.",
  },
  {
    type: "Avocado & Macadamia", emoji: "🥑", videos: [
      { title: "Hass Avocado Farming Start to Finish", url: "https://www.youtube.com/results?search_query=hass+avocado+farming+kenya" },
      { title: "Macadamia — the Long-Term Cash Crop", url: "https://www.youtube.com/results?search_query=macadamia+farming+kenya" },
      { title: "Export Contracts Explained", url: "https://www.youtube.com/results?search_query=avocado+export+contract+kenya" },
      { title: "Grafting & Seedling Selection", url: "https://www.youtube.com/results?search_query=avocado+grafting+seedlings+kenya" },
    ],
    bonus: "Track perennial crops with Wangari's maturity years + harvest season fields.",
  },
  {
    type: "Legumes & Pulses", emoji: "🫘", videos: [
      { title: "Beans — Varieties That Win", url: "https://www.youtube.com/results?search_query=beans+farming+kenya+varieties" },
      { title: "Green Grams (Ndengu) Cash Crop Guide", url: "https://www.youtube.com/results?search_query=green+grams+farming+kenya" },
      { title: "Cowpeas & Pigeon Peas for Dry Areas", url: "https://www.youtube.com/results?search_query=cowpeas+farming+kenya+dryland" },
      { title: "Soybean Processing for Extra Profit", url: "https://www.youtube.com/results?search_query=soybean+farming+kenya+processing" },
    ],
    bonus: "Planting date in Wangari → top-dressing and harvest reminders fire automatically.",
  },
];

const RESOURCE_SECTIONS = [
  {
    title: "Official & Research",
    icon: BookOpen,
    color: "text-wangari-green-700",
    items: [
      { name: "KALRO e-extension (KEEP)", desc: "Location-specific advisories from Kenya's research organization", url: "https://keep.kalro.org/" },
      { name: "Kenya Meteorological Department", desc: "Official seasonal forecasts & county weather warnings", url: "https://meteo.go.ke/" },
      { name: "Kilimo News", desc: "Daily Kenyan agriculture news, prices, policy", url: "https://kilimonews.co.ke/" },
      { name: "FarmBiz Africa", desc: "Agribusiness guides and market analysis", url: "https://www.farmbizafrica.com/" },
    ],
  },
  {
    title: "Disease & Pest Diagnosis (free)",
    icon: Bug,
    color: "text-amber-600",
    items: [
      { name: "PlantVillage", desc: "Diagnose crop disease from a photo — 38 diseases, 14 crops", url: "https://plantvillage.psu.edu/" },
      { name: "Plantwise Knowledge Bank", desc: "Pest & disease factsheets by country, plant doctor answers", url: "https://www.plantwise.org/knowledge-bank/" },
      { name: "KALRO Pest Guides", desc: "Fall armyworm, aflatoxin and local pest management", url: "https://www.kalro.org/" },
    ],
  },
  {
    title: "Open Knowledge Databases",
    icon: Leaf,
    color: "text-emerald-600",
    items: [
      { name: "OpenFarm Growing Guides", desc: "Crowd-sourced structured guides for any crop", url: "https://openfarm.cc/" },
      { name: "Harvest Helper", desc: "Growing + harvest info for 45 common plants, open data", url: "https://github.com/damwhit/harvest_helper" },
      { name: "Growstuff Crop Database", desc: "Community crop records with open API", url: "https://www.growstuff.org/crops" },
    ],
  },
];

const CIVIC_ITEMS = [
  {
    title: "Know your contract rights",
    icon: Coins,
    body: "Before signing a produce contract (avocado, macadamia, French beans): a fair contract states the price basis (per kg, grade), weighing method, payment date, and who pays transport. You cannot legally be forced to sell at 'gate price' if your contract says otherwise. Keep a signed copy — your Wangari documents vault works.",
  },
  {
    title: "Subsidies & programs checklist",
    icon: Sprout,
    body: "Ask your county agriculture office about: the e-voucher input subsidy program, KCEP-CRAL (free seed/fertilizer for smallholders), NCPB crop purchases, and free county training days. Take your ID and farm records — counties prioritize farmers who can show production. These are free public programs — they only reach farmers who ask.",
  },
  {
    title: "Land & written agreements",
    icon: BookOpen,
    body: "If you lease land, a written agreement protects both sides: rent, duration, who may plant what, and notice period. A 'my word is enough' lease ends badly when land prices rise. County law courts offer standard lease templates cheaply.",
  },
  {
    title: "Cooperatives — strength in numbers",
    icon: Wind,
    body: "A registered co-op gives smallholders shared transport, better prices, credit access, and bargaining power with exporters. Registration needs 10+ members through the County Co-operative Officer — cheaper than most farmers think.",
  },
];

export default function LearnPage() {
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-6">
      <PageHeader
        title="Learn"
        description="The full members library — growing guides, video hubs per farming type, disease diagnosis and your rights as a farmer"
      />

      {/* Member-value banner */}
      <div className="flex items-center gap-3 rounded-2xl border border-wangari-green-200 bg-wangari-green-50 px-4 py-3">
        <GraduationCap className="h-5 w-5 shrink-0 text-wangari-green-700" />
        <p className="text-xs font-semibold text-wangari-green-800">
          You're seeing the <span className="font-extrabold">full member library</span> — video hubs,
          complete guides and county checklists. Visitors only get a taste on our public Learn page.
        </p>
        <Badge className="ml-auto shrink-0 border-0 bg-wangari-green-800 text-[10px] font-bold text-white">Member</Badge>
      </div>

      {/* ── Farming-type video hubs ── */}
      <div>
        <h2 className="mb-3 flex items-center gap-2 text-lg font-bold text-wangari-heading">
          <Video className="h-5 w-5 text-wangari-green-700" /> Video hubs by farming type
        </h2>
        <div className="grid gap-4 md:grid-cols-2">
          {FARM_TYPE_HUBS.map((hub, i) => (
            <motion.div key={hub.type} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
              <Card className="h-full rounded-2xl border-wangari-border/70">
                <CardContent className="p-4">
                  <h3 className="text-base font-extrabold text-wangari-heading">{hub.emoji} {hub.type}</h3>
                  <div className="mt-3 space-y-1.5">
                    {hub.videos.map((v) => (
                      <a key={v.title} href={v.url} target="_blank" rel="noreferrer" className="group flex items-center gap-2 rounded-lg px-2 py-1.5 transition-colors hover:bg-wangari-cream/60">
                        <Play className="h-3.5 w-3.5 shrink-0 text-red-500" />
                        <span className="text-xs font-semibold text-wangari-heading group-hover:underline">{v.title}</span>
                        <ExternalLink className="ml-auto h-3 w-3 shrink-0 text-wangari-subtle opacity-0 transition-opacity group-hover:opacity-100" />
                      </a>
                    ))}
                  </div>
                  <p className="mt-3 rounded-lg bg-wangari-green-50 px-3 py-2 text-[11px] font-semibold text-wangari-green-800">
                    💡 {hub.bonus}
                  </p>
                </CardContent>
              </Card>
            </motion.div>
          ))}
        </div>
      </div>

      {/* ── Growing guides ── */}
      <div>
        <h2 className="mb-3 flex items-center gap-2 text-lg font-bold text-wangari-heading">
          <Sprout className="h-5 w-5 text-wangari-green-700" /> Complete growing guides
        </h2>
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {GROWING_GUIDES.map((g, i) => (
            <motion.div key={g.crop} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
              <Card className="h-full rounded-2xl border-wangari-border/70">
                <CardContent className="space-y-2.5 p-4">
                  <div className="flex items-center justify-between">
                    <span className="text-xl font-extrabold text-wangari-heading">{g.emoji} {g.crop}</span>
                    <Badge className="border-0 bg-wangari-green-50 text-[10px] font-bold text-wangari-green-700">{g.season}</Badge>
                  </div>
                  <dl className="space-y-1 text-xs text-wangari-muted">
                    <p><span className="font-bold text-wangari-heading">Spacing:</span> {g.spacing}</p>
                    <p><span className="font-bold text-wangari-heading">Water:</span> {g.water}</p>
                    <p><span className="font-bold text-wangari-heading">Maturity:</span> {g.maturity}</p>
                    <p><span className="font-bold text-wangari-heading">Good with:</span> {g.companions}</p>
                  </dl>
                  <p className="rounded-lg bg-wangari-green-50 px-3 py-2 text-xs font-semibold leading-relaxed text-wangari-green-800">
                    💡 {g.tip}
                  </p>
                </CardContent>
              </Card>
            </motion.div>
          ))}
        </div>
      </div>

      {/* ── Curated free resources ── */}
      <div className="grid gap-4 lg:grid-cols-3">
        {RESOURCE_SECTIONS.map((section) => (
          <Card key={section.title} className="rounded-2xl border-wangari-border/70">
            <CardContent className="p-4">
              <h3 className={`mb-3 flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide ${section.color}`}>
                <section.icon className="h-4 w-4" /> {section.title}
              </h3>
              <div className="space-y-3">
                {section.items.map((r) => (
                  <a key={r.name} href={r.url} target="_blank" rel="noreferrer" className="group block rounded-xl border border-transparent px-3 py-2 transition-colors hover:border-wangari-border/60 hover:bg-wangari-cream/50">
                    <p className="flex items-center gap-1.5 text-sm font-bold text-wangari-heading">
                      {r.name}
                      <ExternalLink className="h-3 w-3 text-wangari-subtle opacity-0 transition-opacity group-hover:opacity-100" />
                    </p>
                    <p className="mt-0.5 text-xs leading-relaxed text-wangari-muted">{r.desc}</p>
                  </a>
                ))}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* ── Civic education ── */}
      <div>
        <h2 className="mb-3 flex items-center gap-2 text-lg font-bold text-wangari-heading">
          <Coins className="h-5 w-5 text-amber-600" /> Know your rights &amp; programs
        </h2>
        <div className="grid gap-4 md:grid-cols-2">
          {CIVIC_ITEMS.map((c) => (
            <Card key={c.title} className="rounded-2xl border-wangari-border/70">
              <CardContent className="p-4">
                <p className="flex items-center gap-2 text-sm font-extrabold text-wangari-heading">
                  <c.icon className="h-4 w-4 text-amber-600" /> {c.title}
                </p>
                <p className="mt-2 text-xs leading-relaxed text-wangari-muted">{c.body}</p>
              </CardContent>
            </Card>
          ))}
        </div>
        <p className="mt-3 text-center text-[11px] text-wangari-subtle">
          General guidance, not legal advice — for specific disputes consult your county agriculture or cooperative office.
        </p>
      </div>

      {/* ── Free tools cross-links ── */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card className="rounded-2xl border-wangari-border/70 bg-wangari-cream/40">
          <CardContent className="flex items-center gap-3 p-4">
            <Droplets className="h-8 w-8 text-sky-600" />
            <div>
              <p className="text-sm font-extrabold text-wangari-heading">7-day rain outlook</p>
              <p className="text-xs text-wangari-muted">Your Weather tab has a free per-location forecast — check before planting or spraying.</p>
            </div>
          </CardContent>
        </Card>
        <Card className="rounded-2xl border-wangari-border/70 bg-wangari-cream/40">
          <CardContent className="flex items-center gap-3 p-4">
            <Bug className="h-8 w-8 text-amber-600" />
            <div>
              <p className="text-sm font-extrabold text-wangari-heading">PHI safety</p>
              <p className="text-xs text-wangari-muted">Log every spray in your crop records — Wangari warns you when produce is safe to harvest.</p>
            </div>
          </CardContent>
        </Card>
        <Card className="rounded-2xl border-wangari-border/70 bg-wangari-cream/40">
          <CardContent className="flex items-center gap-3 p-4">
            <Sun className="h-8 w-8 text-orange-500" />
            <div>
              <p className="text-sm font-extrabold text-wangari-heading">Daily advisory email</p>
              <p className="text-xs text-wangari-muted">Season tips, rain outlook and farm news arrive every morning — free with your account.</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
