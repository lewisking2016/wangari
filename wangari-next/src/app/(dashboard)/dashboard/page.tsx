"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  Bird,
  Egg,
  DollarSign,
  TrendingDown,
  TrendingUp,
  ArrowUpRight,
  ArrowDownRight,
  Plus,
  ShoppingCart,
  Activity,
  Clock,
  BarChart3,
} from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08 } },
};
const scaleIn = {
  hidden: { opacity: 0, scale: 0.92 },
  visible: { opacity: 1, scale: 1, transition: { duration: 0.4, ease: "easeOut" } },
};

export default function DashboardPage() {
  const [data, setData] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/dashboard")
      .then((r) => r.json())
      .then((d) => { setData(d); setLoading(false); })
      .catch(() => setLoading(false));
  }, []);

  const hour = new Date().getHours();
  const greeting =
    hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";

  if (loading)
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" />
      </div>
    );

  const txs = data?.recentTransactions || [];

  const kpis = [
    {
      title: "Total Birds",
      value: (data?.totalBirds || 0).toLocaleString(),
      icon: <Bird className="h-5 w-5" />,
      change: data?.totalFlocks + " active flocks",
      changeType: "positive",
    },
    {
      title: "Eggs Today",
      value: (data?.eggsToday || 0).toLocaleString(),
      icon: <Egg className="h-5 w-5" />,
      change: data?.mortalityToday + " mortality",
      changeType: data?.mortalityToday > 0 ? "negative" : "positive",
    },
    {
      title: "Monthly Revenue",
      value: "KES " + (data?.monthlyRevenue || 0).toLocaleString(),
      icon: <TrendingUp className="h-5 w-5" />,
      change: "This month",
      changeType: "positive",
    },
    {
      title: "Monthly Expenses",
      value: "KES " + (data?.monthlyExpenses || 0).toLocaleString(),
      icon: <TrendingDown className="h-5 w-5" />,
      change: "This month",
      changeType: "negative",
    },
  ];

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">
          {greeting} 👋
        </h1>
        <p className="text-sm text-[#64748B] mt-1">
          Here&apos;s what&apos;s happening on your farm today.
        </p>
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {kpis.map((kpi) => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4, scale: 1.02 }}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg hover:border-[#BBF7D0] transition-all duration-300">
              <CardContent className="pt-6 pb-4 px-5">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md">
                    {kpi.icon}
                  </div>
                  {kpi.changeType === "positive" ? (
                    <ArrowUpRight className="h-4 w-4 text-[#16A34A]" />
                  ) : (
                    <ArrowDownRight className="h-4 w-4 text-[#64748B]" />
                  )}
                </div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A] tracking-tight">{kpi.value}</p>
                <p className="text-xs text-[#94A3B8] mt-1">{kpi.change}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid lg:grid-cols-3 gap-6">
        <motion.div variants={fadeUp} className="lg:col-span-2">
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow duration-300">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <div className="flex items-center gap-2">
                <Activity className="h-4 w-4 text-[#166534]" />
                <CardTitle className="text-base font-bold">Recent Transactions</CardTitle>
              </div>
              <Link href="/finances" className="text-sm text-[#166534] font-semibold hover:underline">View All</Link>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {txs.length === 0 && <p className="text-sm text-[#64748B] py-4 text-center">No transactions yet.</p>}
                {txs.map((tx: any, i: number) => (
                  <motion.div key={tx.id} initial={{ opacity: 0, x: -12 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.05 }}
                    className="flex items-center justify-between rounded-xl px-4 py-3 hover:bg-[#F8FAFC] transition-colors">
                    <div className="flex items-center gap-3">
                      <div className={"flex h-9 w-9 items-center justify-center rounded-xl " + (tx.type === "income" ? "bg-[#F0FDF4] text-[#166534]" : "bg-gray-100 text-[#64748B]")}>
                        {tx.type === "income" ? <ArrowUpRight className="h-4 w-4" /> : <ArrowDownRight className="h-4 w-4" />}
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-[#0F172A]">{tx.description || tx.category}</p>
                        <p className="text-xs text-[#94A3B8]">{new Date(tx.date).toLocaleDateString()}</p>
                      </div>
                    </div>
                    <p className={"text-sm font-bold tabular-nums " + (tx.type === "income" ? "text-[#166534]" : "text-[#64748B]")}>
                      {tx.type === "income" ? "+" : "-"}KES {Number(tx.amount).toLocaleString()}
                    </p>
                  </motion.div>
                ))}
              </div>
            </CardContent>
          </Card>
        </motion.div>

        <motion.div variants={fadeUp}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow duration-300">
            <CardHeader className="pb-2">
              <div className="flex items-center gap-2">
                <Clock className="h-4 w-4 text-[#166534]" />
                <CardTitle className="text-base font-bold">Quick Actions</CardTitle>
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              {[
                { href: "/production", icon: <Plus className="h-4 w-4" />, label: "Log Production" },
                { href: "/sales", icon: <ShoppingCart className="h-4 w-4" />, label: "Record Sale" },
                { href: "/finances", icon: <DollarSign className="h-4 w-4" />, label: "Add Expense" },
                { href: "/inventory", icon: <BarChart3 className="h-4 w-4" />, label: "Check Inventory" },
              ].map((action) => (
                <Link key={action.href} href={action.href}>
                  <Button className="w-full justify-start border border-[#E5E7EB] bg-white font-semibold hover:bg-[#F0FDF4] hover:border-[#BBF7D0] hover:text-[#166534] transition-all duration-200 cursor-pointer" variant="outline">
                    {action.icon}
                    <span className="ml-2">{action.label}</span>
                  </Button>
                </Link>
              ))}
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>
    </div>
  );
}
