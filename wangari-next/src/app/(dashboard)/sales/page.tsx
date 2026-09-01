"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { DollarSign, CheckCircle, Clock, Plus, X } from "lucide-react";
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

export default function SalesPage() {
  const [sales, setSales] = React.useState<any[]>([]);
  const [customers, setCustomers] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [form, setForm] = React.useState({ customerId: "", totalAmount: "", amountPaid: "", paymentStatus: "paid", items: "" });

  const load = () => {
    Promise.all([
      fetch("/api/sales").then(r => r.json()),
      fetch("/api/customers").then(r => r.json()),
    ]).then(([s, c]) => { setSales(s); setCustomers(c); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const totalRevenue = sales.reduce((s, sale) => s + Number(sale.totalAmount), 0);
  const totalPaid = sales.filter(s => s.paymentStatus === "paid").reduce((s, sale) => s + Number(sale.amountPaid), 0);
  const pending = totalRevenue - totalPaid;

  const handleSubmit = async () => {
    await fetch("/api/sales", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        customerId: Number(form.customerId) || null,
        totalAmount: Number(form.totalAmount),
        amountPaid: Number(form.amountPaid || form.totalAmount),
        paymentStatus: form.paymentStatus,
        items: [{ name: "Eggs", quantity: 1, price: Number(form.totalAmount) }],
      }),
    });
    setForm({ customerId: "", totalAmount: "", amountPaid: "", paymentStatus: "paid", items: "" });
    setShowForm(false);
    load();
  };

  const kpis = [
    { title: "Total Revenue", value: "KES " + totalRevenue.toLocaleString(), icon: <DollarSign className="h-5 w-5" />, change: sales.length + " sales" },
    { title: "Paid", value: "KES " + totalPaid.toLocaleString(), icon: <CheckCircle className="h-5 w-5" />, change: sales.filter(s => s.paymentStatus === "paid").length + " orders" },
    { title: "Pending", value: "KES " + pending.toLocaleString(), icon: <Clock className="h-5 w-5" />, change: sales.filter(s => s.paymentStatus !== "paid").length + " orders" },
  ];

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Sales" description="Track customer orders and payments"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Record Sale</Button>}
        />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Record New Sale</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Customer</Label>
                  <select value={form.customerId} onChange={e => setForm({ ...form, customerId: e.target.value })} className="w-full h-10 rounded-xl border border-[#E5E7EB] px-3 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534]">
                    <option value="">Walk-in</option>
                    {customers.map((c: any) => <option key={c.id} value={c.id}>{c.name}</option>)}
                  </select>
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Total Amount (KES)</Label>
                  <Input type="number" placeholder="0" value={form.totalAmount} onChange={e => setForm({ ...form, totalAmount: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Amount Paid (KES)</Label>
                  <Input type="number" placeholder="0" value={form.amountPaid} onChange={e => setForm({ ...form, amountPaid: e.target.value })} className="h-10 rounded-xl" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs font-semibold text-[#64748B]">Payment Status</Label>
                  <select value={form.paymentStatus} onChange={e => setForm({ ...form, paymentStatus: e.target.value })} className="w-full h-10 rounded-xl border border-[#E5E7EB] px-3 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534]">
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="pending">Pending</option>
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

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-4">
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

      {sales.length === 0 ? <EmptyState title="No sales" description="Record your first sale." /> : (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
                      <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Date</th>
                      <th className="px-5 py-3.5 text-left font-bold text-[#64748B] text-xs uppercase tracking-wider">Customer</th>
                      <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Amount</th>
                      <th className="px-5 py-3.5 text-right font-bold text-[#64748B] text-xs uppercase tracking-wider">Paid</th>
                      <th className="px-5 py-3.5 text-center font-bold text-[#64748B] text-xs uppercase tracking-wider">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {sales.slice(0, 30).map((s, i) => (
                      <motion.tr key={s.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: i * 0.03 }} className="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition-colors">
                        <td className="px-5 py-3.5 text-[#0F172A] font-medium">{new Date(s.saleDate).toLocaleDateString()}</td>
                        <td className="px-5 py-3.5 text-[#64748B]">{s.customer?.name || "Walk-in"}</td>
                        <td className="px-5 py-3.5 text-right font-bold text-[#0F172A] tabular-nums">KES {Number(s.totalAmount).toLocaleString()}</td>
                        <td className="px-5 py-3.5 text-right text-[#64748B] tabular-nums">KES {Number(s.amountPaid).toLocaleString()}</td>
                        <td className="px-5 py-3.5 text-center">
                          <Badge className={s.paymentStatus === "paid" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-gray-100 text-[#64748B] border-gray-200"}>{s.paymentStatus}</Badge>
                        </td>
                      </motion.tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}
    </div>
  );
}
