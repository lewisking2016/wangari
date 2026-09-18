import Link from "next/link";
import type { Metadata } from "next";
import { BookOpen, Sprout, GraduationCap, ArrowRight, Leaf, Lock } from "lucide-react";

export const metadata: Metadata = {
  title: "Free Farming Knowledge — Learn Center | Wangari Farm OS",
  description:
    "Free growing guides, KALRO advisories, video learning and farmer rights resources — open to everyone, no account needed. Wangari Farm OS members get the full library inside the dashboard.",
};

/**
 * /learn — PUBLIC marketing page (no login).
 *
 * Strategy: real, useful free content here (grows trust + SEO), with clear
 * "there's more inside" teasers for the full in-dashboard library. Members
 * get per-farming-type video hubs, county resource pages, and deeper guides.
 */

const GUIDES = [
  {
    crop: "Maize", emoji: "🌽",
    season: "Long rains (Mar–May)", maturity: "120–150 days",
    tip: "Top-dress with CAN when knee-high. Dry to 13% moisture before storing to stop aflatoxin.",
    locked: "Full guide: varieties, spacing per county, fertilizer schedule",
  },
  {
    crop: "Kienyeji Chicken", emoji: "🐔",
    season: "Year-round", maturity: "First eggs ~5 months",
    tip: "Vaccinate against Newcastle at day 7, 21 and every 3 months — most losses are preventable.",
    locked: "Full guide: brooding, feed mixing ratios, vaccination calendar, market prices",
  },
  {
    crop: "Dairy", emoji: "🐄",
    season: "Year-round", maturity: "Daily milk",
    tip: "A cow giving 20L/day needs ~70kg good feed + 100L water. Colostrum within 6 hours of birth saves calves.",
    locked: "Full guide: feeding plans by yield, breeding calendar, mastitis prevention",
  },
  {
    crop: "Avocado (Hass)", emoji: "🥑",
    season: "Harvest Apr–Sep", maturity: "3–4 years to first commercial harvest",
    tip: "One Hass tree can earn KES 3,000–8,000/season at maturity. Plant certified seedlings, 7m spacing.",
    locked: "Full guide: export-grade requirements, contract terms to insist on, pest control",
  },
  {
    crop: "Tomatoes", emoji: "🍅",
    season: "Best Jun–Sep", maturity: "75–90 days",
    tip: "High humidity 3+ days = blight risk. Spray preventively, prune lower leaves for airflow.",
    locked: "Full guide: staking systems, market timing, greenhouse vs open field economics",
  },
  {
    crop: "Macadamia", emoji: "🌰",
    season: "Harvest Mar–Jun", maturity: "3–5 years",
    tip: "The decade's best long-term crop — but only with a signed contract before planting. Price per kg varies 3x.",
    locked: "Full guide: varieties (Kirinyagi, Gekou…), spacing, processor contracts explained",
  },
];

const FREE_RESOURCES = [
  { name: "KALRO e-extension (KEEP)", desc: "Kenya's official agricultural research advisories — free", url: "https://keep.kalro.org/" },
  { name: "Kenya Meteorological Department", desc: "Official seasonal forecasts and weather warnings", url: "https://meteo.go.ke/" },
  { name: "PlantVillage", desc: "Diagnose crop disease from a photo — free from Penn State", url: "https://plantvillage.psu.edu/" },
  { name: "Plantwise Knowledge Bank", desc: "Pest & disease factsheets by country — CABI", url: "https://www.plantwise.org/knowledge-bank/" },
  { name: "Kilimo News", desc: "Daily Kenyan agriculture news and market prices", url: "https://kilimonews.co.ke/" },
  { name: "Shamba Shape Up", desc: "Kenya's #1 farming TV show — full episodes free on YouTube", url: "https://www.youtube.com/@shambashapeup" },
];

const RIGHTS = [
  {
    title: "Contract rights",
    body: "A fair produce contract states price basis, weighing method, payment date and who pays transport. Never sign at the farm gate without a copy. You can't be forced to sell at 'gate price' if your contract says otherwise.",
  },
  {
    title: "Subsidies you may qualify for",
    body: "Ask your county agriculture office about the e-voucher input subsidy, KCEP-CRAL seed & fertilizer support, and NCPB purchases. These are free public programs — they only reach farmers who ask.",
  },
  {
    title: "Written land agreements",
    body: "Leasing land? A written agreement protects both sides: rent, duration, allowed crops, notice period. It costs little at the county offices and prevents disputes that ruin farms.",
  },
  {
    title: "Cooperatives",
    body: "10+ members can register a co-op through the County Co-operative Officer — shared transport, better prices, credit access, and real bargaining power with exporters.",
  },
];

export default function PublicLearnPage() {
  return (
    <div className="min-h-screen bg-[#FDFBF7]">
      {/* Hero */}
      <section className="mx-auto max-w-5xl px-4 pb-12 pt-16 text-center md:pt-20">
        <span className="inline-flex items-center gap-1.5 rounded-full bg-wangari-green-50 px-4 py-1.5 text-xs font-bold text-wangari-green-700">
          <Leaf className="h-3.5 w-3.5" /> Free forever · no account needed
        </span>
        <h1 className="mx-auto mt-5 max-w-3xl text-4xl font-extrabold leading-tight text-[#1A2E1A] md:text-5xl">
          The farming knowledge every Kenyan farmer deserves —{" "}
          <span className="text-wangari-green-700">free and actually findable</span>
        </h1>
        <p className="mx-auto mt-4 max-w-2xl text-base text-[#5A6B5A] md:text-lg">
          The best agricultural knowledge in Kenya is free — KALRO's advisories, official weather
          forecasts, disease diagnosis — but buried where farmers never find it. We bring it together.
        </p>
      </section>

      {/* Growing guides */}
      <section className="mx-auto max-w-6xl px-4 pb-14">
        <h2 className="mb-6 flex items-center gap-2 text-2xl font-extrabold text-[#1A2E1A]">
          <Sprout className="h-6 w-6 text-wangari-green-700" /> Growing guides
        </h2>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {GUIDES.map((g) => (
            <div key={g.crop} className="rounded-2xl border border-[#E7EBD8] bg-white p-5 shadow-sm">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-extrabold text-[#1A2E1A]">{g.emoji} {g.crop}</h3>
                <span className="rounded-full bg-wangari-green-50 px-2.5 py-0.5 text-[10px] font-bold text-wangari-green-700">{g.season}</span>
              </div>
              <p className="mt-1 text-xs font-semibold text-[#8A9A8A]">Maturity: {g.maturity}</p>
              <p className="mt-3 rounded-lg bg-[#F0FDF4] px-3 py-2 text-xs font-semibold leading-relaxed text-wangari-green-800">
                💡 {g.tip}
              </p>
              <p className="mt-3 flex items-center gap-1.5 text-[11px] font-bold text-[#B0B8B0]">
                <Lock className="h-3 w-3" /> Members only: {g.locked}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* Free resources */}
      <section className="bg-[#F7F5EE] py-14">
        <div className="mx-auto max-w-6xl px-4">
          <h2 className="mb-6 flex items-center gap-2 text-2xl font-extrabold text-[#1A2E1A]">
            <BookOpen className="h-6 w-6 text-wangari-green-700" /> Official free resources
          </h2>
          <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            {FREE_RESOURCES.map((r) => (
              <a
                key={r.name}
                href={r.url}
                target="_blank"
                rel="noreferrer"
                className="group rounded-xl border border-[#E7EBD8] bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
              >
                <p className="flex items-center justify-between text-sm font-bold text-[#1A2E1A]">
                  {r.name}
                  <ArrowRight className="h-3.5 w-3.5 text-wangari-subtle transition-transform group-hover:translate-x-0.5" />
                </p>
                <p className="mt-1 text-xs text-[#5A6B5A]">{r.desc}</p>
              </a>
            ))}
          </div>
        </div>
      </section>

      {/* Rights */}
      <section className="mx-auto max-w-6xl px-4 py-14">
        <h2 className="mb-6 text-2xl font-extrabold text-[#1A2E1A]">Know your rights as a farmer</h2>
        <div className="grid gap-4 md:grid-cols-2">
          {RIGHTS.map((r) => (
            <div key={r.title} className="rounded-2xl border border-[#E7EBD8] bg-white p-5 shadow-sm">
              <h3 className="text-sm font-extrabold text-[#1A2E1A]">{r.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-[#5A6B5A]">{r.body}</p>
            </div>
          ))}
        </div>
        <p className="mt-4 text-center text-xs text-[#8A9A8A]">
          General guidance, not legal advice — consult your county agriculture or cooperative office for specific disputes.
        </p>
      </section>

      {/* CTA — the marketing strategy */}
      <section className="mx-auto max-w-4xl px-4 pb-20">
        <div className="rounded-3xl bg-wangari-green-800 p-8 text-center md:p-12">
          <GraduationCap className="mx-auto h-10 w-10 text-[#BBF7D0]" />
          <h2 className="mt-4 text-2xl font-extrabold text-white md:text-3xl">
            This page is the free version.
          </h2>
          <p className="mx-auto mt-3 max-w-xl text-sm text-[#BBF7D0] md:text-base">
            Inside the Wangari dashboard, members get the full library: complete growing guides per
            county, video courses for every farming type (poultry, dairy, avocado, macadamia, legumes),
            county subsidy checklists, and smart reminders — vaccinations, births, top-dressing and
            harvest windows — generated automatically from their own farm records.
          </p>
          <div className="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link
              href="/register"
              className="rounded-xl bg-white px-7 py-3 text-sm font-extrabold text-wangari-green-800 transition-colors hover:bg-[#EFFDF3]"
            >
              Create free account →
            </Link>
            <Link
              href="/pricing"
              className="rounded-xl border border-[#4A7A4A] px-7 py-3 text-sm font-bold text-[#BBF7D0] transition-colors hover:bg-[#24542A]"
            >
              See member plans
            </Link>
          </div>
          <p className="mt-4 text-xs text-[#8FBF9A]">Free trial included · no card required</p>
        </div>
      </section>
    </div>
  );
}
