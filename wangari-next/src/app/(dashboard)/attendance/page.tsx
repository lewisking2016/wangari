"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Clock, CheckCircle2, LogIn, LogOut, Trash2, Users, Calendar, DollarSign, AlertTriangle } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar } from "@/components/ui/avatar";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

export default function AttendancePage() {
  const [records, setRecords] = React.useState<any[]>([]);
  const [workers, setWorkers] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [selectedDate, setSelectedDate] = React.useState(new Date().toISOString().split("T")[0]);
  const { showToast, ToastComponent } = useToast();

  const load = () => {
    Promise.all([api.get("/api/attendance"), api.get("/api/workers")])
      .then(([a, w]) => { setRecords(Array.isArray(a) ? a : []); setWorkers(Array.isArray(w) ? w : []); setLoading(false); })
      .catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const isToday = selectedDate === new Date().toISOString().split("T")[0];
  const dayRecords = records.filter(r => new Date(r.date).toISOString().split("T")[0] === selectedDate);
  const present = dayRecords.filter(r => r.checkIn).length;
  const checkedOut = dayRecords.filter(r => r.checkOut).length;
  const absent = workers.filter(w => w.status === "active").length - present;

  // Wage calculation for the day
  const dayWages = dayRecords.reduce((s, r) => s + (r.worker?.dailyWage ? Number(r.worker.dailyWage) : 0), 0);

  const handleClockInOut = async (workerId: number) => {
    await api.post("/api/attendance", { workerId, date: new Date().toISOString() });
    const worker = workers.find(w => w.id === workerId);
    const existing = dayRecords.find(r => r.workerId === workerId);
    showToast(existing?.checkIn && !existing?.checkOut ? `${worker?.name} clocked out` : `${worker?.name} clocked in`);
    load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this record?")) return;
    await api.delete("/api/attendance/" + id); load();
  };

  // Date navigation
  const changeDate = (delta: number) => {
    const d = new Date(selectedDate);
    d.setDate(d.getDate() + delta);
    setSelectedDate(d.toISOString().split("T")[0]);
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Attendance" description="Track worker clock in and out" />
      </motion.div>

      {/* Date picker */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="flex items-center justify-between p-4">
            <button onClick={() => changeDate(-1)} className="px-3 py-2 rounded-xl bg-[#F1F5F9] text-[#64748B] text-sm font-bold cursor-pointer">Prev</button>
            <div className="text-center">
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-[#166534]" />
                <p className="text-sm font-bold text-[#0F172A]">{new Date(selectedDate + "T00:00:00").toLocaleDateString("en-US", { weekday: "long", month: "short", day: "numeric" })}</p>
              </div>
              {isToday && <p className="text-[10px] text-[#166534] font-bold mt-0.5">Today</p>}
            </div>
            <button onClick={() => changeDate(1)} disabled={isToday} className="px-3 py-2 rounded-xl bg-[#F1F5F9] text-[#64748B] text-sm font-bold cursor-pointer disabled:opacity-30">Next</button>
          </CardContent>
        </Card>
      </motion.div>

      {/* KPIs */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {[
          { title: "Present", value: String(present), icon: <CheckCircle2 className="h-5 w-5" />, color: "bg-emerald-500" },
          { title: "Absent", value: String(Math.max(0, absent)), icon: <AlertTriangle className="h-5 w-5" />, color: absent > 0 ? "bg-red-500" : "bg-[#166534]" },
          { title: "Checked Out", value: String(checkedOut), icon: <LogOut className="h-5 w-5" />, color: "bg-[#166534]" },
          { title: "Day Wages", value: `KES ${dayWages.toLocaleString()}`, icon: <DollarSign className="h-5 w-5" />, color: "bg-amber-500" },
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

      {/* Quick clock in/out — only show on today */}
      {isToday && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-4">
              <p className="text-xs font-bold text-[#0F172A] mb-3">Quick Clock In/Out</p>
              <div className="space-y-2">
                {workers.filter(w => w.status === "active").map(w => {
                  const todayRec = dayRecords.find(r => r.workerId === w.id);
                  const isCheckedIn = todayRec?.checkIn && !todayRec?.checkOut;
                  const isDone = todayRec?.checkIn && todayRec?.checkOut;
                  return (
                    <div key={w.id} className="flex items-center justify-between p-3 rounded-xl bg-[#F8FAFC] border border-[#E5E7EB]">
                      <div className="flex items-center gap-3">
                        <Avatar name={w.name} size="sm" />
                        <div>
                          <p className="text-sm font-bold text-[#0F172A]">{w.name}</p>
                          <p className="text-[10px] text-[#94A3B8]">{w.role || "Worker"}</p>
                        </div>
                      </div>
                      {isDone ? (
                        <div className="text-right">
                          <Badge className="bg-gray-100 text-[#64748B] border-gray-200">Done</Badge>
                          <p className="text-[9px] text-[#94A3B8] mt-0.5">{todayRec.checkIn}-{todayRec.checkOut}</p>
                        </div>
                      ) : isCheckedIn ? (
                        <button onClick={() => handleClockInOut(w.id)}
                          className="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 hover:bg-amber-100 cursor-pointer">
                          <LogOut className="h-3.5 w-3.5" />Clock Out
                        </button>
                      ) : (
                        <button onClick={() => handleClockInOut(w.id)}
                          className="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-[#14532D] cursor-pointer">
                          <LogIn className="h-3.5 w-3.5" />Clock In
                        </button>
                      )}
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Records for selected date */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <p className="text-xs font-bold text-[#64748B] uppercase tracking-wider">Records ({dayRecords.length})</p>
      </motion.div>

      {dayRecords.length === 0 ? <EmptyState title="No records" description={isToday ? "Use the clock in buttons above." : "No attendance on this date."} /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
          {dayRecords.map(r => (
            <motion.div key={r.id} variants={fadeUp}>
              <Card className="border border-[#E5E7EB]">
                <CardContent className="p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                      <Avatar name={r.worker?.name || "?"} size="md" />
                      <div>
                        <p className="text-sm font-bold text-[#0F172A]">{r.worker?.name || "Unknown"}</p>
                        <p className="text-[10px] text-[#94A3B8]">{r.worker?.role || "Worker"}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <Badge className={r.checkOut ? "bg-gray-100 text-[#64748B] border-gray-200" : "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]"}>{r.checkOut ? "Completed" : "Present"}</Badge>
                    </div>
                  </div>
                  <div className="flex items-center justify-between mt-3">
                    <div className="flex gap-4">
                      <div className="text-center">
                        <p className="text-[9px] text-[#94A3B8]">In</p>
                        <p className="text-sm font-bold text-[#166534]">{r.checkIn || "--:--"}</p>
                      </div>
                      <div className="text-center">
                        <p className="text-[9px] text-[#94A3B8]">Out</p>
                        <p className="text-sm font-bold text-[#64748B]">{r.checkOut || "--:--"}</p>
                      </div>
                      {r.worker?.dailyWage && (
                        <div className="text-center">
                          <p className="text-[9px] text-[#94A3B8]">Wage</p>
                          <p className="text-sm font-bold text-[#0F172A]">KES {Number(r.worker.dailyWage).toLocaleString()}</p>
                        </div>
                      )}
                    </div>
                    <button onClick={() => handleDelete(r.id)} className="text-[#94A3B8] hover:text-red-500 cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button>
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
