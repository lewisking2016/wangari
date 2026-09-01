"use client";
import * as React from "react";
import { DollarSign, ArrowUpRight, ArrowDownRight } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

export default function FinancesPage() {
  const [txs, setTxs] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  React.useEffect(() => { fetch("/api/transactions").then(r => r.json()).then(d => { setTxs(d); setLoading(false); }); }, []);
  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;
  const income = txs.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const expenses = txs.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const profit = income - expenses;
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader title="Finances" description="Income, expenses, and profit tracking" />
      <div className="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <KpiCard title="Total Income" value={"KES " + income.toLocaleString()} icon={<DollarSign className="h-5 w-5" />} change="All time" changeType="positive" />
        <KpiCard title="Total Expenses" value={"KES " + expenses.toLocaleString()} icon={<DollarSign className="h-5 w-5" />} change="All time" changeType="negative" />
        <KpiCard title="Net Profit" value={"KES " + profit.toLocaleString()} icon={<DollarSign className="h-5 w-5" />} change={profit >= 0 ? "Profitable" : "Loss"} changeType={profit >= 0 ? "positive" : "negative"} />
      </div>
      <Card>
        <CardHeader><CardTitle>All Transactions</CardTitle></CardHeader>
        <CardContent className="p-0"><div className="overflow-x-auto"><table className="w-full text-sm"><thead><tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
          <th className="px-4 py-3 text-left font-semibold text-[#64748B]">Date</th>
          <th className="px-4 py-3 text-left font-semibold text-[#64748B]">Description</th>
          <th className="px-4 py-3 text-left font-semibold text-[#64748B]">Category</th>
          <th className="px-4 py-3 text-right font-semibold text-[#64748B]">Amount</th>
          <th className="px-4 py-3 text-center font-semibold text-[#64748B]">Method</th>
        </tr></thead><tbody>
          {txs.slice(0, 20).map((t: any) => {
            const color = t.type === "income" ? "text-[#166534]" : "text-red-600";
            const sign = t.type === "income" ? "+" : "-";
            return (
              <tr key={t.id} className="border-b border-[#E5E7EB] hover:bg-gray-50">
                <td className="px-4 py-3 text-[#0F172A]">{new Date(t.date).toLocaleDateString()}</td>
                <td className="px-4 py-3 text-[#64748B]">{t.description || "-"}</td>
                <td className="px-4 py-3"><Badge variant="outline" className="capitalize">{t.category || "-"}</Badge></td>
                <td className={"px-4 py-3 text-right font-semibold " + color}>{sign}KES {Number(t.amount).toLocaleString()}</td>
                <td className="px-4 py-3 text-center text-[#64748B] uppercase text-xs">{t.paymentMethod || "-"}</td>
              </tr>
            );
          })}
        </tbody></table></div></CardContent>
      </Card>
    </div>
  );
}