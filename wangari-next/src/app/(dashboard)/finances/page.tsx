"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { DollarSign, TrendingUp, TrendingDown, Wallet, ArrowUpRight, ArrowDownRight, Plus, X, Trash2, BarChart3, Calendar, Pencil } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useToast } from "@/components/shared/toast";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import api from "@/lib/api-client";
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from "recharts";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const EXPENSE_CATEGORIES = [
  { id: "animal_feed", label: "Animal Feed" },
  { id: "seeds", label: "Seeds" },
  { id: "fertilizer", label: "Fertilizer" },
  { id: "pesticide", label: "Pesticide" },
  { id: "labor", label: "Labor" },
  { id: "veterinary", label: "Veterinary" },
  { id: "equipment", label: "Equipment" },
  { id: "transport", label: "Transport" },
  { id: "infrastructure", label: "Infrastructure" },
  { id: "other", label: "Other" },
];

const INCOME_CATEGORIES = [
  { id: "eggs", label: "Egg Sales" },
  { id: "milk", label: "Milk Sales" },
  { id: "meat", label: "Meat Sales" },
  { id: "livestock", label: "Livestock Sales" },
  { id: "crops", label: "Crop Sales" },
  { id: "other_income", label: "Other Income" },
];

const COLORS = ["#166534", "#22C55E", "#86EFAC", "#94A3B8", "#CBD5E1", "#F59E0B", "#EF4444", "#3B82F6", "#8B5CF6", "#EC4899"];

export default function FinancesPage() {
  const [txs, setTxs] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const { showToast, ToastComponent } = useToast();
  const [form, setForm] = React.useState({ type: "expense", description: "", amount: "", category: "animal_feed", paymentMethod: "cash" });
  const [editingTx, setEditingTx] = React.useState<any>(null);

  const load = () => {
    api.get("/api/transactions").then(d => { setTxs(Array.isArray(d) ? d : []); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const handleDelete = async (id: number) => { if (!confirm("Delete this transaction?")) return; await api.delete("/api/transactions/" + id); load(); };
  const handleSubmit = async () => {
    if (editingTx) {
      await api.patch(`/api/transactions/${editingTx.id}`, { ...form, amount: Number(form.amount) });
      setEditingTx(null);
    } else {
      await api.post("/api/transactions", { ...form, amount: Number(form.amount), date: new Date().toISOString() });
    }
    setForm({ type: "expense", description: "", amount: "", category: "animal_feed", paymentMethod: "cash" });
    setShowForm(false); showToast(editingTx ? "Transaction updated!" : "Transaction saved!"); load();
  };

  const handleEdit = (tx: any) => {
    setEditingTx(tx);
    setForm({ type: tx.type, description: tx.description || "", amount: String(tx.amount), category: tx.category || "animal_feed", paymentMethod: tx.paymentMethod || "cash" });
    setShowForm(true);
  };

  const categories = form.type === "expense" ? EXPENSE_CATEGORIES : INCOME_CATEGORIES;

  const income = txs.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const expenses = txs.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const profit = income - expenses;

  // Monthly trend
  const now = new Date();
  const monthlyData: Record<string, { month: string; income: number; expense: number }> = {};
  for (let i = 5; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const key = d.toLocaleDateString("en-KE", { month: "short" });
    monthlyData[key] = { month: key, income: 0, expense: 0 };
  }
  txs.forEach((t: any) => {
    const d = new Date(t.date);
    const key = d.toLocaleDateString("en-KE", { month: "short" });
    if (monthlyData[key]) {
      if (t.type === "income") monthlyData[key].income += Number(t.amount);
      else monthlyData[key].expense += Number(t.amount);
    }
  });
  const monthlyChart = Object.values(monthlyData);

  // Expense breakdown
  const expenseByCategory: Record<string, number> = {};
  txs.filter((t: any) => t.type === "expense").forEach((t: any) => {
    const cat = t.category || "other";
    expenseByCategory[cat] = (expenseByCategory[cat] || 0) + Number(t.amount);
  });
  const expensePie = Object.entries(expenseByCategory).map(([name, value]) => ({ name: name.replace(/_/g, " "), value })).filter(v => v.value > 0);

  // Income breakdown
  const incomeByCategory: Record<string, number> = {};
  txs.filter((t: any) => t.type === "income").forEach((t: any) => {
    const cat = t.category || "other";
    incomeByCategory[cat] = (incomeByCategory[cat] || 0) + Number(t.amount);
  });
  const incomePie = Object.entries(incomeByCategory).map(([name, value]) => ({ name: name.replace(/_/g, " "), value })).filter(v => v.value > 0);

  // This month
  const thisMonth = txs.filter((t: any) => {
    const d = new Date(t.date);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  const monthIncome = thisMonth.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
  const monthExpenses = thisMonth.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Finances" description="Income, expenses, and profit tracking"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Transaction</Button>}
        />
      </motion.div>

      {/* Add form */}
      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-gray-900">{editingTx ? "Edit Transaction" : "Add Transaction"}</h3>
                <button onClick={() => setShowForm(false)} className="text-gray-400 hover:text-gray-600 cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              {/* Step 1: Income or Expense? */}
              <div className="space-y-3">
                <Label className="text-xs font-semibold text-gray-500">Income or Expense?</Label>
                <div className="grid grid-cols-2 gap-3">
                  <button onClick={() => setForm({ ...form, type: "expense", category: "animal_feed" })}
                    className={`py-4 rounded-xl text-sm font-bold transition-all cursor-pointer ${form.type === "expense" ? "bg-red-50 text-red-700 border-2 border-red-300 shadow-md" : "bg-gray-50 text-gray-500 border border-gray-200"}`}>Expense</button>
                  <button onClick={() => setForm({ ...form, type: "income", category: "eggs" })}
                    className={`py-4 rounded-xl text-sm font-bold transition-all cursor-pointer ${form.type === "income" ? "bg-emerald-50 text-emerald-700 border-2 border-emerald-300 shadow-md" : "bg-gray-50 text-gray-500 border border-gray-200"}`}>Income</button>
                </div>
              </div>
              {/* Step 2: Amount */}
              <div className="space-y-1 mt-4">
                <Label className="text-xs font-semibold text-gray-500">Amount (KES)</Label>
                <Input type="number" placeholder="0" value={form.amount} onChange={e => setForm({ ...form, amount: e.target.value })} className="h-12 rounded-xl text-lg font-bold text-center" />
              </div>
              {/* Step 3: Category */}
              <div className="space-y-1 mt-3">
                <Label className="text-xs font-semibold text-gray-500">Category</Label>
                <select value={form.category} onChange={e => setForm({ ...form, category: e.target.value })} className="w-full h-10 rounded-xl border border-gray-200 px-3 text-sm">{categories.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}</select>
              </div>
              <Button onClick={handleSubmit} disabled={!form.amount} className="w-full mt-4 bg-[#166534] hover:bg-[#14532D] cursor-pointer disabled:opacity-50 h-12 text-base font-bold">Save</Button>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* KPIs */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { title: "This Month Income", value: `KES ${monthIncome.toLocaleString()}`, icon: <TrendingUp className="h-5 w-5" />, color: "bg-emerald-500" },
          { title: "This Month Expenses", value: `KES ${monthExpenses.toLocaleString()}`, icon: <TrendingDown className="h-5 w-5" />, color: "bg-red-500" },
          { title: "All-time Income", value: `KES ${income.toLocaleString()}`, icon: <DollarSign className="h-5 w-5" />, color: "bg-[#166534]" },
          { title: "Net Profit", value: `KES ${profit.toLocaleString()}`, icon: <Wallet className="h-5 w-5" />, color: profit >= 0 ? "bg-[#166534]" : "bg-red-500" },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={fadeUp}>
            <Card className="border border-gray-100 hover:shadow-lg transition-all">
              <CardContent className="pt-5 pb-4 px-5">
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${kpi.color} text-white shadow-md mb-3`}>{kpi.icon}</div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-gray-900">{kpi.value}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {/* Charts */}
      <div className="grid lg:grid-cols-2 gap-6">
        {/* Monthly trend */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-gray-100">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-3">
                <BarChart3 className="h-4 w-4 text-[#166534]" />
                <p className="text-xs font-bold text-gray-900">Monthly Trend</p>
              </div>
              <ResponsiveContainer width="100%" height={200}>
                <BarChart data={monthlyChart}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                  <XAxis dataKey="month" tick={{ fontSize: 11, fill: "#94A3B8" }} />
                  <YAxis tick={{ fontSize: 11, fill: "#94A3B8" }} />
                  <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid #E5E7EB", fontSize: 12 }} formatter={(v: any) => `KES ${Number(v).toLocaleString()}`} />
                  <Bar dataKey="income" fill="#166534" radius={[4, 4, 0, 0]} name="Income" />
                  <Bar dataKey="expense" fill="#EF4444" radius={[4, 4, 0, 0]} name="Expenses" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </motion.div>

        {/* Expense breakdown */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-gray-100">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-3">
                <TrendingDown className="h-4 w-4 text-red-500" />
                <p className="text-xs font-bold text-gray-900">Expense Breakdown</p>
              </div>
              {expensePie.length === 0 ? (
                <div className="flex items-center justify-center h-[200px] text-sm text-gray-400">No expenses yet</div>
              ) : (
                <div className="flex items-center gap-4">
                  <ResponsiveContainer width="45%" height={160}>
                    <PieChart>
                      <Pie data={expensePie} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={30} outerRadius={55} strokeWidth={2} stroke="#fff">
                        {expensePie.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                      </Pie>
                      <Tooltip formatter={(v: any) => `KES ${Number(v).toLocaleString()}`} contentStyle={{ borderRadius: 8, border: "1px solid #E5E7EB", fontSize: 11 }} />
                    </PieChart>
                  </ResponsiveContainer>
                  <div className="space-y-2 flex-1">
                    {expensePie.sort((a, b) => b.value - a.value).slice(0, 5).map((e, i) => (
                      <div key={e.name} className="flex items-center gap-2">
                        <div className="h-2.5 w-2.5 rounded-full flex-shrink-0" style={{ background: COLORS[i % COLORS.length] }} />
                        <span className="text-[10px] text-gray-500 capitalize flex-1">{e.name}</span>
                        <span className="text-[10px] font-bold text-gray-900">KES {e.value.toLocaleString()}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </motion.div>
      </div>

      {/* Income breakdown */}
      {incomePie.length > 0 && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-gray-100">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-3">
                <TrendingUp className="h-4 w-4 text-emerald-500" />
                <p className="text-xs font-bold text-gray-900">Income by Source</p>
              </div>
              <div className="flex flex-wrap gap-3">
                {incomePie.sort((a, b) => b.value - a.value).map((e, i) => (
                  <div key={e.name} className="flex items-center gap-2 rounded-xl border border-gray-100 px-4 py-2.5">
                    <div className="h-3 w-3 rounded-full" style={{ background: COLORS[i % COLORS.length] }} />
                    <span className="text-xs font-medium text-gray-700 capitalize">{e.name}</span>
                    <span className="text-xs font-bold text-emerald-700">KES {e.value.toLocaleString()}</span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Transactions list */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-gray-100">
          <CardHeader className="pb-2"><div className="flex items-center gap-2"><DollarSign className="h-4 w-4 text-[#166534]" /><CardTitle className="text-base font-bold">Recent Transactions</CardTitle></div></CardHeader>
          <CardContent className="p-3 space-y-2">
            {txs.slice(0, 25).map((t: any) => {
              const isIncome = t.type === "income";
              return (                  <div key={t.id} className="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                  <div className="flex items-center gap-3">
                    <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${isIncome ? "bg-emerald-50 text-emerald-600" : "bg-red-50 text-red-500"}`}>{isIncome ? <ArrowUpRight className="h-3.5 w-3.5" /> : <ArrowDownRight className="h-3.5 w-3.5" />}</div>
                    <div>
                      <p className="text-xs font-bold text-gray-900">{t.description || "Transaction"}</p>
                      <p className="text-[10px] text-gray-400">{new Date(t.date).toLocaleDateString()} - {(t.category || "other").replace(/_/g, " ")}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <p className={`text-sm font-extrabold ${isIncome ? "text-emerald-700" : "text-gray-600"}`}>{isIncome ? "+" : "-"}KES {Number(t.amount).toLocaleString()}</p>
                    <button onClick={() => handleEdit(t)} className="text-gray-400 hover:text-[#166534] cursor-pointer"><Pencil className="h-3 w-3" /></button>
                    <button onClick={() => handleDelete(t.id)} className="text-gray-400 hover:text-red-500 cursor-pointer"><Trash2 className="h-3 w-3" /></button>
                  </div>
                </div>
              );
            })}
          </CardContent>
        </Card>
      </motion.div>
      {ToastComponent}
    </div>
  );
}
