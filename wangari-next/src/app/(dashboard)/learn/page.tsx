"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  GraduationCap, Sprout, Droplets, Bug, Sun, Wind,
  ExternalLink, Play, BookOpen, Leaf, Coins,
} from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

/**
 * Learn — free, curated farming knowledge.
 *
 * The best resources are already free (KALRO, KMD forecasts, Shamba Shape Up,
 * Kilimo Diaries, OpenFarm guides) — farmers just can't find them when they
 * need them. This page brings them together, per farming type, with the
 * structured "how do I grow X" data from OpenFarm's open database concept.
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

const RESOURCE_SECTIONS = [
  {
    title: "Official & Research",
    icon: BookOpen,
    color: "text-wangari-green-700",
    items: [
      { name: "KALRO e-extension (KEEP)", desc: "Kenya Agricultural & Livestock Research Organization — location-specific advisories, free", url: "https://keep.kalro.org/" },
      { name: "Kenya Meteorological Department", desc: "Official seasonal forecasts & county weather warnings", url: "https://meteo.go.ke/" },
      { name: "Kilimo News", desc: "Daily Kenyan agriculture news, prices, policy", url: "https://kilimonews.co.ke/" },
      { name: "FarmBiz Africa", desc: "Agribusiness guides and market analysis", url: "https://www.farmbizafrica.com/" },
    ],
  },
  {
    title: "Video Learning (free on YouTube)",
    icon: Play,
    color: "text-red-600",
    items: [
      { name: "Shamba Shape Up", desc: "Kenya's #1 farm-makeover show — real farms, expert fixes, proven impact", url: "https://www.youtube.com/@shambashapeup" },
      { name: "Kilimo Diaries", desc: "Deep practical guides — kienyeji poultry, dairy, crops", url: "https://www.youtube.com/results?search_query=kilimo+diaries" },
      { name: "Smart Farm Kenya", desc: "Modern agribusiness setups and profit breakdowns", url: "https://www.youtube.com/results?search_query=smart+farm+kenya" },
      { name: "KTN Farm Kenya", desc: "Expert interviews on goats, dairy, export crops", url: "https://www.youtube.com/results?search_query=ktn+farm+kenya" },
    ],
  },
  {
    title: "Open Knowledge Databases",
    icon: Leaf,
    color: "text-emerald-600",
    items: [
      { name: "OpenFarm Growing Guides", desc: "Crowd-sourced structured guides — spacing, depth, watering, companions for any crop", url: "https://openfarm.cc/" },
      { name: "PlantVillage (Penn State)", desc: "Free crop disease diagnosis by photo — 38 diseases across 14 crops", url: "https://plantvillage.psu.edu/" },
      { name: "Plantwise Knowledge Bank (CABI)", desc: "Pest & disease factsheets by country, plant doctor answers", url: "https://www.plantwise.org/knowledge-bank/" },
      { name: "Harvest Helper", desc: "Growing + harvest info for 45 common plants, open data", url: "https://github.com/damwhit/harvest_helper" },
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
    title: "Subsidies & programs you may qualify for",
    icon: Sprout,
    body: "Ask your county agriculture office about: the e-voucher input subsidy program, KCEP-CRAL (free seed/fertilizer for smallholders), NCPB crop purchases, and free county training days. These are free public programs — but only reach farmers who ask.",
  },
  {
    title: "Land & written agreements",
    icon: BookOpen,
    body: "If you lease land, a written agreement protects both sides: rent, duration, who may plant what, and notice period. A 'my word is enough' lease ends badly when land prices rise. County law courts offer standard lease templates cheaply.",
  },
  {
    title: "Cooperatives — strength in numbers",
    icon: Wind,
    body: "A registered co-op gives smallholders shared transport, better prices, credit access, and bargaining power with exporters. Registration needs 10+ members and is done through the County Co-operative Officer — the process is cheaper than most farmers think.",
  },
];

export default function LearnPage() {
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-6">
      <PageHeader
        title="Learn"
        description="Free, curated farming knowledge — growing guides, official advisories, video learning and your rights as a farmer"
      />

      {/* ── Growing guides (OpenFarm-style, Kenya-tuned) ── */}
      <div>
        <h2 className="mb-3 flex items-center gap-2 text-lg font-bold text-wangari-heading">
          <Sprout className="h-5 w-5 text-wangari-green-700" /> Growing guides
        </h2>
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {GROWING_GUIDES.map((g, i) => (
            <motion.div
              key={g.crop}
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.05 }}
            >
              <Card className="h-full rounded-2xl border-wangari-border/70">
                <CardContent className="space-y-2.5 p-4">
                  <div className="flex items-center justify-between">
                    <span className="text-xl font-extrabold text-wangari-heading">
                      {g.emoji} {g.crop}
                    </span>
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
                  <a
                    key={r.name}
                    href={r.url}
                    target="_blank"
                    rel="noreferrer"
                    className="group block rounded-xl border border-transparent px-3 py-2 transition-colors hover:border-wangari-border/60 hover:bg-wangari-cream/50"
                  >
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
