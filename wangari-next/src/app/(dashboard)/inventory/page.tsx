"use client";
import * as React from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Package, AlertTriangle, TrendingUp, CircleDollarSign, Plus, X, Trash2, Wheat, Leaf, Pill, Wrench, Search, BarChart3, ArrowUp, ArrowDown } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from "recharts";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const COLORS = ["#166534", "#22C55E", "#86EFAC", "#94A3B8", "#CBD5E1", "#F59E0B", "#EF4444", "#3B82F6"];

const CATEGORIES = [
  { id: "all", label: "All", icon: Package },
  { id: "animal_feed", label: "Animal Feed", icon: Wheat },
  { id: "seeds", label: "Seeds", icon: Leaf },
  { id: "fertilizer", label: "Fertilizer", icon: Leaf },
  { id: "pesticide", label: "Pesticide", icon: Pill },
  { id: "herbicide", label: "Herbicide", icon: Pill },
  { id: "medication", label: "Medication", icon: Pill },
  { id: "equipment", label: "Equipment", icon: Wrench },
  { id: "other", label: "Other", icon: Package },
];

const UNITS = ["bags", "kg", "litres", "bottles", "pieces", "rolls", "sachets"];

export default function InventoryPage() {
  const [items, setItems] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [step, setStep] = React.useState(1);
  const [activeCategory, setActiveCategory] = React.useState("all");
  const [search, setSearch] = React.useState("");
  const [adjustItem, setAdjustItem] = React.useState<number | null>(null);
  const [adjustQty, setAdjustQty] = React.useState("");
  const { showToast, ToastComponent } = useToast();
  const [form, setForm] = React.useState({ itemName: "", category: "animal_feed", quantity: "", unit: "bags", unitCost: "", reorderLevel: "", supplier: "" });

  const load = () => {
    api.get("/api/inventory").then(d => { setItems(Array.isArray(d) ? d : []); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const handleSubmit = async () => {
    await api.post("/api/inventory", { ...form, quantity: Number(form.quantity), unitCost: Number(form.unitCost), reorderLevel: Number(form.reorderLevel || 0) });
    setForm({ itemName: "", category: "animal_feed", quantity: "", unit: "bags", unitCost: "", reorderLevel: "", supplier: "" });
    setStep(1); setShowForm(false); showToast("Item added!"); load();
  };

  const handleAdjust = async (id: number, delta: number) => {
    if (!adjustQty) return;
    const qty = delta > 0 ? Math.abs(Number(adjustQty)) : -Math.abs(Number(adjustQty));
    await api.patch(`/api/inventory/${id}`, { quantity: qty });
    setAdjustItem(null); setAdjustQty("");
    showToast(delta > 0 ? "Stock added!" : "Stock used!"); load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this item?")) return;
    await api.delete("/api/inventory/" + id); load();
  };

  const filtered = items.filter(i => {
    const matchCat = activeCategory === "all" || i.category === activeCategory;
    const matchSearch = !search || i.itemName.toLowerCase().includes(search.toLowerCase());
    return matchCat && matchSearch;
  });

  const lowStock = items.filter(i => Number(i.quantity) <= i.reorderLevel);
  const totalValue = items.reduce((s, i) => s + Number(i.quantity) * Number(i.unitCost), 0);

  const valueByCategory: Record<string, number> = {};
  items.forEach(i => { const cat = i.category || "other"; valueByCategory[cat] = (valueByCategory[cat] || 0) + Number(i.quantity) * Number(i.unitCost); });
  const valuePie = Object.entries(valueByCategory).map(([name, value]) => ({ name: name.replace("_", " "), value })).filter(v => v.value > 0);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Inventory" description="Track feed, seeds, fertilizer and farm supplies"
          action={<Button onClick={() => { setShowForm(!showForm); setStep(1); }} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Item</Button>} />
      </motion.div>

      {/* Add form — step by step */}
      <AnimatePresence>
        {showForm && (
          <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }} exit={{ opacity: 0, height: 0 }}>
            <Card className="border border-[#E5E7EB]">
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-3">
                    <h3 className="text-sm font-bold text-[#0F172A]">Add Item</h3>
                    <div className="flex gap-1">{[1, 2, 3].map(s => <div key={s} className={`h-1.5 w-8 rounded-full ${step >= s ? "bg-[#166534]" : "bg-gray-200"}`} />)}</div>
                  </div>
                  <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
                </div>

                {step === 1 && (
                  <div className="space-y-3">
                    <Label className="text-xs font-semibold text-[#64748B]">What is the item?</Label>
                    <Input placeholder="e.g. NPK fertilizer, Layer mash, vaccines" value={form.itemName} onChange={e => setForm({ ...form, itemName: e.target.value })} className="h-12 rounded-xl text-base" autoFocus />
                    <Label className="text-xs font-semibold text-[#64748B]">Category</Label>
                    <div className="grid grid-cols-3 gap-2">
                      {CATEGORIES.filter(c => c.id !== "all").map(c => (
                        <button key={c.id} onClick={() => setForm({ ...form, category: c.id })}
                          className={`py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${form.category === c.id ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>{c.label}</button>
                      ))}
                    </div>
                    <Button onClick={() => form.itemName && setStep(2)} disabled={!form.itemName} className="w-full mt-2 h-11 cursor-pointer">Next</Button>
                  </div>
                )}

                {step === 2 && (
                  <div className="space-y-3">
                    <div className="grid grid-cols-2 gap-3">
                      <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Quantity</Label><Input type="number" placeholder="0" value={form.quantity} onChange={e => setForm({ ...form, quantity: e.target.value })} className="h-12 rounded-xl text-lg font-bold text-center" /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Unit</Label><select value={form.unit} onChange={e => setForm({ ...form, unit: e.target.value })} className="w-full h-12 rounded-xl border border-[#E5E7EB] px-3 text-sm font-bold">{UNITS.map(u => <option key={u}>{u}</option>)}</select></div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Cost per unit (KES)</Label><Input type="number" placeholder="0" value={form.unitCost} onChange={e => setForm({ ...form, unitCost: e.target.value })} className="h-12 rounded-xl text-lg font-bold text-center" /></div>
                      <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Alert when below</Label><Input type="number" placeholder="0" value={form.reorderLevel} onChange={e => setForm({ ...form, reorderLevel: e.target.value })} className="h-12 rounded-xl text-lg font-bold text-center" /></div>
                    </div>
                    {form.quantity && form.unitCost && (
                      <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-3 text-center">
                        <p className="text-xs text-[#64748B]">Total value</p>
                        <p className="text-xl font-extrabold text-[#166534]">KES {(Number(form.quantity) * Number(form.unitCost)).toLocaleString()}</p>
                      </div>
                    )}
                    <div className="flex gap-2">
                      <Button onClick={() => setStep(1)} variant="outline" className="flex-1 cursor-pointer">Back</Button>
                      <Button onClick={() => setStep(3)} disabled={!form.quantity} className="flex-1 h-11 cursor-pointer">Next</Button>
                    </div>
                  </div>
                )}

                {step === 3 && (
                  <div className="space-y-3">
                    <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Supplier (optional)</Label><Input placeholder="e.g. Chemist supplier" value={form.supplier} onChange={e => setForm({ ...form, supplier: e.target.value })} className="h-11 rounded-xl" /></div>
                    <div className="rounded-xl bg-[#F8FAFC] border border-[#E5E7EB] p-4 space-y-2">
                      <div className="flex justify-between text-xs"><span className="text-[#64748B]">Item</span><span className="font-bold text-[#0F172A]">{form.itemName}</span></div>
                      <div className="flex justify-between text-xs"><span className="text-[#64748B]">Category</span><span className="font-bold text-[#0F172A] capitalize">{form.category.replace("_", " ")}</span></div>
                      <div className="flex justify-between text-xs"><span className="text-[#64748B]">Quantity</span><span className="font-bold text-[#0F172A]">{form.quantity} {form.unit}</span></div>
                      <div className="flex justify-between text-xs"><span className="text-[#64748B]">Unit cost</span><span className="font-bold text-[#0F172A]">KES {Number(form.unitCost || 0).toLocaleString()}</span></div>
                      <div className="flex justify-between text-xs border-t border-[#E5E7EB] pt-2"><span className="text-[#64748B]">Total value</span><span className="font-extrabold text-[#166534]">KES {(Number(form.quantity) * Number(form.unitCost)).toLocaleString()}</span></div>
                    </div>
                    <div className="flex gap-2">
                      <Button onClick={() => setStep(2)} variant="outline" className="flex-1 cursor-pointer">Back</Button>
                      <Button onClick={handleSubmit} disabled={!form.itemName || !form.quantity} className="flex-1 h-11 bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save Item</Button>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Low stock alert */}
      {lowStock.length > 0 && (
        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
          <Card className="border border-amber-200 bg-amber-50">
            <CardContent className="flex items-center gap-3 p-4">
              <AlertTriangle className="h-5 w-5 text-amber-600 flex-shrink-0" />
              <div>
                <p className="text-sm font-bold text-amber-800">{lowStock.length} item{lowStock.length > 1 ? "s" : ""} running low</p>
                <p className="text-xs text-amber-600">{lowStock.map(i => i.itemName).join(", ")}</p>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* KPIs */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-3 gap-3">
        {[
          { title: "Total Items", value: items.length.toString(), icon: <Package className="h-5 w-5" />, color: "bg-[#166534]" },
          { title: "Total Value", value: `KES ${totalValue.toLocaleString()}`, icon: <CircleDollarSign className="h-5 w-5" />, color: "bg-emerald-500" },
          { title: "Low Stock", value: lowStock.length.toString(), icon: <AlertTriangle className="h-5 w-5" />, color: lowStock.length > 0 ? "bg-amber-500" : "bg-[#166534]" },
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

      {/* Chart */}
      {valuePie.length > 0 && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-3">
                <BarChart3 className="h-4 w-4 text-[#166534]" />
                <p className="text-xs font-bold text-[#0F172A]">Stock Value by Category</p>
              </div>
              <div className="flex items-center gap-4">
                <ResponsiveContainer width="40%" height={120}>
                  <PieChart>
                    <Pie data={valuePie} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={25} outerRadius={45} strokeWidth={2} stroke="#fff">
                      {valuePie.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                    </Pie>
                    <Tooltip formatter={(v: any) => `KES ${Number(v).toLocaleString()}`} contentStyle={{ borderRadius: 8, border: "1px solid #E5E7EB", fontSize: 11 }} />
                  </PieChart>
                </ResponsiveContainer>
                <div className="flex flex-wrap gap-2 flex-1">
                  {valuePie.map((v, i) => (
                    <div key={v.name} className="flex items-center gap-1"><div className="h-2.5 w-2.5 rounded-full" style={{ background: COLORS[i % COLORS.length] }} /><span className="text-[10px] text-[#64748B] capitalize">{v.name}: KES {v.value.toLocaleString()}</span></div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Category tabs */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {CATEGORIES.map(cat => {
          const Icon = cat.icon;
          const count = cat.id === "all" ? items.length : items.filter(i => i.category === cat.id).length;
          if (cat.id !== "all" && count === 0) return null;
          return (
            <button key={cat.id} onClick={() => setActiveCategory(cat.id)}
              className={`flex items-center gap-1.5 px-3 py-2 rounded-full text-xs font-semibold whitespace-nowrap transition-all cursor-pointer ${activeCategory === cat.id ? "bg-[#166534] text-white shadow-md" : "bg-gray-100 text-gray-600 hover:bg-gray-200"}`}>
              <Icon className="h-3.5 w-3.5" />{cat.label} ({count})
            </button>
          );
        })}
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
        <input placeholder="Search inventory..." value={search} onChange={e => setSearch(e.target.value)} className="w-full h-10 rounded-xl border border-[#E5E7EB] pl-9 pr-3 text-sm" />
      </div>

      {/* Items */}
      {filtered.length === 0 ? <EmptyState title="No items" description="Add inventory items to track your stock." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
          {filtered.map(item => {
            const isLow = Number(item.quantity) <= item.reorderLevel;
            const cat = CATEGORIES.find(c => c.id === item.category);
            return (
              <motion.div key={item.id} variants={fadeUp}>
                <Card className={`border ${isLow ? "border-amber-300 bg-amber-50/30" : "border-[#E5E7EB]"}`}>
                  <CardContent className="p-4">
                    <div className="flex items-start justify-between">
                      <div className="flex items-center gap-3">
                        <div className={`flex h-9 w-9 items-center justify-center rounded-xl ${isLow ? "bg-amber-100 text-amber-700" : "bg-emerald-50 text-emerald-700"}`}>
                          {cat ? <cat.icon className="h-4 w-4" /> : <Package className="h-4 w-4" />}
                        </div>
                        <div>
                          <h3 className="text-sm font-bold text-[#0F172A]">{item.itemName}</h3>
                          <p className="text-[10px] text-[#94A3B8] capitalize">{(item.category || "other").replace("_", " ")}</p>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        {isLow && <Badge className="bg-amber-100 text-amber-700 text-[9px] border-amber-200">Low</Badge>}
                        <p className="text-lg font-extrabold text-[#0F172A]">{Number(item.quantity).toLocaleString()}<span className="text-xs font-normal text-[#94A3B8] ml-1">{item.unit}</span></p>
                      </div>
                    </div>
                    <div className="grid grid-cols-3 gap-2 mt-3">
                      <div className="rounded-lg bg-[#F8FAFC] p-2 text-center"><p className="text-[9px] text-[#94A3B8]">Unit cost</p><p className="text-xs font-bold text-[#0F172A]">KES {Number(item.unitCost).toLocaleString()}</p></div>
                      <div className="rounded-lg bg-[#F8FAFC] p-2 text-center"><p className="text-[9px] text-[#94A3B8]">Value</p><p className="text-xs font-bold text-[#166534]">KES {(Number(item.quantity) * Number(item.unitCost)).toLocaleString()}</p></div>
                      <div className="rounded-lg bg-[#F8FAFC] p-2 text-center"><p className="text-[9px] text-[#94A3B8]">Reorder</p><p className="text-xs font-bold text-[#0F172A]">{item.reorderLevel} {item.unit}</p></div>
                    </div>
                    {/* Quick stock actions */}
                    <div className="flex gap-2 mt-3">
                      <button onClick={() => { setAdjustItem(item.id); setAdjustQty(""); }}
                        className="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-[#F0FDF4] text-[#166534] text-xs font-bold border border-[#BBF7D0] hover:bg-[#DCFCE7] cursor-pointer">
                        <ArrowUp className="h-3.5 w-3.5" />Restock
                      </button>
                      <button onClick={() => { setAdjustItem(item.id); setAdjustQty(""); }}
                        className="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-red-50 text-red-600 text-xs font-bold border border-red-200 hover:bg-red-100 cursor-pointer">
                        <ArrowDown className="h-3.5 w-3.5" />Use Stock
                      </button>
                      <button onClick={() => handleDelete(item.id)} className="py-2.5 px-3 rounded-xl bg-gray-50 text-[#94A3B8] hover:text-red-500 border border-gray-200 cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button>
                    </div>
                  </CardContent>
                </Card>
              </motion.div>
            );
          })}
        </motion.div>
      )}

      {/* Stock adjust modal */}
      {adjustItem && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
          <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }}>
            <Card className="w-80 border border-[#E5E7EB]">
              <CardContent className="p-6 space-y-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Adjust Stock</h3>
                <p className="text-xs text-[#64748B]">How many {items.find(i => i.id === adjustItem)?.unit || "units"}?</p>
                <Input type="number" placeholder="0" value={adjustQty} onChange={e => setAdjustQty(e.target.value)} className="h-12 rounded-xl text-lg font-bold text-center" />
                <div className="flex gap-2">
                  <Button onClick={() => setAdjustItem(null)} variant="outline" className="flex-1 cursor-pointer">Cancel</Button>
                  <Button onClick={() => handleAdjust(adjustItem, 1)} disabled={!adjustQty} className="flex-1 bg-[#166534] hover:bg-[#14532D] cursor-pointer">Add</Button>
                  <Button onClick={() => handleAdjust(adjustItem, -1)} disabled={!adjustQty} className="flex-1 bg-red-500 hover:bg-red-600 text-white cursor-pointer">Use</Button>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        </div>
      )}

      {ToastComponent}
    </div>
  );
}
