"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Users, Plus, X, Phone, Mail, MapPin, Trash2, DollarSign, ShoppingCart } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Avatar } from "@/components/ui/avatar";
import { useToast } from "@/components/shared/toast";
import { EmptyState } from "@/components/shared/empty-state";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

export default function CustomersPage() {
  const [customers, setCustomers] = React.useState<any[]>([]);
  const [sales, setSales] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [form, setForm] = React.useState({ name: "", phone: "", email: "", address: "" });
  const { showToast, ToastComponent } = useToast();

  const load = () => {
    Promise.all([
      api.get("/api/customers"),
      api.get("/api/sales"),
    ]).then(([c, s]) => { setCustomers(Array.isArray(c) ? c : []); setSales(Array.isArray(s) ? s : []); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const handleSubmit = async () => {
    await api.post("/api/customers", form);
    setForm({ name: "", phone: "", email: "", address: "" });
    setShowForm(false);
    showToast("Customer added!");
    load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this customer?")) return;
    await api.delete("/api/customers/" + id);
    load();
  };

  // Calculate stats per customer
  const customerStats = customers.map(c => {
    const customerSales = sales.filter(s => s.customerId === c.id);
    const totalSpent = customerSales.reduce((s, sale) => s + Number(sale.totalAmount), 0);
    const totalOwed = customerSales.reduce((s, sale) => s + (Number(sale.totalAmount) - Number(sale.amountPaid)), 0);
    return { ...c, totalSpent, totalOwed, salesCount: customerSales.length };
  });

  const totalRevenue = customerStats.reduce((s, c) => s + c.totalSpent, 0);
  const totalOwed = customerStats.reduce((s, c) => s + c.totalOwed, 0);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Customers" description="Manage customer profiles and purchase history"
          action={<Button onClick={() => setShowForm(!showForm)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />Add Customer</Button>}
        />
      </motion.div>

      {showForm && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Add New Customer</h3>
                <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Name</Label><Input placeholder="e.g. Grace Wanjiku" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Phone</Label><Input placeholder="+254 7XX XXX XXX" value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Email</Label><Input placeholder="customer@email.com" value={form.email} onChange={e => setForm({ ...form, email: e.target.value })} className="h-10 rounded-xl" /></div>
                <div className="flex items-end"><Button onClick={handleSubmit} className="w-full bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button></div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-4">
        {[
          { title: "Total Customers", value: String(customers.length), icon: <Users className="h-5 w-5" /> },
          { title: "Total Revenue", value: "KES " + totalRevenue.toLocaleString(), icon: <DollarSign className="h-5 w-5" /> },
          { title: "Outstanding Credit", value: "KES " + totalOwed.toLocaleString(), icon: <ShoppingCart className="h-5 w-5" /> },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={scaleIn} whileHover={{ y: -4 }}>
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-all duration-300">
              <CardContent className="pt-6 pb-4 px-5">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white shadow-md mb-3">{kpi.icon}</div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] mb-1">{kpi.title}</p>
                <p className="text-2xl font-extrabold text-[#0F172A]">{kpi.value}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {customers.length === 0 ? <EmptyState title="No customers" description="Add your first customer." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {customerStats.map(c => (
            <motion.div key={c.id} variants={scaleIn} whileHover={{ y: -4 }}>
              <Card className="border border-[#E5E7EB] hover:shadow-xl hover:border-[#BBF7D0] transition-all duration-300">
                <CardContent className="p-6">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                      <Avatar name={c.name} size="md" />
                      <div>
                        <h3 className="font-bold text-[#0F172A]">{c.name}</h3>
                        {c.phone && <p className="text-xs text-[#64748B] flex items-center gap-1"><Phone className="h-3 w-3" />{c.phone}</p>}
                        {c.email && <p className="text-xs text-[#64748B] flex items-center gap-1"><Mail className="h-3 w-3" />{c.email}</p>}
                      </div>
                    </div>
                    <button onClick={() => handleDelete(c.id)} className="text-[#94A3B8] hover:text-red-500 cursor-pointer"><Trash2 className="h-3.5 w-3.5" /></button>
                  </div>
                  <div className="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div className="rounded-lg bg-[#FAFBFC] p-2"><p className="text-[10px] text-[#94A3B8] uppercase">Orders</p><p className="font-bold text-[#0F172A]">{c.salesCount}</p></div>
                    <div className="rounded-lg bg-[#FAFBFC] p-2"><p className="text-[10px] text-[#94A3B8] uppercase">Spent</p><p className="font-bold text-[#166534] text-xs">KES {c.totalSpent.toLocaleString()}</p></div>
                    <div className="rounded-lg bg-[#FAFBFC] p-2"><p className="text-[10px] text-[#94A3B8] uppercase">Owed</p><p className="font-bold text-amber-600 text-xs">KES {c.totalOwed.toLocaleString()}</p></div>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          ))}
        </motion.div>
      )}
      {ToastComponent}
    </div>
  );
}
