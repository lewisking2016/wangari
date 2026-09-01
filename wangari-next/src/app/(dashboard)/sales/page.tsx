"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { DollarSign, CheckCircle, Clock } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/shared/empty-state";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

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

  const kpis = [
    { title: "Total Revenue", value: "KES " + totalRevenue.toLocaleString(), icon: <DollarSign className="h-5 w-5" />, change: sales.length + " sales", color: "from-[#166534] to-[#14532D]" },
    { title: "Paid", value: "KES " + totalPaid.toLocaleString(), icon: <CheckCircle className="h-5 w-5" />, change: sales.filter(s => s.paymentStatus === "paid").length + " orders", color: "from-[#22C55E] to-[#16A34A]" },
    { title: "Pending", value: "KES " + pending.toLocaleString(), icon: <Clock className="h-5 w-5" />, change: sales.filter(s => s.paymentStatus !== "paid").length + " orders", color: "from-[#15803D] to-[#166534]" },
  ];

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Sales" description="Track customer orders and payments" />
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-4">
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
                          <Badge className={s.paymentStatus === "paid" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-[#14532D]/10 text-[#14532D] border-[#14532D]/20"}>
                            {s.paymentStatus}
                          </Badge>
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
