"use client";

import * as React from "react";
import { motion } from "framer-motion";
import { Users, Plus, Briefcase, DollarSign, TrendingUp } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar } from "@/components/ui/avatar";
import { EmptyState } from "@/components/shared/empty-state";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function WorkersPage() {
  const [workers, setWorkers] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/workers").then(r => r.json()).then(d => { setWorkers(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const totalWages = workers.reduce((s, w) => s + Number(w.dailyWage || w.wage || 0), 0);

  const kpis = [
    { title: "Total Workers", value: String(workers.length), icon: <Users className="h-5 w-5" />, color: "from-[#166534] to-[#14532D]" },
    { title: "Daily Wages", value: "KES " + totalWages.toLocaleString(), icon: <DollarSign className="h-5 w-5" />, color: "from-[#22C55E] to-[#16A34A]" },
    { title: "Monthly Cost", value: "KES " + (totalWages * 30).toLocaleString(), icon: <TrendingUp className="h-5 w-5" />, color: "from-[#15803D] to-[#166534]" },
  ];

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader
          title="Workers"
          description="Manage farm workers, attendance, and wages."
          action={<Button className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" /> Add Worker</Button>}
        />
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-4">
        {kpis.map((kpi) => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4 }}>
            <Card className="relative overflow-hidden border border-[#E5E7EB] hover:shadow-lg transition-all duration-300">
              <div className={`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${kpi.color}`} />
              <CardContent className="pt-6 pb-4 px-5">
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br ${kpi.color} text-white shadow-md mb-3`}>
                  {kpi.icon}
                </div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A] tracking-tight">{kpi.value}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {workers.length === 0 ? <EmptyState title="No workers" description="Add your first worker to get started." /> : (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardHeader className="pb-2">
              <div className="flex items-center gap-2">
                <Briefcase className="h-4 w-4 text-[#166534]" />
                <CardTitle className="text-base font-bold">Team Members</CardTitle>
              </div>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                      <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Name</th>
                      <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Role</th>
                      <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Daily Wage</th>
                      <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Phone</th>
                      <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {workers.map((w, i) => (
                      <motion.tr key={w.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.05 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                        <td className="px-5 py-3.5">
                          <div className="flex items-center gap-3">
                            <Avatar name={w.name} size="sm" />
                            <span className="font-semibold text-[#0F172A]">{w.name}</span>
                          </div>
                        </td>
                        <td className="px-5 py-3.5 text-[#64748B]">{w.role}</td>
                        <td className="px-5 py-3.5 text-right font-bold text-[#0F172A] tabular-nums">KES {Number(w.dailyWage || w.wage || 0).toLocaleString()}</td>
                        <td className="px-5 py-3.5 text-[#64748B]">{w.phone || "-"}</td>
                        <td className="px-5 py-3.5 text-center">
                          <Badge className={w.status === "active" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-gray-100 text-gray-600"}>{w.status || "active"}</Badge>
                        </td>
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
