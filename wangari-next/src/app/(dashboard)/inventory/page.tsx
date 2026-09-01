"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Package, AlertTriangle, TrendingUp, CircleDollarSign } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/shared/empty-state";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function InventoryPage() {
  const [items, setItems] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/inventory").then(r => r.json()).then(d => { setItems(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const lowStock = items.filter(i => Number(i.quantity) <= i.reorderLevel);
  const totalValue = items.reduce((s, i) => s + Number(i.quantity) * Number(i.unitCost), 0);

  // Uniform green palette for all categories
  const categoryColors: Record<string, string> = {
    feed: "from-[#166534] to-[#14532D]",
    vaccine: "from-[#15803D] to-[#166534]",
    packaging: "from-[#22C55E] to-[#16A34A]",
    medication: "from-[#14532D] to-[#0B1220]",
    general: "from-[#15803D] to-[#14532D]",
  };

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Inventory" description="Track feed, medication, and supplies" />
      </motion.div>

      {lowStock.length > 0 && (
        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}>
          <Card className="border-[#166534]/20 bg-gradient-to-r from-[#F0FDF4] to-white hover:shadow-md transition-shadow">
            <CardContent className="flex items-center gap-3 p-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534]/10 text-[#166534]">
                <AlertTriangle className="h-5 w-5" />
              </div>
              <div>
                <p className="text-sm font-bold text-[#166534]">{lowStock.length} items below reorder level</p>
                <p className="text-xs text-[#64748B]">{lowStock.map(i => i.itemName).join(", ")}</p>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <motion.div variants={scaleIn}>
          <Card className="relative overflow-hidden border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#166534] to-[#22C55E]" />
            <CardContent className="pt-6 pb-4 px-5">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#166534] to-[#22C55E] text-white shadow-md mb-3">
                <Package className="h-5 w-5" />
              </div>
              <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">Total Items</p>
              <p className="text-2xl font-extrabold text-[#0F172A]">{items.length}</p>
            </CardContent>
          </Card>
        </motion.div>
        <motion.div variants={scaleIn}>
          <Card className="relative overflow-hidden border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#15803D] to-[#166534]" />
            <CardContent className="pt-6 pb-4 px-5">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#15803D] to-[#166534] text-white shadow-md mb-3">
                <CircleDollarSign className="h-5 w-5" />
              </div>
              <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">Total Value</p>
              <p className="text-2xl font-extrabold text-[#0F172A]">KES {totalValue.toLocaleString()}</p>
            </CardContent>
          </Card>
        </motion.div>
        <motion.div variants={scaleIn}>
          <Card className="relative overflow-hidden border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <div className={`absolute top-0 left-0 right-0 h-1 ${lowStock.length > 0 ? "bg-gradient-to-r from-[#14532D] to-[#0B1220]" : "bg-gradient-to-r from-[#22C55E] to-[#16A34A]"}`} />
            <CardContent className="pt-6 pb-4 px-5">
              <div className={`flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br ${lowStock.length > 0 ? "from-[#14532D] to-[#0B1220]" : "from-[#22C55E] to-[#16A34A]"} text-white shadow-md mb-3`}>
                <TrendingUp className="h-5 w-5" />
              </div>
              <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">Low Stock</p>
              <p className="text-2xl font-extrabold text-[#0F172A]">{lowStock.length}</p>
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>

      {items.length === 0 ? <EmptyState title="No inventory" description="Add items to track your stock." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {items.map(item => {
            const isLow = Number(item.quantity) <= item.reorderLevel;
            const gradient = categoryColors[item.category?.toLowerCase()] || categoryColors.general;
            return (
              <motion.div key={item.id} variants={scaleIn} whileHover={{ y: -4, scale: 1.02 }}>
                <Card className={`border hover:shadow-xl transition-all duration-300 relative overflow-hidden ${isLow ? "border-[#14532D]/30" : "border-[#E5E7EB] hover:border-[#BBF7D0]"}`}>
                  <div className={`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${gradient}`} />
                  <CardContent className="p-6">
                    <div className="flex items-start justify-between">
                      <div className={`flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br ${gradient} text-white shadow-md`}>
                        <Package className="h-6 w-6" />
                      </div>
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
