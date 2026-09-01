"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { DollarSign, TrendingUp, TrendingDown, Wallet, ArrowUpRight, ArrowDownRight, Plus, X, Trash2 } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useToast } from "@/components/shared/toast";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function FinancesPage() {
  const [txs, setTxs] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const { showToast, ToastComponent } = useToast();
  const [form, setForm] = React.useState({ type: "expense", description: "", amount: "", category: "feed", paymentMethod: "cash" });

  const load = () => {
    fetch("/api/transactions").then(r => r.json()).then(d => { setTxs(d); setLoading(false); }).catch(() => setLoading(false));
  };
  const handleDelete = async (id: number) => {
    if (!confirm("Delete this transaction?")) return;
    await fetch("/api/transactions/" + id, { method: "DELETE" });
    load();
  };
  React.useEffect(() => { load(); }, []);

  const income = txs.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const expenses = txs.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const profit = income - expenses;

  const handleSubmit = async () => {
    await fetch("/api/transactions", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ...form, amount: Number(form.amount), date: new Date().toISOString() }),
    });
    setForm({ type: "expense", description: "", amount: "", category: "feed", paymentMethod: "cash" });
    setShowForm(false);
    showToast("Transaction saved!");
    load();
  };

  const kpis = [
    { title: "Total Income", value: "KES " + income.toLocaleString(), icon: <TrendingUp className="h-5 w-5" />, change: txs.filter(t => t.type === "income").length + " transactions" },
    { title: "Total Expenses", value: "KES " + expenses.toLocaleString(), icon: <TrendingDown className="h-5 w-5" />, change: txs.filter(t => t.type === "expense").length + " transactions" },
    { title: "Net Profit", value: "KES " + profit.toLocaleString(), icon: <Wallet className="h-5 w-5" />, change: profit >= 0 ? "Profitable" : "Loss" },
  ];

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Finances" description="Income, expenses, and profit tracking"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Transaction</Button>}
        />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Add Transaction</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Type</Label>
                  <select value={form.type} onChange={e => setForm({ ...form, type: e.target.value })} className="w-full h-10 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                  </select>
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Description</Label>
                  <Input placeholder="e.g. Layer mash purchase" value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Amount (KES)</Label>
                  <Input type="number" placeholder="0" value={form.amount} onChange={e => setForm({ ...form, amount: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Category</Label>
                  <select value={form.category} onChange={e => setForm({ ...form, category: e.target.value })} className="w-full h-10 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option value="feed">Feed</option>
                    <option value="labor">Labor</option>
                    <option value="medication">Medication</option>
                    <option value="eggs">Egg Sales</option>
                    <option value="birds">Bird Sales</option>
                    <option value="infrastructure">Infrastructure</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div className="flex items-end">
                  <Button onClick={handleSubmit} className="w-full bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-3 gap-4">
        {kpis.map((kpi) => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4 }}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-all duration-300">
              <CardContent className="pt-6 pb-4 px-5">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3">{kpi.icon}</div>
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
          <CardHeader className="pb-2"><div className="flex items-center gap-2"><DollarSign className="h-4 w-4 text-[#166534]" /><CardTitle className="text-base font-bold">All Transactions</CardTitle></div></CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead><tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                  <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Date</th>
                  <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Description</th>
                  <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Category</th>
                  <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Amount</th>
                  <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Method</th>
                </tr></thead>
                <tbody>{txs.slice(0, 25).map((t: any, i: number) => {
                  const isIncome = t.type === "income";
                  return (
                    <motion.tr key={t.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.03 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                      <td className="px-5 py-3.5 text-[#0F172A] font-medium">{new Date(t.date).toLocaleDateString()}</td>
                      <td className="px-5 py-3.5"><div className="flex items-center gap-2"><div className={"flex h-7 w-7 items-center justify-center rounded-lg " + (isIncome ? "bg-[#F0FDF4] text-[#166534]" : "bg-gray-100 text-[#64748B]")}>{isIncome ? <ArrowUpRight className="h-3.5 w-3.5" /> : <ArrowDownRight className="h-3.5 w-3.5" />}</div><span className="text-[#64748B]">{t.description || "-"}</span></div></td>
                      <td className="px-5 py-3.5"><Badge variant="outline" className="capitalize text-xs">{t.category || "-"}</Badge></td>
                      <td className={"px-5 py-3.5 text-right font-bold tabular-nums " + (isIncome ? "text-[#166534]" : "text-[#64748B]")}>{isIncome ? "+" : "-"}KES {Number(t.amount).toLocaleString()}</td>
                      <td className="px-5 py-3.5 text-center"><div className="flex items-center justify-center gap-2"><span className="text-[#94A3B8] uppercase text-xs font-semibold">{t.paymentMethod || "-"}</span><button onClick={() => handleDelete(t.id)} className="text-[#94A3B8] hover:text-red-500 transition-colors cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button></div></td>
                    </motion.tr>
                  );
                })}</tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </motion.div>
      {ToastComponent}
    </div>
  );
}
