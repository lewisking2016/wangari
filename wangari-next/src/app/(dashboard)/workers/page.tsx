"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Users, Plus, DollarSign, TrendingUp, X, Trash2, Phone, Briefcase, UserCheck, UserX } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Avatar } from "@/components/ui/avatar";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const ROLES = ["Farm Manager", "Herdsman", "Farmhand", "Veterinary", "Driver", "Other"];

export default function WorkersPage() {
  const [workers, setWorkers] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [step, setStep] = React.useState(1);
  const [filter, setFilter] = React.useState<"all" | "active" | "inactive">("all");
  const { showToast, ToastComponent } = useToast();
  const [form, setForm] = React.useState({ name: "", role: "Farmhand", phone: "", dailyWage: "" });

  const load = () => {
    api.get("/api/workers").then(d => { setWorkers(Array.isArray(d) ? d : []); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const handleSubmit = async () => {
    await api.post("/api/workers", { ...form, dailyWage: Number(form.dailyWage), status: "active" });
    setForm({ name: "", role: "Farmhand", phone: "", dailyWage: "" });
    setStep(1); setShowForm(false); showToast("Worker added!"); load();
  };

  const handleToggleStatus = async (id: number, currentStatus: string) => {
    const newStatus = currentStatus === "active" ? "inactive" : "active";
    await api.patch(`/api/workers/${id}`, { status: newStatus });
    showToast(newStatus === "active" ? "Worker activated" : "Worker deactivated");
    load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this worker?")) return;
    await api.delete("/api/workers/" + id); load();
  };

  const filtered = workers.filter(w => {
    if (filter === "active" && w.status !== "active") return false;
    if (filter === "inactive" && w.status === "active") return false;
    return true;
  });

  const activeWorkers = workers.filter(w => w.status === "active");
  const totalDailyWages = activeWorkers.reduce((s, w) => s + Number(w.dailyWage || 0), 0);
  const monthlyCost = totalDailyWages * 30;

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Workers" description="Manage farm workers and wages"
          action={<Button onClick={() => { setShowForm(!showForm); setStep(1); }} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Worker</Button>} />
      </motion.div>

      {/* Form — step by step */}
      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                  <h3 className="text-sm font-bold text-[#0F172A]">Add Worker</h3>
                  <div className="flex gap-1">{[1, 2].map(s => <div key={s} className={`h-1.5 w-8 rounded-full ${step >= s ? "bg-[#166534]" : "bg-gray-200"}`} />)}</div>
                </div>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>

              {step === 1 && (
                <div className="space-y-3">
                  <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Full name</Label><Input placeholder="e.g. Peter Ochieng" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} className="h-12 rounded-xl text-base" autoFocus /></div>
                  <Label className="text-xs font-semibold text-[#64748B]">Role</Label>
                  <div className="grid grid-cols-3 gap-2">
                    {ROLES.map(r => (
                      <button key={r} onClick={() => setForm({ ...form, role: r })}
                        className={`py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${form.role === r ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>{r}</button>
                    ))}
                  </div>
                  <Button onClick={() => form.name && setStep(2)} disabled={!form.name} className="w-full h-11 cursor-pointer">Next</Button>
                </div>
              )}

              {step === 2 && (
                <div className="space-y-3">
                  <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Phone (optional)</Label><Input placeholder="+254 7XX XXX XXX" value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })} className="h-11 rounded-xl" /></div>
                  <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Daily wage (KES)</Label><Input type="number" placeholder="0" value={form.dailyWage} onChange={e => setForm({ ...form, dailyWage: e.target.value })} className="h-12 rounded-xl text-lg font-bold text-center" /></div>
                  {form.dailyWage && (
                    <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-3 text-center">
                      <p className="text-xs text-[#64748B]">Monthly estimate</p>
                      <p className="text-lg font-extrabold text-[#166534]">KES {(Number(form.dailyWage) * 30).toLocaleString()}</p>
                    </div>
                  )}
                  <div className="flex gap-2">
                    <Button onClick={() => setStep(1)} variant="outline" className="flex-1 cursor-pointer">Back</Button>
                    <Button onClick={handleSubmit} disabled={!form.name} className="flex-1 h-11 bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* KPIs */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-3 gap-3">
        {[
          { title: "Active Workers", value: String(activeWorkers.length), icon: <Users className="h-5 w-5" />, color: "bg-[#166534]" },
          { title: "Daily Wages", value: `KES ${totalDailyWages.toLocaleString()}`, icon: <DollarSign className="h-5 w-5" />, color: "bg-emerald-500" },
          { title: "Monthly Estimate", value: `KES ${monthlyCost.toLocaleString()}`, icon: <TrendingUp className="h-5 w-5" />, color: "bg-amber-500" },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={fadeUp}>
            <Card className="border border-[#E5E7EB]">
              <CardContent className="pt-4 pb-3 px-4">
                <div className={`flex h-9 w-9 items-center justify-center rounded-xl ${kpi.color} text-white mb-2`}>{kpi.icon}</div>
                <p className="text-[10px] font-semibold uppercase tracking-wider text-[#64748B]">{kpi.title}</p>
                <p className="text-xl font-extrabold text-[#0F172A]">{kpi.value}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {/* Filter */}
      <div className="flex gap-2">
        {(["all", "active", "inactive"] as const).map(f => (
          <button key={f} onClick={() => setFilter(f)}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer capitalize ${filter === f ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>{f}</button>
        ))}
      </div>

      {/* Worker cards */}
      {filtered.length === 0 ? <EmptyState title="No workers" description="Add your first worker." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
          {filtered.map(w => (
            <motion.div key={w.id} variants={fadeUp}>
              <Card className="border border-[#E5E7EB]">
                <CardContent className="p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                      <Avatar name={w.name} size="md" />
                      <div>
                        <h3 className="text-sm font-bold text-[#0F172A]">{w.name}</h3>
                        <p className="text-[10px] text-[#94A3B8]">{w.role || "No role"}</p>
                        {w.phone && <p className="text-[10px] text-[#94A3B8] flex items-center gap-1 mt-0.5"><Phone className="h-2.5 w-2.5" />{w.phone}</p>}
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-lg font-extrabold text-[#0F172A]">KES {Number(w.dailyWage || 0).toLocaleString()}</p>
                      <p className="text-[9px] text-[#94A3B8]">per day</p>
                    </div>
                  </div>
                  <div className="flex gap-2 mt-3">
                    <Badge className={w.status === "active" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-gray-100 text-[#64748B] border-gray-200"}>{w.status || "active"}</Badge>
                    <button onClick={() => handleToggleStatus(w.id, w.status || "active")}
                      className={`flex items-center gap-1 px-3 py-1 rounded-lg text-[10px] font-bold border cursor-pointer ${w.status === "active" ? "bg-red-50 text-red-600 border-red-200" : "bg-emerald-50 text-emerald-600 border-emerald-200"}`}>
                      {w.status === "active" ? <><UserX className="h-3 w-3" />Deactivate</> : <><UserCheck className="h-3 w-3" />Activate</>}
                    </button>
                    <button onClick={() => handleDelete(w.id)} className="ml-auto text-[#94A3B8] hover:text-red-500 cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          ))}
        </motion.div>
      )}
      {ToastComponent}
    </div>
  );
}
