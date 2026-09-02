"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Egg, Wheat, AlertTriangle, TrendingUp, Plus, X, Search } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function ProductionPage() {
  const [records, setRecords] = React.useState<any[]>([]);
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [search, setSearch] = React.useState("");
  const { showToast, ToastComponent } = useToast();
  const [form, setForm] = React.useState({ flockId: "", eggsCollected: "", mortality: "", feedUsed: "", notes: "" });

  const load = () => {
    Promise.all([
      api.get("/api/production"),
      api.get("/api/flocks"),
    ]).then(([p, f]) => { setRecords(p); setFlocks(f); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const totalEggs = records.reduce((s, r) => s + r.eggsCollected, 0);
  const totalMortality = records.reduce((s, r) => s + r.mortality, 0);
  const totalFeed = records.reduce((s, r) => s + Number(r.feedUsed), 0);
  const avgEggs = records.length ? Math.round(totalEggs / records.length) : 0;

  const handleSubmit = async () => {
    await api.post("/api/production", {
      flockId: Number(form.flockId),
      eggsCollected: Number(form.eggsCollected),
      mortality: Number(form.mortality || 0),
      feedUsed: Number(form.feedUsed),
      notes: form.notes,
    });
    setForm({ flockId: "", eggsCollected: "", mortality: "", feedUsed: "", notes: "" });
    setShowForm(false);
    showToast("Production record saved!");
    load();
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const kpis = [
    { title: "Total Eggs", value: totalEggs.toLocaleString(), icon: <Egg className="h-5 w-5" />, change: "All time" },
    { title: "Avg Daily", value: avgEggs.toLocaleString(), icon: <TrendingUp className="h-5 w-5" />, change: "Per day" },
    { title: "Total Feed", value: totalFeed.toFixed(0) + " kg", icon: <Wheat className="h-5 w-5" />, change: "All time" },
    { title: "Mortality", value: String(totalMortality), icon: <AlertTriangle className="h-5 w-5" />, change: "All time" },
  ];

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Production" description="Daily egg collection and mortality tracking"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Log Production</Button>}
        />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Log Today&apos;s Production</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Flock</Label>
                  <select value={form.flockId} onChange={e => setForm({ ...form, flockId: e.target.value })} className="w-full h-10 rounded-xl border border-[#E5E7EB] px-3 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534]">
                    <option value="">Select flock</option>
                    {flocks.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
                  </select>
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Eggs Collected</Label>
                  <Input type="number" placeholder="0" value={form.eggsCollected} onChange={e => setForm({ ...form, eggsCollected: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Mortality</Label>
                  <Input type="number" placeholder="0" value={form.mortality} onChange={e => setForm({ ...form, mortality: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Feed (kg)</Label>
                  <Input type="number" placeholder="0" step="0.5" value={form.feedUsed} onChange={e => setForm({ ...form, feedUsed: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Notes</Label>
                  <Input placeholder="Optional" value={form.notes} onChange={e => setForm({ ...form, notes: e.target.value })} className="h-10 rounded-xl" />
                </div>
              </div>
              <div className="mt-4 flex gap-2">
                <Button onClick={handleSubmit} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save Record</Button>
                <Button variant="outline" onClick={() => setShowForm(false)} className="cursor-pointer">Cancel</Button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {kpis.map((kpi) => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4, scale: 1.02 }}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg hover:border-[#BBF7D0] transition-all duration-300">
              <CardContent className="pt-6 pb-4 px-5">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3">{kpi.icon}</div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A] tracking-tight">{kpi.value}</p>
                <p className="text-xs text-[#94A3B8] mt-1">{kpi.change}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
        <input placeholder="Search by flock name..." value={search} onChange={e => setSearch(e.target.value)} className="w-full h-11 rounded-xl border border-[#E5E7EB] pl-10 pr-4 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all" />
      </motion.div>

      {records.length === 0 ? <EmptyState title="No records" description="Start logging daily production." /> : (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                      <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Date</th>
                      <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Flock</th>
                      <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Eggs</th>
                      <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Mortality</th>
                      <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Feed (kg)</th>
                    </tr>
                  </thead>
                  <tbody>
                    {records.filter(r => !search || r.flock?.name?.toLowerCase().includes(search.toLowerCase())).slice(0, 30).map((r, i) => (
                      <motion.tr key={r.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.03 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                        <td className="px-5 py-3.5 text-[#0F172A] font-medium">{new Date(r.date).toLocaleDateString()}</td>
                        <td className="px-5 py-3.5 text-[#64748B]">{r.flock?.name || "-"}</td>
                        <td className="px-5 py-3.5 text-right"><span className="inline-flex items-center gap-1 font-bold text-[#166534]"><Egg className="h-3.5 w-3.5" />{r.eggsCollected.toLocaleString()}</span></td>
                        <td className="px-5 py-3.5 text-right font-semibold text-[#64748B]">{r.mortality}</td>
                        <td className="px-5 py-3.5 text-right text-[#0F172A] font-medium tabular-nums">{Number(r.feedUsed).toFixed(1)}</td>
                      </motion.tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}
      {ToastComponent}
    </div>
  );
}
