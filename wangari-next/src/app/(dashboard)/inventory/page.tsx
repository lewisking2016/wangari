"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Package, AlertTriangle, TrendingUp, CircleDollarSign, Plus, X } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function InventoryPage() {
  const [items, setItems] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [form, setForm] = React.useState({ itemName: "", category: "feed", quantity: "", unit: "bags", unitCost: "", reorderLevel: "" });

  const load = () => {
    fetch("/api/inventory").then(r => r.json()).then(d => { setItems(d); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const lowStock = items.filter(i => Number(i.quantity) <= i.reorderLevel);
  const totalValue = items.reduce((s, i) => s + Number(i.quantity) * Number(i.unitCost), 0);

  const handleSubmit = async () => {
    await fetch("/api/inventory", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ...form, quantity: Number(form.quantity), unitCost: Number(form.unitCost), reorderLevel: Number(form.reorderLevel || 10) }),
    });
    setForm({ itemName: "", category: "feed", quantity: "", unit: "bags", unitCost: "", reorderLevel: "" });
    setShowForm(false);
    load();
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Inventory" description="Track feed, medication, and supplies"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Item</Button>}
        />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Add Inventory Item</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Item Name</Label>
                  <Input placeholder="e.g. Layer Mash" value={form.itemName} onChange={e => setForm({ ...form, itemName: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Category</Label>
                  <select value={form.category} onChange={e => setForm({ ...form, category: e.target.value })} className="w-full h-10 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option value="feed">Feed</option>
                    <option value="medication">Medication</option>
                    <option value="vaccine">Vaccine</option>
                    <option value="packaging">Packaging</option>
                    <option value="equipment">Equipment</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Quantity</Label>
                  <Input type="number" placeholder="0" value={form.quantity} onChange={e => setForm({ ...form, quantity: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Unit Cost (KES)</Label>
                  <Input type="number" placeholder="0" value={form.unitCost} onChange={e => setForm({ ...form, unitCost: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Reorder Level</Label>
                  <Input type="number" placeholder="10" value={form.reorderLevel} onChange={e => setForm({ ...form, reorderLevel: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="flex items-end">
                  <Button onClick={handleSubmit} className="w-full bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {lowStock.length > 0 && (
        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}>
          <Card className="border-[#166534]/20 bg-[#F0FDF4] hover:shadow-md transition-shadow">
            <CardContent className="flex items-center gap-3 p-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534]/10 text-[#166534]"><AlertTriangle className="h-5 w-5" /></div>
              <div>
                <p className="text-sm font-bold text-[#166534]">{lowStock.length} items below reorder level</p>
                <p className="text-xs text-[#64748B]">{lowStock.map(i => i.itemName).join(", ")}</p>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <motion.div variants={scaleIn}><Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow"><CardContent className="pt-6 pb-4 px-5"><div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3"><Package className="h-5 w-5" /></div><p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">Total Items</p><p className="text-2xl font-extrabold text-[#0F172A]">{items.length}</p></CardContent></Card></motion.div>
        <motion.div variants={scaleIn}><Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow"><CardContent className="pt-6 pb-4 px-5"><div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3"><CircleDollarSign className="h-5 w-5" /></div><p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">Total Value</p><p className="text-2xl font-extrabold text-[#0F172A]">KES {totalValue.toLocaleString()}</p></CardContent></Card></motion.div>
        <motion.div variants={scaleIn}><Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow"><CardContent className="pt-6 pb-4 px-5"><div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3"><TrendingUp className="h-5 w-5" /></div><p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">Low Stock</p><p className="text-2xl font-extrabold text-[#0F172A]">{lowStock.length}</p></CardContent></Card></motion.div>
      </motion.div>

      {items.length === 0 ? <EmptyState title="No inventory" description="Add items to track your stock." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {items.map(item => {
            const isLow = Number(item.quantity) <= item.reorderLevel;
            return (
              <motion.div key={item.id} variants={scaleIn} whileHover={{ y: -4, scale: 1.02 }}>
                <Card className={`border hover:shadow-xl transition-all duration-300 ${isLow ? "border-[#14532D]/30" : "border-[#E5E7EB] hover:border-[#BBF7D0]"}`}>
                  <CardContent className="p-6">
                    <div className="flex items-start justify-between">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md"><Package className="h-6 w-6" /></div>
                      {isLow ? <Badge className="bg-[#14532D]/10 text-[#166534] border-[#14532D]/20">Low Stock</Badge> : <Badge className="bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]">In Stock</Badge>}
                    </div>
                    <h3 className="mt-4 text-lg font-bold text-[#0F172A]">{item.itemName}</h3>
                    <p className="text-sm text-[#64748B] capitalize">{item.category || "General"}</p>
                    <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                      <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Quantity</p><p className="font-bold text-[#0F172A] tabular-nums">{Number(item.quantity).toLocaleString()} {item.unit}</p></div>
                      <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Unit Cost</p><p className="font-bold text-[#0F172A] tabular-nums">KES {Number(item.unitCost).toLocaleString()}</p></div>
                      <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Reorder At</p><p className="font-bold text-[#0F172A] tabular-nums">{item.reorderLevel} {item.unit}</p></div>
                      <div><p className="text-[10px] font-semibold uppercase tracking-wider text-[#94A3B8]">Value</p><p className="font-bold text-[#166534] tabular-nums">KES {(Number(item.quantity) * Number(item.unitCost)).toLocaleString()}</p></div>
                    </div>
                  </CardContent>
                </Card>
              </motion.div>
            );
          })}
        </motion.div>
      )}
    </div>
  );
}
