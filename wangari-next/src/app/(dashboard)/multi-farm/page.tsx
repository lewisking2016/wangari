"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Tractor, Plus, MapPin, Users, PawPrint, Leaf, DollarSign, X } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import { useFarm } from "@/hooks/useFarm";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const FARM_TYPES = ["Mixed Farm", "Poultry", "Livestock", "Crop", "Dairy", "Aquaculture"];

export default function MultiFarmPage() {
  const { farm: activeFarm, farms, selectFarm, createFarm, refresh } = useFarm();
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [farmStats, setFarmStats] = React.useState<Record<number, any>>({});
  const [form, setForm] = React.useState({ name: "", location: "", farmType: "" });
  const { showToast, ToastComponent } = useToast();

  React.useEffect(() => {
    // Fetch stats for each farm
    const loadStats = async () => {
      const stats: Record<number, any> = {};
      await Promise.all(farms.map(async f => {
        try { stats[f.id] = await api.get(`/api/farms/${f.id}`); } catch { stats[f.id] = { stats: { flocks: 0, crops: 0, workers: 0, revenue: 0 } }; }
      }));
      setFarmStats(stats);
      setLoading(false);
    };
    if (farms.length > 0) loadStats();
    else setLoading(false);
  }, [farms]);

  const handleCreate = async () => {
    if (!form.name) return;
    await createFarm(form.name, form.location, form.farmType);
    setForm({ name: "", location: "", farmType: "" });
    setShowForm(false);
    showToast("Farm created!");
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="My Farms" description="Manage and switch between your farms"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />New Farm</Button>} />
      </motion.div>

      {/* Create form */}
      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Create New Farm</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="space-y-3">
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Farm name</Label><Input placeholder="e.g. Kiambu Dairy Farm" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} className="h-12 rounded-xl text-base" autoFocus /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Location</Label><Input placeholder="e.g. Kiambu County" value={form.location} onChange={e => setForm({ ...form, location: e.target.value })} className="h-11 rounded-xl" /></div>
                <Label className="text-xs font-semibold text-[#64748B]">Farm type</Label>
                <div className="grid grid-cols-3 gap-2">
                  {FARM_TYPES.map(t => (
                    <button key={t} onClick={() => setForm({ ...form, farmType: t })}
                      className={`py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${form.farmType === t ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>{t}</button>
                  ))}
                </div>
                <Button onClick={handleCreate} disabled={!form.name} className="w-full h-12 bg-[#166534] hover:bg-[#14532D] cursor-pointer disabled:opacity-50 font-bold">Create Farm</Button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Farm cards */}
      {farms.length === 0 ? <EmptyState title="No farms" description="Create your first farm to get started." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-3">
          {farms.map(f => {
            const stats = farmStats[f.id]?.stats || { flocks: 0, crops: 0, workers: 0, revenue: 0 };
            const isActive = f.id === activeFarm?.id;
            return (
              <motion.div key={f.id} variants={fadeUp}>
                <Card className={`border transition-all ${isActive ? "border-[#166534] bg-[#F0FDF4] shadow-md" : "border-[#E5E7EB] hover:border-[#BBF7D0]"}`}>
                  <CardContent className="p-5">
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex items-center gap-3">
                        <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${isActive ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>
                          <Tractor className="h-5 w-5" />
                        </div>
                        <div>
                          <h3 className="text-sm font-bold text-[#0F172A]">{f.name}</h3>
                          {(f.location || f.county) && <p className="text-[10px] text-[#94A3B8] flex items-center gap-1"><MapPin className="h-2.5 w-2.5" />{f.location || f.county}</p>}
                        </div>
                      </div>
                      {isActive && <Badge className="bg-[#166534] text-white text-[9px]">Active</Badge>}
                    </div>
                    <div className="grid grid-cols-4 gap-2 mb-3">
                      <div className="rounded-lg bg-[#F8FAFC] p-2 text-center"><p className="text-[9px] text-[#94A3B8]">Livestock</p><p className="text-xs font-bold text-[#0F172A]">{stats.flocks}</p></div>
                      <div className="rounded-lg bg-[#F8FAFC] p-2 text-center"><p className="text-[9px] text-[#94A3B8]">Crops</p><p className="text-xs font-bold text-[#0F172A]">{stats.crops}</p></div>
                      <div className="rounded-lg bg-[#F8FAFC] p-2 text-center"><p className="text-[9px] text-[#94A3B8]">Workers</p><p className="text-xs font-bold text-[#0F172A]">{stats.workers}</p></div>
                      <div className="rounded-lg bg-[#F8FAFC] p-2 text-center"><p className="text-[9px] text-[#94A3B8]">Revenue</p><p className="text-[10px] font-bold text-[#166534]">KES {stats.revenue.toLocaleString()}</p></div>
                    </div>
                    {!isActive && (
                      <Button onClick={() => selectFarm(f.id)} variant="outline" className="w-full border-[#166534] text-[#166534] hover:bg-[#F0FDF4] cursor-pointer text-xs font-bold">Switch to this farm</Button>
                    )}
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
