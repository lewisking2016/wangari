"use client";
import * as React from "react";
import { Package, AlertTriangle } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/shared/empty-state";

export default function InventoryPage() {
  const [items, setItems] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/inventory").then(r => r.json()).then(d => { setItems(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const lowStock = items.filter(i => Number(i.quantity) <= i.reorderLevel);

  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader title="Inventory" description="Track feed, medication, and supplies" />

      {lowStock.length > 0 && (
        <Card className="border-red-200 bg-red-50"><CardContent className="flex items-center gap-3 p-4">
          <AlertTriangle className="h-5 w-5 text-red-600" />
          <p className="text-sm font-medium text-red-800">{lowStock.length} items below reorder level</p>
        </CardContent></Card>
      )}

      {items.length === 0 ? <EmptyState title="No inventory" description="Add items to track your stock." /> : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {items.map(item => {
            const isLow = Number(item.quantity) <= item.reorderLevel;
            return (
              <Card key={item.id} className="hover:shadow-lg transition-all duration-300">
                <CardContent className="p-6">
                  <div className="flex items-start justify-between">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]"><Package className="h-6 w-6" /></div>
                    {isLow ? <Badge className="bg-red-100 text-red-700">Low Stock</Badge> : <Badge className="bg-[#F0FDF4] text-[#166534]">In Stock</Badge>}
                  </div>
                  <h3 className="mt-4 text-lg font-bold text-[#0F172A]">{item.itemName}</h3>
                  <p className="text-sm text-[#64748B] capitalize">{item.category || "General"}</p>
                  <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div><p className="text-[#94A3B8]">Quantity</p><p className="font-semibold text-[#0F172A]">{Number(item.quantity).toLocaleString()} {item.unit}</p></div>
                    <div><p className="text-[#94A3B8]">Unit Cost</p><p className="font-semibold text-[#0F172A]">KES {Number(item.unitCost).toLocaleString()}</p></div>
                    <div><p className="text-[#94A3B8]">Reorder At</p><p className="font-semibold text-[#0F172A]">{item.reorderLevel} {item.unit}</p></div>
                    <div><p className="text-[#94A3B8]">Value</p><p className="font-semibold text-[#166534]">KES {(Number(item.quantity) * Number(item.unitCost)).toLocaleString()}</p></div>
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}