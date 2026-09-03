"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Bird, Leaf, Syringe, Wheat, AlertTriangle, CheckCircle, Clock, Plus, TrendingUp, DollarSign, Calendar } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import api from "@/lib/api-client";
import { speciesTemplates } from "@/lib/species-templates";

const fadeUp = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

export default function FarmHealthPage() {
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [crops, setCrops] = React.useState<any[]>([]);
  const [vaccinations, setVaccinations] = React.useState<any[]>([]);
  const [production, setProduction] = React.useState<any[]>([]);
  const [transactions, setTransactions] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    Promise.allSettled([
      api.get("/api/flocks"),
      api.get("/api/crops"),
      api.get("/api/vaccinations"),
      api.get("/api/production"),
      api.get("/api/transactions"),
    ]).then(([f, c, v, p, t]) => {
      if (f.status === "fulfilled") setFlocks(Array.isArray(f.value) ? f.value : []);
      if (c.status === "fulfilled") setCrops(Array.isArray(c.value) ? c.value : []);
      if (v.status === "fulfilled") setVaccinations(Array.isArray(v.value) ? v.value : []);
      if (p.status === "fulfilled") setProduction(Array.isArray(p.value) ? p.value : []);
      if (t.status === "fulfilled") setTransactions(Array.isArray(t.value) ? t.value : []);
      setLoading(false);
    }).catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  // Livestock summary
  const totalAnimals = flocks.reduce((s, f) => s + (f.currentCount || 0), 0);
  const totalMortality = flocks.reduce((s, f) => s + (f.mortality || 0), 0);
  const mortalityRate = totalAnimals + totalMortality > 0 ? ((totalMortality / (totalAnimals + totalMortality)) * 100).toFixed(1) : "0";
  const speciesGroups: Record<string, { count: number; name: string; icon: any }> = {};
  flocks.forEach((f) => {
    const sp = speciesTemplates[f.type];
    const cat = sp?.category || "other";
    if (!speciesGroups[cat]) speciesGroups[cat] = { count: 0, name: cat.charAt(0).toUpperCase() + cat.slice(1), icon: cat === "poultry" ? Bird : Leaf };
    speciesGroups[cat].count += f.currentCount || 0;
  });

  // Crops summary
  const activeCrops = crops.filter(c => c.status === "active").length;
  const totalAcres = crops.reduce((s, c) => s + Number(c.areaAcres || 0), 0);

  // Vaccinations due soon (next 7 days)
  const now = new Date();
  const nextWeek = new Date(now.getTime() + 7 * 86400000);
  const pendingVax = vaccinations.filter(v => v.status === "pending");
  const dueSoon = pendingVax.filter(v => {
    const d = new Date(v.scheduledDate);
    return d <= nextWeek;
  });

  // Recent production
  const todayProd = production.filter(r => {
    const d = new Date(r.date);
    return d.toDateString() === now.toDateString();
  });
  const todayOutput = todayProd.reduce((s, r) => s + (r.eggsCollected || 0) + Number(r.milkCollected || 0) + Number(r.weightGain || 0), 0);

  // Financial health
  const thisMonth = transactions.filter(t => {
    const d = new Date(t.date);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  const monthIncome = thisMonth.filter(t => t.type === "income").reduce((s, t) => s + Number(t.amount), 0);
  const monthExpenses = thisMonth.filter(t => t.type === "expense").reduce((s, t) => s + Number(t.amount), 0);

  // Health score (simple calculation)
  const issues: string[] = [];
  if (Number(mortalityRate) > 5) issues.push("High mortality rate");
  if (dueSoon.length > 0) issues.push(`${dueSoon.length} vaccination(s) due soon`);
  if (pendingVax.length > 5) issues.push(`${pendingVax.length} pending vaccinations`);
  const healthScore = issues.length === 0 ? "Good" : issues.length <= 2 ? "Fair" : "Needs Attention";
  const healthColor = issues.length === 0 ? "text-emerald-600 bg-emerald-50" : issues.length <= 2 ? "text-amber-600 bg-amber-50" : "text-red-600 bg-red-50";

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <h1 className="text-2xl font-extrabold text-gray-900 tracking-tight">Farm Health</h1>
        <p className="text-sm text-gray-400 mt-0.5">All your operations at a glance</p>
      </motion.div>

      {/* Health Score */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-gray-100">
          <CardContent className="p-5">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-bold uppercase text-gray-400 tracking-wider">Overall Status</p>
                <p className={`text-2xl font-extrabold mt-1 ${healthColor.split(" ")[0]}`}>{healthScore}</p>
              </div>
              <Badge className={healthColor}>{issues.length === 0 ? "✓ All clear" : `${issues.length} issue${issues.length > 1 ? "s" : ""}`}</Badge>
            </div>
            {issues.length > 0 && (
              <div className="mt-3 space-y-1">
                {issues.map((issue, i) => (
                  <div key={i} className="flex items-center gap-2 text-xs text-amber-700">
                    <AlertTriangle className="h-3 w-3" /> {issue}
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Summary Cards */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { title: "Total Animals", value: totalAnimals.toLocaleString(), icon: <Bird className="h-5 w-5" />, sub: `${flocks.length} groups`, color: "bg-emerald-500" },
          { title: "Active Crops", value: String(activeCrops), icon: <Leaf className="h-5 w-5" />, sub: `${totalAcres.toFixed(1)} acres`, color: "bg-green-500" },
          { title: "Today's Output", value: String(todayOutput), icon: <TrendingUp className="h-5 w-5" />, sub: "all species", color: "bg-blue-500" },
          { title: "Monthly Profit", value: `KES ${(monthIncome - monthExpenses).toLocaleString()}`, icon: <DollarSign className="h-5 w-5" />, sub: `income: KES ${monthIncome.toLocaleString()}`, color: monthIncome >= monthExpenses ? "bg-emerald-500" : "bg-red-500" },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={fadeUp}>
            <Card className="border border-gray-100 hover:shadow-lg transition-all">
              <CardContent className="pt-5 pb-4 px-5">
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${kpi.color} text-white shadow-md mb-3`}>{kpi.icon}</div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-gray-900">{kpi.value}</p>
                <p className="text-[10px] text-gray-400 mt-0.5">{kpi.sub}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      <div className="grid lg:grid-cols-2 gap-6">
        {/* Livestock Breakdown */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-gray-100">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-sm font-bold text-gray-900">Livestock</CardTitle>
                <Link href="/flocks" className="text-xs font-semibold text-emerald-600 hover:underline">View all</Link>
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              {Object.entries(speciesGroups).map(([cat, data]) => (
                <div key={cat} className="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                  <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><data.icon className="h-4 w-4" /></div>
                    <div>
                      <p className="text-sm font-bold text-gray-900">{data.name}</p>
                      <p className="text-[10px] text-gray-400">{data.count} head</p>
                    </div>
                  </div>
                </div>
              ))}
              {Object.keys(speciesGroups).length === 0 && <p className="text-sm text-gray-400 text-center py-4">No livestock yet</p>}
              {Number(mortalityRate) > 0 && (
                <div className="flex items-center justify-between text-xs pt-2 border-t border-gray-100">
                  <span className="text-gray-400">Mortality rate</span>
                  <Badge className={Number(mortalityRate) <= 3 ? "bg-emerald-50 text-emerald-700" : Number(mortalityRate) <= 5 ? "bg-amber-50 text-amber-700" : "bg-red-50 text-red-700"}>{mortalityRate}%</Badge>
                </div>
              )}
            </CardContent>
          </Card>
        </motion.div>

        {/* Vaccinations Due */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-gray-100">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-sm font-bold text-gray-900">Vaccinations Due</CardTitle>
                <Link href="/vaccinations" className="text-xs font-semibold text-emerald-600 hover:underline">View all</Link>
              </div>
            </CardHeader>
            <CardContent className="space-y-2">
              {dueSoon.length === 0 && pendingVax.length === 0 && <p className="text-sm text-gray-400 text-center py-4">No pending vaccinations</p>}
              {dueSoon.slice(0, 5).map((v: any) => (
                <div key={v.id} className="flex items-center gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100">
                  <Syringe className="h-4 w-4 text-amber-600" />
                  <div className="flex-1">
                    <p className="text-sm font-bold text-gray-900">{v.vaccineName}</p>
                    <p className="text-[10px] text-gray-400">{v.flock?.name || "Group"} · Due {new Date(v.scheduledDate).toLocaleDateString()}</p>
                  </div>
                  <Badge className="bg-amber-100 text-amber-700 text-[10px]">Due soon</Badge>
                </div>
              ))}
              {pendingVax.length > 5 && (
                <p className="text-xs text-gray-400 text-center">+{pendingVax.length - 5} more pending</p>
              )}
            </CardContent>
          </Card>
        </motion.div>
      </div>

      {/* Crops */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-gray-100">
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <CardTitle className="text-sm font-bold text-gray-900">Crops</CardTitle>
              <Link href="/crops" className="text-xs font-semibold text-emerald-600 hover:underline">View all</Link>
            </div>
          </CardHeader>
          <CardContent>
            {crops.length === 0 ? (
              <p className="text-sm text-gray-400 text-center py-4">No crops registered yet</p>
            ) : (
              <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                {crops.slice(0, 6).map(crop => {
                  const daysLeft = crop.expectedHarvest ? Math.ceil((new Date(crop.expectedHarvest).getTime() - now.getTime()) / 86400000) : null;
                  return (
                    <div key={crop.id} className="rounded-xl border border-gray-100 p-3">
                      <p className="text-sm font-bold text-gray-900">{crop.name}</p>
                      <p className="text-[10px] text-gray-400">{crop.cropType}{crop.areaAcres ? ` · ${crop.areaAcres} acres` : ""}</p>
                      {daysLeft !== null && <p className={`text-[10px] mt-1 font-semibold ${daysLeft <= 7 ? "text-amber-600" : "text-emerald-600"}`}>{daysLeft > 0 ? `${daysLeft} days to harvest` : "Ready to harvest"}</p>}
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Actions */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-gray-100">
          <CardContent className="p-4">
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              {[
                { href: "/production", icon: Plus, label: "Log Output", color: "bg-emerald-50 text-emerald-700" },
                { href: "/flocks", icon: Bird, label: "Add Livestock", color: "bg-blue-50 text-blue-700" },
                { href: "/crops", icon: Leaf, label: "Add Crop", color: "bg-green-50 text-green-700" },
                { href: "/vaccinations", icon: Syringe, label: "Vaccinations", color: "bg-amber-50 text-amber-700" },
              ].map(a => (
                <Link key={a.href} href={a.href}>
                  <div className={`flex flex-col items-center gap-2 rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all cursor-pointer`}>
                    <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${a.color}`}><a.icon className="h-5 w-5" /></div>
                    <span className="text-xs font-bold text-gray-900">{a.label}</span>
                  </div>
                </Link>
              ))}
            </div>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}
