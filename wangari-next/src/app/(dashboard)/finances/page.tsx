"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { DollarSign, TrendingUp, TrendingDown, Wallet, ArrowUpRight, ArrowDownRight } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function FinancesPage() {
  const [txs, setTxs] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/transactions").then(r => r.json()).then(d => { setTxs(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const income = txs.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const expenses = txs.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const profit = income - expenses;

  const kpis = [
    { title: "Total Income", value: "KES " + income.toLocaleString(), icon: <TrendingUp className="h-5 w-5" />, change: txs.filter(t => t.type === "income").length + " transactions", color: "from-emerald-400 to-green-500" },
    { title: "Total Expenses", value: "KES " + expenses.toLocaleString(), icon: <TrendingDown className="h-5 w-5" />, change: txs.filter(t => t.type === "expense").length + " transactions", color: "from-red-400 to-rose-500" },
    { title: "Net Profit", value: "KES " + profit.toLocaleString(), icon: <Wallet className="h-5 w-5" />, change: profit >= 0 ? "Profitable" : "Loss", color: profit >= 0 ? "from-blue-400 to-indigo-500" : "from-red-400 to-rose-500" },
  ];

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Finances" description="Income, expenses, and profit tracking" />
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-3 gap-4">
        {kpis.map((kpi) => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4 }}>
            <Card className="relative overflow-hidden border border-[#E5E7EB] hover:shadow-lg transition-all duration-300">
              <div className={`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${kpi.color}`} />
              <CardContent className="pt-6 pb-4 px-5">
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br ${kpi.color} text-white shadow-md mb-3`}>
                  {kpi.icon}
                </div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A] tracking-tight">{kpi.value}</p>
                <p className="text-xs text-[#94A3B8] mt-1">{kpi.change}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
          <CardHeader className="pb-2">
            <div className="flex items-center gap-2">
              <DollarSign className="h-4 w-4 text-[#166534]" />
              <CardTitle className="text-base font-bold">All Transactions</CardTitle>
            </div>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Date</th>
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Description</th>
                    <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Category</th>
                    <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Amount</th>
                    <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Method</th>
                  </tr>
                </thead>
                <tbody>
                  {txs.slice(0, 25).map((t: any, i: number) => {
                    const isIncome = t.type === "income";
                    return (
                      <motion.tr
                        key={t.id}
                        initial={{ opacity: 0, x: -10 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: i * 0.03 }}
                        className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors"
                      >
                        <td className="px-5 py-3.5 text-[#0F172A] font-medium">{new Date(t.date).toLocaleDateString()}</td>
                        <td className="px-5 py-3.5">
                          <div className="flex items-center gap-2">
                            <div className={`flex h-7 w-7 items-center justify-center rounded-lg ${isIncome ? "bg-[#F0FDF4] text-[#166534]" : "bg-red-50 text-red-600"}`}>
                              {isIncome ? <ArrowUpRight className="h-3.5 w-3.5" /> : <ArrowDownRight className="h-3.5 w-3.5" />}
                            </div>
                            <span className="text-[#64748B]">{t.description || "-"}</span>
                          </div>
                        </td>
                        <td className="px-5 py-3.5">
                          <Badge variant="outline" className="capitalize text-xs">{t.category || "-"}</Badge>
                        </td>
                        <td className={"px-5 py-3.5 text-right font-bold tabular-nums " + (isIncome ? "text-[#166534]" : "text-red-600")}>
                          {isIncome ? "+" : "-"}KES {Number(t.amount).toLocaleString()}
                        </td>
                        <td className="px-5 py-3.5 text-center text-[#94A3B8] uppercase text-xs font-semibold">{t.paymentMethod || "-"}</td>
                      </motion.tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}
