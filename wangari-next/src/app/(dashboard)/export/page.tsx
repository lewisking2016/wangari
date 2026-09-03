"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { FileText, Download, Printer, TrendingUp, DollarSign, ShoppingCart, Package, Users, Bird, Leaf, BarChart3, ChevronRight } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useToast } from "@/components/shared/toast";
import { ListSkeleton } from "@/components/shared/skeleton";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const REPORTS = [
  { id: "profile", title: "Farm Profile", desc: "Complete farm overview for loan officers", icon: Bird, color: "bg-[#166534]" },
  { id: "production", title: "Production Report", desc: "Output trends — eggs, milk, meat, crops", icon: TrendingUp, color: "bg-emerald-500" },
  { id: "financial", title: "Financial Statement", desc: "Income vs expenses, profit/loss summary", icon: DollarSign, color: "bg-amber-500" },
  { id: "sales", title: "Sales Report", desc: "Customer list, revenue by product type", icon: ShoppingCart, color: "bg-blue-500" },
  { id: "inventory", title: "Inventory Report", desc: "Livestock, crops, and supplies with values", icon: Package, color: "bg-violet-500" },
  { id: "cashflow", title: "Cash Flow Projection", desc: "Monthly income vs expenses trend", icon: BarChart3, color: "bg-rose-500" },
];

export default function ExportPage() {
  const [data, setData] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [activeReport, setActiveReport] = React.useState<string | null>(null);
  const { showToast, ToastComponent } = useToast();

  React.useEffect(() => {
    api.get("/api/export").then(d => { setData(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  const handlePrint = (reportId: string) => {
    const d = data;
    if (!d) return;

    let html = "";
    const header = `<div style="text-align:center;margin-bottom:24px;border-bottom:2px solid #166534;padding-bottom:16px">
      <h1 style="color:#166534;margin:0;font-size:22px">Wangari Farm Manager</h1>
      <p style="margin:4px 0 0;color:#64748B;font-size:13px">${d.farm?.name || "Farm Report"} — ${d.farm?.location || ""}</p>
      <p style="margin:2px 0 0;color:#94A3B8;font-size:11px">Generated ${new Date().toLocaleDateString("en-KE", { year: "numeric", month: "long", day: "numeric" })}</p>
    </div>`;
    const footer = `<div style="text-align:center;margin-top:32px;font-size:10px;color:#999;border-top:1px solid #eee;padding-top:12px">Wangari by iMeanTech — Farm Management System</div>`;

    if (reportId === "profile") {
      html = `${header}
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Farm Profile</h2>
        <table style="width:100%;font-size:13px;margin-bottom:16px"><tbody>
          <tr><td style="padding:6px 0;color:#64748B;width:40%">Farm Name</td><td style="padding:6px 0;font-weight:bold">${d.farm?.name || "-"}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Location</td><td style="padding:6px 0;font-weight:bold">${d.farm?.location || "-"}, ${d.farm?.county || ""}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Farm Type</td><td style="padding:6px 0;font-weight:bold">${d.farm?.farmType || "Mixed"}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Livestock</td><td style="padding:6px 0;font-weight:bold">${d.livestock?.total || 0} heads (${d.livestock?.groups || 0} groups)</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Active Crops</td><td style="padding:6px 0;font-weight:bold">${d.crops?.total || 0} (${(d.crops?.types || []).join(", ") || "None"})</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Workers</td><td style="padding:6px 0;font-weight:bold">${d.workers?.active || 0} active (${d.workers?.total || 0} total)</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Customers</td><td style="padding:6px 0;font-weight:bold">${d.sales?.customers || 0}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Farm Asset Value</td><td style="padding:6px 0;font-weight:bold;color:#166534">KES ${((d.livestock?.value || 0) + (d.inventory?.totalValue || 0)).toLocaleString()}</td></tr>
        </tbody></table>
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Financial Summary</h2>
        <table style="width:100%;font-size:13px"><tbody>
          <tr><td style="padding:6px 0;color:#64748B">Total Revenue</td><td style="padding:6px 0;font-weight:bold;color:#166534">KES ${(d.financials?.totalIncome || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Expenses</td><td style="padding:6px 0;font-weight:bold;color:#EF4444">KES ${(d.financials?.totalExpenses || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Net Profit</td><td style="padding:6px 0;font-weight:bold;color:${(d.financials?.netProfit || 0) >= 0 ? "#166534" : "#EF4444"}">KES ${(d.financials?.netProfit || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Outstanding Credit</td><td style="padding:6px 0;font-weight:bold">KES ${(d.financials?.outstandingCredit || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Monthly Staff Cost</td><td style="padding:6px 0;font-weight:bold">KES ${(d.workers?.monthlyWages || 0).toLocaleString()}</td></tr>
        </tbody></table>${footer}`;
    } else if (reportId === "production") {
      html = `${header}
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Production Report</h2>
        <table style="width:100%;font-size:13px;margin-bottom:16px"><tbody>
          <tr><td style="padding:6px 0;color:#64748B">Total Eggs Produced</td><td style="padding:6px 0;font-weight:bold">${(d.production?.totalEggs || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Milk (Litres)</td><td style="padding:6px 0;font-weight:bold">${(d.production?.totalMilk || 0).toFixed(1)}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Meat (kg)</td><td style="padding:6px 0;font-weight:bold">${(d.production?.totalMeat || 0).toFixed(1)}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Feed Used (kg)</td><td style="padding:6px 0;font-weight:bold">${(d.production?.totalFeedUsed || 0).toFixed(1)}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Mortality</td><td style="padding:6px 0;font-weight:bold">${d.production?.totalMortality || 0}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Production Records</td><td style="padding:6px 0;font-weight:bold">${d.production?.records || 0}</td></tr>
        </tbody></table>
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Livestock Inventory</h2>
        <table style="width:100%;font-size:13px"><thead><tr style="border-bottom:2px solid #166534"><th style="text-align:left;padding:6px;font-size:11px;color:#64748B">Species</th><th style="text-align:right;padding:6px;font-size:11px;color:#64748B">Count</th></tr></thead><tbody>
        ${Object.entries(d.livestock?.speciesBreakdown || {}).map(([k, v]: [string, any]) => `<tr style="border-bottom:1px solid #eee"><td style="padding:6px 0;text-transform:capitalize">${k.replace(/_/g, " ")}</td><td style="padding:6px 0;text-align:right;font-weight:bold">${v}</td></tr>`).join("")}
        </tbody></table>${footer}`;
    } else if (reportId === "financial") {
      html = `${header}
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Financial Statement</h2>
        <table style="width:100%;font-size:13px;margin-bottom:16px"><tbody>
          <tr><td style="padding:8px 0;color:#64748B">Total Revenue</td><td style="padding:8px 0;font-weight:bold;color:#166534;text-align:right">KES ${(d.financials?.totalIncome || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:8px 0;color:#64748B">Total Expenses</td><td style="padding:8px 0;font-weight:bold;color:#EF4444;text-align:right">KES ${(d.financials?.totalExpenses || 0).toLocaleString()}</td></tr>
          <tr style="border-top:2px solid #166534"><td style="padding:8px 0;font-weight:bold">Net Profit/Loss</td><td style="padding:8px 0;font-weight:bold;text-align:right;color:${(d.financials?.netProfit || 0) >= 0 ? "#166534" : "#EF4444"}">KES ${(d.financials?.netProfit || 0).toLocaleString()}</td></tr>
        </tbody></table>
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Monthly Breakdown</h2>
        <table style="width:100%;font-size:13px"><thead><tr style="border-bottom:2px solid #166534"><th style="text-align:left;padding:6px;font-size:11px;color:#64748B">Month</th><th style="text-align:right;padding:6px;font-size:11px;color:#64748B">Income</th><th style="text-align:right;padding:6px;font-size:11px;color:#64748B">Expenses</th><th style="text-align:right;padding:6px;font-size:11px;color:#64748B">Net</th></tr></thead><tbody>
        ${Object.entries(d.financials?.monthlyData || {}).map(([k, v]: [string, any]) => `<tr style="border-bottom:1px solid #eee"><td style="padding:6px 0">${k}</td><td style="padding:6px 0;text-align:right;color:#166534">KES ${v.income.toLocaleString()}</td><td style="padding:6px 0;text-align:right;color:#EF4444">KES ${v.expense.toLocaleString()}</td><td style="padding:6px 0;text-align:right;font-weight:bold;color:${v.income - v.expense >= 0 ? "#166534" : "#EF4444"}">KES ${(v.income - v.expense).toLocaleString()}</td></tr>`).join("")}
        </tbody></table>${footer}`;
    } else if (reportId === "sales") {
      html = `${header}
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Sales Report</h2>
        <table style="width:100%;font-size:13px;margin-bottom:16px"><tbody>
          <tr><td style="padding:6px 0;color:#64748B">Total Sales</td><td style="padding:6px 0;font-weight:bold">${d.sales?.total || 0}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Revenue</td><td style="padding:6px 0;font-weight:bold;color:#166534">KES ${(d.sales?.totalRevenue || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Customers</td><td style="padding:6px 0;font-weight:bold">${d.sales?.customers || 0}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Outstanding Credit</td><td style="padding:6px 0;font-weight:bold;color:#F59E0B">KES ${(d.financials?.outstandingCredit || 0).toLocaleString()}</td></tr>
        </tbody></table>
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Outstanding Invoices</h2>
        <table style="width:100%;font-size:13px"><tbody>
          <tr><td style="padding:6px 0;color:#64748B">Total Invoices</td><td style="padding:6px 0;font-weight:bold">${d.invoices?.total || 0}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Outstanding Amount</td><td style="padding:6px 0;font-weight:bold;color:#F59E0B">KES ${(d.invoices?.outstanding || 0).toLocaleString()}</td></tr>
        </tbody></table>${footer}`;
    } else if (reportId === "inventory") {
      html = `${header}
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Inventory Report</h2>
        <table style="width:100%;font-size:13px;margin-bottom:16px"><tbody>
          <tr><td style="padding:6px 0;color:#64748B">Total Items</td><td style="padding:6px 0;font-weight:bold">${d.inventory?.totalItems || 0}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Total Value</td><td style="padding:6px 0;font-weight:bold;color:#166534">KES ${(d.inventory?.totalValue || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Livestock Value</td><td style="padding:6px 0;font-weight:bold">KES ${(d.livestock?.value || 0).toLocaleString()}</td></tr>
          <tr><td style="padding:6px 0;color:#64748B">Crop Value</td><td style="padding:6px 0;font-weight:bold">KES ${(d.crops?.value || 0).toLocaleString()}</td></tr>
          <tr style="border-top:2px solid #166534"><td style="padding:6px 0;font-weight:bold">Total Asset Value</td><td style="padding:6px 0;font-weight:bold;color:#166534">KES ${((d.livestock?.value || 0) + (d.inventory?.totalValue || 0) + (d.crops?.value || 0)).toLocaleString()}</td></tr>
        </tbody></table>${footer}`;
    } else if (reportId === "cashflow") {
      const months = Object.entries(d.financials?.monthlyData || {});
      html = `${header}
        <h2 style="color:#166534;font-size:16px;border-bottom:1px solid #eee;padding-bottom:8px">Cash Flow Projection</h2>
        <p style="font-size:12px;color:#64748B;margin-bottom:16px">Based on ${months.length} months of recorded data</p>
        <table style="width:100%;font-size:13px"><thead><tr style="border-bottom:2px solid #166534"><th style="text-align:left;padding:6px;font-size:11px;color:#64748B">Month</th><th style="text-align:right;padding:6px;font-size:11px;color:#64748B">Income</th><th style="text-align:right;padding:6px;font-size:11px;color:#64748B">Expenses</th><th style="text-align:right;padding:6px;font-size:11px;color:#64748B">Net Cash</th></tr></thead><tbody>
        ${months.map(([k, v]: [string, any]) => `<tr style="border-bottom:1px solid #eee"><td style="padding:6px 0">${k}</td><td style="padding:6px 0;text-align:right;color:#166534">KES ${v.income.toLocaleString()}</td><td style="padding:6px 0;text-align:right;color:#EF4444">KES ${v.expense.toLocaleString()}</td><td style="padding:6px 0;text-align:right;font-weight:bold;color:${v.income - v.expense >= 0 ? "#166534" : "#EF4444"}">KES ${(v.income - v.expense).toLocaleString()}</td></tr>`).join("")}
        </tbody></table>
        <div style="margin-top:16px;padding:12px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px">
          <p style="font-size:12px;font-weight:bold;color:#166534;margin:0">Average Monthly Net Cash Flow</p>
          <p style="font-size:18px;font-weight:bold;color:#166534;margin:4px 0 0">KES ${months.length > 0 ? Math.round(months.reduce((s: number, [, v]: [string, any]) => s + (v.income - v.expense), 0) / months.length).toLocaleString() : "0"}</p>
        </div>${footer}`;
    }

    const printWindow = window.open("", "_blank");
    if (printWindow) {
      printWindow.document.write(`<!DOCTYPE html><html><head><title>${REPORTS.find(r => r.id === reportId)?.title || "Report"}</title><style>body{font-family:Arial,sans-serif;padding:32px;color:#333;max-width:800px;margin:0 auto}table{width:100%;border-collapse:collapse}</style></head><body>${html}</body></html>`);
      printWindow.document.close();
      printWindow.print();
    }
    showToast("Report ready to print");
  };

  if (loading) return <div className="space-y-6"><PageHeader title="Export Data" description="Generate reports for loan applications" /><ListSkeleton count={4} /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Export Data" description="Generate professional reports for loan applications, audits, and farm records" />
      </motion.div>

      {/* Quick summary */}
      {data && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB] bg-gradient-to-br from-[#F0FDF4] to-white">
            <CardContent className="p-5">
              <p className="text-xs font-bold text-[#64748B] uppercase tracking-wider mb-3">Your Farm at a Glance</p>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div className="text-center"><p className="text-lg font-extrabold text-[#166534]">{data.livestock?.total || 0}</p><p className="text-[10px] text-[#94A3B8]">Livestock</p></div>
                <div className="text-center"><p className="text-lg font-extrabold text-[#166534]">{data.crops?.total || 0}</p><p className="text-[10px] text-[#94A3B8]">Crops</p></div>
                <div className="text-center"><p className="text-lg font-extrabold text-[#166534]">KES {(data.financials?.totalIncome || 0).toLocaleString()}</p><p className="text-[10px] text-[#94A3B8]">Revenue</p></div>
                <div className="text-center"><p className="text-lg font-extrabold text-[#166534]">KES {(data.financials?.netProfit || 0).toLocaleString()}</p><p className="text-[10px] text-[#94A3B8]">Net Profit</p></div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Report cards */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-3">
        {REPORTS.map(report => {
          const Icon = report.icon;
          return (
            <motion.div key={report.id} variants={fadeUp}>
              <Card className="border border-[#E5E7EB] hover:border-[#BBF7D0] hover:shadow-md transition-all">
                <CardContent className="p-4">
                  <div className="flex items-center gap-4">
                    <div className={`flex h-11 w-11 items-center justify-center rounded-xl ${report.color} text-white flex-shrink-0`}>
                      <Icon className="h-5 w-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="text-sm font-bold text-[#0F172A]">{report.title}</h3>
                      <p className="text-[11px] text-[#94A3B8]">{report.desc}</p>
                    </div>
                    <div className="flex gap-2 flex-shrink-0">
                      <button onClick={() => handlePrint(report.id)}
                        className="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-[#14532D] cursor-pointer">
                        <Printer className="h-3.5 w-3.5" />Print
                      </button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          );
        })}
      </motion.div>

      {/* Data input templates */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-4">
            <p className="text-xs font-bold text-[#0F172A] mb-2">Need to add data first?</p>
            <p className="text-[11px] text-[#94A3B8] mb-3">Download a CSV template, fill in your records, then import them into the system.</p>
            <div className="grid grid-cols-2 lg:grid-cols-3 gap-2">
              {["livestock", "production", "sales", "customers", "inventory", "finances"].map(type => (
                <button key={type} onClick={() => window.open(`/api/import/templates/${type}`, "_blank")}
                  className="flex items-center gap-2 p-2.5 rounded-xl bg-[#F8FAFC] border border-[#E5E7EB] hover:bg-[#F0FDF4] hover:border-[#BBF7D0] transition-all text-left cursor-pointer">
                  <Download className="h-3.5 w-3.5 text-[#166534] flex-shrink-0" />
                  <span className="text-[11px] font-bold text-[#0F172A] capitalize">{type}</span>
                </button>
              ))}
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Tips for loan applications */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-amber-200 bg-amber-50">
          <CardContent className="p-4">
            <p className="text-xs font-bold text-amber-800 mb-2">Tips for Loan Applications</p>
            <div className="space-y-1.5 text-[11px] text-amber-700">
              <p>Print your <strong>Farm Profile</strong> and <strong>Financial Statement</strong> — these are the most requested documents.</p>
              <p>Banks typically ask for 3-6 months of <strong>production records</strong> and <strong>bank statements</strong>.</p>
              <p>Keep your <strong>inventory values updated</strong> — they determine your farm's asset base for collateral.</p>
              <p>Generate the <strong>Cash Flow Projection</strong> to show you can repay the loan.</p>
            </div>
          </CardContent>
        </Card>
      </motion.div>
      {ToastComponent}
    </div>
  );
}
