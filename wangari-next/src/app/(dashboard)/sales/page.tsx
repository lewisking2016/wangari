"use client";
import * as React from "react";
import { ShoppingCart } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/shared/empty-state";

export default function SalesPage() {
  const [sales, setSales] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/sales").then(r => r.json()).then(d => { setSales(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const totalRevenue = sales.reduce((s, sale) => s + Number(sale.totalAmount), 0);
  const totalPaid = sales.filter(s => s.paymentStatus === "paid").reduce((s, sale) => s + Number(sale.amountPaid), 0);
  const pending = totalRevenue - totalPaid;

  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader title="Sales" description="Track customer orders and payments" />
      <div className="grid grid-cols-3 gap-4">
        <Card><CardContent className="p-4"><p className="text-sm text-[#64748B]">Total Revenue</p><p className="text-2xl font-bold text-[#0F172A]">KES {totalRevenue.toLocaleString()}</p></CardContent></Card>
        <Card><CardContent className="p-4"><p className="text-sm text-[#64748B]">Paid</p><p className="text-2xl font-bold text-[#166534]">KES {totalPaid.toLocaleString()}</p></CardContent></Card>
        <Card><CardContent className="p-4"><p className="text-sm text-[#64748B]">Pending</p><p className="text-2xl font-bold text-orange-600">KES {pending.toLocaleString()}</p></CardContent></Card>
      </div>
      {sales.length === 0 ? <EmptyState title="No sales" description="Record your first sale." /> : (
        <Card><CardContent className="p-0"><div className="overflow-x-auto"><table className="w-full text-sm"><thead><tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
          <th className="px-4 py-3 text-left font-semibold text-[#64748B]">Date</th>
          <th className="px-4 py-3 text-left font-semibold text-[#64748B]">Customer</th>
          <th className="px-4 py-3 text-right font-semibold text-[#64748B]">Amount</th>
          <th className="px-4 py-3 text-right font-semibold text-[#64748B]">Paid</th>
          <th className="px-4 py-3 text-center font-semibold text-[#64748B]">Status</th>
        </tr></thead><tbody>
          {sales.map(s => (
            <tr key={s.id} className="border-b border-[#E5E7EB] hover:bg-gray-50">
              <td className="px-4 py-3 text-[#0F172A]">{new Date(s.saleDate).toLocaleDateString()}</td>
              <td className="px-4 py-3 text-[#64748B]">{s.customer?.name || "Walk-in"}</td>
              <td className="px-4 py-3 text-right font-semibold text-[#0F172A]">KES {Number(s.totalAmount).toLocaleString()}</td>
              <td className="px-4 py-3 text-right text-[#64748B]">KES {Number(s.amountPaid).toLocaleString()}</td>
              <td className="px-4 py-3 text-center"><Badge className={s.paymentStatus === "paid" ? "bg-[#F0FDF4] text-[#166534]" : "bg-orange-100 text-orange-700"}>{s.paymentStatus}</Badge></td>
            </tr>
          ))}
        </tbody></table></div></CardContent></Card>
      )}
    </div>
  );
}