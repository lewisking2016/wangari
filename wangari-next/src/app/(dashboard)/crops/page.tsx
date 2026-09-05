"use client";
import * as React from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Leaf, Plus, X, Search, Droplets, TrendingUp, Trash2, Check, MapPin, ChevronRight, Bug, Pill, AlertTriangle, Sprout, Calendar, BarChart3 } from "lucide-react";
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from "recharts";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const CROP_TYPES = ["Maize", "Beans", "Tomatoes", "Kale", "Cabbage", "Onions", "Potatoes", "Sorghum", "Millet", "Wheat", "Sugarcane", "Bananas", "Avocado", "Mango", "Coffee", "Tea"];
const GROWTH_STAGES = ["Planted", "Germinating", "Vegetative", "Flowering", "Fruiting", "Ready"];
const HEALTH_ISSUES = ["Pest", "Disease", "Weed", "Nutrient Deficiency", "Weather Damage"];
const APPLICATION_TYPES = ["Fertilizer", "Pesticide", "Herbicide", "Irrigation", "Organic Manure"];

function getGrowthProgress(plantingDate: string | null, expectedHarvest: string | null): { stage: string; percent: number } {
  if (!plantingDate) return { stage: "Unknown", percent: 0 };
  const planted = new Date(plantingDate).getTime();
  const now = Date.now();
  const total = expectedHarvest ? new Date(expectedHarvest).getTime() - planted : 120 * 86400000;
  const elapsed = now - planted;
  const pct = Math.min(Math.max((elapsed / total) * 100, 0), 100);
  if (pct < 10) return { stage: "Germinating", percent: pct };
  if (pct < 30) return { stage: "Vegetative", percent: pct };
  if (pct < 55) return { stage: "Flowering", percent: pct };
  if (pct < 80) return { stage: "Fruiting", percent: pct };
  return { stage: "Ready", percent: pct };
}

type Modal = null | "harvest" | "health" | "apply";

export default function CropsPage() {
  const [crops, setCrops] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [search, setSearch] = React.useState("");
  const { showToast, ToastComponent } = useToast();

  // Create form
  const [showForm, setShowForm] = React.useState(false);
  const [step, setStep] = React.useState(0);
  const [form, setForm] = React.useState({ name: "", cropType: "", variety: "", areaAcres: "", plantingDate: "", expectedHarvest: "", location: "", pricePerKg: "" });

  // Action modals
  const [activeModal, setActiveModal] = React.useState<Modal>(null);
  const [modalCrop, setModalCrop] = React.useState<any>(null);
  const [harvestForm, setHarvestForm] = React.useState({ date: new Date().toISOString().split("T")[0], quantityKg: "", quality: "A", salePrice: "" });
  const [healthForm, setHealthForm] = React.useState({ date: new Date().toISOString().split("T")[0], issueType: "Pest", description: "", severity: "low", treatment: "" });
  const [applyForm, setApplyForm] = React.useState({ date: new Date().toISOString().split("T")[0], type: "Fertilizer", productName: "", quantity: "", unit: "kg", cost: "" });

  const load = () => {
    api.get("/api/crops").then(d => { setCrops(Array.isArray(d) ? d : []); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const openModal = (modal: Modal, crop: any) => { setActiveModal(modal); setModalCrop(crop); };
  const closeModal = () => { setActiveModal(null); setModalCrop(null); };

  const resetForm = () => { setForm({ name: "", cropType: "", variety: "", areaAcres: "", plantingDate: "", expectedHarvest: "", location: "", pricePerKg: "" }); setStep(0); setShowForm(false); };

  const handleCreate = async () => { await api.post("/api/crops", form); resetForm(); showToast("Crop registered!"); load(); };
  const handleDelete = async (id: number) => { if (!confirm("Delete this crop?")) return; await api.delete("/api/crops/" + id); load(); };

  const handleHarvest = async () => { if (!modalCrop) return; await api.post(`/api/crops/${modalCrop.id}/harvest`, harvestForm); setHarvestForm({ date: new Date().toISOString().split("T")[0], quantityKg: "", quality: "A", salePrice: "" }); closeModal(); showToast("Harvest recorded!"); load(); };
  const handleHealth = async () => { if (!modalCrop) return; await api.post(`/api/crops/${modalCrop.id}/health`, healthForm); setHealthForm({ date: new Date().toISOString().split("T")[0], issueType: "Pest", description: "", severity: "low", treatment: "" }); closeModal(); showToast("Health issue recorded!"); load(); };
  const handleApply = async () => { if (!modalCrop) return; await api.post(`/api/crops/${modalCrop.id}/apply`, applyForm); setApplyForm({ date: new Date().toISOString().split("T")[0], type: "Fertilizer", productName: "", quantity: "", unit: "kg", cost: "" }); closeModal(); showToast("Application recorded!"); load(); };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const totalAcres = crops.reduce((s, c) => s + Number(c.areaAcres || 0), 0);
  const totalHarvest = crops.reduce((s, c) => s + (c.harvests || []).reduce((hs: number, h: any) => hs + Number(h.quantityKg || 0), 0), 0);
  const activeCrops = crops.filter(c => c.status === "active").length;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Crops & Plantation" description="Register, track growth, record harvests & treatments"
          action={<Button onClick={() => { setShowForm(true); setStep(0); }} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Crop</Button>}
        />
      </motion.div>

      {/* Step-by-step form */}
      <AnimatePresence>
        {showForm && (
          <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }} exit={{ opacity: 0, height: 0 }}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
              <CardContent className="p-6">
                <div className="flex items-center gap-2 mb-6">
                  {["What crop?", "Field details", "Confirm"].map((label, i) => (
                    <React.Fragment key={label}>
                      <div className={`flex items-center gap-1.5 text-xs font-semibold ${i <= step ? "text-[#166534]" : "text-gray-300"}`}>
                        <div className={`h-6 w-6 rounded-full flex items-center justify-center text-[10px] font-bold ${i < step ? "bg-[#166534] text-white" : i === step ? "bg-[#166534] text-white" : "bg-gray-100 text-gray-400"}`}>{i < step ? <Check className="h-3 w-3" /> : i + 1}</div>
                        {label}
                      </div>
                      {i < 2 && <div className={`flex-1 h-0.5 rounded ${i < step ? "bg-[#166534]" : "bg-gray-100"}`} />}
                    </React.Fragment>
                  ))}
                  <button onClick={resetForm} className="ml-auto text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
                </div>

                {step === 0 && (
                  <div>
                    <p className="text-sm font-bold text-gray-900 mb-3">What are you planting?</p>
                    <div className="grid grid-cols-3 md:grid-cols-4 gap-2">
                      {CROP_TYPES.map(c => (
                        <button key={c} onClick={() => { setForm({ ...form, cropType: c }); setStep(1); }}
                          className="rounded-xl border border-gray-200 px-3 py-3 text-center text-sm font-medium hover:border-[#166534] hover:bg-[#F0FDF4] transition-all cursor-pointer">{c}</button>
                      ))}
                    </div>
                  </div>
                )}

                {step === 1 && (
                  <div>
                    <p className="text-sm font-bold text-gray-900 mb-1">Planting <span className="text-[#166534]">{form.cropType}</span></p>
                    <p className="text-xs text-gray-400 mb-4">Give your field a name and set the basics</p>
                    <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                      <div className="space-y-1"><Label className="text-xs font-semibold text-gray-500">🏷️ Field Name *</Label><Input placeholder="e.g. North Field" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} className="h-11 rounded-xl" autoFocus /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-gray-500">📐 Area (Acres)</Label><Input type="number" placeholder="e.g. 2" step="0.1" value={form.areaAcres} onChange={e => setForm({ ...form, areaAcres: e.target.value })} className="h-11 rounded-xl" /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-gray-500">🌱 Variety</Label><Input placeholder="e.g. H614" value={form.variety} onChange={e => setForm({ ...form, variety: e.target.value })} className="h-11 rounded-xl" /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-gray-500">📅 Planting Date</Label><Input type="date" value={form.plantingDate} onChange={e => setForm({ ...form, plantingDate: e.target.value })} className="h-11 rounded-xl" /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-gray-500">🎯 Expected Harvest</Label><Input type="date" value={form.expectedHarvest} onChange={e => setForm({ ...form, expectedHarvest: e.target.value })} className="h-11 rounded-xl" /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-gray-500">📍 Location</Label><Input placeholder="e.g. Behind house" value={form.location} onChange={e => setForm({ ...form, location: e.target.value })} className="h-11 rounded-xl" /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-gray-500">💰 Price per kg (KES)</Label><Input type="number" placeholder="e.g. 50" value={form.pricePerKg} onChange={e => setForm({ ...form, pricePerKg: e.target.value })} className="h-11 rounded-xl" /></div>
                    </div>
                    <div className="mt-4 flex gap-2">
                      <Button onClick={() => setStep(2)} disabled={!form.name} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer disabled:opacity-50">Review <ChevronRight className="h-4 w-4 ml-1" /></Button>
                      <Button variant="outline" onClick={() => setStep(0)} className="cursor-pointer">Back</Button>
                    </div>
                  </div>
                )}

                {step === 2 && (
                  <div>
                    <p className="text-sm font-bold text-gray-900 mb-4">Confirm</p>
                    <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-4 space-y-2 text-sm">
                      <div className="flex justify-between"><span className="text-gray-500">Crop:</span><span className="font-bold">{form.cropType}</span></div>
                      <div className="flex justify-between"><span className="text-gray-500">Field:</span><span className="font-bold">{form.name || "Unnamed"}</span></div>
                      {form.areaAcres && <div className="flex justify-between"><span className="text-gray-500">Area:</span><span className="font-bold">{form.areaAcres} acres</span></div>}
                      {form.plantingDate && <div className="flex justify-between"><span className="text-gray-500">Planting:</span><span className="font-bold">{new Date(form.plantingDate).toLocaleDateString()}</span></div>}
                      {form.pricePerKg && <div className="flex justify-between"><span className="text-gray-500">Price/kg:</span><span className="font-bold">KES {form.pricePerKg}</span></div>}
                    </div>
                    <div className="mt-4 flex gap-2">
                      <Button onClick={handleCreate} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">✓ Save Crop</Button>
                      <Button variant="outline" onClick={() => setStep(1)} className="cursor-pointer">Edit</Button>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </motion.div>
        )}
      </AnimatePresence>

      {/* KPIs */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { title: "Active Crops", value: String(activeCrops), icon: <Leaf className="h-5 w-5" /> },
          { title: "Total Area", value: `${totalAcres.toFixed(1)} acres`, icon: <MapPin className="h-5 w-5" /> },
          { title: "Total Harvest", value: `${totalHarvest.toFixed(0)} kg`, icon: <Droplets className="h-5 w-5" /> },
          { title: "Crops", value: String(crops.length), icon: <Sprout className="h-5 w-5" /> },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={fadeUp}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-all">
              <CardContent className="pt-6 pb-4 px-5">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#E6F4EA] text-[#166534] mb-3">{kpi.icon}</div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A]">{kpi.value}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
        <input placeholder="Search crops..." value={search} onChange={e => setSearch(e.target.value)} className="w-full h-11 rounded-xl border border-[#E5E7EB] pl-10 pr-4 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all" />
      </motion.div>

      {/* Charts */}
      {crops.length > 0 && (() => {
        // Harvest by crop type
        const harvestByType: Record<string, number> = {};
        crops.forEach(c => { (c.harvests || []).forEach((h: any) => { harvestByType[c.cropType] = (harvestByType[c.cropType] || 0) + Number(h.quantityKg || 0); }); });
        const harvestPie = Object.entries(harvestByType).map(([name, value]) => ({ name, value })).filter(h => h.value > 0);

        // Input costs by type
        const costsByType: Record<string, number> = {};
        crops.forEach(c => { (c.applications || []).forEach((a: any) => { costsByType[a.type] = (costsByType[a.type] || 0) + Number(a.cost || 0); }); });
        const costPie = Object.entries(costsByType).map(([name, value]) => ({ name, value })).filter(c => c.value > 0);

        // Health issues count
        const healthByType: Record<string, number> = {};
        crops.forEach(c => { (c.health || []).forEach((h: any) => { healthByType[h.issueType] = (healthByType[h.issueType] || 0) + 1; }); });
        const healthBar = Object.entries(healthByType).map(([name, count]) => ({ name, count }));

        const COLORS = ["#166534", "#22C55E", "#86EFAC", "#94A3B8", "#CBD5E1"];

        if (harvestPie.length === 0 && costPie.length === 0 && healthBar.length === 0) return null;

        return (
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <div className="grid lg:grid-cols-3 gap-4">
              {harvestPie.length > 0 && (
                <Card className="border border-[#E5E7EB]">
                  <CardContent className="p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <BarChart3 className="h-4 w-4 text-[#166534]" />
                      <p className="text-xs font-bold text-gray-900">Harvest by Crop</p>
                    </div>
                    <ResponsiveContainer width="100%" height={160}>
                      <PieChart>
                        <Pie data={harvestPie} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={35} outerRadius={60} strokeWidth={2} stroke="#fff">
                          {harvestPie.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                        </Pie>
                        <Tooltip formatter={(v: any) => `${Number(v).toFixed(0)} kg`} contentStyle={{ borderRadius: 8, border: "1px solid #E5E7EB", fontSize: 11 }} />
                      </PieChart>
                    </ResponsiveContainer>
                    <div className="flex flex-wrap gap-2 mt-2">
                      {harvestPie.map((h, i) => (
                        <div key={h.name} className="flex items-center gap-1"><div className="h-2 w-2 rounded-full" style={{ background: COLORS[i % COLORS.length] }} /><span className="text-[10px] text-gray-500">{h.name}: {h.value.toFixed(0)}kg</span></div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}

              {costPie.length > 0 && (
                <Card className="border border-[#E5E7EB]">
                  <CardContent className="p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <Pill className="h-4 w-4 text-[#166534]" />
                      <p className="text-xs font-bold text-gray-900">Input Costs</p>
                    </div>
                    <ResponsiveContainer width="100%" height={160}>
                      <PieChart>
                        <Pie data={costPie} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={35} outerRadius={60} strokeWidth={2} stroke="#fff">
                          {costPie.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                        </Pie>
                        <Tooltip formatter={(v: any) => `KES ${Number(v).toLocaleString()}`} contentStyle={{ borderRadius: 8, border: "1px solid #E5E7EB", fontSize: 11 }} />
                      </PieChart>
                    </ResponsiveContainer>
                    <div className="flex flex-wrap gap-2 mt-2">
                      {costPie.map((c, i) => (
                        <div key={c.name} className="flex items-center gap-1"><div className="h-2 w-2 rounded-full" style={{ background: COLORS[i % COLORS.length] }} /><span className="text-[10px] text-gray-500">{c.name}: KES {c.value.toLocaleString()}</span></div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}

              {healthBar.length > 0 && (
                <Card className="border border-[#E5E7EB]">
                  <CardContent className="p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <Bug className="h-4 w-4 text-amber-500" />
                      <p className="text-xs font-bold text-gray-900">Health Issues</p>
                    </div>
                    <ResponsiveContainer width="100%" height={160}>
                      <BarChart data={healthBar} layout="vertical">
                        <XAxis type="number" tick={{ fontSize: 10, fill: "#94A3B8" }} />
                        <YAxis type="category" dataKey="name" tick={{ fontSize: 10, fill: "#64748B" }} width={90} />
                        <Tooltip contentStyle={{ borderRadius: 8, border: "1px solid #E5E7EB", fontSize: 11 }} />
                        <Bar dataKey="count" fill="#F59E0B" radius={[0, 4, 4, 0]} name="Issues" />
                      </BarChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>
              )}
            </div>
          </motion.div>
        );
      })()}

      {/* Crop cards */}
      {crops.length === 0 ? <EmptyState title="No crops yet" description="Tap 'Add Crop' to register your first field." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {crops.filter(c => !search || c.name.toLowerCase().includes(search.toLowerCase()) || c.cropType.toLowerCase().includes(search.toLowerCase())).map((crop) => {
            const totalKg = (crop.harvests || []).reduce((s: number, h: any) => s + Number(h.quantityKg || 0), 0);
            const growth = getGrowthProgress(crop.plantingDate, crop.expectedHarvest);
            const daysLeft = crop.expectedHarvest ? Math.ceil((new Date(crop.expectedHarvest).getTime() - Date.now()) / 86400000) : null;
            const hasActiveIssues = (crop.health || []).some((h: any) => !h.outcome || h.outcome === "ongoing");

            return (
              <motion.div key={crop.id} variants={fadeUp} whileHover={{ y: -4 }}>
                <Card className="border border-[#E5E7EB] hover:shadow-xl transition-all duration-300">
                  <CardContent className="p-5">
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><Leaf className="h-5 w-5" /></div>
                        <div>
                          <h3 className="text-base font-bold text-gray-900">{crop.name}</h3>
                          <p className="text-xs text-gray-400">{crop.cropType}{crop.variety ? ` (${crop.variety})` : ""}</p>
                        </div>
                      </div>
                      <div className="flex gap-1">
                        {hasActiveIssues && <Badge className="bg-red-50 text-red-700 border-red-200 text-[9px]"><Bug className="h-2.5 w-2.5 mr-0.5" />Issue</Badge>}
                        <Badge className={crop.status === "active" ? "bg-emerald-50 text-emerald-700 border-emerald-200" : "bg-gray-50 text-gray-500"}>{crop.status}</Badge>
                      </div>
                    </div>

                    {/* Growth Progress */}
                    <div className="mb-3">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] font-semibold text-gray-400 uppercase">Growth</span>
                        <span className="text-[10px] font-bold text-[#166534]">{growth.stage}</span>
                      </div>
                      <div className="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div className="h-full rounded-full bg-[#166534] transition-all" style={{ width: `${growth.percent}%` }} />
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-2 text-xs mb-3">
                      {crop.areaAcres && <div className="rounded-lg bg-gray-50 p-2"><span className="text-gray-400">Area</span><p className="font-bold">{crop.areaAcres} acres</p></div>}
                      {daysLeft !== null && <div className="rounded-lg bg-gray-50 p-2"><span className="text-gray-400">Harvest</span><p className="font-bold">{daysLeft > 0 ? `${daysLeft} days` : "Ready"}</p></div>}
                      {totalKg > 0 && <div className="rounded-lg bg-emerald-50 p-2"><span className="text-emerald-600">Harvested</span><p className="font-bold text-emerald-700">{totalKg.toFixed(0)} kg</p></div>}
                    </div>

                    {/* Lifecycle tabs */}
                    <div className="mt-3 pt-3 border-t border-gray-100">
                      <div className="flex gap-1 mb-2">
                        {[
                          { key: "growth", label: "Growth", icon: <Sprout className="h-3 w-3" />, color: "bg-emerald-50 text-emerald-700" },
                          { key: "watering", label: "Water", icon: <Droplets className="h-3 w-3" />, color: "bg-blue-50 text-blue-700" },
                          { key: "flower", label: "Flower", icon: <Leaf className="h-3 w-3" />, color: "bg-purple-50 text-purple-700" },
                          { key: "harvest", label: "Harvest", icon: <Check className="h-3 w-3" />, color: "bg-amber-50 text-amber-700" },
                        ].map(tab => {
                          const isActive = growth.stage.toLowerCase().includes(tab.key) ||
                            (tab.key === "growth" && ["Germinating", "Vegetative"].includes(growth.stage)) ||
                            (tab.key === "flower" && ["Flowering", "Fruiting"].includes(growth.stage)) ||
                            (tab.key === "harvest" && growth.stage === "Ready");
                          return (
                            <div key={tab.key} className={`flex-1 flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg text-[10px] font-semibold transition-all ${isActive ? tab.color + " ring-1 ring-current/20" : "bg-gray-50 text-gray-400"}`}>
                              {tab.icon}{tab.label}
                            </div>
                          );
                        })}
                      </div>
                      {/* Action buttons */}
                      <div className="grid grid-cols-3 gap-1.5">
                        <button onClick={() => openModal("harvest", crop)} className="flex flex-col items-center gap-1 px-2 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-[10px] font-semibold hover:bg-emerald-100 transition-colors cursor-pointer">
                          <Check className="h-3.5 w-3.5" />Harvest
                        </button>
                        <button onClick={() => openModal("health", crop)} className="flex flex-col items-center gap-1 px-2 py-2 rounded-lg bg-amber-50 text-amber-700 text-[10px] font-semibold hover:bg-amber-100 transition-colors cursor-pointer">
                          <Bug className="h-3.5 w-3.5" />Report Issue
                        </button>
                        <button onClick={() => openModal("apply", crop)} className="flex flex-col items-center gap-1 px-2 py-2 rounded-lg bg-blue-50 text-blue-700 text-[10px] font-semibold hover:bg-blue-100 transition-colors cursor-pointer">
                          <Pill className="h-3.5 w-3.5" />Apply Input
                        </button>
                      </div>
                    </div>
                    <button onClick={() => handleDelete(crop.id)} className="w-full mt-2 text-[10px] text-gray-400 hover:text-red-500 cursor-pointer">Delete crop</button>
                  </CardContent>
                </Card>
              </motion.div>
            );
          })}
        </motion.div>
      )}

      {/* Modals */}
      <AnimatePresence>
        {activeModal && modalCrop && (
          <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" onClick={closeModal}>
            <motion.div initial={{ scale: 0.95 }} animate={{ scale: 1 }} exit={{ scale: 0.95 }} className="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl" onClick={e => e.stopPropagation()}>
              <h3 className="text-sm font-bold text-gray-900 mb-1">
                {activeModal === "harvest" && "Record Harvest"}
                {activeModal === "health" && "Report Health Issue"}
                {activeModal === "apply" && "Record Input Application"}
              </h3>
              <p className="text-xs text-gray-400 mb-4">{modalCrop.name} — {modalCrop.cropType}</p>

              {activeModal === "harvest" && (
                <div className="space-y-3">
                  <div className="grid grid-cols-2 gap-3">
                    <div><Label className="text-xs font-semibold text-gray-400">Date</Label><Input type="date" value={harvestForm.date} onChange={e => setHarvestForm({ ...harvestForm, date: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                    <div><Label className="text-xs font-semibold text-gray-400">Quantity (kg)</Label><Input type="number" placeholder="0" value={harvestForm.quantityKg} onChange={e => setHarvestForm({ ...harvestForm, quantityKg: e.target.value })} className="h-9 rounded-lg text-sm" autoFocus /></div>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><Label className="text-xs font-semibold text-gray-400">Grade</Label><select value={harvestForm.quality} onChange={e => setHarvestForm({ ...harvestForm, quality: e.target.value })} className="w-full h-9 rounded-lg border border-gray-200 px-2 text-sm"><option>A</option><option>B</option><option>C</option></select></div>
                    <div><Label className="text-xs font-semibold text-gray-400">Sale Price (KES)</Label><Input type="number" placeholder="0" value={harvestForm.salePrice} onChange={e => setHarvestForm({ ...harvestForm, salePrice: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                  </div>
                </div>
              )}

              {activeModal === "health" && (
                <div className="space-y-3">
                  <div className="grid grid-cols-2 gap-3">
                    <div><Label className="text-xs font-semibold text-gray-400">Date</Label><Input type="date" value={healthForm.date} onChange={e => setHealthForm({ ...healthForm, date: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                    <div><Label className="text-xs font-semibold text-gray-400">Issue Type</Label><select value={healthForm.issueType} onChange={e => setHealthForm({ ...healthForm, issueType: e.target.value })} className="w-full h-9 rounded-lg border border-gray-200 px-2 text-sm">{HEALTH_ISSUES.map(i => <option key={i}>{i}</option>)}</select></div>
                  </div>
                  <div><Label className="text-xs font-semibold text-gray-400">Description</Label><Input placeholder="e.g. Aphids on leaves" value={healthForm.description} onChange={e => setHealthForm({ ...healthForm, description: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                  <div className="grid grid-cols-2 gap-3">
                    <div><Label className="text-xs font-semibold text-gray-400">Severity</Label><select value={healthForm.severity} onChange={e => setHealthForm({ ...healthForm, severity: e.target.value })} className="w-full h-9 rounded-lg border border-gray-200 px-2 text-sm"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
                    <div><Label className="text-xs font-semibold text-gray-400">Treatment</Label><Input placeholder="e.g. Malathion" value={healthForm.treatment} onChange={e => setHealthForm({ ...healthForm, treatment: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                  </div>
                </div>
              )}

              {activeModal === "apply" && (
                <div className="space-y-3">
                  <div className="grid grid-cols-2 gap-3">
                    <div><Label className="text-xs font-semibold text-gray-400">Date</Label><Input type="date" value={applyForm.date} onChange={e => setApplyForm({ ...applyForm, date: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                    <div><Label className="text-xs font-semibold text-gray-400">Type</Label><select value={applyForm.type} onChange={e => setApplyForm({ ...applyForm, type: e.target.value })} className="w-full h-9 rounded-lg border border-gray-200 px-2 text-sm">{APPLICATION_TYPES.map(t => <option key={t}>{t}</option>)}</select></div>
                  </div>
                  <div><Label className="text-xs font-semibold text-gray-400">Product Name</Label><Input placeholder="e.g. NPK 17:17:17" value={applyForm.productName} onChange={e => setApplyForm({ ...applyForm, productName: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                  <div className="grid grid-cols-3 gap-3">
                    <div><Label className="text-xs font-semibold text-gray-400">Quantity</Label><Input type="number" placeholder="0" value={applyForm.quantity} onChange={e => setApplyForm({ ...applyForm, quantity: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                    <div><Label className="text-xs font-semibold text-gray-400">Unit</Label><select value={applyForm.unit} onChange={e => setApplyForm({ ...applyForm, unit: e.target.value })} className="w-full h-9 rounded-lg border border-gray-200 px-2 text-sm"><option>kg</option><option>litres</option><option>bags</option><option>ml</option></select></div>
                    <div><Label className="text-xs font-semibold text-gray-400">Cost (KES)</Label><Input type="number" placeholder="0" value={applyForm.cost} onChange={e => setApplyForm({ ...applyForm, cost: e.target.value })} className="h-9 rounded-lg text-sm" /></div>
                  </div>
                </div>
              )}

              <div className="flex gap-2 mt-4">
                <Button onClick={activeModal === "harvest" ? handleHarvest : activeModal === "health" ? handleHealth : handleApply}
                  className="flex-1 bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button>
                <Button variant="outline" onClick={closeModal} className="cursor-pointer">Cancel</Button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
      {ToastComponent}
    </div>
  );
}
