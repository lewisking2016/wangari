"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface UserRow {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: string;
  emailVerified: string | null;
  createdAt: string;
  ownedFarms: { id: number; name: string }[];
}

export default function AdminUsersPage() {
  const [rows, setRows] = React.useState<UserRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [q, setQ] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const pageSize = 20;

  const load = React.useCallback(async () => {
    try {
      const res = await adminApi.get<{ rows: UserRow[]; total: number }>(
        `/users?q=${encodeURIComponent(q)}&page=${page}`
      );
      setRows(res.rows);
      setTotal(res.total);
    } catch (e: any) {
      setError(e.message);
    }
  }, [q, page]);
  React.useEffect(() => { load(); }, [load]);

  async function act(id: number, action: "force-logout" | "verify-email", confirmMsg?: string) {
    if (confirmMsg && !confirm(confirmMsg)) return;
    try {
      await adminApi.post(`/users/${id}/${action}`);
      setFlash(action === "force-logout" ? "User signed out of all devices." : "Email marked verified.");
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
        <h1 className="text-2xl font-semibold tracking-tight text-white">Users</h1>
        <p className="mt-1 text-sm text-slate-400">All accounts with controlled support actions — every action audited.</p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-900/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300">{flash}</div>}
      {error && <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <input
        value={q}
        onChange={(e) => { setQ(e.target.value); setPage(1); }}
        placeholder="Search name, email, or phone…"
        className="h-10 w-72 rounded-lg border border-slate-700 bg-slate-800/60 px-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none"
      />

      {!rows ? (
        <div className="animate-pulse text-sm text-slate-400">Loading users…</div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-slate-800">
          <table className="w-full text-sm">
            <thead className="bg-slate-900/80 text-left text-[11px] uppercase tracking-wider text-slate-500">
              <tr>
                <th className="px-4 py-3">User</th>
                <th className="px-4 py-3">Farms</th>
                <th className="px-4 py-3">Verified</th>
                <th className="px-4 py-3">Joined</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800 bg-slate-900/40">
              {rows.map((u) => (
                <tr key={u.id}>
                  <td className="px-4 py-3">
                    <div className="font-medium text-slate-100">{u.name} {u.role !== "farm_owner" && <span className="ml-1 rounded bg-violet-500/20 px-1.5 py-0.5 text-[10px] text-violet-300">{u.role}</span>}</div>
                    <div className="text-xs text-slate-500">{u.email}{u.phone ? ` · ${u.phone}` : ""}</div>
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-400">
                    {u.ownedFarms.length ? u.ownedFarms.map((f) => f.name).join(", ") : "—"}
                  </td>
                  <td className="px-4 py-3">
                    {u.emailVerified ? <span className="text-emerald-400 text-xs">✓ yes</span> : <span className="text-amber-400 text-xs">no</span>}
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-400">{new Date(u.createdAt).toLocaleDateString()}</td>
                  <td className="px-4 py-3 text-right">
                    <button
                      onClick={() => act(u.id, "force-logout", `Force-logout ${u.email} from all devices?`)}
                      className="rounded-lg px-2.5 py-1 text-xs text-amber-400 hover:bg-amber-950/40"
                    >Force logout</button>
                    {!u.emailVerified && (
                      <button onClick={() => act(u.id, "verify-email")} className="rounded-lg px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800 hover:text-white">Verify email</button>
                    )}
                  </td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={5} className="px-4 py-8 text-center text-sm text-slate-500">No users match.</td></tr>}
            </tbody>
          </table>
        </div>
      )}

      {pages > 1 && (
        <div className="flex items-center justify-between text-sm text-slate-400">
          <span>Page {page} of {pages} · {total} users</span>
          <div className="flex gap-2">
            <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="rounded-lg border border-slate-700 px-3 py-1.5 disabled:opacity-40">← Prev</button>
            <button onClick={() => setPage((p) => Math.min(pages, p + 1))} disabled={page >= pages} className="rounded-lg border border-slate-700 px-3 py-1.5 disabled:opacity-40">Next →</button>
          </div>
        </div>
      )}
    </div>
  );
}
