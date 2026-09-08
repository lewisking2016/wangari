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
        <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">Promo & Partnership Codes</h1>
        <p className="mt-1 text-sm text-wangari-muted">
          Codes are redeemed at checkout — the payment webhook records attribution and discount. Percent codes apply to the plan price; fixed codes cap at it.
        </p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash}</div>}
      {error && <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>}

      <form onSubmit={create} className="grid grid-cols-2 gap-3 rounded-xl border border-wangari-border bg-white p-5 lg:grid-cols-7">
        <div className="col-span-2 lg:col-span-1">
          <label className="mb-1 block text-[11px] text-wangari-muted">Code *</label>
          <input required value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} placeholder="LAUNCH25"
            className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-wangari-muted">Type</label>
          <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}
            className="h-10 w-full rounded-lg border border-wangari-border bg-white px-2 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none">
            <option value="discount">Discount</option>
            <option value="partnership">Partnership</option>
            <option value="credit">Credit</option>
          </select>
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-wangari-muted">Discount</label>
          <select value={form.discountType} onChange={(e) => setForm({ ...form, discountType: e.target.value })}
            className="h-10 w-full rounded-lg border border-wangari-border bg-white px-2 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none">
            <option value="percent">% off</option>
            <option value="fixed">KES off</option>
          </select>
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-wangari-muted">Value *</label>
          <input required type="number" min="1" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} placeholder="25"
            className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-wangari-muted">Max uses</label>
          <input type="number" min="1" value={form.maxRedemptions} onChange={(e) => setForm({ ...form, maxRedemptions: e.target.value })} placeholder="∞"
            className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
        </div>
        <div>
          <label className="mb-1 block text-[11px] text-wangari-muted">Partner</label>
          <input value={form.partnerName} onChange={(e) => setForm({ ...form, partnerName: e.target.value })} placeholder="optional"
            className="h-10 w-full rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
        </div>
        <div className="flex items-end">
          <button type="submit" disabled={busy} className="h-10 w-full rounded-lg bg-wangari-green-800 text-sm font-semibold text-wangari-heading shadow-md hover:bg-wangari-green-900 disabled:opacity-60">
            {busy ? "Creating…" : "Create code"}
          </button>
        </div>
      </form>

      {!rows ? (
        <div className="animate-pulse text-sm text-wangari-muted">Loading codes…</div>
      ) : rows.length === 0 ? (
        <div className="rounded-xl border border-wangari-border bg-white px-4 py-8 text-center text-sm text-wangari-subtle">No promo codes yet — create the first one above.</div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-wangari-border">
          <table className="w-full min-w-[640px] text-sm">
            <thead className="bg-wangari-green-50/60 text-left text-[11px] font-bold uppercase tracking-wider text-wangari-muted">
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
            <tbody className="divide-y divide-wangari-border bg-white">
              {rows.map((p) => (
                <tr key={p.id}>
                  <td className="px-4 py-3 font-mono font-semibold text-emerald-700">{p.code}</td>
                  <td className="px-4 py-3 text-wangari-text">
                    {p.discountType === "percent" ? `${p.value}%` : `KES ${p.value?.toLocaleString()}`}
                    <span className="ml-1.5 text-xs text-wangari-subtle">({p.type})</span>
                  </td>
                  <td className="px-4 py-3 text-wangari-text">{p.timesRedeemed}{p.maxRedemptions ? ` / ${p.maxRedemptions}` : ""}</td>
                  <td className="px-4 py-3 text-wangari-muted">{p.partnerName || "—"}</td>
                  <td className="px-4 py-3 text-xs text-wangari-muted">{p.expiresAt ? new Date(p.expiresAt).toLocaleDateString() : "never"}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${p.active ? "bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200" : "bg-wangari-cream text-wangari-muted"}`}>
                      {p.active ? "Active" : "Off"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button onClick={() => toggle(p)} className="rounded-lg px-2.5 py-1 text-xs text-wangari-text hover:bg-wangari-cream hover:text-wangari-heading">
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
