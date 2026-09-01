"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Egg, Wheat, AlertTriangle, TrendingUp } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { EmptyState } from "@/components/shared/empty-state";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function ProductionPage() {
  const [records, setRecords] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/production").then(r => r.json()).then(d => { setRecords(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  const totalEggs = records.reduce((s, r) => s + r.eggsCollected, 0);
  const totalMortality = records.reduce((s, r) => s + r.mortality, 0);
  const totalFeed = records.reduce((s, r) => s + Number(r.feedUsed), 0);
  const avgEggs = records.length ? Math.round(totalEggs / records.length) : 0;

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const kpis = [
    { title: "Total Eggs", value: totalEggs.toLocaleString(), icon: <Egg className="h-5 w-5" />, change: "All time", color: "from-[#166534] to-[#14532D]" },
    { title: "Avg Daily", value: avgEggs.toLocaleString(), icon: <TrendingUp className="h-5 w-5" />, change: "Per day", color: "from-[#22C55E] to-[#16A34A]" },
    { title: "Total Feed", value: totalFeed.toFixed(0) + " kg", icon: <Wheat className="h-5 w-5" />, change: "All time", color: "from-[#15803D] to-[#166534]" },
    { title: "Mortality", value: String(totalMortality), icon: <AlertTriangle className="h-5 w-5" />, change: "All time", color: "from-[#14532D] to-[#0B1220]" },
  ];

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Production" description="Daily egg collection and mortality tracking" />
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {kpis.map((kpi) => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4, scale: 1.02 }}>
            <Card className="relative overflow-hidden border border-[#E5E7EB] hover:shadow-lg hover:border-[#BBF7D0] transition-all duration-300">
              <div className={`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${kpi.color}`} />
              <CardContent className="pt-6 pb-4 px-5">
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br ${kpi.color} text-white shadow-md mb-3`}>
                  {kpi.icon}
                </div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A] tracking-tight">{kpi.value}</p>
                <p className="text-xs text-[#94A3B8] mt-1">{kpi.change}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
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
                    {records.slice(0, 30).map((r, i) => (
                      <motion.tr key={r.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.03 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                        <td className="px-5 py-3.5 text-[#0F172A] font-medium">{new Date(r.date).toLocaleDateString()}</td>
                        <td className="px-5 py-3.5 text-[#64748B]">{r.flock?.name || "-"}</td>
                        <td className="px-5 py-3.5 text-right">
                          <span className="inline-flex items-center gap-1 font-bold text-[#166534]">
                            <Egg className="h-3.5 w-3.5" />{r.eggsCollected.toLocaleString()}
                          </span>
                        </td>
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
    </div>
  );
}
