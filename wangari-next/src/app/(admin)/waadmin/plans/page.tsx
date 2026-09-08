"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface Plan {
  id: string;
  name: string;
  description: string | null;
  amount: number; // pesewas
  days: number;
  active: boolean;
  sortOrder: number;
  activeSubscriptions: number;
}

export default function AdminPlansPage() {
  const [plans, setPlans] = React.useState<Plan[] | null>(null);
  const [error, setError] = React.useState("");
  const [editing, setEditing] = React.useState<Plan | null>(null);
  const [saving, setSaving] = React.useState(false);
  const [flash, setFlash] = React.useState("");

  const load = React.useCallback(() => {
    adminApi.get<Plan[]>("/plans").then(setPlans).catch((e) => setError(e.message));
  }, []);
  React.useEffect(load, [load]);

  async function save() {
    if (!editing) return;
    setSaving(true);
    setError("");
    try {
      await adminApi.patch(`/plans/${encodeURIComponent(editing.id)}`, {
        name: editing.name,
        description: editing.description,
        amount: Number(editing.amount),
        days: Number(editing.days),
        active: editing.active,
        sortOrder: Number(editing.sortOrder),
      });
      setEditing(null);
      setFlash("Plan saved — live on checkout immediately.");
      setTimeout(() => setFlash(""), 4000);
      load();
    } catch (e: any) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  }

  async function toggleActive(p: Plan) {
    try {
      await adminApi.patch(`/plans/${encodeURIComponent(p.id)}`, { active: !p.active });
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  if (!plans) return <div className="animate-pulse text-sm text-slate-400">{error || "Loading plans…"}</div>;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-white">Plans & Pricing</h1>
        <p className="mt-1 text-sm text-slate-400">DB-driven pricing — changes apply to checkout and webhook validation instantly, every edit audited.</p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-900/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300">{flash}</div>}
      {error && <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="overflow-hidden rounded-xl border border-slate-800">
        <table className="w-full text-sm">
          <thead className="bg-slate-900/80 text-left text-[11px] uppercase tracking-wider text-slate-500">
            <tr>
              <th className="px-4 py-3">Plan</th>
              <th className="px-4 py-3">Price (KES)</th>
              <th className="px-4 py-3">Days</th>
              <th className="px-4 py-3">Active subs</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800 bg-slate-900/40">
            {plans.map((p) => (
              <tr key={p.id}>
                <td className="px-4 py-3">
                  <div className="font-medium text-slate-100">{p.name}</div>
                  <div className="text-xs text-slate-500">{p.id}{p.description ? ` — ${p.description}` : ""}</div>
                </td>
                <td className="px-4 py-3 text-slate-200">{(p.amount / 100).toLocaleString()}</td>
                <td className="px-4 py-3 text-slate-300">{p.days}</td>
                <td className="px-4 py-3 text-slate-300">{p.activeSubscriptions}</td>
                <td className="px-4 py-3">
                  <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${p.active ? "bg-emerald-500/15 text-emerald-400" : "bg-slate-700/50 text-slate-400"}`}>
                    {p.active ? "Active" : "Hidden"}
                  </span>
                </td>
                <td className="px-4 py-3 text-right">
                  <button onClick={() => setEditing({ ...p })} className="rounded-lg px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800 hover:text-white">Edit</button>
                  <button onClick={() => toggleActive(p)} className="rounded-lg px-2.5 py-1 text-xs text-slate-400 hover:bg-slate-800 hover:text-white">
                    {p.active ? "Hide" : "Show"}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {editing && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" onClick={() => setEditing(null)}>
          <div className="w-full max-w-md space-y-4 rounded-2xl border border-slate-700 bg-slate-900 p-6" onClick={(e) => e.stopPropagation()}>
            <h2 className="text-lg font-semibold text-white">Edit plan — {editing.id}</h2>
            <div>
              <label className="mb-1 block text-xs text-slate-400">Name</label>
              <input value={editing.name} onChange={(e) => setEditing({ ...editing, name: e.target.value })} className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white focus:border-emerald-500 focus:outline-none" />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="mb-1 block text-xs text-slate-400">Price (KES)</label>
                <input type="number" value={editing.amount / 100} onChange={(e) => setEditing({ ...editing, amount: Math.round(Number(e.target.value) * 100) })} className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white focus:border-emerald-500 focus:outline-none" />
              </div>
              <div>
                <label className="mb-1 block text-xs text-slate-400">Duration (days)</label>
                <input type="number" value={editing.days} onChange={(e) => setEditing({ ...editing, days: Number(e.target.value) })} className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white focus:border-emerald-500 focus:outline-none" />
              </div>
            </div>
            <div>
              <label className="mb-1 block text-xs text-slate-400">Description</label>
              <input value={editing.description || ""} onChange={(e) => setEditing({ ...editing, description: e.target.value })} className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white focus:border-emerald-500 focus:outline-none" />
            </div>
            <label className="flex items-center gap-2 text-sm text-slate-300">
              <input type="checkbox" checked={editing.active} onChange={(e) => setEditing({ ...editing, active: e.target.checked })} className="h-4 w-4 accent-emerald-500" />
              Visible to customers
            </label>
            <div className="flex justify-end gap-2 pt-2">
              <button onClick={() => setEditing(null)} className="rounded-lg px-4 py-2 text-sm text-slate-400 hover:text-white">Cancel</button>
              <button onClick={save} disabled={saving} className="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                {saving ? "Saving…" : "Save plan"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
