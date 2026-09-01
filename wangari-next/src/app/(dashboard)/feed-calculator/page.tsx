"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Calculator, Wheat, AlertTriangle, TrendingUp } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };

const feedTypes = [
  { name: "Starter Feed (0-4 weeks)", kgPerBird: 0.5, costPerBag: 3500, bagSize: 50 },
  { name: "Grower Feed (4-8 weeks)", kgPerBird: 1.2, costPerBag: 3200, bagSize: 50 },
  { name: "Layer Mash (16+ weeks)", kgPerBird: 0.12, costPerBag: 3000, bagSize: 50 },
  { name: "Broiler Finisher", kgPerBird: 0.18, costPerBag: 3100, bagSize: 50 },
  { name: "Chick Starter (0-2 weeks)", kgPerBird: 0.3, costPerBag: 3800, bagSize: 50 },
];

export default function FeedCalculatorPage() {
  const [birdCount, setBirdCount] = React.useState("500");
  const [selectedFeed, setSelectedFeed] = React.useState(2); // Default to Layer Mash
  const [days, setDays] = React.useState("30");

  const feed = feedTypes[selectedFeed];
  const count = Number(birdCount) || 0;
  const numDays = Number(days) || 1;

  const dailyKg = count * feed.kgPerBird;
  const totalKg = dailyKg * numDays;
  const bagsNeeded = Math.ceil(totalKg / feed.bagSize);
  const totalCost = bagsNeeded * feed.costPerBag;
  const costPerBird = count > 0 ? totalCost / count : 0;

  const kpis = [
    { title: "Daily Need", value: dailyKg.toFixed(1) + " kg", icon: <Wheat className="h-5 w-5" /> },
    { title: "Total Need", value: totalKg.toFixed(0) + " kg", icon: <TrendingUp className="h-5 w-5" /> },
    { title: "Bags Needed", value: String(bagsNeeded), icon: <Calculator className="h-5 w-5" /> },
    { title: "Total Cost", value: "KES " + totalCost.toLocaleString(), icon: <AlertTriangle className="h-5 w-5" /> },
  ];

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Feed Calculator" description="Calculate feed requirements and costs for your flocks" />
      </motion.div>

      <div className="grid lg:grid-cols-2 gap-6">
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardHeader><CardTitle className="text-base font-bold">Input Parameters</CardTitle></CardHeader>
            <CardContent className="space-y-5">
              <div className="space-y-2">
                <Label className="text-sm font-semibold text-[#334155]">Feed Type</Label>
                <div className="space-y-2">
                  {feedTypes.map((f, i) => (
                    <button key={f.name} onClick={() => setSelectedFeed(i)} className={`w-full text-left rounded-xl border px-4 py-3 text-sm transition-all cursor-pointer ${i === selectedFeed ? "border-[#166534] bg-[#F0FDF4] text-[#166534] font-semibold" : "border-[#E5E7EB] hover:border-[#BBF7D0]"}`}>
                      <p className="font-medium">{f.name}</p>
                      <p className="text-xs text-[#94A3B8] mt-0.5">{f.kgPerBird} kg/bird/day · KES {f.costPerBag}/bag</p>
                    </button>
                  ))}
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Number of Birds</Label>
                  <Input type="number" value={birdCount} onChange={e => setBirdCount(e.target.value)} className="h-11 rounded-xl" />
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Number of Days</Label>
                  <Input type="number" value={days} onChange={e => setDays(e.target.value)} className="h-11 rounded-xl" />
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>

        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardHeader><CardTitle className="text-base font-bold">Results</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                {kpis.map(kpi => (
                  <div key={kpi.title} className="rounded-xl border border-[#E5E7EB] p-4">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#166534] text-white mb-2">{kpi.icon}</div>
                    <p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">{kpi.title}</p>
                    <p className="text-xl font-extrabold text-[#0F172A] mt-1">{kpi.value}</p>
                  </div>
                ))}
              </div>
              <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-4">
                <p className="text-sm font-bold text-[#166534]">Cost Summary</p>
                <div className="mt-3 space-y-2 text-sm">
                  <div className="flex justify-between"><span className="text-[#64748B]">Feed type:</span><span className="font-semibold text-[#0F172A]">{feed.name}</span></div>
                  <div className="flex justify-between"><span className="text-[#64748B]">Cost per bag:</span><span className="font-semibold text-[#0F172A]">KES {feed.costPerBag.toLocaleString()}</span></div>
                  <div className="flex justify-between"><span className="text-[#64748B]">Cost per bird/day:</span><span className="font-semibold text-[#0F172A]">KES {(feed.costPerBag / feed.bagSize * feed.kgPerBird).toFixed(2)}</span></div>
                  <div className="flex justify-between"><span className="text-[#64748B]">Cost per bird ({numDays} days):</span><span className="font-semibold text-[#166534]">KES {costPerBird.toFixed(2)}</span></div>
                  <div className="flex justify-between border-t border-[#BBF7D0] pt-2"><span className="font-bold text-[#0F172A]">Total cost:</span><span className="font-extrabold text-[#166534]">KES {totalCost.toLocaleString()}</span></div>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </div>
    </div>
  );
}
