"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Download, FileText, Bird, Egg, DollarSign, Package, Users, ShoppingCart, Clock } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useToast } from "@/components/shared/toast";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };
const scaleIn = { hidden: { opacity: 0, scale: 0.92 }, visible: { opacity: 1, scale: 1, transition: { duration: 0.4 } } };

function downloadCSV(data: any[], filename: string) {
  if (data.length === 0) return;
  const headers = Object.keys(data[0]);
  const csv = [
    headers.join(","),
    ...data.map(row => headers.map(h => {
      const val = row[h];
      if (typeof val === "string" && val.includes(",")) return `"${val}"`;
      return val ?? "";
    }).join(","))
  ].join("\n");
  const blob = new Blob([csv], { type: "text/csv" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

const exports = [
  { label: "Flocks", icon: <Bird className="h-5 w-5" />, endpoint: "/api/flocks", filename: "flocks-export.csv" },
  { label: "Production", icon: <Egg className="h-5 w-5" />, endpoint: "/api/production", filename: "production-export.csv" },
  { label: "Transactions", icon: <DollarSign className="h-5 w-5" />, endpoint: "/api/transactions", filename: "transactions-export.csv" },
  { label: "Inventory", icon: <Package className="h-5 w-5" />, endpoint: "/api/inventory", filename: "inventory-export.csv" },
  { label: "Workers", icon: <Users className="h-5 w-5" />, endpoint: "/api/workers", filename: "workers-export.csv" },
  { label: "Sales", icon: <ShoppingCart className="h-5 w-5" />, endpoint: "/api/sales", filename: "sales-export.csv" },
  { label: "Vaccinations", icon: <FileText className="h-5 w-5" />, endpoint: "/api/vaccinations", filename: "vaccinations-export.csv" },
  { label: "Attendance", icon: <Clock className="h-5 w-5" />, endpoint: "/api/attendance", filename: "attendance-export.csv" },
];

export default function ExportPage() {
  const [exporting, setExporting] = React.useState<string | null>(null);
  const { showToast, ToastComponent } = useToast();

  const handleExport = async (item: typeof exports[0]) => {
    setExporting(item.label);
    try {
      const res = await fetch(item.endpoint);
      const data = await res.json();
      // Flatten nested objects for CSV
      const flat = data.map((row: any) => {
        const flatRow: any = {};
        for (const [key, value] of Object.entries(row)) {
          if (typeof value === "object" && value !== null) {
            if (value instanceof Date) flatRow[key] = value.toISOString();
            else flatRow[key] = JSON.stringify(value);
          } else {
            flatRow[key] = value;
          }
        }
        return flatRow;
      });
      downloadCSV(flat, item.filename);
      showToast(`${item.label} exported as CSV!`);
    } catch {
      showToast("Export failed", "error");
    }
    setExporting(null);
  };

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Data Export" description="Download your farm data as CSV files for reporting and backup." />
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
          <CardContent className="p-6">
            <p className="text-sm text-[#64748B] mb-6">Click any button below to download that module&apos;s data as a CSV file. You can open it in Excel, Google Sheets, or any spreadsheet application.</p>
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
              {exports.map(item => (
                <button key={item.label} onClick={() => handleExport(item)} disabled={exporting === item.label} className="flex items-center gap-3 rounded-xl border border-[#E5E7EB] p-4 text-left hover:border-[#BBF7D0] hover:shadow-md transition-all duration-200 cursor-pointer disabled:opacity-50">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#166534] text-white">
                    {exporting === item.label ? <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white" /> : item.icon}
                  </div>
                  <div>
                    <p className="text-sm font-bold text-[#0F172A]">{item.label}</p>
                    <p className="text-xs text-[#94A3B8]">.csv file</p>
                  </div>
                  <Download className="h-4 w-4 text-[#94A3B8] ml-auto" />
                </button>
              ))}
            </div>
          </CardContent>
        </Card>
      </motion.div>
      {ToastComponent}
    </div>
  );
}
