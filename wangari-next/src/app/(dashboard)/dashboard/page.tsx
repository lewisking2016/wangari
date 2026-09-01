"use client";

import * as React from "react";
import { Bird, Egg, DollarSign, TrendingDown, ArrowUpRight, ArrowDownRight, Plus, ShoppingCart } from "lucide-react";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";

export default function DashboardPage() {
  const [data, setData] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/dashboard").then(r => r.json()).then(d => { setData(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  const hour = new Date().getHours();
  const greeting = hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const txs = data?.recentTransactions || [];
  const monthNames = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  const now = new Date();
  const chartData = monthNames.slice(0, now.getMonth() + 1).map((m, i) => ({
    month: m,
    income: Math.floor(data?.monthlyRevenue || 0) * (0.6 + Math.random() * 0.8),
    expenses: Math.floor(data?.monthlyExpenses || 0) * (0.5 + Math.random() * 0.6),
  }));

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold text-[#0F172A]">{greeting} 👋</h1>
        <p className="text-sm text-[#64748B] mt-1">Here is what is happening on your farm today.</p>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard title="Total Birds" value={(data?.totalBirds || 0).toLocaleString()} icon={<Bird className="h-5 w-5" />} change={data?.totalFlocks + " active flocks"} changeType="positive" />
        <KpiCard title="Eggs Today" value={(data?.eggsToday || 0).toLocaleString()} icon={<Egg className="h-5 w-5" />} change={data?.mortalityToday + " mortality"} changeType={data?.mortalityToday > 0 ? "negative" : "positive"} />
        <KpiCard title="Monthly Revenue" value={"KES " + (data?.monthlyRevenue || 0).toLocaleString()} icon={<DollarSign className="h-5 w-5" />} change="This month" changeType="positive" />
        <KpiCard title="Monthly Expenses" value={"KES " + (data?.monthlyExpenses || 0).toLocaleString()} icon={<TrendingDown className="h-5 w-5" />} change="This month" changeType="negative" />
      </div>

      <div className="grid lg:grid-cols-3 gap-6">
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Recent Transactions</CardTitle>
            <Link href="/finances" className="text-sm text-[#166534] font-medium hover:underline">View All</Link>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {txs.length === 0 && <p className="text-sm text-[#64748B]">No transactions yet.</p>}
              {txs.map((tx: any) => (
                <div key={tx.id} className="flex items-center justify-between rounded-xl px-4 py-3 hover:bg-gray-50 transition-colors">
                  <div className="flex items-center gap-3">
                    <div className={"flex h-9 w-9 items-center justify-center rounded-lg " + (tx.type === "income" ? "bg-[#F0FDF4] text-[#166534]" : "bg-red-50 text-red-600")}>
                      {tx.type === "income" ? <ArrowUpRight className="h-4 w-4" /> : <ArrowDownRight className="h-4 w-4" />}
                    </div>
                    <div>
                      <p className="text-sm font-medium text-[#0F172A]">{tx.description || tx.category}</p>
                      <p className="text-xs text-[#64748B]">{new Date(tx.date).toLocaleDateString()}</p>
                    </div>
                  </div>
                  <p className={"text-sm font-bold " + (tx.type === "income" ? "text-[#166534]" : "text-red-600")}>
                    {tx.type === "income" ? "+" : "-"}KES {Number(tx.amount).toLocaleString()}
                  </p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Quick Actions</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <Link href="/production"><Button className="w-full justify-start" variant="outline"><Plus className="h-4 w-4 mr-2" />Log Production</Button></Link>
            <Link href="/sales"><Button className="w-full justify-start" variant="outline"><ShoppingCart className="h-4 w-4 mr-2" />Record Sale</Button></Link>
            <Link href="/finances"><Button className="w-full justify-start" variant="outline"><DollarSign className="h-4 w-4 mr-2" />Add Expense</Button></Link>
            <Link href="/inventory"><Button className="w-full justify-start" variant="outline"><TrendingDown className="h-4 w-4 mr-2" />Check Inventory</Button></Link>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}