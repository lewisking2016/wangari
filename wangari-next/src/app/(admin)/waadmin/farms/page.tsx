"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface FarmRow {
  id: number;
  name: string;
  code: string | null;
  location: string | null;
  county: string | null;
  owner: { id: number; name: string; email: string } | null;
  workers: number;
  flocks: number;
  plan: { name: string; status: string; expiresAt: string } | null;
  createdAt: string;
}

export default function AdminFarmsPage() {
  const [rows, setRows] = React.useState<FarmRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [q, setQ] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const pageSize = 20;

  const load = React.useCallback(async () => {
    try {
      const res = await adminApi.get<{ rows: FarmRow[]; total: number }>(
        `/farms?q=${encodeURIComponent(q)}&page=${page}`
      );
      setRows(res.rows);
      setTotal(res.total);
    } catch (e: any) {
      setError(e.message);
    }
  }, [q, page]);
  React.useEffect(() => { load(); }, [load]);

  async function extend(farm: FarmRow) {
    const days = prompt(`Extend "${farm.name}" subscription by how many days?`);
    if (!days) return;
    try {
      await adminApi.post(`/farms/${farm.id}/extend`, { days: Number(days) });
      setFlash(`"${farm.name}" extended by ${days} days.`);
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
        <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">Farms</h1>
        <p className="mt-1 text-sm text-wangari-muted">All tenant farms with owner, plan state, and workforce size.</p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash}</div>}
      {error && <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>}

      <input
        value={q}
        onChange={(e) => { setQ(e.target.value); setPage(1); }}
        placeholder="Search farm name or code…"
        className="h-10 w-72 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none"
      />

      {!rows ? (
        <div className="animate-pulse text-sm text-wangari-muted">Loading farms…</div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-wangari-border">
          <table className="w-full min-w-[640px] text-sm">
            <thead className="bg-wangari-green-50/60 text-left text-[11px] font-bold uppercase tracking-wider text-wangari-muted">
              <tr>
                <th className="px-4 py-3">Farm</th>
                <th className="px-4 py-3">Owner</th>
                <th className="px-4 py-3">Workers</th>
                <th className="px-4 py-3">Flocks</th>
                <th className="px-4 py-3">Plan</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-wangari-border bg-white">
              {rows.map((f) => (
                <tr key={f.id}>
                  <td className="px-4 py-3">
                    <div className="font-medium text-wangari-heading">{f.name}</div>
                    <div className="text-xs text-wangari-subtle">{f.code || "no code"}{f.county ? ` · ${f.county}` : ""}</div>
                  </td>
                  <td className="px-4 py-3">
                    <div className="text-wangari-text">{f.owner?.name || "—"}</div>
                    <div className="text-xs text-wangari-subtle">{f.owner?.email}</div>
                  </td>
                  <td className="px-4 py-3 text-wangari-text">{f.workers}</td>
                  <td className="px-4 py-3 text-wangari-text">{f.flocks}</td>
                  <td className="px-4 py-3">
                    {f.plan ? (
                      <div>
                        <span className="inline-flex rounded-full bg-wangari-green-50 px-2 py-0.5 text-[11px] font-medium text-wangari-green-800 border border-wangari-green-200">{f.plan.name}</span>
                        <div className="mt-0.5 text-[11px] text-wangari-subtle">till {new Date(f.plan.expiresAt).toLocaleDateString()}</div>
                      </div>
                    ) : (
                      <span className="text-xs text-wangari-subtle">trial / none</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button onClick={() => extend(f)} className="rounded-lg px-2.5 py-1 text-xs text-wangari-text hover:bg-wangari-cream hover:text-wangari-heading">Extend…</button>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-sm text-wangari-subtle">No farms match.</td></tr>}
            </tbody>
          </table>
        </div>
      )}

      {pages > 1 && (
        <div className="flex items-center justify-between text-sm text-wangari-muted">
          <span>Page {page} of {pages} · {total} farms</span>
          <div className="flex gap-2">
            <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="rounded-lg border border-wangari-border px-3 py-1.5 disabled:opacity-40">← Prev</button>
            <button onClick={() => setPage((p) => Math.min(pages, p + 1))} disabled={page >= pages} className="rounded-lg border border-wangari-border px-3 py-1.5 disabled:opacity-40">Next →</button>
          </div>
        </div>
      )}
    </div>
  );
}
