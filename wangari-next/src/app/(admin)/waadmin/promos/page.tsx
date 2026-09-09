"use client";

import * as React from "react";
import {
  TicketPercent, Plus, Power, X,
  Ticket as TicketIcon, Zap, Handshake, ReceiptText,
} from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, Field, inputClass, Loading, ErrorState, Flash,
  EmptyState, Modal, PrimaryButton, GhostButton, StatCard,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface Redemption {
  id: number;
  reference: string | null;
  discountKes: any;
  createdAt: string;
}

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
  createdAt: string;
  redemptions: number;
  recentRedemptions: Redemption[];
  summary: {
    totalCodes: number;
    active: number;
    totalRedemptions: number;
    totalDiscountKes: number;
    partnerCodes: number;
  };
}

interface PromoListRes extends Array<PromoRow> {}

const EMPTY = { code: "", type: "discount", discountType: "percent", value: "", maxRedemptions: "", partnerName: "", expiresAt: "" };

function promoState(p: PromoRow): "active" | "expired" | "disabled" {
  if (!p.active) return "disabled";
  if (p.expiresAt && new Date(p.expiresAt) < new Date()) return "expired";
  return "active";
}

export default function AdminPromosPage() {
  const [rows, setRows] = React.useState<PromoRow[] | null>(null);
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const [form, setForm] = React.useState({ ...EMPTY });
  const [busy, setBusy] = React.useState(false);
  const [showCreate, setShowCreate] = React.useState(false);
  const [detail, setDetail] = React.useState<PromoRow | null>(null);

  const load = React.useCallback(() => {
    adminApi.get<PromoListRes>("/promos").then((r) => setRows(r)).catch((e) => setError(e.message));
  }, []);
  React.useEffect(load, [load]);

  const summary = rows?.[0]?.summary ?? null;

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
      setDetail(null);
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
          <PrimaryButton onClick={() => setShowCreate(true)}>
            <Plus className="h-4 w-4" /> New code
          </PrimaryButton>
        }
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      {/* Summary stats */}
      {summary && (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
          <StatCard label="Total codes" value={summary.totalCodes} icon={<TicketIcon className="h-5 w-5" />} accent="green" />
          <StatCard label="Active now" value={summary.active} icon={<Zap className="h-5 w-5" />} accent="blue" hint="usable at checkout" />
          <StatCard label="Total redemptions" value={summary.totalRedemptions} icon={<ReceiptText className="h-5 w-5" />} accent="violet" />
          <StatCard label="Discount given" value={`KES ${summary.totalDiscountKes.toLocaleString()}`} icon={<TicketPercent className="h-5 w-5" />} accent="amber" hint="across all codes" />
          <StatCard label="Partner codes" value={summary.partnerCodes} icon={<Handshake className="h-5 w-5" />} accent="slate" />
        </div>
      )}

      <Panel bodyClassName="p-0">
        {!rows ? (
          <Loading label="Loading codes…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No promo codes yet" hint="Create the first one with the New code button." icon={<TicketPercent className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={820}>
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
              {rows.map((p) => {
                const state = promoState(p);
                return (
                  <tr
                    key={p.id}
                    onClick={() => setDetail(p)}
                    className="cursor-pointer transition-colors hover:bg-wangari-green-50/40"
                  >
                    <Td>
                      <code className="rounded bg-wangari-green-50 px-2 py-0.5 font-mono text-xs font-bold text-wangari-green-800">{p.code}</code>
                      <div className="mt-0.5 text-[11px] capitalize text-wangari-subtle">{p.type}</div>
                    </Td>
                    <Td className="text-wangari-text">
                      {p.discountType === "percent" ? `${p.value}%` : `KES ${p.value?.toLocaleString()}`}
                    </Td>
                    <Td className="text-wangari-text">
                      <span className="font-medium text-wangari-heading">{p.timesRedeemed}</span>
                      {p.maxRedemptions ? <span className="text-wangari-muted"> / {p.maxRedemptions}</span> : ""}
                    </Td>
                    <Td className="text-wangari-muted">{p.partnerName || "—"}</Td>
                    <Td className="whitespace-nowrap text-xs text-wangari-muted">
                      {p.expiresAt ? new Date(p.expiresAt).toLocaleDateString() : "never"}
                    </Td>
                    <Td>
                      {state === "active" ? (
                        <Badge variant="success">active</Badge>
                      ) : state === "expired" ? (
                        <Badge variant="warning">expired</Badge>
                      ) : (
                        <Badge variant="outline">disabled</Badge>
                      )}
                    </Td>
                    <Td className="text-right">
                      <GhostButton
                        onClick={(e) => { e.stopPropagation(); toggle(p); }}
                        className="h-7 px-2 text-xs"
                      >
                        <Power className="h-3 w-3" /> {p.active ? "Disable" : "Enable"}
                      </GhostButton>
                    </Td>
                  </tr>
                );
              })}
            </tbody>
          </TableShell>
        )}
      </Panel>

      {/* Create modal */}
      <Modal title="Create promo code" onClose={() => setShowCreate(false)} open={showCreate} width="max-w-lg">
        <form onSubmit={create} className="space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <Field label="Code" hint="Uppercase, unique">
              <input required value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} placeholder="LAUNCH25" className={inputClass} />
            </Field>
            <Field label="Type">
              <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className={inputClass}>
                <option value="discount">Discount</option>
                <option value="partnership">Partnership</option>
                <option value="credit">Credit</option>
              </select>
            </Field>
            <Field label="Discount type">
              <select value={form.discountType} onChange={(e) => setForm({ ...form, discountType: e.target.value })} className={inputClass}>
                <option value="percent">% off</option>
                <option value="fixed">KES off</option>
              </select>
            </Field>
            <Field label="Value" hint={form.discountType === "percent" ? "1-100" : "KES amount"}>
              <input required type="number" min="1" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} placeholder="25" className={inputClass} />
            </Field>
            <Field label="Max uses" hint="Empty = unlimited">
              <input type="number" min="1" value={form.maxRedemptions} onChange={(e) => setForm({ ...form, maxRedemptions: e.target.value })} className={inputClass} />
            </Field>
            <Field label="Expires" hint="Empty = never">
              <input type="date" value={form.expiresAt} onChange={(e) => setForm({ ...form, expiresAt: e.target.value })} className={inputClass} />
            </Field>
          </div>
          <Field label="Partner name" hint="Optional — attribution for partnership codes">
            <input value={form.partnerName} onChange={(e) => setForm({ ...form, partnerName: e.target.value })} className={inputClass} />
          </Field>
          <div className="flex justify-end gap-2 pt-1">
            <GhostButton onClick={() => setShowCreate(false)}>Cancel</GhostButton>
            <PrimaryButton type="submit" disabled={busy}>
              {busy ? "Creating…" : "Create code"}
            </PrimaryButton>
          </div>
        </form>
      </Modal>

      {/* Code detail drawer */}
      {detail && (
        <div className="fixed inset-0 z-50 flex justify-end bg-black/30" onClick={() => setDetail(null)}>
          <div
            className="h-full w-full max-w-md overflow-y-auto border-l border-wangari-border bg-white shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="space-y-5 p-6">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <code className="rounded bg-wangari-green-50 px-3 py-1 font-mono text-lg font-bold text-wangari-green-800">{detail.code}</code>
                  <div className="mt-1.5 flex gap-1.5">
                    <Badge variant="info">{detail.type}</Badge>
                    {promoState(detail) === "active" ? <Badge variant="success">active</Badge> : promoState(detail) === "expired" ? <Badge variant="warning">expired</Badge> : <Badge variant="outline">disabled</Badge>}
                  </div>
                </div>
                <button onClick={() => setDetail(null)} className="rounded-lg p-1 text-wangari-muted hover:bg-wangari-cream hover:text-wangari-heading">
                  <X className="h-5 w-5" />
                </button>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="rounded-2xl border border-wangari-border p-3.5">
                  <div className="text-[11px] font-bold uppercase tracking-wider text-wangari-subtle">Discount</div>
                  <div className="mt-1 text-xl font-bold text-wangari-heading">
                    {detail.discountType === "percent" ? `${detail.value}%` : `KES ${detail.value?.toLocaleString()}`}
                  </div>
                </div>
                <div className="rounded-2xl border border-wangari-border p-3.5">
                  <div className="text-[11px] font-bold uppercase tracking-wider text-wangari-subtle">Redeemed</div>
                  <div className="mt-1 text-xl font-bold text-wangari-heading">
                    {detail.timesRedeemed}{detail.maxRedemptions ? <span className="text-sm text-wangari-muted"> / {detail.maxRedemptions}</span> : ""}
                  </div>
                </div>
                <div className="rounded-2xl border border-wangari-border p-3.5">
                  <div className="text-[11px] font-bold uppercase tracking-wider text-wangari-subtle">Partner</div>
                  <div className="mt-1 font-medium text-wangari-heading">{detail.partnerName || "—"}</div>
                </div>
                <div className="rounded-2xl border border-wangari-border p-3.5">
                  <div className="text-[11px] font-bold uppercase tracking-wider text-wangari-subtle">Expires</div>
                  <div className="mt-1 font-medium text-wangari-heading">
                    {detail.expiresAt ? new Date(detail.expiresAt).toLocaleDateString() : "Never"}
                  </div>
                </div>
              </div>

              <div>
                <div className="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-wangari-subtle">
                  <ReceiptText className="h-3.5 w-3.5" /> Recent redemptions
                </div>
                {detail.recentRedemptions.length === 0 ? (
                  <div className="rounded-xl border border-dashed border-wangari-border px-3 py-4 text-center text-xs text-wangari-subtle">
                    Not redeemed yet — redemptions appear here as customers use it at checkout.
                  </div>
                ) : (
                  <div className="space-y-1.5">
                    {detail.recentRedemptions.map((r) => (
                      <div key={r.id} className="flex items-center justify-between rounded-lg border border-wangari-border px-3 py-2 text-sm">
                        <div>
                          <div className="font-mono text-xs text-wangari-heading">{r.reference || "no reference"}</div>
                          <div className="text-[11px] text-wangari-subtle">{new Date(r.createdAt).toLocaleString()}</div>
                        </div>
                        <span className="font-semibold text-wangari-green-700">−KES {Number(r.discountKes).toLocaleString()}</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <div className="flex gap-2 border-t border-wangari-border pt-4">
                <PrimaryButton onClick={() => toggle(detail)}>
                  <Power className="h-4 w-4" /> {detail.active ? "Disable code" : "Enable code"}
                </PrimaryButton>
                <GhostButton onClick={() => setDetail(null)}>Close</GhostButton>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
