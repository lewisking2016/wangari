"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Syringe, Plus, X, CheckCircle2, Clock, AlertTriangle, Trash2, Calendar, ChevronRight } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";
import { speciesTemplates } from "@/lib/species-templates";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

export default function VaccinationsPage() {
  const [records, setRecords] = React.useState<any[]>([]);
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [filter, setFilter] = React.useState<"all" | "pending" | "completed">("all");
  const [form, setForm] = React.useState({ flockId: "", vaccineName: "", scheduledDate: "", notes: "" });
  const { showToast, ToastComponent } = useToast();

  const load = () => {
    Promise.all([api.get("/api/vaccinations"), api.get("/api/flocks")])
      .then(([v, f]) => { setRecords(Array.isArray(v) ? v : []); setFlocks(Array.isArray(f) ? f : []); setLoading(false); })
      .catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const selectedFlock = flocks.find((f: any) => String(f.id) === form.flockId);
  const speciesTemplate = selectedFlock ? Object.values(speciesTemplates).find((t: any) => t.id === selectedFlock.type) : null;
  const suggestedVaccines = speciesTemplate ? speciesTemplate.vaccinationSchedule : [];

  const handleSubmit = async () => {
    await api.post("/api/vaccinations", { ...form, flockId: Number(form.flockId), status: "pending" });
    setForm({ flockId: "", vaccineName: "", scheduledDate: "", notes: "" });
    setShowForm(false);
    showToast("Vaccination scheduled!");
    load();
  };

  const handleComplete = async (id: number) => {
    await api.patch("/api/vaccinations/" + id, { status: "completed", completedDate: new Date().toISOString() });
    showToast("Done!");
    load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete?")) return;
    await api.delete("/api/vaccinations/" + id); load();
  };

  const pending = records.filter(r => r.status === "pending");
  const completed = records.filter(r => r.status === "completed");
  const now = new Date();
  const next7 = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
  const upcoming = pending.filter(r => { const d = new Date(r.scheduledDate); return d >= now && d <= next7; });
  const overdue = pending.filter(r => new Date(r.scheduledDate) < now);

  const filtered = records.filter(r => {
    if (filter === "pending" && r.status !== "pending") return false;
    if (filter === "completed" && r.status !== "completed") return false;
    return true;
  });

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Vaccinations" description="Schedule and track group vaccinations"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Schedule</Button>} />
      </motion.div>

      {/* Form */}
      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Schedule Vaccination</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="space-y-3">
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Which group?</Label>
                  <select value={form.flockId} onChange={e => setForm({ ...form, flockId: e.target.value, vaccineName: "" })} className="w-full h-11 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option value="">Select group</option>
                    {flocks.map((f: any) => <option key={f.id} value={f.id}>{f.name} ({f.type || "unknown"})</option>)}
                  </select>
                </div>

                {/* Auto-suggested vaccines from species template */}
                {suggestedVaccines.length > 0 && !form.vaccineName && (
                  <div className="space-y-2">
                    <Label className="text-xs font-semibold text-[#64748B]">Suggested vaccines for {selectedFlock?.type}</Label>
                    <div className="space-y-1.5 max-h-48 overflow-y-auto">
                      {suggestedVaccines.map((v: any, i: number) => (
                        <button key={i} onClick={() => {
                          const days = v.daysFromStart;
                          const base = selectedFlock?.hatchDate ? new Date(selectedFlock.hatchDate) : new Date();
                          const sched = new Date(base.getTime() + days * 24 * 60 * 60 * 1000);
                          setForm({ ...form, vaccineName: v.vaccine, scheduledDate: sched.toISOString().split("T")[0], notes: v.description });
                        }}
                          className="w-full flex items-center justify-between p-3 rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] hover:bg-[#DCFCE7] text-left cursor-pointer transition-all">
                          <div>
                            <p className="text-xs font-bold text-[#0F172A]">{v.vaccine}</p>
                            <p className="text-[10px] text-[#64748B]">{v.ageLabel} - {v.description}</p>
                          </div>
                          <ChevronRight className="h-4 w-4 text-[#166534]" />
                        </button>
                      ))}
                    </div>
                  </div>
                )}

                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Vaccine</Label>
                  <Input placeholder="e.g. Newcastle Disease" value={form.vaccineName} onChange={e => setForm({ ...form, vaccineName: e.target.value })} className="h-11 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Date</Label>
                  <Input type="date" value={form.scheduledDate} onChange={e => setForm({ ...form, scheduledDate: e.target.value })} className="h-11 rounded-xl" />
                </div>
                <Button onClick={handleSubmit} disabled={!form.flockId || !form.vaccineName || !form.scheduledDate} className="w-full h-12 bg-[#166534] hover:bg-[#14532D] cursor-pointer disabled:opacity-50 font-bold">Save</Button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Reminders */}
      {(overdue.length > 0 || upcoming.length > 0) && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-3">
                <Calendar className="h-4 w-4 text-[#166534]" />
                <p className="text-xs font-bold text-[#0F172A]">Upcoming (next 7 days)</p>
              </div>
              {overdue.length > 0 && (
                <div className="space-y-1.5 mb-3">
                  {overdue.map(r => (
                    <div key={r.id} className="flex items-center justify-between p-2.5 rounded-xl bg-red-50 border border-red-200">
                      <div className="flex items-center gap-2">
                        <AlertTriangle className="h-3.5 w-3.5 text-red-500" />
                        <div>
                          <p className="text-xs font-bold text-red-700">{r.vaccineName}</p>
                          <p className="text-[10px] text-red-500">{r.flock?.name} - overdue</p>
                        </div>
                      </div>
                      <button onClick={() => handleComplete(r.id)} className="px-3 py-1.5 rounded-lg bg-[#166534] text-white text-[10px] font-bold cursor-pointer">Mark Done</button>
                    </div>
                  ))}
                </div>
              )}
              {upcoming.length > 0 ? (
                <div className="space-y-1.5">
                  {upcoming.map(r => (
                    <div key={r.id} className="flex items-center justify-between p-2.5 rounded-xl bg-amber-50 border border-amber-200">
                      <div className="flex items-center gap-2">
                        <Clock className="h-3.5 w-3.5 text-amber-600" />
                        <div>
                          <p className="text-xs font-bold text-amber-800">{r.vaccineName}</p>
                          <p className="text-[10px] text-amber-600">{r.flock?.name} - {new Date(r.scheduledDate).toLocaleDateString()}</p>
                        </div>
                      </div>
                      <button onClick={() => handleComplete(r.id)} className="px-3 py-1.5 rounded-lg bg-[#166534] text-white text-[10px] font-bold cursor-pointer">Mark Done</button>
                    </div>
                  ))}
                </div>
              ) : overdue.length === 0 && <p className="text-xs text-[#94A3B8] text-center py-2">No upcoming vaccinations</p>}
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* KPIs */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-3">
        {[
          { title: "Total", value: String(records.length), icon: <Syringe className="h-5 w-5" />, color: "bg-[#166534]" },
          { title: "Pending", value: String(pending.length), icon: <Clock className="h-5 w-5" />, color: pending.length > 0 ? "bg-amber-500" : "bg-[#166534]" },
          { title: "Done", value: String(completed.length), icon: <CheckCircle2 className="h-5 w-5" />, color: "bg-emerald-500" },
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

      {/* Filter tabs */}
      <div className="flex gap-2">
        {(["all", "pending", "completed"] as const).map(f => (
          <button key={f} onClick={() => setFilter(f)}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer capitalize ${filter === f ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>{f}</button>
        ))}
      </div>

      {/* Vaccination cards */}
      {filtered.length === 0 ? <EmptyState title="No vaccinations" description="Schedule vaccinations for your groups." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
          {filtered.map(r => {
            const isPending = r.status === "pending";
            const isOverdue = isPending && new Date(r.scheduledDate) < now;
            return (
              <motion.div key={r.id} variants={fadeUp}>
                <Card className={`border ${isOverdue ? "border-red-300 bg-red-50/30" : isPending ? "border-amber-200" : "border-[#E5E7EB]"}`}>
                  <CardContent className="p-4">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <Badge className={isPending ? (isOverdue ? "bg-red-100 text-red-700 border-red-200" : "bg-amber-50 text-amber-700 border-amber-200") : "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]"}>{isPending ? "Pending" : "Done"}</Badge>
                          <span className="text-[10px] text-[#94A3B8]">{new Date(r.scheduledDate).toLocaleDateString()}</span>
                        </div>
                        <p className="text-sm font-bold text-[#0F172A]">{r.vaccineName}</p>
                        <p className="text-[10px] text-[#64748B]">{r.flock?.name || "Unknown group"}</p>
                        {r.notes && <p className="text-[10px] text-[#94A3B8] mt-0.5">{r.notes}</p>}
                      </div>
                      <div className="flex items-center gap-2">
                        {isPending && (
                          <button onClick={() => handleComplete(r.id)} className="px-3 py-2 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-[#14532D] cursor-pointer">Done</button>
                        )}
                        <button onClick={() => handleDelete(r.id)} className="text-[#94A3B8] hover:text-red-500 cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </motion.div>
            );
          })}
        </motion.div>
      )}

      {ToastComponent}
    </div>
  );
}
