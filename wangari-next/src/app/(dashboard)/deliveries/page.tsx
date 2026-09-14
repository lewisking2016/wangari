"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Milk, Wheat, Leaf, Truck, Plus, X, Trash2, ReceiptText, AlertTriangle, CheckCircle2 } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4 } } };

const COMMODITIES = [
  { value: "milk", label: "Milk", unit: "litres", icon: Milk },
  { value: "coffee_cherry", label: "Coffee cherry", unit: "kg", icon: Leaf },
  { value: "maize", label: "Maize", unit: "kg", icon: Wheat },
  { value: "other", label: "Other produce", unit: "kg", icon: Truck },
];

const statusBadge: Record<string, { label: string; cls: string }> = {
  pending: { label: "Awaiting payment", cls: "bg-amber-50 text-amber-700 border-amber-200" },
  paid: { label: "Paid", cls: "bg-green-50 text-green-700 border-green-200" },
  disputed: { label: "Disputed", cls: "bg-red-50 text-red-700 border-red-200" },
};

interface Statement {
  month: string; deliveries: number; gross: number; deductions: number;
  inputExpenses: number; net: number; paid: number; outstanding: number;
  byCommodity: Record<string, { quantity: number; gross: number; deliveries: number }>;
}

export default function DeliveriesPage() {
  const [deliveries, setDeliveries] = React.useState<any[]>([]);
  const [statement, setStatement] = React.useState<Statement | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const { showToast, ToastComponent } = useToast();

  const [form, setForm] = React.useState({
    date: new Date().toISOString().slice(0, 10),
    commodity: "milk",
    quantity: "",
    buyer: "",
    receiptRef: "",
    unitPrice: "",
    deductionLabel: "",
    deductionAmount: "",
  });

  const month = new Date().toISOString().slice(0, 7);

  const load = () => {
    Promise.all([api.get("/api/deliveries"), api.get(`/api/deliveries/statement?month=${month}`)])
      .then(([d, s]) => { setDeliveries(Array.isArray(d) ? d : []); setStatement(s); setLoading(false); })
      .catch(() => setLoading(false));
  };
  React.useEffect(load, []);

  const submit = async () => {
    if (!form.quantity || !form.buyer) { showToast("Enter quantity and buyer", "error"); return; }
    const deductions = form.deductionAmount && Number(form.deductionAmount) > 0
      ? [{ label: form.deductionLabel || "Deduction", amount: Number(form.deductionAmount) }] : [];
    try {
      await api.post("/api/deliveries", {
        date: form.date, commodity: form.commodity, quantity: Number(form.quantity),
        buyer: form.buyer, receiptRef: form.receiptRef || undefined,
        unitPrice: form.unitPrice ? Number(form.unitPrice) : undefined, deductions,
      });
      showToast("Delivery recorded — your money trail is building", "success");
      setShowForm(false);
      setForm({ ...form, quantity: "", buyer: "", receiptRef: "", unitPrice: "", deductionLabel: "", deductionAmount: "" });
      load();
    } catch { showToast("Failed to record delivery", "error"); }
  };

  const markPaid = async (id: number) => {
    const d = deliveries.find((x) => x.id === id);
    const gross = d?.expectedPay ?? 0;
    const ded = (d?.deductions ?? []).reduce((s: number, x: any) => s + Number(x.amount), 0);
    try {
      await api.patch(`/api/deliveries/${id}`, { status: "paid", paidAmount: gross - ded });
      showToast("Marked as paid", "success"); load();
    } catch { showToast("Failed", "error"); }
  };

  const remove = async (id: number) => {
    try { await api.delete(`/api/deliveries/${id}`); load(); } catch { showToast("Failed", "error"); }
  };

  const money = (n: number) => `KES ${Number(n).toLocaleString()}`;
  const commodityInfo = COMMODITIES.find((c) => c.value === form.commodity)!;

  return (
    <div className="space-y-6 p-4 md:p-6">
      {ToastComponent}
      <PageHeader
        title="Deliveries"
        description="Every litre and kilo you deliver — recorded, with what you're owed"
        action={<Button onClick={() => setShowForm(!showForm)}>{showForm ? <><X className="h-4 w-4 mr-2" />Close</> : <><Plus className="h-4 w-4 mr-2" />Record delivery</>}</Button>}
      />

      {/* Monthly statement — the dispute-proof summary */}
      {statement && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border-green-100 bg-gradient-to-br from-green-50/60 to-white">
            <CardContent className="pt-6">
              <div className="flex items-center gap-2 mb-4">
                <ReceiptText className="h-5 w-5 text-green-600" />
                <h2 className="font-semibold text-lg">Statement — {new Date(statement.month + "-01").toLocaleString("en", { month: "long", year: "numeric" })}</h2>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Delivered (gross)</p>
                  <p className="text-2xl font-bold text-green-700">{money(statement.gross)}</p>
                  <p className="text-xs text-muted-foreground">{statement.deliveries} deliveries</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Deductions</p>
                  <p className="text-2xl font-bold text-amber-600">−{money(statement.deductions)}</p>
                  <p className="text-xs text-muted-foreground">AI, agrovet, transport…</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Farm input expenses</p>
                  <p className="text-2xl font-bold text-red-500">−{money(statement.inputExpenses)}</p>
                  <p className="text-xs text-muted-foreground">Feed, seed, fertilizer, labour</p>
                </div>
                <div className="border-l pl-4">
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Net this month</p>
                  <p className={`text-2xl font-bold ${statement.net >= 0 ? "text-green-700" : "text-red-600"}`}>{money(statement.net)}</p>
                  <p className="text-xs text-muted-foreground">{money(statement.outstanding)} still outstanding</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Record delivery form */}
      {showForm && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card>
            <CardContent className="pt-6 space-y-4">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                {COMMODITIES.map((c) => (
                  <button key={c.value} type="button" onClick={() => setForm({ ...form, commodity: c.value })}
                    className={`rounded-xl border p-3 text-left transition ${form.commodity === c.value ? "border-green-500 bg-green-50" : "border-border hover:border-green-300"}`}>
                    <c.icon className={`h-5 w-5 mb-1 ${form.commodity === c.value ? "text-green-600" : "text-muted-foreground"}`} />
                    <span className="text-sm font-medium">{c.label}</span>
                  </button>
                ))}
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div><Label>Date</Label><Input type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} /></div>
                <div><Label>Quantity ({commodityInfo.unit})</Label><Input type="number" step="0.5" placeholder="e.g. 12" value={form.quantity} onChange={(e) => setForm({ ...form, quantity: e.target.value })} /></div>
                <div><Label>Delivered to (buyer)</Label><Input placeholder="e.g. Brookside, factory name, broker" value={form.buyer} onChange={(e) => setForm({ ...form, buyer: e.target.value })} /></div>
                <div><Label>Receipt / slip no. (optional)</Label><Input placeholder="from the delivery book" value={form.receiptRef} onChange={(e) => setForm({ ...form, receiptRef: e.target.value })} /></div>
                <div><Label>Price per {commodityInfo.unit} (optional)</Label><Input type="number" step="0.5" placeholder="KES" value={form.unitPrice} onChange={(e) => setForm({ ...form, unitPrice: e.target.value })} /></div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-3 md:col-span-2">
                <div><Label>Deduction label (optional)</Label><Input placeholder="e.g. AI service, agrovet" value={form.deductionLabel} onChange={(e) => setForm({ ...form, deductionLabel: e.target.value })} /></div>
                <div><Label>Deduction amount (optional)</Label><Input type="number" placeholder="KES" value={form.deductionAmount} onChange={(e) => setForm({ ...form, deductionAmount: e.target.value })} /></div>
              </div>
              <div className="flex justify-end gap-2">
                <Button variant="outline" onClick={() => setShowForm(false)}>Cancel</Button>
                <Button onClick={submit} disabled={!form.quantity || !form.buyer}>Save delivery</Button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Delivery list */}
      <Card>
        <CardContent className="pt-6">
          {loading ? (
            <p className="text-sm text-muted-foreground py-8 text-center">Loading…</p>
          ) : deliveries.length === 0 ? (
            <EmptyState icon={<Truck className="h-8 w-8" />} title="No deliveries yet" description="Record your first delivery — even one line a day builds the record that wins disputes." />
          ) : (
            <div className="divide-y">
              {deliveries.map((d) => {
                const ded = (d.deductions ?? []).reduce((s: number, x: any) => s + Number(x.amount), 0);
                const net = (d.expectedPay ?? 0) - ded;
                const Icon = COMMODITIES.find((c) => c.value === d.commodity)?.icon ?? Truck;
                const sb = statusBadge[d.status] ?? statusBadge.pending;
                return (
                  <div key={d.id} className="flex items-center gap-3 py-3">
                    <div className="h-9 w-9 rounded-full bg-green-50 flex items-center justify-center shrink-0"><Icon className="h-4 w-4 text-green-600" /></div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium truncate">{Number(d.quantity).toLocaleString()} {d.unit} → {d.buyer}</p>
                      <p className="text-xs text-muted-foreground">
                        {new Date(d.date).toLocaleDateString()}
                        {d.receiptRef ? ` · Slip #${d.receiptRef}` : ""}
                        {ded > 0 ? ` · −KES ${ded.toLocaleString()} deductions` : ""}
                        {d.status === "paid" && d.paidAmount ? ` · paid KES ${Number(d.paidAmount).toLocaleString()}` : ""}
                      </p>
                    </div>
                    <div className="text-right shrink-0">
                      <p className="text-sm font-semibold">{d.expectedPay != null ? money(net) : "—"}</p>
                      <span className={`inline-flex text-[11px] px-2 py-0.5 rounded-full border ${sb.cls}`}>{sb.label}</span>
                    </div>
                    {d.status === "pending" && (
                      <Button size="sm" variant="outline" onClick={() => markPaid(d.id)}><CheckCircle2 className="h-3.5 w-3.5 mr-1" />Paid</Button>
                    )}
                    <Button size="sm" variant="ghost" onClick={() => remove(d.id)}><Trash2 className="h-3.5 w-3.5 text-red-400" /></Button>
                  </div>
                );
              })}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
