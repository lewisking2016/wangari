"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  FileText,
  Download,
  Printer,
  Search,
  Plus,
  CheckCircle2,
  Clock,
  AlertCircle,
  RefreshCw,
  Eye,
  Send,
  X,
} from "lucide-react";

interface Invoice {
  id: number;
  invoiceNumber: string;
  customerName: string;
  customerPhone: string;
  items: unknown;
  totalAmount: number;
  amountPaid: number;
  balance: number;
  paymentStatus: string;
  date: string;
  createdAt: string;
}

const fadeUp = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5 } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.05 } },
};

function getStatusBadge(status: string) {
  switch (status) {
    case "paid":
      return <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><CheckCircle2 className="h-3 w-3" /> Paid</span>;
    case "partial":
      return <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"><Clock className="h-3 w-3" /> Partial</span>;
    case "pending":
      return <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200"><AlertCircle className="h-3 w-3" /> Pending</span>;
    default:
      return <span className="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-50 text-gray-700">{status}</span>;
  }
}

export default function InvoicesPage() {
  const [invoices, setInvoices] = React.useState<Invoice[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [search, setSearch] = React.useState("");
  const [viewInvoice, setViewInvoice] = React.useState<Invoice | null>(null);

  React.useEffect(() => {
    const fetchInvoices = async () => {
      try {
        const res = await fetch("/api/invoices");
        const data = await res.json();
        setInvoices(data);
      } catch {
        // Use empty
      } finally {
        setLoading(false);
      }
    };
    fetchInvoices();
  }, []);

  const filtered = invoices.filter((inv) =>
    !search || inv.customerName.toLowerCase().includes(search.toLowerCase()) || inv.invoiceNumber.toLowerCase().includes(search.toLowerCase())
  );

  const totalRevenue = invoices.reduce((sum, inv) => sum + inv.totalAmount, 0);
  const totalPaid = invoices.reduce((sum, inv) => sum + inv.amountPaid, 0);
  const totalOutstanding = invoices.reduce((sum, inv) => sum + inv.balance, 0);

  const handlePrint = (invoice: Invoice) => {
    const items = typeof invoice.items === "object" ? invoice.items : [];
    const itemRows = Array.isArray(items)
      ? items.map((item: Record<string, unknown>) => `<tr><td>${item.name || "Item"}</td><td>${item.quantity || 1}</td><td>KES ${Number(item.price || 0).toLocaleString()}</td><td>KES ${Number(item.quantity || 1) * Number(item.price || 0)}</td></tr>`).join("")
      : "<tr><td colspan='4'>No items</td></tr>";

    const printWindow = window.open("", "_blank");
    if (printWindow) {
      printWindow.document.write(`
        <html><head><title>${invoice.invoiceNumber}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
          .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #166534; padding-bottom: 20px; }
          .header h1 { color: #166534; margin: 0; font-size: 28px; }
          .info { display: flex; justify-content: space-between; margin-bottom: 20px; }
          table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
          th { background: #166534; color: white; padding: 10px; text-align: left; }
          td { padding: 10px; border-bottom: 1px solid #eee; }
          .total { text-align: right; font-size: 18px; font-weight: bold; }
          .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #999; }
        </style></head><body>
        <div class="header">
          <h1>Wangari Farm Manager</h1>
          <p>Invoice ${invoice.invoiceNumber}</p>
        </div>
        <div class="info">
          <div><strong>To:</strong> ${invoice.customerName}<br>${invoice.customerPhone}</div>
          <div><strong>Date:</strong> ${new Date(invoice.date).toLocaleDateString()}<br><strong>Status:</strong> ${invoice.paymentStatus}</div>
        </div>
        <table><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>${itemRows}</tbody></table>
        <div class="total">
          <p>Total: KES ${invoice.totalAmount.toLocaleString()}</p>
          <p>Paid: KES ${invoice.amountPaid.toLocaleString()}</p>
          ${invoice.balance > 0 ? `<p style="color:red">Balance: KES ${invoice.balance.toLocaleString()}</p>` : ""}
        </div>
        <div class="footer">Wangari by iMeanTech · info@imeantech.com</div>
        </body></html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <RefreshCw className="h-8 w-8 text-wangari-green-800 animate-spin" />
      </div>
    );
  }

  return (
    <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-6">
      <motion.div variants={fadeUp} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-wangari-heading">Invoices</h1>
          <p className="text-sm text-wangari-muted mt-1">Generate and manage invoices for your customers</p>
        </div>
      </motion.div>

      {/* Stats */}
      <motion.div variants={fadeUp} className="grid grid-cols-3 gap-4">
        {[
          { label: "Total Revenue", value: `KES ${totalRevenue.toLocaleString()}`, color: "text-wangari-green-800" },
          { label: "Collected", value: `KES ${totalPaid.toLocaleString()}`, color: "text-emerald-600" },
          { label: "Outstanding", value: `KES ${totalOutstanding.toLocaleString()}`, color: totalOutstanding > 0 ? "text-red-600" : "text-wangari-green-800" },
        ].map((stat) => (
          <div key={stat.label} className="rounded-2xl border border-wangari-border bg-white p-5 text-center">
            <p className={`text-2xl font-extrabold ${stat.color}`}>{stat.value}</p>
            <p className="text-xs font-medium text-wangari-muted mt-1">{stat.label}</p>
          </div>
        ))}
      </motion.div>

      {/* Search */}
      <motion.div variants={fadeUp}>
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-wangari-muted" />
          <input
            type="text"
            placeholder="Search by invoice number or customer..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-wangari-border text-sm focus:outline-none focus:ring-2 focus:ring-wangari-green-800/20 focus:border-wangari-green-800"
          />
        </div>
      </motion.div>

      {/* Invoice List */}
      <motion.div variants={fadeUp} className="rounded-2xl border border-wangari-border bg-white overflow-hidden">
        {filtered.length === 0 ? (
          <div className="p-12 text-center">
            <FileText className="h-12 w-12 text-wangari-border mx-auto mb-3" />
            <p className="text-sm font-medium text-wangari-muted">No invoices found</p>
            <p className="text-xs text-wangari-subtle mt-1">Invoices are created when you record sales</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-wangari-border bg-gray-50">
                  <th className="px-6 py-3 text-left text-xs font-semibold text-wangari-muted uppercase">Invoice</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-wangari-muted uppercase">Customer</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-wangari-muted uppercase">Date</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-wangari-muted uppercase">Total</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-wangari-muted uppercase">Paid</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-wangari-muted uppercase">Balance</th>
                  <th className="px-6 py-3 text-center text-xs font-semibold text-wangari-muted uppercase">Status</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold text-wangari-muted uppercase">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-wangari-border">
                {filtered.map((inv, i) => (
                  <motion.tr
                    key={inv.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: i * 0.03 }}
                    className="hover:bg-gray-50 transition-colors"
                  >
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-wangari-green-800">{inv.invoiceNumber}</td>
                    <td className="px-6 py-4">
                      <p className="font-medium text-wangari-heading">{inv.customerName}</p>
                      {inv.customerPhone && <p className="text-xs text-wangari-muted">{inv.customerPhone}</p>}
                    </td>
                    <td className="px-6 py-4 text-wangari-muted">{new Date(inv.date).toLocaleDateString()}</td>
                    <td className="px-6 py-4 text-right font-semibold text-wangari-heading">KES {inv.totalAmount.toLocaleString()}</td>
                    <td className="px-6 py-4 text-right text-emerald-600">KES {inv.amountPaid.toLocaleString()}</td>
                    <td className={`px-6 py-4 text-right font-semibold ${inv.balance > 0 ? "text-red-600" : "text-wangari-muted"}`}>
                      KES {inv.balance.toLocaleString()}
                    </td>
                    <td className="px-6 py-4 text-center">{getStatusBadge(inv.paymentStatus)}</td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button onClick={() => setViewInvoice(inv)} className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer" title="View">
                          <Eye className="h-4 w-4 text-wangari-muted" />
                        </button>
                        <button onClick={() => handlePrint(inv)} className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer" title="Print">
                          <Printer className="h-4 w-4 text-wangari-muted" />
                        </button>
                      </div>
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </motion.div>

      {/* Invoice Preview Modal */}
      {viewInvoice && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" onClick={() => setViewInvoice(null)}>
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between p-6 border-b border-wangari-border">
              <h3 className="text-lg font-bold text-wangari-heading">{viewInvoice.invoiceNumber}</h3>
              <button onClick={() => setViewInvoice(null)} className="p-1 rounded-lg hover:bg-gray-100 cursor-pointer"><X className="h-5 w-5" /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="flex justify-between text-sm">
                <div>
                  <p className="text-wangari-muted">Customer</p>
                  <p className="font-semibold text-wangari-heading">{viewInvoice.customerName}</p>
                </div>
                <div className="text-right">
                  <p className="text-wangari-muted">Date</p>
                  <p className="font-semibold text-wangari-heading">{new Date(viewInvoice.date).toLocaleDateString()}</p>
                </div>
              </div>
              <div className="border-t border-wangari-border pt-4 space-y-2">
                <div className="flex justify-between text-sm"><span className="text-wangari-muted">Total Amount</span><span className="font-bold">KES {viewInvoice.totalAmount.toLocaleString()}</span></div>
                <div className="flex justify-between text-sm"><span className="text-wangari-muted">Amount Paid</span><span className="text-emerald-600">KES {viewInvoice.amountPaid.toLocaleString()}</span></div>
                <div className="flex justify-between text-sm"><span className="text-wangari-muted">Balance</span><span className={`font-bold ${viewInvoice.balance > 0 ? "text-red-600" : "text-emerald-600"}`}>KES {viewInvoice.balance.toLocaleString()}</span></div>
                <div className="flex justify-between text-sm"><span className="text-wangari-muted">Status</span>{getStatusBadge(viewInvoice.paymentStatus)}</div>
              </div>
              <div className="flex gap-3 pt-4">
                <button onClick={() => { handlePrint(viewInvoice); setViewInvoice(null); }} className="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-wangari-green-800 text-white rounded-xl text-sm font-semibold hover:bg-wangari-green-900 transition-colors cursor-pointer">
                  <Printer className="h-4 w-4" /> Print
                </button>
              </div>
            </div>
          </motion.div>
        </div>
      )}
    </motion.div>
  );
}
