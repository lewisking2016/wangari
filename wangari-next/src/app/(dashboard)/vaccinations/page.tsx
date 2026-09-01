"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Syringe, Plus, X, CheckCircle2, Clock, AlertTriangle, Trash2, Search } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/components/shared/toast";
import { EmptyState } from "@/components/shared/empty-state";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function VaccinationsPage() {
  const [records, setRecords] = React.useState<any[]>([]);
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [search, setSearch] = React.useState("");
  const [form, setForm] = React.useState({ flockId: "", vaccineName: "", scheduledDate: "", notes: "" });
  const { showToast, ToastComponent } = useToast();

  const load = () => {
    Promise.all([
      fetch("/api/vaccinations").then(r => r.json()),
      fetch("/api/flocks").then(r => r.json()),
    ]).then(([v, f]) => { setRecords(v); setFlocks(f); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const handleSubmit = async () => {
    await fetch("/api/vaccinations", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ...form, status: "pending" }),
    });
    setForm({ flockId: "", vaccineName: "", scheduledDate: "", notes: "" });
    setShowForm(false);
    showToast("Vaccination scheduled!");
    load();
  };

  const handleComplete = async (id: number) => {
    await fetch("/api/vaccinations/" + id, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status: "completed", completedDate: new Date().toISOString() }),
    });
    showToast("Vaccination marked complete!");
    load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this vaccination record?")) return;
    await fetch("/api/vaccinations/" + id, { method: "DELETE" });
    load();
  };

  const pending = records.filter(r => r.status === "pending").length;
  const completed = records.filter(r => r.status === "completed").length;
  const upcoming = records.filter(r => r.status === "pending" && new Date(r.scheduledDate) > new Date());

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Vaccinations" description="Schedule and track flock vaccinations"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Schedule Vaccination</Button>}
        />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Schedule Vaccination</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Flock</Label><select value={form.flockId} onChange={e => setForm({ ...form, flockId: e.target.value })} className="w-full h-10 rounded-xl border border-[#E5E7EB] px-3 text-sm"><option value="">Select flock</option>{flocks.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}</select></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Vaccine Name</Label><Input placeholder="e.g. Newcastle (NDV)" value={form.vaccineName} onChange={e => setForm({ ...form, vaccineName: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Scheduled Date</Label><Input type="date" value={form.scheduledDate} onChange={e => setForm({ ...form, scheduledDate: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="flex items-end"><Button onClick={handleSubmit} className="w-full bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button></div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-4">
        {[
          { title: "Total Scheduled", value: String(records.length), icon: <Syringe className="h-5 w-5" /> },
          { title: "Pending", value: String(pending), icon: <Clock className="h-5 w-5" /> },
          { title: "Completed", value: String(completed), icon: <CheckCircle2 className="h-5 w-5" /> },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4 }}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-all duration-300">
              <CardContent className="pt-6 pb-4 px-5">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3">{kpi.icon}</div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A]">{kpi.value}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {records.length === 0 ? <EmptyState title="No vaccinations" description="Schedule vaccinations for your flocks." /> : (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead><tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Date</th>
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Flock</th>
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Vaccine</th>
                    <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Status</th>
                    <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Actions</th>
                  </tr></thead>
                  <tbody>{records.map((r, i) => (
                    <motion.tr key={r.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.03 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                      <td className="px-5 py-3.5 font-medium text-[#0F172A]">{new Date(r.scheduledDate).toLocaleDateString()}</td>
                      <td className="px-5 py-3.5 text-[#64748B]">{r.flock?.name || "-"}</td>
                      <td className="px-5 py-3.5 font-semibold text-[#0F172A]">{r.vaccineName}</td>
                      <td className="px-5 py-3.5 text-center"><Badge className={r.status === "completed" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-amber-50 text-amber-700 border-amber-200"}>{r.status}</Badge></td>
                      <td className="px-5 py-3.5 text-center"><div className="flex items-center justify-center gap-2">{r.status === "pending" && <button onClick={() => handleComplete(r.id)} className="text-[#166534] hover:text-[#14532D] cursor-pointer" title="Mark complete"><CheckCircle2 className="h-4 w-4" /></button>}<button onClick={() => handleDelete(r.id)} className="text-[#94A3B8] hover:text-red-500 cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button></div></td>
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
