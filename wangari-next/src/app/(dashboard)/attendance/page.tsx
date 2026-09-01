"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Clock, CheckCircle2, LogIn, LogOut, Trash2, Users } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Avatar } from "@/components/ui/avatar";
import { useToast } from "@/components/shared/toast";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function AttendancePage() {
  const [records, setRecords] = React.useState<any[]>([]);
  const [workers, setWorkers] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [selectedWorker, setSelectedWorker] = React.useState("");
  const { showToast, ToastComponent } = useToast();

  const load = () => {
    Promise.all([
      fetch("/api/attendance").then(r => r.json()),
      fetch("/api/workers").then(r => r.json()),
    ]).then(([a, w]) => { setRecords(a); setWorkers(w); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const today = new Date().toISOString().split("T")[0];
  const todayRecords = records.filter(r => new Date(r.date).toISOString().split("T")[0] === today);
  const present = todayRecords.filter(r => r.checkIn).length;
  const absent = workers.length - present;

  const handleClockIn = async (workerId: string) => {
    await fetch("/api/attendance", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ workerId, action: "checkin", time: new Date().toTimeString().slice(0, 5) }),
    });
    showToast("Worker clocked in!");
    load();
  };

  const handleClockOut = async (workerId: string) => {
    await fetch("/api/attendance", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ workerId, action: "checkout", time: new Date().toTimeString().slice(0, 5) }),
    });
    showToast("Worker clocked out!");
    load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this record?")) return;
    await fetch("/api/attendance/" + id, { method: "DELETE" });
    load();
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Attendance" description="Track worker clock in/out today" />
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-4">
        {[
          { title: "Total Workers", value: String(workers.length), icon: <Users className="h-5 w-5" /> },
          { title: "Present Today", value: String(present), icon: <CheckCircle2 className="h-5 w-5" /> },
          { title: "Absent Today", value: String(absent), icon: <Clock className="h-5 w-5" /> },
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

      {/* Quick Clock In/Out */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
          <CardContent className="p-6">
            <h3 className="text-sm font-bold text-[#0F172A] mb-4">Quick Clock In/Out</h3>
            <div className="flex flex-wrap gap-2">
              {workers.map(w => {
                const todayRecord = todayRecords.find(r => r.workerId === w.id);
                const isCheckedIn = todayRecord?.checkIn && !todayRecord?.checkOut;
                return (
                  <div key={w.id} className="flex items-center gap-2 rounded-xl border border-[#E5E7EB] px-4 py-2 hover:border-[#BBF7D0] transition-colors">
                    <Avatar name={w.name} size="sm" />
                    <span className="text-sm font-medium text-[#0F172A]">{w.name}</span>
                    {todayRecord?.checkOut ? (
                      <Badge className="bg-gray-100 text-[#64748B] border-gray-200">Done</Badge>
                    ) : isCheckedIn ? (
                      <Button size="sm" onClick={() => handleClockOut(w.id)} className="h-7 px-3 text-xs bg-[#166534] hover:bg-[#14532D] cursor-pointer"><LogOut className="h-3 w-3 mr-1" />Out</Button>
                    ) : (
                      <Button size="sm" onClick={() => handleClockIn(w.id)} className="h-7 px-3 text-xs bg-[#166534] hover:bg-[#14532D] cursor-pointer"><LogIn className="h-3 w-3 mr-1" />In</Button>
                    )}
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Today's Records */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead><tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                  <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Worker</th>
                  <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Role</th>
                  <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Check In</th>
                  <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Check Out</th>
                  <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Status</th>
                </tr></thead>
                <tbody>{todayRecords.length === 0 ? (
                  <tr><td colSpan={5} className="px-5 py-8 text-center text-[#94A3B8]">No attendance records today. Use the clock in buttons above.</td></tr>
                ) : todayRecords.map((r, i) => (
                  <motion.tr key={r.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.03 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                    <td className="px-5 py-3.5"><div className="flex items-center gap-3"><Avatar name={r.worker?.name || "?"} size="sm" /><span className="font-semibold text-[#0F172A]">{r.worker?.name || "-"}</span></div></td>
                    <td className="px-5 py-3.5 text-[#64748B]">{r.worker?.role || "-"}</td>
                    <td className="px-5 py-3.5 text-center font-bold text-[#166534]">{r.checkIn || "-"}</td>
                    <td className="px-5 py-3.5 text-center font-semibold text-[#64748B]">{r.checkOut || "-"}</td>
                    <td className="px-5 py-3.5 text-center"><Badge className={r.checkOut ? "bg-gray-100 text-[#64748B]" : "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]"}>{r.checkOut ? "Completed" : "Present"}</Badge></td>
                  </motion.tr>
                ))}</tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </motion.div>
      {ToastComponent}
    </div>
  );
}
