"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { FileText, Download, Search, Plus, CheckCircle2, Clock, AlertCircle, Eye, X, Printer, DollarSign, Palette, Settings, Save } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EmptyState } from "@/components/shared/empty-state";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";
import { INVOICE_TEMPLATES, generateInvoiceHtml, generateReceiptHtml, resolveAccent, getDefaultFarmProfile, type FarmProfile, type DocLayout } from "@/components/invoices/InvoiceTemplates";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

export default function InvoicesPage() {
  const [invoices, setInvoices] = React.useState<any[]>([]);
  const [sales, setSales] = React.useState<any[]>([]);
  const [customers, setCustomers] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [search, setSearch] = React.useState("");
  const [filter, setFilter] = React.useState<"all" | "paid" | "pending" | "partial">("all");
  const [viewInvoice, setViewInvoice] = React.useState<any>(null);
  const [showFromSale, setShowFromSale] = React.useState(false);
  const [showPayModal, setShowPayModal] = React.useState<number | null>(null);
  const [payAmount, setPayAmount] = React.useState("");
  const [selectedTemplate, setSelectedTemplate] = React.useState("professional");
  const [receiptTemplate, setReceiptTemplate] = React.useState("same");
  const [quoteTemplate, setQuoteTemplate] = React.useState("same");
  const [accentColor, setAccentColor] = React.useState("");
  const [layout, setLayout] = React.useState<DocLayout>({});
  const [savingTemplate, setSavingTemplate] = React.useState(false);
  const [farmProfile, setFarmProfile] = React.useState<FarmProfile>(getDefaultFarmProfile());
  const [showTemplatePicker, setShowTemplatePicker] = React.useState(false);
  const { showToast, ToastComponent } = useToast();

  const load = () => {
    Promise.all([api.get("/api/invoices"), api.get("/api/sales"), api.get("/api/customers"), api.get("/api/settings")])
      .then(([i, s, c, settingsData]) => {
        setInvoices(Array.isArray(i) ? i : []);
        setSales(Array.isArray(s) ? s : []);
        setCustomers(Array.isArray(c) ? c : []);
        const st = (settingsData as any).settings || {};
        setSelectedTemplate(st.farm_invoice_template || "professional");
        setReceiptTemplate(st.farm_receipt_template || "same");
        setQuoteTemplate(st.farm_quote_template || "same");
        setAccentColor(st.farm_invoice_accent_color || "");
        try { setLayout(st.farm_doc_layout ? JSON.parse(st.farm_doc_layout) : {}); } catch { setLayout({}); }
        setFarmProfile({
          businessName: st.farm_business_name || "",
          logoUrl: st.farm_logo_url || "",
          phone: st.farm_phone || "",
          email: st.farm_email || "",
          address: st.farm_address || "",
          tinNumber: st.farm_tin_number || "",
          slogan: st.farm_slogan || "",
          bankName: st.farm_bank_name || "",
          bankAccount: st.farm_bank_account || "",
          bankBranch: st.farm_bank_branch || "",
          invoiceNotes: st.farm_invoice_notes || "",
          invoiceTerms: st.farm_invoice_terms || "",
          accentColor: st.farm_invoice_accent_color || "",
          layout: (() => { try { return st.farm_doc_layout ? JSON.parse(st.farm_doc_layout) : {}; } catch { return {}; } })(),
        });
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };
  React.useEffect(() => { load(); }, []);

  const handleGenerateFromSale = async (saleId: number) => {
    await api.post(`/api/invoices/from-sale/${saleId}`, {});
    showToast("Invoice generated!"); setShowFromSale(false); load();
  };

  const handleRecordPayment = async (invoiceId: number) => {
    if (!payAmount) return;
    await api.patch(`/api/invoices/${invoiceId}`, { amountPaid: Number(payAmount) });
    setShowPayModal(null); setPayAmount("");
    showToast("Payment recorded!"); load();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this invoice?")) return;
    await api.delete("/api/invoices/" + id); load();
  };

  const filtered = invoices.filter(inv => {
    if (filter === "paid" && inv.paymentStatus !== "paid") return false;
    if (filter === "pending" && inv.paymentStatus !== "pending") return false;
    if (filter === "partial" && inv.paymentStatus !== "partial") return false;
    if (search) {
      const q = search.toLowerCase();
      return inv.invoiceNumber?.toLowerCase().includes(q) || inv.customer?.name?.toLowerCase().includes(q) || false;
    }
    return true;
  });

  const totalRevenue = invoices.reduce((s, i) => s + Number(i.totalAmount), 0);
  const totalPaid = invoices.reduce((s, i) => s + Number(i.amountPaid), 0);
  const totalOutstanding = totalRevenue - totalPaid;

  // Sales without invoices
  const salesWithoutInvoice = sales.filter(s => !invoices.some(inv => inv.saleId === s.id));

  const handlePrint = (inv: any) => {
    const html = generateInvoiceHtml(inv, selectedTemplate, { ...farmProfile, layout });
    const printWindow = window.open("", "_blank");
    if (printWindow) {
      printWindow.document.write(html);
      printWindow.document.close();
      setTimeout(() => printWindow.print(), 300);
    }
  };

  const handlePrintReceipt = (sale: any) => {
    const effective = receiptTemplate === "same" ? selectedTemplate : receiptTemplate;
    const html = generateReceiptHtml(sale, effective, { ...farmProfile, layout }, `RCP-${String(sale.id).padStart(5, "0")}`);
    const printWindow = window.open("", "_blank");
    if (printWindow) {
      printWindow.document.write(html);
      printWindow.document.close();
      setTimeout(() => printWindow.print(), 300);
    }
  };

  const handleSaveTemplate = async () => {
    setSavingTemplate(true);
    try {
      await api.put("/api/settings", {
        settings: {
          farm_invoice_template: selectedTemplate,
          farm_receipt_template: receiptTemplate,
          farm_quote_template: quoteTemplate,
          farm_invoice_accent_color: accentColor,
          farm_doc_layout: JSON.stringify(layout),
        },
      });
      setFarmProfile((p) => ({ ...p, accentColor }));
      showToast(receiptTemplate === "same" ? "Template saved — applies to invoices & receipts" : "Templates saved!");
      setShowTemplatePicker(false);
    } catch {
      showToast("Failed to save template. Please try again.");
    } finally {
      setSavingTemplate(false);
    }
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Invoices" description="Generate and manage customer invoices"
          action={<Button onClick={() => setShowFromSale(!showFromSale)} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Plus className="h-4 w-4 mr-2" />New Invoice</Button>} />
      </motion.div>

      {/* Generate from sale */}
      {showFromSale && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-4">
              <div className="flex items-center justify-between mb-3">
                <p className="text-sm font-bold text-[#0F172A]">Generate from Sale</p>
                <button onClick={() => setShowFromSale(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              {salesWithoutInvoice.length === 0 ? (
                <p className="text-xs text-[#94A3B8] text-center py-4">All sales already have invoices</p>
              ) : (
                <div className="space-y-2 max-h-64 overflow-y-auto">
                  {salesWithoutInvoice.slice(0, 10).map(s => (
                    <div key={s.id} className="flex items-center justify-between p-3 rounded-xl bg-[#F8FAFC] border border-[#E5E7EB]">
                      <div>
                        <p className="text-xs font-bold text-[#0F172A]">{s.customer?.name || "Walk-in"}</p>
                        <p className="text-[10px] text-[#94A3B8]">{new Date(s.saleDate).toLocaleDateString()}</p>
                      </div>
                      <div className="flex items-center gap-3">
                        <p className="text-sm font-extrabold text-[#0F172A]">KES {Number(s.totalAmount).toLocaleString()}</p>
                        <button onClick={() => handleGenerateFromSale(s.id)} className="px-3 py-1.5 rounded-lg bg-[#166534] text-white text-[10px] font-bold cursor-pointer">Generate</button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Template Picker */}
      <div className="flex items-center gap-2 flex-wrap">
        <button onClick={() => setShowTemplatePicker(!showTemplatePicker)}
          className="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#F8FAFC] border border-[#E5E7EB] hover:border-[#166534] hover:bg-[#F0FDF4] transition-all cursor-pointer">
          <Palette className="h-4 w-4 text-[#166534]" />
          <span className="text-xs font-bold text-[#0F172A]">Templates</span>
          <span className="text-[10px] font-bold text-[#166534] bg-[#F0FDF4] px-2 py-0.5 rounded-full border border-[#BBF7D0]">
            {INVOICE_TEMPLATES.find(t => t.id === selectedTemplate)?.name || "Professional"}
          </span>
          {receiptTemplate !== "same" && (
            <span className="text-[10px] font-bold text-[#1E3A5F] bg-[#EFF6FF] px-2 py-0.5 rounded-full border border-[#BFDBFE]">
              receipts: {INVOICE_TEMPLATES.find(t => t.id === receiptTemplate)?.name}
            </span>
          )}
          {quoteTemplate !== "same" && (
            <span className="text-[10px] font-bold text-[#7C2D12] bg-[#FFF7ED] px-2 py-0.5 rounded-full border border-[#FED7AA]">
              quotes: {INVOICE_TEMPLATES.find(t => t.id === quoteTemplate)?.name}
            </span>
          )}
        </button>
        {!farmProfile.businessName && (
          <a href="/settings" className="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold hover:bg-amber-100 cursor-pointer">
            <Settings className="h-3 w-3" />Set up farm profile for branded invoices
          </a>
        )}
      </div>

      {/* Template Picker Expanded */}
      {showTemplatePicker && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-5">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <h3 className="text-sm font-bold text-[#0F172A]">Choose Template</h3>
                  <p className="text-xs text-[#94A3B8] mt-1">One setting styles all invoices and receipts — new and existing.</p>
                </div>
                <button onClick={() => setShowTemplatePicker(false)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {INVOICE_TEMPLATES.map(template => (
                  <button key={template.id} onClick={() => setSelectedTemplate(template.id)}
                    className={`text-left rounded-2xl border-2 p-5 transition-all cursor-pointer ${
                      selectedTemplate === template.id
                        ? "border-[#166534] bg-[#F0FDF4] shadow-md"
                        : "border-[#E5E7EB] hover:border-[#BBF7D0] bg-white"
                    }`}>
                    {/* Mini preview */}
                    <div className="rounded-xl border border-[#E5E7EB] bg-white p-3 mb-3 overflow-hidden">
                      <div className="h-2 rounded-full mb-2" style={{ background: template.color, width: "40%" }} />
                      <div className="h-1 rounded bg-gray-100 mb-1 w-3/4" />
                      <div className="h-1 rounded bg-gray-100 mb-1 w-1/2" />
                      <div className="h-1 rounded bg-gray-100 mb-2 w-2/3" />
                      <div className="space-y-1">
                        <div className="flex justify-between"><div className="h-1 rounded bg-gray-100 w-1/3" /><div className="h-1 rounded bg-gray-100 w-1/4" /></div>
                        <div className="flex justify-between"><div className="h-1 rounded bg-gray-100 w-1/4" /><div className="h-1 rounded bg-gray-100 w-1/5" /></div>
                      </div>
                      <div className="mt-2 pt-2 border-t border-gray-100 flex justify-between">
                        <div className="h-1.5 rounded bg-gray-200 w-1/3" />
                        <div className="h-1.5 rounded w-1/4" style={{ background: template.color }} />
                      </div>
                    </div>
                    <p className="text-sm font-bold text-[#0F172A]">{template.name}</p>
                    <p className="text-[10px] text-[#94A3B8] mt-1">{template.preview}</p>
                    {selectedTemplate === template.id && (
                      <div className="mt-2 flex items-center gap-1 text-[10px] font-bold text-[#166534]">
                        <CheckCircle2 className="h-3 w-3" /> Invoice template
                      </div>
                    )}
                  </button>
                ))}
              </div>

              {/* Receipt template — defaults to following the invoice template */}
              <div className="mt-6 pt-5 border-t border-[#E5E7EB]">
                <p className="text-xs font-bold text-[#0F172A] mb-1">Sale receipts</p>
                <p className="text-[11px] text-[#94A3B8] mb-3">Receipts printed from the Sales page. By default they match the invoice template.</p>
                <div className="flex flex-wrap gap-2">
                  <button onClick={() => setReceiptTemplate("same")}
                    className={`px-3 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                      receiptTemplate === "same" ? "bg-[#166534] text-white border-[#166534]" : "bg-white text-[#64748B] border-[#E5E7EB] hover:border-[#BBF7D0]"
                    }`}>
                    Same as invoices
                  </button>
                  {INVOICE_TEMPLATES.map(t => (
                    <button key={t.id} onClick={() => setReceiptTemplate(t.id)}
                      className={`px-3 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                        receiptTemplate === t.id ? "bg-[#166534] text-white border-[#166534]" : "bg-white text-[#64748B] border-[#E5E7EB] hover:border-[#BBF7D0]"
                      }`}>
                      {t.name}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quote template — defaults to following the invoice template */}
              <div className="mt-6 pt-5 border-t border-[#E5E7EB]">
                <p className="text-xs font-bold text-[#0F172A] mb-1">Quotes &amp; estimates</p>
                <p className="text-[11px] text-[#94A3B8] mb-3">Quote documents get their own design — Corporate Navy and Teal Estimate work great here.</p>
                <div className="flex flex-wrap gap-2">
                  <button onClick={() => setQuoteTemplate("same")}
                    className={`px-3 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                      quoteTemplate === "same" ? "bg-[#166534] text-white border-[#166534]" : "bg-white text-[#64748B] border-[#E5E7EB] hover:border-[#BBF7D0]"
                    }`}>
                    Same as invoices
                  </button>
                  {INVOICE_TEMPLATES.map(t => (
                    <button key={t.id} onClick={() => setQuoteTemplate(t.id)}
                      className={`px-3 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                        quoteTemplate === t.id ? "bg-[#166534] text-white border-[#166534]" : "bg-white text-[#64748B] border-[#E5E7EB] hover:border-[#BBF7D0]"
                      }`}>
                      {t.name}
                    </button>
                  ))}
                </div>
              </div>

              {/* Accent color — Manta-style brand override */}
              <div className="mt-6 pt-5 border-t border-[#E5E7EB]">
                <p className="text-xs font-bold text-[#0F172A] mb-1">Brand accent</p>
                <p className="text-[11px] text-[#94A3B8] mb-3">Optional color override applied to headers, totals and tables across every template.</p>
                <div className="flex flex-wrap items-center gap-2">
                  {["", "#166534", "#1E3A5F", "#B45309", "#7C2D12", "#4C1D95", "#0F172A"].map(c => (
                    <button key={c || "default"} onClick={() => setAccentColor(c)}
                      title={c || "Template default"}
                      className={`h-8 w-8 rounded-full border-2 transition-all cursor-pointer flex items-center justify-center ${
                        accentColor === c ? "border-[#0F172A] scale-110" : "border-[#E5E7EB] hover:scale-105"
                      }`}
                      style={{ background: c || "linear-gradient(135deg,#E5E7EB 50%,#F8FAFC 50%)" }}>
                      {accentColor === c && <CheckCircle2 className="h-3.5 w-3.5 text-white drop-shadow" />}
                    </button>
                  ))}
                  <input
                    type="color"
                    value={accentColor || "#166534"}
                    onChange={e => setAccentColor(e.target.value)}
                    className="h-8 w-10 rounded cursor-pointer border border-[#E5E7EB] bg-white"
                    title="Custom color"
                  />
                  {accentColor && (
                    <button onClick={() => setAccentColor("")} className="text-[11px] font-bold text-[#94A3B8] hover:text-[#64748B] cursor-pointer">Reset</button>
                  )}
                </div>
              </div>

              {/* Layout customizer — move sections where you want them */}
              <div className="mt-6 pt-5 border-t border-[#E5E7EB]">
                <p className="text-xs font-bold text-[#0F172A] mb-1">Arrange sections</p>
                <p className="text-[11px] text-[#94A3B8] mb-3">Move the header, logo, totals and signature wherever you want — applies to every template.</p>
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                  {([
                    { key: "headerPosition", label: "Header (title & number)", options: [["left", "Left"], ["center", "Center"], ["right", "Right"]] },
                    { key: "logoPosition", label: "Logo", options: [["left", "Left"], ["center", "Center"], ["right", "Right"], ["hidden", "Hide"]] },
                    { key: "customerPosition", label: "Customer block", options: [["left", "Left"], ["right", "Right"]] },
                    { key: "totalsSide", label: "Totals block", options: [["left", "Left"], ["right", "Right"]] },
                    { key: "balancedTotals", label: "Opposite the totals", options: [["off", "Empty"], ["payment", "Payment QR"], ["notes", "Notes panel"]] },
                    { key: "signaturePosition", label: "Signature", options: [["left", "Left"], ["center", "Center"], ["right", "Right"], ["none", "Hide"]] },
                  ] as const).map(row => (
                    <div key={row.key}>
                      <Label className="text-[11px] font-semibold text-[#64748B] mb-1.5 block">{row.label}</Label>
                      <div className="flex flex-wrap gap-1.5">
                        {row.options.map(([val, lab]) => (
                          <button key={val} onClick={() => setLayout({ ...layout, [row.key]: val })}
                            className={`px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition-all cursor-pointer ${
                              (layout[row.key as keyof DocLayout] || (row.key === "headerPosition" || row.key === "totalsSide" || row.key === "signaturePosition" ? "right" : "left")) === val
                                ? "bg-[#166534] text-white border-[#166534]"
                                : "bg-white text-[#64748B] border-[#E5E7EB] hover:border-[#BBF7D0]"
                            }`}>
                            {lab}
                          </button>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
                <button onClick={() => setLayout({})} className="mt-3 text-[11px] font-bold text-[#94A3B8] hover:text-[#64748B] cursor-pointer">Reset to template defaults</button>
              </div>

              <div className="mt-5 flex items-center justify-between">
                <p className="text-[11px] text-[#94A3B8]">
                  Preview any invoice with the Print button — the saved template is used automatically.
                </p>
                <Button onClick={handleSaveTemplate} disabled={savingTemplate} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">
                  <Save className="h-4 w-4 mr-2" />{savingTemplate ? "Saving..." : "Save — Applies Everywhere"}
                </Button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* KPIs */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {[
          { title: "Total Invoiced", value: `KES ${totalRevenue.toLocaleString()}`, icon: <FileText className="h-5 w-5" />, color: "bg-[#166534]" },
          { title: "Collected", value: `KES ${totalPaid.toLocaleString()}`, icon: <CheckCircle2 className="h-5 w-5" />, color: "bg-emerald-500" },
          { title: "Outstanding", value: `KES ${totalOutstanding.toLocaleString()}`, icon: <AlertCircle className="h-5 w-5" />, color: totalOutstanding > 0 ? "bg-amber-500" : "bg-[#166534]" },
          { title: "Total Invoices", value: String(invoices.length), icon: <DollarSign className="h-5 w-5" />, color: "bg-[#166534]" },
        ].map(kpi => (
          <motion.div key={kpi.title} variants={fadeUp}>
            <Card className="border border-[#E5E7EB]">
              <CardContent className="pt-4 pb-3 px-4">
                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[#E6F4EA] text-[#166534] mb-2">{kpi.icon}</div>
                <p className="text-[10px] font-semibold uppercase tracking-wider text-[#64748B]">{kpi.title}</p>
                <p className="text-xl font-extrabold text-[#0F172A]">{kpi.value}</p>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {/* Filters + Search */}
      <div className="flex gap-2">
        {(["all", "paid", "pending", "partial"] as const).map(f => (
          <button key={f} onClick={() => setFilter(f)}
            className={`px-3 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer capitalize ${filter === f ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#64748B]"}`}>{f}</button>
        ))}
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" />
          <input placeholder="Search..." value={search} onChange={e => setSearch(e.target.value)} className="w-full h-10 rounded-xl border border-[#E5E7EB] pl-9 pr-3 text-sm" />
        </div>
      </div>

      {/* Invoice cards */}
      {filtered.length === 0 ? <EmptyState title="No invoices" description="Generate invoices from your sales." /> : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
          {filtered.map(inv => {
            const balance = Number(inv.totalAmount) - Number(inv.amountPaid);
            return (
              <motion.div key={inv.id} variants={fadeUp}>
                <Card className="border border-[#E5E7EB]">
                  <CardContent className="p-4">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <p className="text-xs font-bold text-[#166534] font-mono">{inv.invoiceNumber}</p>
                          <Badge className={inv.paymentStatus === "paid" ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0] text-[9px]" : inv.paymentStatus === "partial" ? "bg-amber-50 text-amber-700 border-amber-200 text-[9px]" : "bg-red-50 text-red-700 border-red-200 text-[9px]"}>{inv.paymentStatus}</Badge>
                        </div>
                        <p className="text-sm font-bold text-[#0F172A]">{inv.customer?.name || "Walk-in"}</p>
                        <p className="text-[10px] text-[#94A3B8]">{new Date(inv.createdAt).toLocaleDateString()}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-lg font-extrabold text-[#0F172A]">KES {Number(inv.totalAmount).toLocaleString()}</p>
                        {balance > 0 && <p className="text-[10px] font-bold text-amber-600">KES {balance.toLocaleString()} owing</p>}
                      </div>
                    </div>
                    <div className="flex gap-2 mt-3">
                      <button onClick={() => setViewInvoice(inv)} className="flex-1 flex items-center justify-center gap-1 py-2 rounded-xl bg-[#F1F5F9] text-[#64748B] text-xs font-bold hover:bg-[#E2E8F0] cursor-pointer"><Eye className="h-3 w-3" />View</button>
                      <button onClick={() => handlePrint(inv)} className="flex-1 flex items-center justify-center gap-1 py-2 rounded-xl bg-[#F1F5F9] text-[#64748B] text-xs font-bold hover:bg-[#E2E8F0] cursor-pointer"><Printer className="h-3 w-3" />Print</button>
                      {balance > 0 && (
                        <button onClick={() => { setShowPayModal(inv.id); setPayAmount(String(balance)); }}
                          className="flex-1 flex items-center justify-center gap-1 py-2 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 hover:bg-amber-100 cursor-pointer"><DollarSign className="h-3 w-3" />Pay</button>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </motion.div>
            );
          })}
        </motion.div>
      )}

      {/* View modal */}
      {viewInvoice && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" onClick={() => setViewInvoice(null)}>
          <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} onClick={e => e.stopPropagation()}>
            <Card className="w-96 max-h-[80vh] overflow-y-auto border border-[#E5E7EB]">
              <CardContent className="p-6 space-y-4">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-bold text-[#166534] font-mono">{viewInvoice.invoiceNumber}</p>
                  <button onClick={() => setViewInvoice(null)} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
                </div>
                <div className="flex justify-between text-xs">
                  <div><p className="text-[#94A3B8]">Customer</p><p className="font-bold text-[#0F172A]">{viewInvoice.customer?.name || "Walk-in"}</p></div>
                  <div className="text-right"><p className="text-[#94A3B8]">Date</p><p className="font-bold text-[#0F172A]">{new Date(viewInvoice.createdAt).toLocaleDateString()}</p></div>
                </div>
                {Array.isArray(viewInvoice.items) && viewInvoice.items.length > 0 && (
                  <div className="border-t border-[#E5E7EB] pt-3 space-y-1.5">
                    {viewInvoice.items.map((item: any, i: number) => (
                      <div key={i} className="flex justify-between text-xs">
                        <span className="text-[#64748B]">{item.name || "Item"} x{item.quantity || 1}</span>
                        <span className="font-bold text-[#0F172A]">KES {Number(item.price || 0).toLocaleString()}</span>
                      </div>
                    ))}
                  </div>
                )}
                <div className="border-t border-[#E5E7EB] pt-3 space-y-1.5">
                  <div className="flex justify-between text-xs"><span className="text-[#64748B]">Total</span><span className="font-extrabold text-[#0F172A]">KES {Number(viewInvoice.totalAmount).toLocaleString()}</span></div>
                  <div className="flex justify-between text-xs"><span className="text-[#64748B]">Paid</span><span className="font-bold text-emerald-600">KES {Number(viewInvoice.amountPaid).toLocaleString()}</span></div>
                  {Number(viewInvoice.totalAmount) - Number(viewInvoice.amountPaid) > 0 && (
                    <div className="flex justify-between text-xs"><span className="text-[#64748B]">Balance</span><span className="font-bold text-amber-600">KES {(Number(viewInvoice.totalAmount) - Number(viewInvoice.amountPaid)).toLocaleString()}</span></div>
                  )}
                </div>
                <button onClick={() => { handlePrint(viewInvoice); setViewInvoice(null); }} className="w-full flex items-center justify-center gap-2 py-2.5 bg-[#166534] text-white rounded-xl text-sm font-bold hover:bg-[#14532D] cursor-pointer"><Printer className="h-4 w-4" />Print Invoice</button>
              </CardContent>
            </Card>
          </motion.div>
        </div>
      )}

      {/* Pay modal */}
      {showPayModal && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
          <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }}>
            <Card className="w-80 border border-[#E5E7EB]">
              <CardContent className="p-6 space-y-4">
                <h3 className="text-sm font-bold text-[#0F172A]">Record Payment</h3>
                <Input type="number" placeholder="Amount" value={payAmount} onChange={e => setPayAmount(e.target.value)} className="h-12 rounded-xl text-lg font-bold text-center" />
                <div className="flex gap-2">
                  <Button onClick={() => setShowPayModal(null)} variant="outline" className="flex-1 cursor-pointer">Cancel</Button>
                  <Button onClick={() => handleRecordPayment(showPayModal)} className="flex-1 bg-[#166534] hover:bg-[#14532D] cursor-pointer">Save</Button>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        </div>
      )}

      {ToastComponent}
    </div>
  );
}
