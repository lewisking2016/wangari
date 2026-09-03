"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Upload, Download, FileText, CheckCircle, AlertCircle, Bird, TrendingUp, ShoppingCart, Users, Package, DollarSign, X } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const IMPORT_TYPES = [
  { id: "livestock", title: "Livestock", desc: "Import your animal groups (poultry, cattle, goats...)", icon: Bird, color: "bg-[#166534]", columns: "name, type, count, breed, hatchDate, costPerAnimal" },
  { id: "production", title: "Production Records", desc: "Daily output — eggs, milk, meat, feed, mortality", icon: TrendingUp, color: "bg-emerald-500", columns: "date, group, eggs, milk, weight, mortality, feed" },
  { id: "sales", title: "Sales", desc: "Who you sold to, what, and how much", icon: ShoppingCart, color: "bg-blue-500", columns: "date, customer, product, amount, paid, status" },
  { id: "customers", title: "Customers", desc: "Your buyer list with phone numbers", icon: Users, color: "bg-violet-500", columns: "name, phone, email, address" },
  { id: "inventory", title: "Inventory", desc: "Feed, fertilizer, seeds, equipment", icon: Package, color: "bg-amber-500", columns: "name, category, quantity, unit, cost, reorderLevel" },
  { id: "finances", title: "Finances", desc: "Income and expense records", icon: DollarSign, color: "bg-rose-500", columns: "date, type, category, amount, description" },
];

function parseCSV(text: string): Record<string, string>[] {
  const lines = text.trim().split("\n");
  if (lines.length < 2) return [];
  const headers = lines[0].split(",").map(h => h.trim().toLowerCase());
  return lines.slice(1).map(line => {
    const values = line.split(",").map(v => v.trim());
    const row: Record<string, string> = {};
    headers.forEach((h, i) => { row[h] = values[i] || ""; });
    return row;
  });
}

export default function ImportPage() {
  const [selectedType, setSelectedType] = React.useState<string | null>(null);
  const [step, setStep] = React.useState(1);
  const [parsedData, setParsedData] = React.useState<Record<string, string>[]>([]);
  const [fileName, setFileName] = React.useState("");
  const [importing, setImporting] = React.useState(false);
  const [result, setResult] = React.useState<{ created: number; skipped: number; total: number } | null>(null);
  const fileRef = React.useRef<HTMLInputElement>(null);
  const { showToast, ToastComponent } = useToast();

  const selected = IMPORT_TYPES.find(t => t.id === selectedType);

  const handleFile = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setFileName(file.name);
    const reader = new FileReader();
    reader.onload = (ev) => {
      const text = ev.target?.result as string;
      const data = parseCSV(text);
      setParsedData(data);
      setStep(3);
    };
    reader.readAsText(file);
  };

  const handleImport = async () => {
    if (!selectedType || parsedData.length === 0) return;
    setImporting(true);
    try {
      const res = await api.post<{ created: number; skipped: number; total: number }>(`/api/import/${selectedType}`, { rows: parsedData });
      setResult(res);
      showToast(`${res.created} records imported!`);
    } catch {
      showToast("Import failed. Check your CSV format.");
    } finally {
      setImporting(false);
    }
  };

  const handleDownloadTemplate = (type: string) => {
    window.open(`/api/import/templates/${type}`, "_blank");
  };

  const reset = () => {
    setSelectedType(null); setStep(1); setParsedData([]); setFileName(""); setResult(null);
  };

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Import Data" description="Upload your existing records from spreadsheets or notebooks" />
      </motion.div>

      {/* Step indicator */}
      <div className="flex items-center gap-2">
        {[1, 2, 3].map(s => (
          <React.Fragment key={s}>
            <div className={`flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ${step >= s ? "bg-[#166534] text-white" : "bg-[#F1F5F9] text-[#94A3B8]"}`}>{s}</div>
            {s < 3 && <div className={`flex-1 h-1 rounded-full ${step > s ? "bg-[#166534]" : "bg-[#F1F5F9]"}`} />}
          </React.Fragment>
        ))}
      </div>
      <div className="flex justify-between text-[10px] text-[#94A3B8] -mt-4">
        <span>Choose type</span><span>Upload file</span><span>Review and import</span>
      </div>

      {/* Quick template downloads */}
      {step === 1 && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-4">
              <p className="text-xs font-bold text-[#0F172A] mb-3">Quick Download — CSV Templates</p>
              <p className="text-[11px] text-[#94A3B8] mb-3">Download a template, fill it in on your phone or computer, then upload it back here.</p>
              <div className="grid grid-cols-2 lg:grid-cols-3 gap-2">
                {IMPORT_TYPES.map(t => (
                  <button key={t.id} onClick={() => handleDownloadTemplate(t.id)}
                    className="flex items-center gap-2 p-2.5 rounded-xl bg-[#F8FAFC] border border-[#E5E7EB] hover:bg-[#F0FDF4] hover:border-[#BBF7D0] transition-all text-left cursor-pointer">
                    <Download className="h-3.5 w-3.5 text-[#166534] flex-shrink-0" />
                    <span className="text-[11px] font-bold text-[#0F172A]">{t.title}</span>
                  </button>
                ))}
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Step 1: Choose type */}
      {step === 1 && (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-2">
          {IMPORT_TYPES.map(t => {
            const Icon = t.icon;
            return (
              <motion.div key={t.id} variants={fadeUp}>
                <button onClick={() => { setSelectedType(t.id); setStep(2); }}
                  className="w-full text-left">
                  <Card className="border border-[#E5E7EB] hover:border-[#BBF7D0] hover:shadow-md transition-all cursor-pointer">
                    <CardContent className="p-4 flex items-center gap-4">
                      <div className={`flex h-11 w-11 items-center justify-center rounded-xl ${t.color} text-white flex-shrink-0`}><Icon className="h-5 w-5" /></div>
                      <div className="flex-1">
                        <h3 className="text-sm font-bold text-[#0F172A]">{t.title}</h3>
                        <p className="text-[11px] text-[#94A3B8]">{t.desc}</p>
                      </div>
                    </CardContent>
                  </Card>
                </button>
              </motion.div>
            );
          })}
        </motion.div>
      )}

      {/* Step 2: Upload file */}
      {step === 2 && selected && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-6 space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-sm font-bold text-[#0F172A]">Import {selected.title}</h3>
                <button onClick={reset} className="text-[#94A3B8] hover:text-[#64748B] cursor-pointer"><X className="h-4 w-4" /></button>
              </div>

              {/* Template download */}
              <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-4">
                <p className="text-xs font-bold text-[#166534] mb-2">Step 1: Download the template</p>
                <p className="text-[11px] text-[#64748B] mb-3">Fill in your data using this CSV template. The columns are:</p>
                <p className="text-[10px] text-[#94A3B8] font-mono mb-3">{selected.columns}</p>
                <button onClick={() => handleDownloadTemplate(selected.id)}
                  className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-[#14532D] cursor-pointer">
                  <Download className="h-3.5 w-3.5" />Download Template
                </button>
              </div>

              {/* File upload */}
              <div className="rounded-xl bg-[#F8FAFC] border-2 border-dashed border-[#E5E7EB] p-8 text-center">
                <Upload className="h-8 w-8 text-[#94A3B8] mx-auto mb-3" />
                <p className="text-sm font-bold text-[#0F172A] mb-1">Step 2: Upload your filled CSV</p>
                <p className="text-[11px] text-[#94A3B8] mb-4">Select a .csv file from your phone or computer</p>
                <input ref={fileRef} type="file" accept=".csv" onChange={handleFile} className="hidden" />
                <button onClick={() => fileRef.current?.click()}
                  className="px-6 py-2.5 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-[#14532D] cursor-pointer">Choose File</button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Step 3: Review and import */}
      {step === 3 && (
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          {result ? (
            <Card className="border border-emerald-200 bg-emerald-50">
              <CardContent className="p-6 text-center">
                <CheckCircle className="h-12 w-12 text-emerald-500 mx-auto mb-3" />
                <h3 className="text-lg font-bold text-[#0F172A] mb-1">Import Complete</h3>
                <p className="text-sm text-[#64748B] mb-4">{result.created} records imported, {result.skipped} skipped</p>
                <div className="flex gap-2 justify-center">
                  <Button onClick={reset} variant="outline" className="cursor-pointer">Import More</Button>
                </div>
              </CardContent>
            </Card>
          ) : (
            <Card className="border border-[#E5E7EB]">
              <CardContent className="p-6 space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="text-sm font-bold text-[#0F172A]">Review {parsedData.length} records</h3>
                    <p className="text-[11px] text-[#94A3B8]">{fileName} — {selected?.title}</p>
                  </div>
                  <Badge className="bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]">{parsedData.length} rows</Badge>
                </div>

                {/* Preview table */}
                <div className="overflow-x-auto max-h-64">
                  <table className="w-full text-xs">
                    <thead><tr className="border-b border-[#E5E7EB]">
                      {parsedData[0] && Object.keys(parsedData[0]).map(h => (
                        <th key={h} className="px-3 py-2 text-left font-bold text-[#64748B] capitalize">{h}</th>
                      ))}
                    </tr></thead>
                    <tbody>
                      {parsedData.slice(0, 10).map((row, i) => (
                        <tr key={i} className="border-b border-[#F1F5F9]">
                          {Object.values(row).map((val, j) => (
                            <td key={j} className="px-3 py-2 text-[#0F172A]">{val || "-"}</td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  {parsedData.length > 10 && <p className="text-[10px] text-[#94A3B8] text-center py-2">...and {parsedData.length - 10} more rows</p>}
                </div>

                <div className="flex gap-2">
                  <Button onClick={() => setStep(2)} variant="outline" className="flex-1 cursor-pointer">Back</Button>
                  <Button onClick={handleImport} disabled={importing} className="flex-1 bg-[#166534] hover:bg-[#14532D] cursor-pointer">
                    {importing ? "Importing..." : `Import ${parsedData.length} Records`}
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </motion.div>
      )}

      {/* Tips */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-4">
            <p className="text-xs font-bold text-[#0F172A] mb-2">How to import from paper records</p>
            <div className="space-y-1.5 text-[11px] text-[#64748B]">
              <p>1. Download the template for the data type you want to import</p>
              <p>2. Open it in Excel, Google Sheets, or any spreadsheet app on your phone</p>
              <p>3. Fill in your data following the column headers</p>
              <p>4. Save as CSV (Comma Separated Values)</p>
              <p>5. Upload the CSV file here and review before importing</p>
            </div>
          </CardContent>
        </Card>
      </motion.div>
      {ToastComponent}
    </div>
  );
}
