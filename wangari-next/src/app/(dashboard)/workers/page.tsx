"use client";
import * as React from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Users, Plus, DollarSign, TrendingUp, X, Trash2, Phone, Briefcase, UserCheck, UserX, Edit3, Clock, Calendar, CheckCircle2, Save } from "lucide-react";
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
  const [attendance, setAttendance] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [step, setStep] = React.useState(1);
  const [filter, setFilter] = React.useState<"all" | "active" | "inactive">("all");
  const [editingId, setEditingId] = React.useState<number | null>(null);
  const [selectedWorker, setSelectedWorker] = React.useState<any>(null);
  const { showToast, ToastComponent } = useToast();

  const [form, setForm] = React.useState({ name: "", role: "Farmhand", phone: "", dailyWage: "" });

  const load = () => {
    Promise.all([api.get("/api/workers"), api.get("/api/attendance")])
      .then(([w, a]) => {
        setWorkers(Array.isArray(w) ? w : []);
        setAttendance(Array.isArray(a) ? a : []);
        setLoading(false);
      }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const resetForm = () => { setForm({ name: "", role: "Farmhand", phone: "", dailyWage: "" }); setEditingId(null); };

  const handleSubmit = async () => {
    const payload = { ...form, dailyWage: Number(form.dailyWage), status: "active" };
    if (editingId) {
      await api.patch(`/api/workers/${editingId}`, payload);
      showToast("Worker updated!");
    } else {
      await api.post("/api/workers", payload);
      showToast("Worker added!");
    }
    resetForm(); setStep(1); setShowForm(false); load();
  };

  const openEdit = (w: any) => {
    setEditingId(w.id);
    setForm({ name: w.name || "", role: w.role || "Farmhand", phone: w.phone || "", dailyWage: String(w.dailyWage || "") });
    setShowForm(true); setStep(1);
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
    if (selectedWorker?.id === id) setSelectedWorker(null);
  };

  const filtered = workers.filter(w => {
    if (filter === "active" && w.status !== "active") return false;
    if (filter === "inactive" && w.status === "active") return false;
    return true;
  });

  const activeWorkers = workers.filter(w => w.status === "active");
  const totalDailyWages = activeWorkers.reduce((s, w) => s + Number(w.dailyWage || 0), 0);
  const monthlyCost = totalDailyWages * 30;

  // Get attendance for selected worker
  const workerAttendance = selectedWorker
    ? attendance.filter(r => r.workerId === selectedWorker.id).slice(0, 14)
    : [];

  // Worker detail view
  if (selectedWorker) {
    const w = workers.find((wr: any) => wr.id === selectedWorker.id) || selectedWorker;
    const workerAtts = attendance.filter(r => r.workerId === w.id);
    const totalDaysWorked = workerAtts.filter(r => r.checkIn).length;
    const totalWages = workerAtts.reduce((s, r) => s + Number(r.worker?.dailyWage || w.dailyWage || 0), 0);

    return (
      <div className="space-y-6">
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <button onClick={() => setSelectedWorker(null)} className="text-sm text-gray-500 hover:text-gray-700 cursor-pointer mb-2">← Back to Workers</button>
        </motion.div>

        {/* Worker header */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Avatar name={w.name} size="lg" />
            <div>
              <h1 className="text-2xl font-bold text-gray-900">{w.name}</h1>
              <p className="text-sm text-gray-400">{w.role || "Worker"}{w.phone ? ` • ${w.phone}` : ""}</p>
            </div>
          </div>
          <div className="flex gap-2">
            <Badge className={w.status === "active" ? "bg-emerald-50 text-emerald-700 border-emerald-200" : "bg-gray-100 text-gray-500"}>{w.status}</Badge>
            <Button onClick={() => openEdit(w)} variant="ghost" size="sm" className="gap-1 cursor-pointer"><Edit3 className="h-4 w-4" />Edit</Button>
          </div>
        </motion.div>

        {/* Worker stats */}
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          {[
            { title: "Daily Wage", value: `KES ${Number(w.dailyWage || 0).toLocaleString()}`, icon: <DollarSign className="h-5 w-5" />, color: "bg-[#166534]" },
            { title: "Days Worked", value: String(totalDaysWorked), icon: <Calendar className="h-5 w-5" />, color: "bg-blue-500" },
            { title: "Total Earned", value: `KES ${totalWages.toLocaleString()}`, icon: <TrendingUp className="h-5 w-5" />, color: "bg-emerald-500" },
            { title: "Monthly Est.", value: `KES ${(Number(w.dailyWage || 0) * 30).toLocaleString()}`, icon: <Clock className="h-5 w-5" />, color: "bg-amber-500" },
          ].map(kpi => (
            <motion.div key={kpi.title} variants={fadeUp}>
              <Card className="border border-gray-100">
                <CardContent className="pt-4 pb-3 px-4">
                  <div className={`flex h-9 w-9 items-center justify-center rounded-xl ${kpi.color} text-white mb-2`}>{kpi.icon}</div>
                  <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{kpi.title}</p>
                  <p className="text-xl font-extrabold text-gray-900">{kpi.value}</p>
                </CardContent>
              </Card>
            </motion.div>
          ))}
        </motion.div>

        {/* Attendance history */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-gray-100">
            <CardContent className="p-5">
              <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4">Recent Attendance</h3>
              {workerAtts.length === 0 ? (
                <p className="text-sm text-gray-400 text-center py-4">No attendance records yet</p>
              ) : (
                <div className="space-y-2">
                  {workerAtts.slice(0, 14).map((r: any) => (
                    <div key={r.id} className="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                      <div className="flex items-center gap-3">
                        <div className={`h-8 w-8 rounded-lg flex items-center justify-center ${r.checkOut ? "bg-emerald-100 text-emerald-600" : "bg-amber-100 text-amber-600"}`}>
                          {r.checkOut ? <CheckCircle2 className="h-4 w-4" /> : <Clock className="h-4 w-4" />}
                        </div>
                        <div>
                          <p className="text-xs font-bold text-gray-900">{new Date(r.date).toLocaleDateString("en-KE", { weekday: "short", month: "short", day: "numeric" })}</p>
                          <p className="text-[10px] text-gray-400">{r.checkIn || "--:--"} → {r.checkOut || "--:--"}</p>
                        </div>
                      </div>
                      <Badge className={r.checkOut ? "bg-gray-100 text-gray-600" : "bg-emerald-50 text-emerald-700"}>{r.checkOut ? "Full day" : "Present"}</Badge>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </motion.div>

        {/* Quick actions */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-gray-100">
            <CardContent className="p-5">
              <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4">Quick Actions</h3>
              <div className="grid grid-cols-2 gap-3">
                <button onClick={() => openEdit(w)} className="flex items-center gap-2 p-3 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold hover:bg-blue-100 cursor-pointer">
                  <Edit3 className="h-4 w-4" />Edit Details
                </button>
                <button onClick={() => handleToggleStatus(w.id, w.status)} className={`flex items-center gap-2 p-3 rounded-xl text-xs font-bold cursor-pointer ${w.status === "active" ? "bg-red-50 text-red-600 hover:bg-red-100" : "bg-emerald-50 text-emerald-600 hover:bg-emerald-100"}`}>
                  {w.status === "active" ? <><UserX className="h-4 w-4" />Deactivate</> : <><UserCheck className="h-4 w-4" />Activate</>}
                </button>
              </div>
            </CardContent>
          </Card>
        </motion.div>

        {ToastComponent}
      </div>
    );
  }

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Workers" description="Manage farm workers, wages, and attendance"
          action={<Button onClick={() => { resetForm(); setShowForm(!showForm); setStep(1); }} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Worker</Button>} />
      </motion.div>

      {/* Form */}
      <AnimatePresence>
        {showForm && (
          <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }} exit={{ opacity: 0, height: 0 }}>
            <Card className="border border-[#E5E7EB]">
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-3">
                    <h3 className="text-sm font-bold text-[#0F172A]">{editingId ? "Edit Worker" : "Add Worker"}</h3>
                    <div className="flex gap-1">{[1, 2].map(s => <div key={s} className={`h-1.5 w-8 rounded-full ${step >= s ? "bg-[#166534]" : "bg-gray-200"}`} />)}</div>
                  </div>
                  <button onClick={() => { setShowForm(false); resetForm(); }} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
                </div>

                {step === 1 && (
                  <div className="space-y-3">
                    <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Full name *</Label><Input placeholder="e.g. Peter Ochieng" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} className="h-12 rounded-xl text-base" autoFocus /></div>
                    <Label className="text-xs font-semibold text-[#64748B]">Role</Label>
                    <div className="flex flex-wrap gap-2">
                      {ROLES.map(r => (
                        <button key={r} onClick={() => setForm({ ...form, role: r })}
                          className={`px-3 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${form.role === r ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>{r}</button>
                      ))}
                    </div>
                    <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Phone</Label><Input placeholder="+254 7XX XXX XXX" value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })} className="h-11 rounded-xl" /></div>
                    <Button onClick={() => form.name && setStep(2)} disabled={!form.name} className="w-full h-11 cursor-pointer">Next</Button>
                  </div>
                )}

                {step === 2 && (
                  <div className="space-y-3">
                    <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Daily wage (KES) *</Label><Input type="number" placeholder="e.g. 500" value={form.dailyWage} onChange={e => setForm({ ...form, dailyWage: e.target.value })} className="h-12 rounded-xl text-lg font-bold text-center" /></div>
                    {form.dailyWage && (
                      <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-3 text-center">
                        <p className="text-xs text-[#64748B]">Monthly estimate (30 days)</p>
                        <p className="text-lg font-extrabold text-[#166534]">KES {(Number(form.dailyWage) * 30).toLocaleString()}</p>
                      </div>
                    )}
                    <div className="flex gap-2">
                      <Button onClick={() => setStep(1)} variant="outline" className="flex-1 cursor-pointer">Back</Button>
                      <Button onClick={handleSubmit} disabled={!form.name} className="flex-1 h-11 bg-[#166534] hover:bg-[#14532D] cursor-pointer">{editingId ? "Update" : "Save"}</Button>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </motion.div>
        )}
      </AnimatePresence>

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
          {filtered.map(w => {
            const wAtts = attendance.filter(r => r.workerId === w.id);
            const daysWorked = wAtts.length;
            return (
              <motion.div key={w.id} variants={fadeUp}>
                <Card className="border border-[#E5E7EB] hover:shadow-md transition-all cursor-pointer" onClick={() => setSelectedWorker(w)}>
                  <CardContent className="p-4">
                    <div className="flex items-start justify-between">
                      <div className="flex items-center gap-3">
                        <Avatar name={w.name} size="md" />
                        <div>
                          <h3 className="text-sm font-bold text-[#0F172A]">{w.name}</h3>
                          <p className="text-[10px] text-[#94A3B8]">{w.role || "No role"}{w.phone ? ` • ${w.phone}` : ""}</p>
                        </div>
                      </div>
                      <div className="text-right">
                        <p className="text-lg font-extrabold text-[#0F172A]">KES {Number(w.dailyWage || 0).toLocaleString()}</p>
                        <p className="text-[9px] text-[#94A3B8]">per day</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3 mt-3 text-[10px] text-[#94A3B8]">
                      <Badge className={w.status === "active" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-gray-100 text-[#64748B] border-gray-200"}>{w.status}</Badge>
                      <span>{daysWorked} days worked</span>
                      <span className="ml-auto text-[#94A3B8]">→</span>
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
