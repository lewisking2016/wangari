"use client";

import * as React from "react";
import { TicketPercent, Plus, Power } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, Field, inputClass, Loading, ErrorState, Flash,
  EmptyState, PrimaryButton, GhostButton,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

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
  const [showCreate, setShowCreate] = React.useState(false);

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
      setShowCreate(false);
      setFlash("Promo code created — it's live at checkout immediately.");
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
      <PageHeader
        icon={<TicketPercent className="h-5 w-5" />}
        title="Promo & Partnership Codes"
        description="Codes are redeemed at checkout — the payment webhook records attribution and discount."
        actions={
          <PrimaryButton onClick={() => setShowCreate((v) => !v)}>
            <Plus className="h-4 w-4" /> New code
          </PrimaryButton>
        }
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      {showCreate && (
        <Panel title="Create promo code" description="Live at checkout as soon as it's saved. Every action is audited.">
          <form onSubmit={create} className="grid grid-cols-2 gap-4 lg:grid-cols-6">
            <Field label="Code">
              <input required value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} placeholder="LAUNCH25" className={inputClass} />
            </Field>
            <Field label="Type">
              <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className={inputClass}>
                <option value="discount">Discount</option>
                <option value="partnership">Partnership</option>
                <option value="credit">Credit</option>
              </select>
            </Field>
            <Field label="Discount">
              <select value={form.discountType} onChange={(e) => setForm({ ...form, discountType: e.target.value })} className={inputClass}>
                <option value="percent">% off</option>
                <option value="fixed">KES off</option>
              </select>
            </Field>
            <Field label="Value">
              <input required type="number" min="1" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} placeholder="25" className={inputClass} />
            </Field>
            <Field label="Max uses" hint="Empty = unlimited">
              <input type="number" min="1" value={form.maxRedemptions} onChange={(e) => setForm({ ...form, maxRedemptions: e.target.value })} className={inputClass} />
            </Field>
            <Field label="Expires" hint="Empty = never">
              <input type="date" value={form.expiresAt} onChange={(e) => setForm({ ...form, expiresAt: e.target.value })} className={inputClass} />
            </Field>
            <div className="col-span-2 lg:col-span-4">
              <Field label="Partner name" hint="Optional — attribution for partnership codes">
                <input value={form.partnerName} onChange={(e) => setForm({ ...form, partnerName: e.target.value })} className={inputClass} />
              </Field>
            </div>
            <div className="col-span-2 flex items-end gap-2 lg:col-span-2">
              <GhostButton onClick={() => setShowCreate(false)} className="flex-1">Cancel</GhostButton>
              <PrimaryButton type="submit" disabled={busy} className="flex-1">
                {busy ? "Creating…" : "Create code"}
              </PrimaryButton>
            </div>
          </form>
        </Panel>
      )}

      <Panel bodyClassName="p-0">
        {!rows ? (
          <Loading label="Loading codes…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No promo codes yet" hint="Create the first one with the New code button." icon={<TicketPercent className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={780}>
            <thead>
              <tr>
                <Th>Code</Th>
                <Th>Discount</Th>
                <Th>Redeemed</Th>
                <Th>Partner</Th>
                <Th>Expires</Th>
                <Th>Status</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {rows.map((p) => (
                <tr key={p.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td>
                    <code className="rounded bg-wangari-green-50 px-2 py-0.5 font-mono text-xs font-bold text-wangari-green-800">{p.code}</code>
                    <div className="mt-0.5 text-[11px] capitalize text-wangari-subtle">{p.type}</div>
                  </Td>
                  <Td className="text-wangari-text">
                    {p.discountType === "percent" ? `${p.value}%` : `KES ${p.value?.toLocaleString()}`}
                  </Td>
                  <Td className="text-wangari-text">
                    {p.timesRedeemed}{p.maxRedemptions ? ` / ${p.maxRedemptions}` : ""}
                  </Td>
                  <Td className="text-wangari-muted">{p.partnerName || "—"}</Td>
                  <Td className="whitespace-nowrap text-xs text-wangari-muted">
                    {p.expiresAt ? new Date(p.expiresAt).toLocaleDateString() : "never"}
                  </Td>
                  <Td>
                    {p.active ? <Badge variant="success">active</Badge> : <Badge variant="outline">disabled</Badge>}
                  </Td>
                  <Td className="text-right">
                    <GhostButton onClick={() => toggle(p)} className="h-7 px-2 text-xs">
                      <Power className="h-3 w-3" /> {p.active ? "Disable" : "Enable"}
                    </GhostButton>
                  </Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}
      </Panel>
    </div>
  );
}
