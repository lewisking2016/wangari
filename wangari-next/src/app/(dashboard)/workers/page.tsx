"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Users, Plus, Briefcase, DollarSign, TrendingUp, X, Trash2 } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Avatar } from "@/components/ui/avatar";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function WorkersPage() {
  const [workers, setWorkers] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const { showToast, ToastComponent } = useToast();
  const [form, setForm] = React.useState({ name: "", role: "", phone: "", dailyWage: "" });

  const load = () => {
    fetch("/api/workers").then(r => r.json()).then(d => { setWorkers(d); setLoading(false); }).catch(() => setLoading(false));
  };
  const handleDelete = async (id: number) => {
    if (!confirm("Delete this worker?")) return;
    await fetch("/api/workers/" + id, { method: "DELETE" });
    load();
  };
  React.useEffect(() => { load(); }, []);

  const totalWages = workers.reduce((s, w) => s + Number(w.dailyWage || w.wage || 0), 0);

  const handleSubmit = async () => {
    await fetch("/api/workers", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ...form, dailyWage: Number(form.dailyWage), status: "active" }),
    });
    setForm({ name: "", role: "", phone: "", dailyWage: "" });
    setShowForm(false);
    showToast("Worker added!");
    load();
  };

  const kpis = [
    { title: "Total Workers", value: String(workers.length), icon: <Users className="h-5 w-5" /> },
    { title: "Daily Wages", value: "KES " + totalWages.toLocaleString(), icon: <DollarSign className="h-5 w-5" /> },
    { title: "Monthly Cost", value: "KES " + (totalWages * 30).toLocaleString(), icon: <TrendingUp className="h-5 w-5" /> },
  ];

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Workers" description="Manage farm workers, attendance, and wages."
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" /> Add Worker</Button>}
        />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Add New Worker</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Full Name</Label><Input placeholder="e.g. Peter Ochieng" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Role</Label><Input placeholder="e.g. Farm Manager" value={form.role} onChange={e => setForm({ ...form, role: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Phone</Label><Input placeholder="+254 7XX XXX XXX" value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Daily Wage (KES)</Label><Input type="number" placeholder="0" value={form.dailyWage} onChange={e => setForm({ ...form, dailyWage: e.target.value })} className="h-10 rounded-xl" /></div>
              </div>
              <div className="mt-4 flex gap-2">
                <Button onClick={handleSubmit} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button>
                <Button variant="outline" onClick={() => setShowForm(false)} className="cursor-pointer">Cancel</Button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-4">
        {kpis.map((kpi) => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4 }}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-all duration-300">
              <CardContent className="pt-6 pb-4 px-5">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3">{kpi.icon}</div>
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
            <CardHeader className="pb-2"><div className="flex items-center gap-2"><Briefcase className="h-4 w-4 text-[#166534]" /><CardTitle className="text-base font-bold">Team Members</CardTitle></div></CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead><tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Name</th>
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Role</th>
                    <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Daily Wage</th>
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Phone</th>
                    <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Status</th>
                  </tr></thead>
                  <tbody>{workers.map((w, i) => (
                    <motion.tr key={w.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.05 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                      <td className="px-5 py-3.5"><div className="flex items-center gap-3"><Avatar name={w.name} size="sm" /><span className="font-semibold text-[#0F172A]">{w.name}</span></div></td>
                      <td className="px-5 py-3.5 text-[#64748B]">{w.role}</td>
                      <td className="px-5 py-3.5 text-right font-bold text-[#0F172A] tabular-nums">KES {Number(w.dailyWage || w.wage || 0).toLocaleString()}</td>
                      <td className="px-5 py-3.5 text-[#64748B]">{w.phone || "-"}</td>
                      <td className="px-5 py-3.5 text-center"><div className="flex items-center justify-center gap-2"><Badge className={w.status === "active" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-gray-100 text-gray-600"}>{w.status || "active"}</Badge><button onClick={() => handleDelete(w.id)} className="text-[#94A3B8] hover:text-red-500 transition-colors cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button></div></td>
                    </motion.tr>
                  ))}</tbody>
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
