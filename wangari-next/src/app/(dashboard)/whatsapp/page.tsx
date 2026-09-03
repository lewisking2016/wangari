"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { MessageSquare, Send, FileText, Clock, Users, ShoppingCart, CloudSun, Search, ChevronRight, X, CheckCircle2 } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/components/shared/toast";
import { EmptyState } from "@/components/shared/empty-state";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const TEMPLATES = [
  { id: "invoice", title: "Send Invoice", desc: "Share invoice details with customer", icon: FileText, color: "bg-[#166534]" },
  { id: "reminder", title: "Payment Reminder", desc: "Remind about outstanding balance", icon: Clock, color: "bg-amber-500" },
  { id: "order", title: "Order Ready", desc: "Notify customer order is ready", icon: ShoppingCart, color: "bg-blue-500" },
  { id: "broadcast", title: "Bulk Message", desc: "Send to all customers at once", icon: Users, color: "bg-violet-500" },
  { id: "weather", title: "Weather Alert", desc: "Warn customers about weather", icon: CloudSun, color: "bg-rose-500" },
  { id: "custom", title: "Custom Message", desc: "Write your own message", icon: MessageSquare, color: "bg-emerald-500" },
];

function openWhatsApp(phone: string, message: string) {
  const clean = phone.replace(/[^0-9+]/g, "");
  const num = clean.startsWith("+") ? clean.slice(1) : clean.startsWith("254") ? clean : "254" + clean.replace(/^0/, "");
  const url = `https://wa.me/${num}?text=${encodeURIComponent(message)}`;
  window.open(url, "_blank");
}

export default function WhatsAppPage() {
  const [customers, setCustomers] = React.useState<any[]>([]);
  const [sales, setSales] = React.useState<any[]>([]);
  const [invoices, setInvoices] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [activeTemplate, setActiveTemplate] = React.useState<string | null>(null);
  const [selectedCustomer, setSelectedCustomer] = React.useState<any>(null);
  const [customMessage, setCustomMessage] = React.useState("");
  const [search, setSearch] = React.useState("");
  const [sentLog, setSentLog] = React.useState<{ phone: string; message: string; time: number }[]>([]);
  const { showToast, ToastComponent } = useToast();

  React.useEffect(() => {
    Promise.all([api.get("/api/customers"), api.get("/api/sales"), api.get("/api/invoices")])
      .then(([c, s, i]) => { setCustomers(Array.isArray(c) ? c : []); setSales(Array.isArray(s) ? s : []); setInvoices(Array.isArray(i) ? i : []); setLoading(false); })
      .catch(() => setLoading(false));
  }, []);

  const filtered = customers.filter(c => !search || c.name.toLowerCase().includes(search.toLowerCase()) || c.phone?.includes(search));

  const getCustomerData = (customerId: number) => {
    const custSales = sales.filter(s => s.customerId === customerId);
    const custInvoices = invoices.filter(i => i.customerId === customerId);
    const totalSpent = custSales.reduce((s, sale) => s + Number(sale.totalAmount), 0);
    const totalOwed = custSales.reduce((s, sale) => s + (Number(sale.totalAmount) - Number(sale.amountPaid)), 0);
    const latestInvoice = custInvoices[0];
    return { totalSpent, totalOwed, latestInvoice, salesCount: custSales.length };
  };

  const generateMessage = (templateId: string, customer: any) => {
    const data = getCustomerData(customer.id);
    const farmName = "Your Farm"; // Could be from settings

    switch (templateId) {
      case "invoice":
        if (!data.latestInvoice) return "No invoice found for this customer.";
        const items = Array.isArray(data.latestInvoice.items) ? data.latestInvoice.items : [];
        const itemList = items.map((it: any) => `- ${it.name}: KES ${Number(it.price || 0).toLocaleString()}`).join("\n");
        return `Hello ${customer.name},\n\nHere is your invoice from ${farmName}:\nInvoice: ${data.latestInvoice.invoiceNumber}\n${itemList}\nTotal: KES ${Number(data.latestInvoice.totalAmount).toLocaleString()}\nPaid: KES ${Number(data.latestInvoice.amountPaid).toLocaleString()}\nBalance: KES ${(Number(data.latestInvoice.totalAmount) - Number(data.latestInvoice.amountPaid)).toLocaleString()}\n\nThank you for your business!`;
      case "reminder":
        if (data.totalOwed <= 0) return "This customer has no outstanding balance.";
        return `Hello ${customer.name},\n\nThis is a friendly reminder that you have an outstanding balance of KES ${data.totalOwed.toLocaleString()} with ${farmName}.\n\nPlease pay at your earliest convenience.\n\nThank you!`;
      case "order":
        return `Hello ${customer.name},\n\nYour order from ${farmName} is ready for collection/delivery.\n\nPlease confirm your preferred pickup time.\n\nThank you!`;
      case "weather":
        return `Hello ${customer.name},\n\nWeather alert from ${farmName}: Rain is expected in the next 24 hours. Plan your farm activities accordingly.\n\nStay safe!`;
      case "custom":
        return customMessage || "Type your message above.";
      default:
        return "";
    }
  };

  const handleSend = (phone: string, message: string) => {
    if (!phone) { showToast("No phone number for this customer"); return; }
    openWhatsApp(phone, message);
    setSentLog(prev => [{ phone, message, time: Date.now() }, ...prev].slice(0, 20));
    showToast("Opening WhatsApp...");
  };

  const handleBulkSend = (templateId: string) => {
    const customersWithPhone = customers.filter(c => c.phone);
    if (customersWithPhone.length === 0) { showToast("No customers with phone numbers"); return; }
    customersWithPhone.forEach(c => {
      const msg = generateMessage(templateId, c);
      if (msg && c.phone) openWhatsApp(c.phone, msg);
    });
    showToast(`Opening WhatsApp for ${customersWithPhone.length} customers`);
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  // Template selected — pick customer
  if (activeTemplate && activeTemplate !== "broadcast") {
    return (
      <div className="space-y-6">
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <button onClick={() => { setActiveTemplate(null); setSelectedCustomer(null); }} className="text-xs font-bold text-[#166534] mb-3 cursor-pointer">&larr; Back to templates</button>
          <h1 className="text-xl font-extrabold text-[#0F172A]">{TEMPLATES.find(t => t.id === activeTemplate)?.title}</h1>
          <p className="text-sm text-[#64748B] mt-1">Select a customer to send to</p>
        </motion.div>

        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
          <input placeholder="Search customer..." value={search} onChange={e => setSearch(e.target.value)} className="w-full h-10 rounded-xl border border-[#E5E7EB] pl-9 pr-3 text-sm" />
        </div>

        {!selectedCustomer ? (
          <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
            {filtered.map(c => {
              const data = getCustomerData(c.id);
              return (
                <motion.div key={c.id} variants={fadeUp}>
                  <button onClick={() => setSelectedCustomer(c)} className="w-full text-left">
                    <Card className="border border-[#E5E7EB] hover:border-[#BBF7D0] cursor-pointer transition-all">
                      <CardContent className="p-4 flex items-center justify-between">
                        <div>
                          <p className="text-sm font-bold text-[#0F172A]">{c.name}</p>
                          <p className="text-[10px] text-[#94A3B8]">{c.phone || "No phone"}</p>
                        </div>
                        <div className="flex items-center gap-2">
                          {data.totalOwed > 0 && <Badge className="bg-amber-50 text-amber-700 border-amber-200 text-[9px]">KES {data.totalOwed.toLocaleString()} owed</Badge>}
                          <ChevronRight className="h-4 w-4 text-[#94A3B8]" />
                        </div>
                      </CardContent>
                    </Card>
                  </button>
                </motion.div>
              );
            })}
          </motion.div>
        ) : (
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-[#E5E7EB]">
              <CardContent className="p-5 space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-bold text-[#0F172A]">{selectedCustomer.name}</p>
                    <p className="text-[10px] text-[#94A3B8]">{selectedCustomer.phone}</p>
                  </div>
                  <button onClick={() => setSelectedCustomer(null)} className="text-[#94A3B8] cursor-pointer"><X className="h-4 w-4" /></button>
                </div>

                {activeTemplate === "custom" && (
                  <div className="space-y-1">
                    <Label className="text-xs font-semibold text-[#64748B]">Your message</Label>
                    <textarea value={customMessage} onChange={e => setCustomMessage(e.target.value)} rows={4} className="w-full rounded-xl border border-[#E5E7EB] p-3 text-sm resize-none" placeholder="Type your message..." />
                  </div>
                )}

                <div className="rounded-xl bg-[#F8FAFC] border border-[#E5E7EB] p-4">
                  <p className="text-[10px] font-bold text-[#64748B] uppercase mb-2">Preview</p>
                  <p className="text-xs text-[#0F172A] whitespace-pre-wrap">{generateMessage(activeTemplate, selectedCustomer)}</p>
                </div>

                <Button onClick={() => handleSend(selectedCustomer.phone, generateMessage(activeTemplate, selectedCustomer))}
                  disabled={!selectedCustomer.phone}
                  className="w-full bg-[#25D366] hover:bg-[#20BA5C] text-white cursor-pointer disabled:opacity-50">
                  <Send className="h-4 w-4 mr-2" />Send via WhatsApp
                </Button>
              </CardContent>
            </Card>
          </motion.div>
        )}
        {ToastComponent}
      </div>
    );
  }

  // Broadcast selected
  if (activeTemplate === "broadcast") {
    return (
      <div className="space-y-6">
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <button onClick={() => setActiveTemplate(null)} className="text-xs font-bold text-[#166534] mb-3 cursor-pointer">&larr; Back to templates</button>
          <h1 className="text-xl font-extrabold text-[#0F172A]">Bulk Message</h1>
          <p className="text-sm text-[#64748B] mt-1">Send to all customers with phone numbers</p>
        </motion.div>

        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-5 space-y-4">
            <div className="space-y-1">
              <Label className="text-xs font-semibold text-[#64748B]">Message</Label>
              <textarea value={customMessage} onChange={e => setCustomMessage(e.target.value)} rows={4} className="w-full rounded-xl border border-[#E5E7EB] p-3 text-sm resize-none" placeholder="Type your message to all customers..." />
            </div>
            <div className="rounded-xl bg-[#F8FAFC] border border-[#E5E7EB] p-3">
              <p className="text-[10px] text-[#94A3B8]">Will be sent to {customers.filter(c => c.phone).length} customers</p>
            </div>
            <Button onClick={() => {
              customers.filter(c => c.phone).forEach(c => openWhatsApp(c.phone, customMessage));
              showToast(`Opening WhatsApp for ${customers.filter(c => c.phone).length} customers`);
            }} disabled={!customMessage || customers.filter(c => c.phone).length === 0}
              className="w-full bg-[#25D366] hover:bg-[#20BA5C] text-white cursor-pointer disabled:opacity-50">
              <Send className="h-4 w-4 mr-2" />Send to All
            </Button>
          </CardContent>
        </Card>
        {ToastComponent}
      </div>
    );
  }

  // Main view — templates
  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="WhatsApp" description="Send messages to customers via WhatsApp" />
      </motion.div>

      {/* Quick stats */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-3 gap-3">
        {[
          { title: "Customers", value: String(customers.length), sub: `${customers.filter(c => c.phone).length} with phone` },
          { title: "With Balance", value: String(customers.filter(c => { const d = getCustomerData(c.id); return d.totalOwed > 0; }).length), sub: "Need reminders" },
          { title: "Sent Today", value: String(sentLog.filter(l => Date.now() - l.time < 86400000).length), sub: "messages" },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={fadeUp}>
            <Card className="border border-[#E5E7EB]">
              <CardContent className="pt-4 pb-3 px-4 text-center">
                <p className="text-xl font-extrabold text-[#0F172A]">{kpi.value}</p>
                <p className="text-[10px] text-[#94A3B8]">{kpi.title}</p>
                <p className="text-[9px] text-[#94A3B8]">{kpi.sub}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {/* Template cards */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
        {TEMPLATES.map(t => {
          const Icon = t.icon;
          return (
            <motion.div key={t.id} variants={fadeUp}>
              <button onClick={() => setActiveTemplate(t.id)} className="w-full text-left">
                <Card className="border border-[#E5E7EB] hover:border-[#BBF7D0] hover:shadow-md transition-all cursor-pointer">
                  <CardContent className="p-4 flex items-center gap-4">
                    <div className={`flex h-11 w-11 items-center justify-center rounded-xl ${t.color} text-white flex-shrink-0`}><Icon className="h-5 w-5" /></div>
                    <div className="flex-1">
                      <h3 className="text-sm font-bold text-[#0F172A]">{t.title}</h3>
                      <p className="text-[11px] text-[#94A3B8]">{t.desc}</p>
                    </div>
                    <ChevronRight className="h-4 w-4 text-[#94A3B8]" />
                  </CardContent>
                </Card>
              </button>
            </motion.div>
          );
        })}
      </motion.div>

      {/* Recent sent */}
      {sentLog.length > 0 && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <p className="text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">Recent Messages</p>
          <div className="space-y-1.5">
            {sentLog.slice(0, 5).map((log, i) => (
              <div key={i} className="flex items-center gap-3 p-2.5 rounded-xl bg-[#F8FAFC]">
                <CheckCircle2 className="h-4 w-4 text-[#25D366] flex-shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-[10px] text-[#94A3B8]">{log.phone}</p>
                  <p className="text-xs text-[#0F172A] truncate">{log.message.slice(0, 60)}...</p>
                </div>
                <p className="text-[9px] text-[#94A3B8]">{new Date(log.time).toLocaleTimeString()}</p>
              </div>
            ))}
          </div>
        </motion.div>
      )}
      {ToastComponent}
    </div>
  );
}
