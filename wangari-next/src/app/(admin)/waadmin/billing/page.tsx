"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface SubRow {
  id: number;
  userId: number;
  plan: string;
  planName: string;
  amount: string;
  status: string;
  reference: string | null;
  startsAt: string;
  expiresAt: string;
  user: { id: number; name: string; email: string } | null;
}

export default function AdminBillingPage() {
  const [rows, setRows] = React.useState<SubRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [status, setStatus] = React.useState("all");
  const [q, setQ] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const pageSize = 20;

  const load = React.useCallback(async () => {
    try {
      const res = await adminApi.get<{ rows: SubRow[]; total: number }>(
        `/billing?status=${encodeURIComponent(status)}&q=${encodeURIComponent(q)}&page=${page}`
      );
      setRows(res.rows);
      setTotal(res.total);
    } catch (e: any) {
      setError(e.message);
    }
  }, [status, q, page]);
  React.useEffect(() => { load(); }, [load]);

  async function cancel(id: number) {
    if (!confirm("Cancel this subscription? The farm loses paid access immediately.")) return;
    try {
      await adminApi.post(`/billing/${id}/cancel`);
      setFlash(`Subscription #${id} cancelled.`);
      setTimeout(() => setFlash(""), 4000);
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  const pages = Math.max(1, Math.ceil(total / pageSize));

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">Billing & Payments</h1>
        <p className="mt-1 text-sm text-wangari-muted">All subscriptions across the platform with Paystack references.</p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash}</div>}
      {error && <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>}

      <div className="flex flex-wrap items-center gap-3">
        <input
          value={q}
          onChange={(e) => { setQ(e.target.value); setPage(1); }}
          placeholder="Search farm owner name or email…"
          className="h-10 w-72 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none"
        />
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1); }}
          className="h-10 rounded-lg border border-wangari-border bg-wangari-cream px-3 text-sm text-wangari-heading focus:border-emerald-500 focus:outline-none"
        >
          {["all", "active", "cancelled", "expired"].map((s) => (
            <option key={s} value={s}>{s}</option>
          ))}
        </select>
      </div>

      {!rows ? (
        <div className="animate-pulse text-sm text-wangari-muted">Loading subscriptions…</div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-wangari-border">
          <table className="w-full text-sm">
            <thead className="bg-wangari-green-50/60 text-left text-[11px] font-bold uppercase tracking-wider text-wangari-muted">
              <tr>
                <th className="px-4 py-3">Customer</th>
                <th className="px-4 py-3">Plan</th>
                <th className="px-4 py-3">Amount</th>
                <th className="px-4 py-3">Reference</th>
                <th className="px-4 py-3">Period</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-wangari-border bg-white">
              {rows.map((r) => (
                <tr key={r.id}>
                  <td className="px-4 py-3">
                    <div className="text-wangari-heading">{r.user?.name || `User #${r.userId}`}</div>
                    <div className="text-xs text-wangari-subtle">{r.user?.email}</div>
                  </td>
                  <td className="px-4 py-3 text-wangari-text">{r.planName}</td>
                  <td className="px-4 py-3 font-medium text-wangari-heading">KES {Number(r.amount).toLocaleString()}</td>
                  <td className="px-4 py-3 font-mono text-xs text-wangari-subtle">{r.reference || "—"}</td>
                  <td className="px-4 py-3 text-xs text-wangari-muted">
                    {new Date(r.startsAt).toLocaleDateString()} → {new Date(r.expiresAt).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${
                      r.status === "active" ? "bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200" : "bg-wangari-cream text-wangari-muted"
                    }`}>
                      {r.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    {r.status === "active" && (
                      <button onClick={() => cancel(r.id)} className="rounded-lg px-2.5 py-1 text-xs text-badge-red-text hover:bg-badge-red-bg">
                        Cancel
                      </button>
                    )}
                  </td>
                </tr>
              ))}
              {rows.length === 0 && (
                <tr><td colSpan={7} className="px-4 py-8 text-center text-sm text-wangari-subtle">No subscriptions match.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {pages > 1 && (
        <div className="flex items-center justify-between text-sm text-wangari-muted">
          <span>Page {page} of {pages} · {total} total</span>
          <div className="flex gap-2">
            <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="rounded-lg border border-wangari-border px-3 py-1.5 disabled:opacity-40">← Prev</button>
            <button onClick={() => setPage((p) => Math.min(pages, p + 1))} disabled={page >= pages} className="rounded-lg border border-wangari-border px-3 py-1.5 disabled:opacity-40">Next →</button>
          </div>
        </div>
      )}
    </div>
  );
}
