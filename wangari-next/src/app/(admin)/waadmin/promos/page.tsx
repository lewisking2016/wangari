"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface PromoRow {
  id: string;
  code: string;
  type: string;
  discountType: string | null;
  value: number | null;
  maxRedemptions: number | null;
  timesRedeemed: number;
  partnerName: string | null;
  expiresAt: string | null;
  active: boolean;
  redemptions: number;
}

const EMPTY = { code: "", type: "discount", discountType: "percent", value: "", maxRedemptions: "", partnerName: "", expiresAt: "" };

export default function AdminPromosPage() {
  const [rows, setRows] = React.useState<PromoRow[] | null>(null);
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const [form, setForm] = React.useState({ ...EMPTY });
  const [busy, setBusy] = React.useState(false);

  const load = React.useCallback(() => {
    adminApi.get<PromoRow[]>("/promos").then(setRows).catch((e) => setError(e.message));
  }, []);
  React.useEffect(load, [load]);

  async function create(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      await adminApi.post("/promos", {
        code: form.code,
        type: form.type,
        discountType: form.discountType,
        value: Number(form.value),
        maxRedemptions: form.maxRedemptions ? Number(form.maxRedemptions) : null,
        partnerName: form.partnerName || null,
        expiresAt: form.expiresAt || null,
      });
      setForm({ ...EMPTY });
      setFlash("Promo code created.");
      setTimeout(() => setFlash(""), 4000);
      load();
    } catch (e: any) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  async function toggle(p: PromoRow) {
    try {
      await adminApi.patch(`/promos/${p.id}`, { active: !p.active });
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-white">Promo & Partnership Codes</h1>
        <p className="mt-1 text-sm text-slate-400">
          Codes are redeemed at checkout — the payment webhook records attribution and discount. Percent codes apply to the plan price; fixed codes cap at it.
        </p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-900/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300">{flash}</div>}
      {error && <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <form onSubmit={create} className="grid grid-cols-2 gap-3 rounded-xl border border-slate-800 bg-slate-900/60 p-5 lg:grid-cols-7">
        <div className="col-span-2 lg:col-span-1">
          <label className="mb-1 block text-[11px] text-slate-400">Code *</label>
          <input required value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} placeholder="LAUNCH25"
            className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:outline-none" />
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-slate-400">Type</label>
          <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}
            className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
            <option value="discount">Discount</option>
            <option value="partnership">Partnership</option>
            <option value="credit">Credit</option>
          </select>
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-slate-400">Discount</label>
          <select value={form.discountType} onChange={(e) => setForm({ ...form, discountType: e.target.value })}
            className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
            <option value="percent">% off</option>
            <option value="fixed">KES off</option>
          </select>
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-slate-400">Value *</label>
          <input required type="number" min="1" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} placeholder="25"
            className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:outline-none" />
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-slate-400">Max uses</label>
          <input type="number" min="1" value={form.maxRedemptions} onChange={(e) => setForm({ ...form, maxRedemptions: e.target.value })} placeholder="∞"
            className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:outline-none" />
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-slate-400">Partner</label>
          <input value={form.partnerName} onChange={(e) => setForm({ ...form, partnerName: e.target.value })} placeholder="optional"
            className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:outline-none" />
        </div>
        <div className="flex items-end">
          <button type="submit" disabled={busy} className="h-10 w-full rounded-lg bg-emerald-500 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
            {busy ? "Creating…" : "Create code"}
          </button>
        </div>
      </form>

      {!rows ? (
        <div className="animate-pulse text-sm text-slate-400">Loading codes…</div>
      ) : rows.length === 0 ? (
        <div className="rounded-xl border border-slate-800 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-500">No promo codes yet — create the first one above.</div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-slate-800">
          <table className="w-full text-sm">
            <thead className="bg-slate-900/80 text-left text-[11px] uppercase tracking-wider text-slate-500">
              <tr>
                <th className="px-4 py-3">Code</th>
                <th className="px-4 py-3">Discount</th>
                <th className="px-4 py-3">Redeemed</th>
                <th className="px-4 py-3">Partner</th>
                <th className="px-4 py-3">Expires</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800 bg-slate-900/40">
              {rows.map((p) => (
                <tr key={p.id}>
                  <td className="px-4 py-3 font-mono font-semibold text-emerald-400">{p.code}</td>
                  <td className="px-4 py-3 text-slate-300">
                    {p.discountType === "percent" ? `${p.value}%` : `KES ${p.value?.toLocaleString()}`}
                    <span className="ml-1.5 text-xs text-slate-500">({p.type})</span>
                  </td>
                  <td className="px-4 py-3 text-slate-300">{p.timesRedeemed}{p.maxRedemptions ? ` / ${p.maxRedemptions}` : ""}</td>
                  <td className="px-4 py-3 text-slate-400">{p.partnerName || "—"}</td>
                  <td className="px-4 py-3 text-xs text-slate-400">{p.expiresAt ? new Date(p.expiresAt).toLocaleDateString() : "never"}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${p.active ? "bg-emerald-500/15 text-emerald-400" : "bg-slate-700/50 text-slate-400"}`}>
                      {p.active ? "Active" : "Off"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button onClick={() => toggle(p)} className="rounded-lg px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800 hover:text-white">
                      {p.active ? "Disable" : "Enable"}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
