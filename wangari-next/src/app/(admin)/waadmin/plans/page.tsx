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

  if (!plans) return <div className="animate-pulse text-sm text-wangari-muted">{error || "Loading plans…"}</div>;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">Plans & Pricing</h1>
        <p className="mt-1 text-sm text-wangari-muted">DB-driven pricing — changes apply to checkout and webhook validation instantly, every edit audited.</p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash}</div>}
      {error && <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>}

      <div className="overflow-x-auto rounded-xl border border-wangari-border">
        <table className="w-full min-w-[640px] text-sm">
          <thead className="bg-wangari-green-50/60 text-left text-[11px] font-bold uppercase tracking-wider text-wangari-muted">
            <tr>
              <th className="px-4 py-3">Plan</th>
              <th className="px-4 py-3">Price (KES)</th>
              <th className="px-4 py-3">Days</th>
              <th className="px-4 py-3">Active subs</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-wangari-border bg-white">
            {plans.map((p) => (
              <tr key={p.id}>
                <td className="px-4 py-3">
                  <div className="font-medium text-wangari-heading">{p.name}</div>
                  <div className="text-xs text-wangari-subtle">{p.id}{p.description ? ` — ${p.description}` : ""}</div>
                </td>
                <td className="px-4 py-3 text-wangari-heading">{(p.amount / 100).toLocaleString()}</td>
                <td className="px-4 py-3 text-wangari-text">{p.days}</td>
                <td className="px-4 py-3 text-wangari-text">{p.activeSubscriptions}</td>
                <td className="px-4 py-3">
                  <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${p.active ? "bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200" : "bg-wangari-cream text-wangari-muted"}`}>
                    {p.active ? "Active" : "Hidden"}
                  </span>
                </td>
                <td className="px-4 py-3 text-right">
                  <button onClick={() => setEditing({ ...p })} className="rounded-lg px-2.5 py-1 text-xs text-wangari-text hover:bg-wangari-cream hover:text-wangari-heading">Edit</button>
                  <button onClick={() => toggleActive(p)} className="rounded-lg px-2.5 py-1 text-xs text-wangari-muted hover:bg-wangari-cream hover:text-wangari-heading">
                    {p.active ? "Hide" : "Show"}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {editing && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4" onClick={() => setEditing(null)}>
          <div className="w-full max-w-md space-y-4 rounded-2xl border border-wangari-border bg-white p-6" onClick={(e) => e.stopPropagation()}>
            <h2 className="text-lg font-semibold text-wangari-heading">Edit plan — {editing.id}</h2>
            <div>
              <label className="mb-1 block text-xs text-wangari-muted">Name</label>
              <input value={editing.name} onChange={(e) => setEditing({ ...editing, name: e.target.value })} className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none" />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="mb-1 block text-xs text-wangari-muted">Price (KES)</label>
                <input type="number" value={editing.amount / 100} onChange={(e) => setEditing({ ...editing, amount: Math.round(Number(e.target.value) * 100) })} className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none" />
              </div>
              <div>
                <label className="mb-1 block text-xs text-wangari-muted">Duration (days)</label>
                <input type="number" value={editing.days} onChange={(e) => setEditing({ ...editing, days: Number(e.target.value) })} className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none" />
              </div>
            </div>
            <div>
              <label className="mb-1 block text-xs text-wangari-muted">Description</label>
              <input value={editing.description || ""} onChange={(e) => setEditing({ ...editing, description: e.target.value })} className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none" />
            </div>
            <label className="flex items-center gap-2 text-sm text-wangari-text">
              <input type="checkbox" checked={editing.active} onChange={(e) => setEditing({ ...editing, active: e.target.checked })} className="h-4 w-4 accent-emerald-500" />
              Visible to customers
            </label>
            <div className="flex justify-end gap-2 pt-2">
              <button onClick={() => setEditing(null)} className="rounded-lg px-4 py-2 text-sm text-wangari-muted hover:text-wangari-heading">Cancel</button>
              <button onClick={save} disabled={saving} className="rounded-lg bg-wangari-green-800 px-4 py-2 text-sm font-semibold text-wangari-heading shadow-md hover:bg-wangari-green-900 disabled:opacity-60">
                {saving ? "Saving…" : "Save plan"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
