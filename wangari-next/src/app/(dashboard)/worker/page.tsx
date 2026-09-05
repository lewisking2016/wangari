"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  Egg,
  Milk,
  Wheat,
  Heart,
  CheckCircle2,
  ListTodo,
  RefreshCw,
  Plus,
  ArrowRight,
  ShieldCheck,
} from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import api from "@/lib/api-client";
import { WorkerQuickLogModal } from "@/components/worker/WorkerQuickLogModal";
import { WorkerTaskCard } from "@/components/worker/WorkerTaskCard";
import { useAuth } from "@/hooks/useAuth";

export default function WorkerDashboardPage() {
  const { user } = useAuth();
  const [tasks, setTasks] = React.useState<any[]>([]);
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [activities, setActivities] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [refreshing, setRefreshing] = React.useState(false);

  // Quick Log Modal state
  const [modalOpen, setModalOpen] = React.useState(false);
  const [logType, setLogType] = React.useState<"eggs" | "milk" | "feed" | "mortality">("eggs");

  const fetchData = async () => {
    try {
      const [tasksRes, flocksRes, activityRes] = await Promise.allSettled([
        api.get("/api/worker/tasks"),
        api.get("/api/flocks"),
        api.get("/api/worker/my-activity"),
      ]);

      if (tasksRes.status === "fulfilled" && Array.isArray(tasksRes.value)) {
        setTasks(tasksRes.value);
      }
      if (flocksRes.status === "fulfilled" && Array.isArray(flocksRes.value)) {
        setFlocks(flocksRes.value);
      }
      if (activityRes.status === "fulfilled" && Array.isArray(activityRes.value)) {
        setActivities(activityRes.value);
      }
    } catch (err) {
      console.error("Worker dashboard fetch error:", err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  React.useEffect(() => {
    fetchData();
  }, []);

  const handleRefresh = () => {
    setRefreshing(true);
    fetchData();
  };

  const handleToggleTask = async (taskId: number) => {
    // Optimistic UI update
    setTasks((prev) =>
      prev.map((t) => (t.id === taskId ? { ...t, isCompleted: !t.isCompleted } : t))
    );
    try {
      await api.post(`/api/worker/tasks/${taskId}/complete`, {});
      fetchData();
    } catch (err) {
      console.error("Failed to complete task:", err);
      fetchData(); // Rollback on error
    }
  };

  const openLog = (type: "eggs" | "milk" | "feed" | "mortality") => {
    setLogType(type);
    setModalOpen(true);
  };

  const completedCount = tasks.filter((t) => t.isCompleted).length;
  const totalTasks = tasks.length;
  const progressPct = totalTasks > 0 ? Math.round((completedCount / totalTasks) * 100) : 100;

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-3">
        <RefreshCw className="h-10 w-10 text-[#166534] animate-spin" />
        <p className="text-sm font-extrabold text-[#64748B]">Loading Worker Portal...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-2xl mx-auto pb-12">
      {/* Worker Header */}
      <div className="bg-gradient-to-br from-[#0F172A] via-[#14532D] to-[#166534] p-6 rounded-3xl text-white shadow-xl flex items-center justify-between">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase tracking-wider">
              Worker Portal
            </span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black tracking-tight mt-2">
            Jambo, {user?.name?.split(" ")[0] || "Worker"}! 👋
          </h1>
          <p className="text-xs text-emerald-100/80 font-medium mt-1">
            Tap any big card below to log output or mark tasks done.
          </p>
        </div>

        <button
          onClick={handleRefresh}
          className="p-3 bg-white/10 hover:bg-white/20 text-white rounded-2xl cursor-pointer transition-all"
        >
          <RefreshCw className={`h-6 w-6 ${refreshing ? "animate-spin" : ""}`} />
        </button>
      </div>

      {/* Task Progress Summary */}
      <Card className="border-2 border-emerald-200 bg-emerald-50/50 rounded-3xl p-5 shadow-sm">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="h-12 w-12 rounded-2xl bg-[#166534] text-white flex items-center justify-center font-black text-lg">
              {progressPct}%
            </div>
            <div>
              <h3 className="text-base font-black text-[#0F172A]">Today's Task Progress</h3>
              <p className="text-xs font-bold text-[#64748B]">
                {completedCount} of {totalTasks} tasks completed
              </p>
            </div>
          </div>
          {progressPct === 100 && totalTasks > 0 && (
            <span className="px-3 py-1.5 bg-emerald-600 text-white text-xs font-extrabold rounded-full flex items-center gap-1 shadow-xs">
              <CheckCircle2 className="h-4 w-4" /> All Done!
            </span>
          )}
        </div>

        {/* Progress Bar */}
        <div className="w-full h-3 bg-emerald-200/60 rounded-full mt-4 overflow-hidden">
          <div
            className="h-full bg-[#166534] rounded-full transition-all duration-500"
            style={{ width: `${progressPct}%` }}
          />
        </div>
      </Card>

      {/* GIANT QUICK LOG CARDS */}
      <div>
        <h2 className="text-xs font-black text-[#64748B] uppercase tracking-wider mb-3 px-1">
          Quick Logging (Tap to Record)
        </h2>
        <div className="grid grid-cols-2 gap-4">
          {/* Log Eggs */}
          <motion.div
            whileTap={{ scale: 0.96 }}
            onClick={() => openLog("eggs")}
            className="p-5 rounded-3xl bg-emerald-600 text-white shadow-lg cursor-pointer hover:bg-emerald-700 transition-all flex flex-col justify-between h-36 border-2 border-emerald-500"
          >
            <div className="flex items-center justify-between">
              <div className="p-3 bg-white/20 rounded-2xl">
                <Egg className="h-7 w-7 stroke-[2.5]" />
              </div>
              <Plus className="h-6 w-6 text-white/80" />
            </div>
            <div>
              <h3 className="text-xl font-black">Log Eggs</h3>
              <p className="text-xs text-emerald-100 font-medium">Record egg harvest</p>
            </div>
          </motion.div>

          {/* Log Milk */}
          <motion.div
            whileTap={{ scale: 0.96 }}
            onClick={() => openLog("milk")}
            className="p-5 rounded-3xl bg-sky-600 text-white shadow-lg cursor-pointer hover:bg-sky-700 transition-all flex flex-col justify-between h-36 border-2 border-sky-500"
          >
            <div className="flex items-center justify-between">
              <div className="p-3 bg-white/20 rounded-2xl">
                <Milk className="h-7 w-7 stroke-[2.5]" />
              </div>
              <Plus className="h-6 w-6 text-white/80" />
            </div>
            <div>
              <h3 className="text-xl font-black">Log Milk</h3>
              <p className="text-xs text-sky-100 font-medium">Record litres milked</p>
            </div>
          </motion.div>

          {/* Log Feed */}
          <motion.div
            whileTap={{ scale: 0.96 }}
            onClick={() => openLog("feed")}
            className="p-5 rounded-3xl bg-amber-600 text-white shadow-lg cursor-pointer hover:bg-amber-700 transition-all flex flex-col justify-between h-36 border-2 border-amber-500"
          >
            <div className="flex items-center justify-between">
              <div className="p-3 bg-white/20 rounded-2xl">
                <Wheat className="h-7 w-7 stroke-[2.5]" />
              </div>
              <Plus className="h-6 w-6 text-white/80" />
            </div>
            <div>
              <h3 className="text-xl font-black">Log Feed</h3>
              <p className="text-xs text-amber-100 font-medium">Record feed given</p>
            </div>
          </motion.div>

          {/* Log Loss */}
          <motion.div
            whileTap={{ scale: 0.96 }}
            onClick={() => openLog("mortality")}
            className="p-5 rounded-3xl bg-rose-600 text-white shadow-lg cursor-pointer hover:bg-rose-700 transition-all flex flex-col justify-between h-36 border-2 border-rose-500"
          >
            <div className="flex items-center justify-between">
              <div className="p-3 bg-white/20 rounded-2xl">
                <Heart className="h-7 w-7 stroke-[2.5]" />
              </div>
              <Plus className="h-6 w-6 text-white/80" />
            </div>
            <div>
              <h3 className="text-xl font-black">Log Animal Loss</h3>
              <p className="text-xs text-rose-100 font-medium">Record dead animals</p>
            </div>
          </motion.div>
        </div>
      </div>

      {/* TODAY'S TASKS LIST */}
      <div className="space-y-3">
        <div className="flex items-center justify-between px-1">
          <h2 className="text-xs font-black text-[#64748B] uppercase tracking-wider">
            Today's Assigned Tasks
          </h2>
          <span className="text-xs font-bold text-[#166534] bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
            {tasks.length} tasks
          </span>
        </div>

        {tasks.length === 0 ? (
          <Card className="border-2 border-dashed border-gray-200 p-8 text-center rounded-3xl bg-gray-50/50">
            <ListTodo className="h-10 w-10 text-gray-400 mx-auto mb-2" />
            <p className="text-sm font-extrabold text-[#0F172A]">No tasks assigned today</p>
            <p className="text-xs text-[#64748B] mt-1">Use the big logging cards above to record farm activity.</p>
          </Card>
        ) : (
          <div className="space-y-3">
            {tasks.map((task) => (
              <WorkerTaskCard
                key={task.id}
                task={task}
                onToggleComplete={handleToggleTask}
              />
            ))}
          </div>
        )}
      </div>

      {/* TODAY'S ACTIVITY LOG */}
      {activities.length > 0 && (
        <div className="space-y-3 pt-2">
          <h2 className="text-xs font-black text-[#64748B] uppercase tracking-wider px-1">
            My Activity Today ({activities.length})
          </h2>
          <Card className="border border-gray-200 rounded-3xl p-4 divide-y divide-gray-100 bg-white">
            {activities.map((act) => (
              <div key={act.id} className="py-3 first:pt-0 last:pb-0 flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="h-9 w-9 rounded-xl bg-gray-100 flex items-center justify-center font-black text-xs text-[#166534]">
                    ✓
                  </div>
                  <div>
                    <p className="text-sm font-extrabold text-[#0F172A] capitalize">
                      Logged {act.quantity} {act.unit || act.type}
                    </p>
                    <p className="text-[10px] text-[#94A3B8]">
                      {new Date(act.createdAt).toLocaleTimeString("en-KE", { hour: "2-digit", minute: "2-digit" })}
                    </p>
                  </div>
                </div>
                <span className="text-xs font-bold text-[#64748B] bg-gray-50 px-2.5 py-1 rounded-xl">
                  {act.type}
                </span>
              </div>
            ))}
          </Card>
        </div>
      )}

      {/* Quick Modal */}
      <WorkerQuickLogModal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        type={logType}
        flocks={flocks}
        onSuccess={fetchData}
      />
    </div>
  );
}
