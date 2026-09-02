"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Bird, Plus, Trash2, Search, Calendar, AlertTriangle } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { EmptyState } from "@/components/shared/empty-state";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function FlocksPage() {
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [search, setSearch] = React.useState("");
  const [showForm, setShowForm] = React.useState(false);
  const [form, setForm] = React.useState({ name: "", breed: "", type: "layers", initialCount: "" });

  const loadFlocks = () => {
    api.get("/api/flocks").then(d => { setFlocks(d); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { loadFlocks(); }, []);

  const filtered = flocks.filter(f => f.name.toLowerCase().includes(search.toLowerCase()));

  const handleCreate = async () => {
    await api.post("/api/flocks", { ...form, initialCount: Number(form.initialCount) });
    setForm({ name: "", breed: "", type: "layers", initialCount: "" });
    setShowForm(false);
    loadFlocks();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this flock?")) return;
    await api.delete("/api/flocks/" + id);
    loadFlocks();
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Flocks" description="Manage your flocks and poultry" action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Flock</Button>} />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }} exit={{ opacity: 0, height: 0 }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <h3 className="text-sm font-bold text-[#0F172A] mb-4">New Flock</h3>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <input placeholder="Flock name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all" />
                <input placeholder="Breed" value={form.breed} onChange={e => setForm({...form, breed: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all" />
                <select value={form.type} onChange={e => setForm({...form, type: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all">
                  <option value="layers">Layers</option>
                  <option value="broilers">Broilers</option>
                </select>
                <input type="number" placeholder="Bird count" value={form.initialCount} onChange={e => setForm({...form, initialCount: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all" />
              </div>
              <div className="mt-4 flex gap-2">
                <Button onClick={handleCreate} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button>
                <Button variant="outline" onClick={() => setShowForm(false)} className="cursor-pointer">Cancel</Button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
        <Input placeholder="Search flocks..." value={search} onChange={e => setSearch(e.target.value)} className="pl-10 h-12 rounded-xl border-[#E5E7EB]" />
      </motion.div>

      {filtered.length === 0 ? <EmptyState title="No flocks" description="Add your first flock to get started." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filtered.map(f => (
            <motion.div key={f.id} variants={scaleIn} whileHover={{ y: -4, scale: 1.02 }}>
              <Card className="border border-[#E5E7EB] hover:shadow-xl hover:border-[#BBF7D0] transition-all duration-300">
                {/* Accent stripe */}
                <CardContent className="p-6">
                  <div className="flex items-start justify-between">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]">
                      <Bird className="h-6 w-6" />
                    </div>
                    <div className="flex gap-2">
                      <Badge variant={f.status === "active" ? "default" : "outline"} className={f.status === "active" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : ""}>{f.status}</Badge>
                      <button onClick={() => handleDelete(f.id)} className="text-[#94A3B8] hover:text-red-500 transition-colors cursor-pointer"><Trash2 className="h-4 w-4" /></button>
                    </div>
                  </div>
                  <h3 className="mt-4 text-lg font-bold text-[#0F172A]">{f.name}</h3>
                  <p className="text-sm text-[#64748B] capitalize">{f.breed || f.type}</p>
                  <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Initial</p><p className="font-bold text-[#0F172A] tabular-nums">{f.initialCount.toLocaleString()}</p></div>
                    <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Current</p><p className="font-bold text-[#0F172A] tabular-nums">{f.currentCount.toLocaleString()}</p></div>
                    <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Mortality</p><p className="font-bold text-red-600 tabular-nums">{f.mortality}</p></div>
                    <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Started</p><p className="font-bold text-[#0F172A]">{f.hatchDate ? new Date(f.hatchDate).toLocaleDateString() : "-"}</p></div>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          ))}
        </motion.div>
      )}
    </div>
  );
}
