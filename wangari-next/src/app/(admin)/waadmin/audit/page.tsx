"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface AuditRow {
  id: number;
  userId: number | null;
  farmId: number | null;
  action: string;
  entityType: string | null;
  entityId: number | null;
  details: any;
  createdAt: string;
  user: { name: string; email: string } | null;
}

export default function AdminAuditPage() {
  const [rows, setRows] = React.useState<AuditRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [error, setError] = React.useState("");
  const pageSize = 30;

  React.useEffect(() => {
    adminApi.get<{ rows: AuditRow[]; total: number }>(`/audit?page=${page}`)
      .then((r) => { setRows(r.rows); setTotal(r.total); })
      .catch((e) => setError(e.message));
  }, [page]);

  const pages = Math.max(1, Math.ceil(total / pageSize));

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-white">Audit Log</h1>
        <p className="mt-1 text-sm text-slate-400">Immutable trail of admin actions and money-path mutations. Append-only.</p>
      </div>
      {error && <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}
      {!rows ? (
        <div className="animate-pulse text-sm text-slate-400">Loading audit trail…</div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-slate-800">
          <table className="w-full text-sm">
            <thead className="bg-slate-900/80 text-left text-[11px] uppercase tracking-wider text-slate-500">
              <tr>
                <th className="px-4 py-3">When</th>
                <th className="px-4 py-3">Actor</th>
                <th className="px-4 py-3">Action</th>
                <th className="px-4 py-3">Entity</th>
                <th className="px-4 py-3">Details</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800 bg-slate-900/40">
              {rows.map((r) => (
                <tr key={r.id}>
                  <td className="whitespace-nowrap px-4 py-3 text-xs text-slate-400">{new Date(r.createdAt).toLocaleString()}</td>
                  <td className="px-4 py-3 text-xs text-slate-300">
                    {r.details?._actor ? <span className="text-emerald-400">{String(r.details._actor)}</span> : r.user ? `${r.user.name}` : `user #${r.userId ?? "?"}`}
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-slate-200">{r.action}</td>
                  <td className="px-4 py-3 text-xs text-slate-400">
                    {r.entityType ? `${r.entityType}${r.details?._entityIdStr != null ? `#${r.details._entityIdStr}` : r.entityId != null ? `#${r.entityId}` : ""}` : "—"}
                  </td>
                  <td className="max-w-xs truncate px-4 py-3 font-mono text-[11px] text-slate-500" title={JSON.stringify(r.details)}>
                    {r.details ? JSON.stringify(r.details) : "—"}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {pages > 1 && (
        <div className="flex items-center justify-between text-sm text-slate-400">
          <span>Page {page} of {pages} · {total} entries</span>
          <div className="flex gap-2">
            <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="rounded-lg border border-slate-700 px-3 py-1.5 disabled:opacity-40">← Prev</button>
            <button onClick={() => setPage((p) => Math.min(pages, p + 1))} disabled={page >= pages} className="rounded-lg border border-slate-700 px-3 py-1.5 disabled:opacity-40">Next →</button>
          </div>
        </div>
      )}
    </div>
  );
}
