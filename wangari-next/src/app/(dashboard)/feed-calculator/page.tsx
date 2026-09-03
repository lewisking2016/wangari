"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Calculator, Wheat, AlertTriangle, TrendingUp, Beef, Leaf, ChevronRight, Check } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import api from "@/lib/api-client";
import { speciesTemplates } from "@/lib/species-templates";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };

type Mode = "livestock" | "crops";

const livestockFeeds: Record<string, Array<{ name: string; kgPerHead: number; costPerBag: number; bagSize: number }>> = {
  poultry: [
    { name: "Starter (0-4 weeks)", kgPerHead: 0.5, costPerBag: 3500, bagSize: 50 },
    { name: "Grower (4-8 weeks)", kgPerHead: 1.2, costPerBag: 3200, bagSize: 50 },
    { name: "Layer Mash", kgPerHead: 0.12, costPerBag: 3000, bagSize: 50 },
    { name: "Broiler Finisher", kgPerHead: 0.18, costPerBag: 3100, bagSize: 50 },
  ],
  cattle: [
    { name: "Dairy Concentrate", kgPerHead: 4.0, costPerBag: 4500, bagSize: 50 },
    { name: "Dairy Hay", kgPerHead: 8.0, costPerBag: 800, bagSize: 20 },
    { name: "Beef Supplement", kgPerHead: 2.5, costPerBag: 3800, bagSize: 50 },
  ],
  goats: [
    { name: "Goat Pellets", kgPerHead: 0.8, costPerBag: 2800, bagSize: 25 },
    { name: "Hay Supplement", kgPerHead: 1.5, costPerBag: 800, bagSize: 20 },
  ],
  pigs: [
    { name: "Pig Grower", kgPerHead: 2.5, costPerBag: 3200, bagSize: 50 },
    { name: "Pig Finisher", kgPerHead: 3.0, costPerBag: 3400, bagSize: 50 },
  ],
  fish: [
    { name: "Fish Feed (Floating)", kgPerHead: 0.06, costPerBag: 5500, bagSize: 25 },
  ],
};

const cropInputs = [
  { name: "NPK 17:17:17", kgPerAcre: 200, costPerBag: 3800, bagSize: 50 },
  { name: "CAN (Top Dress)", kgPerAcre: 100, costPerBag: 2500, bagSize: 50 },
  { name: "DAP (Planting)", kgPerAcre: 150, costPerBag: 3200, bagSize: 50 },
  { name: "Manure (Organic)", kgPerAcre: 2000, costPerBag: 500, bagSize: 50 },
];

function getFeedCategory(type: string | null): string {
  if (!type) return "poultry";
  if (type.includes("cattle")) return "cattle";
  if (type.includes("goat") || type.includes("sheep")) return "goats";
  if (type.includes("pig")) return "pigs";
  if (type.includes("fish")) return "fish";
  return "poultry";
}

export default function FeedCalculatorPage() {
  const [mode, setMode] = React.useState<Mode>("livestock");
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [selectedFlock, setSelectedFlock] = React.useState<string>("");
  const [selectedFeed, setSelectedFeed] = React.useState(0);
  const [headCount, setHeadCount] = React.useState("");
  const [days, setDays] = React.useState("30");
  const [acres, setAcres] = React.useState("1");

  React.useEffect(() => { api.get("/api/flocks").then(d => setFlocks(Array.isArray(d) ? d : [])).catch(() => {}); }, []);

  const flock = flocks.find((f: any) => f.id === Number(selectedFlock));
  const feedCategory = getFeedCategory(flock?.type || null);
  const feeds = mode === "livestock" ? (livestockFeeds[feedCategory] || livestockFeeds.poultry) : cropInputs;
  const feed = feeds[selectedFeed] || feeds[0];
  const count = Number(headCount) || flock?.currentCount || 0;
  const numDays = Number(days) || 1;
  const numAcres = Number(acres) || 1;

  const isLivestock = mode === "livestock";

  // Auto-fill head count when flock selected
  React.useEffect(() => {
    if (flock && !headCount) setHeadCount(String(flock.currentCount));
  }, [flock]);

  const dailyKg = count * (feed as any).kgPerHead;
  const totalKg = dailyKg * numDays;
  const bagsNeeded = Math.ceil(totalKg / feed.bagSize);
  const totalCost = bagsNeeded * feed.costPerBag;
  const costPerHead = count > 0 ? totalCost / count : 0;

  const cropTotal = numAcres * ((feed as any).kgPerAcre || 0);
  const cropBags = Math.ceil(cropTotal / feed.bagSize);
  const cropCost = cropBags * feed.costPerBag;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Feed & Input Calculator" description="Estimate feed or fertilizer costs" />
      </motion.div>

      {/* Mode toggle */}
      <div className="flex gap-2">
        <button onClick={() => { setMode("livestock"); setSelectedFeed(0); }} className={`flex-1 py-3 rounded-xl text-sm font-semibold transition-all cursor-pointer flex items-center justify-center gap-2 ${isLivestock ? "bg-[#166534] text-white shadow-md" : "bg-gray-100 text-gray-600 hover:bg-gray-200"}`}>
          <Beef className="h-5 w-5" /> Livestock
        </button>
        <button onClick={() => { setMode("crops"); setSelectedFeed(0); }} className={`flex-1 py-3 rounded-xl text-sm font-semibold transition-all cursor-pointer flex items-center justify-center gap-2 ${!isLivestock ? "bg-[#166534] text-white shadow-md" : "bg-gray-100 text-gray-600 hover:bg-gray-200"}`}>
          <Leaf className="h-5 w-5" /> Crops
        </button>
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        {/* Input */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardHeader className="pb-3"><CardTitle className="text-sm font-bold text-gray-900">{isLivestock ? "1. Select your group" : "1. Select input type"}</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              {isLivestock && (
                <div className="space-y-2">
                  <Label className="text-xs font-semibold text-gray-500">Quick select (auto-fills head count)</Label>
                  <select value={selectedFlock} onChange={e => { setSelectedFlock(e.target.value); setSelectedFeed(0); }}
                    className="w-full h-12 rounded-xl border border-gray-200 px-3 text-sm font-medium focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534]">
                    <option value="">Choose a group...</option>
                    {flocks.map(f => <option key={f.id} value={f.id}>{f.name} — {f.currentCount} head ({speciesTemplates[f.type]?.name || f.type})</option>)}
                  </select>
                </div>
              )}

              <div className="space-y-2">
                <Label className="text-xs font-semibold text-gray-500">{isLivestock ? "Feed type" : "Input type"}</Label>
                <div className="space-y-2 max-h-60 overflow-y-auto">
                  {feeds.map((f: any, i: number) => (
                    <button key={f.name} onClick={() => setSelectedFeed(i)}
                      className={`w-full text-left rounded-xl border px-4 py-3 text-sm transition-all cursor-pointer ${i === selectedFeed ? "border-[#166534] bg-[#F0FDF4] font-semibold" : "border-gray-200 hover:border-[#BBF7D0]"}`}>
                      <p className="font-medium">{f.name}</p>
                      <p className="text-xs text-gray-400 mt-0.5">KES {f.costPerBag.toLocaleString()} / {f.bagSize}kg bag</p>
                    </button>
                  ))}
                </div>
              </div>

              {isLivestock ? (
                <div className="grid grid-cols-2 gap-3">
                  <div><Label className="text-xs font-semibold text-gray-500">Head count</Label><Input type="number" value={headCount} onChange={e => setHeadCount(e.target.value)} className="h-11 rounded-xl text-lg font-bold" /></div>
                  <div><Label className="text-xs font-semibold text-gray-500">Days</Label><Input type="number" value={days} onChange={e => setDays(e.target.value)} className="h-11 rounded-xl text-lg font-bold" /></div>
                </div>
              ) : (
                <div><Label className="text-xs font-semibold text-gray-500">Acres</Label><Input type="number" value={acres} onChange={e => setAcres(e.target.value)} className="h-11 rounded-xl text-lg font-bold" /></div>
              )}
            </CardContent>
          </Card>
        </motion.div>

        {/* Results */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardHeader className="pb-3"><CardTitle className="text-sm font-bold text-gray-900">2. Results</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                {isLivestock ? (
                  <>
                    <div className="rounded-xl border border-gray-200 p-4 text-center">
                      <Wheat className="h-6 w-6 text-[#166534] mx-auto mb-1" />
                      <p className="text-[10px] text-gray-400 uppercase font-semibold">Daily</p>
                      <p className="text-xl font-extrabold">{dailyKg.toFixed(1)} kg</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 p-4 text-center">
                      <TrendingUp className="h-6 w-6 text-[#166534] mx-auto mb-1" />
                      <p className="text-[10px] text-gray-400 uppercase font-semibold">{numDays} days</p>
                      <p className="text-xl font-extrabold">{totalKg.toFixed(0)} kg</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 p-4 text-center">
                      <Calculator className="h-6 w-6 text-[#166534] mx-auto mb-1" />
                      <p className="text-[10px] text-gray-400 uppercase font-semibold">Bags</p>
                      <p className="text-xl font-extrabold">{bagsNeeded}</p>
                    </div>
                    <div className="rounded-xl bg-[#166534] text-white p-4 text-center">
                      <AlertTriangle className="h-6 w-6 mx-auto mb-1" />
                      <p className="text-[10px] text-white/70 uppercase font-semibold">Total Cost</p>
                      <p className="text-xl font-extrabold">KES {totalCost.toLocaleString()}</p>
                    </div>
                  </>
                ) : (
                  <>
                    <div className="rounded-xl border border-gray-200 p-4 text-center col-span-2">
                      <Leaf className="h-6 w-6 text-[#166534] mx-auto mb-1" />
                      <p className="text-[10px] text-gray-400 uppercase font-semibold">Total for {numAcres} acres</p>
                      <p className="text-xl font-extrabold">{cropTotal.toFixed(0)} kg</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 p-4 text-center">
                      <Calculator className="h-6 w-6 text-[#166534] mx-auto mb-1" />
                      <p className="text-[10px] text-gray-400 uppercase font-semibold">Bags</p>
                      <p className="text-xl font-extrabold">{cropBags}</p>
                    </div>
                    <div className="rounded-xl bg-[#166534] text-white p-4 text-center">
                      <AlertTriangle className="h-6 w-6 mx-auto mb-1" />
                      <p className="text-[10px] text-white/70 uppercase font-semibold">Total Cost</p>
                      <p className="text-xl font-extrabold">KES {cropCost.toLocaleString()}</p>
                    </div>
                  </>
                )}
              </div>

              <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-4 text-sm space-y-1.5">
                <p className="font-bold text-[#166534]">Summary</p>
                <div className="flex justify-between"><span className="text-gray-500">Type:</span><span className="font-semibold">{feed.name}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">Bag size:</span><span className="font-semibold">{feed.bagSize} kg</span></div>
                {isLivestock && count > 0 && <div className="flex justify-between"><span className="text-gray-500">Per head/day:</span><span className="font-semibold">KES {costPerHead.toFixed(0)}</span></div>}
                <div className="flex justify-between border-t border-[#BBF7D0] pt-1.5"><span className="font-bold">You need:</span><span className="font-bold text-[#166534]">{isLivestock ? bagsNeeded : cropBags} bags × KES {feed.costPerBag.toLocaleString()}</span></div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </div>
    </div>
  );
}
