"use client";

import * as React from "react";
import { Tags, Plus, Eye, EyeOff, Pencil, Wallet, Users, ReceiptText, TrendingUp } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, Field, inputClass, Loading, ErrorState, Flash,
  EmptyState, Modal, PrimaryButton, GhostButton, StatCard,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";
import { ComparisonBarChart } from "@/components/admin/charts";

interface Plan {
  id: string;
  name: string;
  description: string | null;
  amount: number; // pesewas
  days: number;
  active: boolean;
  sortOrder: number;
  activeSubscriptions: number;
  mrr: number;
  lifetimeSubscribers: number;
  lifetimeRevenue: number;
}

type EditState = Plan & { isNew?: boolean };

const BLANK: EditState = {
  id: "", name: "", description: "", amount: 0, days: 30, active: true, sortOrder: 0,
  activeSubscriptions: 0, mrr: 0, lifetimeSubscribers: 0, lifetimeRevenue: 0, isNew: true,
};

export default function AdminPlansPage() {
  const [plans, setPlans] = React.useState<Plan[] | null>(null);
  const [error, setError] = React.useState("");
  const [editing, setEditing] = React.useState<EditState | null>(null);
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
      const payload = {
        name: editing.name,
        description: editing.description,
        amount: Number(editing.amount),
        days: Number(editing.days),
        active: editing.active,
        sortOrder: Number(editing.sortOrder),
      };
      if (editing.isNew) {
        await adminApi.post("/plans", { id: editing.id, ...payload });
        setFlash("Plan created — live on checkout immediately.");
      } else {
        await adminApi.patch(`/plans/${encodeURIComponent(editing.id)}`, payload);
        setFlash("Plan saved — live on checkout immediately.");
      }
      setEditing(null);
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

  const totalActive = plans?.reduce((s, p) => s + p.activeSubscriptions, 0) ?? 0;
  const totalMrr = plans?.reduce((s, p) => s + p.mrr, 0) ?? 0;
  const totalLifetime = plans?.reduce((s, p) => s + p.lifetimeRevenue, 0) ?? 0;
  const visiblePlans = plans?.filter((p) => p.active) ?? [];
  const chartData = plans
    ?.filter((p) => p.mrr > 0 || p.activeSubscriptions > 0)
    .map((p) => ({ name: p.name, mrr: p.mrr })) ?? [];

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<Tags className="h-5 w-5" />}
        title="Plans & Pricing"
        description="DB-driven pricing — changes apply to checkout and webhook validation instantly, every edit audited."
        actions={
          <PrimaryButton onClick={() => setEditing({ ...BLANK })}>
            <Plus className="h-4 w-4" /> New plan
          </PrimaryButton>
        }
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      {/* Pricing performance stats */}
      {plans && (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <StatCard label="Active subscriptions" value={totalActive} icon={<Users className="h-5 w-5" />} accent="green" hint={`${visiblePlans.length} visible plans`} />
          <StatCard label="MRR" value={`KES ${totalMrr.toLocaleString()}`} icon={<Wallet className="h-5 w-5" />} accent="blue" hint="from active subs" />
          <StatCard label="Lifetime revenue" value={`KES ${totalLifetime.toLocaleString()}`} icon={<ReceiptText className="h-5 w-5" />} accent="violet" hint="all paid subs ever" />
          <StatCard
            label="Best seller"
            value={plans.length ? [...plans].sort((a, b) => b.lifetimeSubscribers - a.lifetimeSubscribers)[0]?.name || "—" : "—"}
            icon={<TrendingUp className="h-5 w-5" />}
            accent="amber"
            hint="by lifetime subscribers"
          />
        </div>
      )}

      {/* Revenue by plan */}
      {chartData.length > 0 && (
        <ComparisonBarChart
          title="MRR by plan"
          subtitle="Recurring revenue contribution per plan (KES)"
          data={chartData}
          dataKey="mrr"
          height={220}
        />
      )}

      <Panel bodyClassName="p-0">
        {!plans ? (
          <Loading label="Loading plans…" />
        ) : plans.length === 0 ? (
          <EmptyState title="No plans yet" hint="Create the first pricing plan — it appears at checkout immediately." icon={<Tags className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={880}>
            <thead>
              <tr>
                <Th>Plan</Th>
                <Th>Price (KES)</Th>
                <Th>Days</Th>
                <Th>Active subs</Th>
                <Th>MRR</Th>
                <Th>Lifetime</Th>
                <Th>Status</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {plans.map((p) => (
                <tr key={p.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td>
                    <div className="font-medium text-wangari-heading">{p.name}</div>
                    <div className="text-xs text-wangari-subtle">{p.id}{p.description ? ` — ${p.description}` : ""}</div>
                  </Td>
                  <Td className="font-semibold text-wangari-heading">{(p.amount / 100).toLocaleString()}</Td>
                  <Td>{p.days}</Td>
                  <Td>
                    <span className="font-medium text-wangari-heading">{p.activeSubscriptions}</span>
                    {p.activeSubscriptions > 0 && <span className="ml-1 text-xs text-wangari-muted">· KES {p.mrr.toLocaleString()}/mo</span>}
                  </Td>
                  <Td className="font-semibold text-wangari-green-700">KES {p.mrr.toLocaleString()}</Td>
                  <Td className="text-xs text-wangari-muted">
                    {p.lifetimeSubscribers} sold · KES {p.lifetimeRevenue.toLocaleString()}
                  </Td>
                  <Td>
                    {p.active ? <Badge variant="success">active</Badge> : <Badge variant="outline">hidden</Badge>}
                  </Td>
                  <Td className="text-right">
                    <div className="inline-flex gap-1.5">
                      <GhostButton onClick={() => setEditing({ ...p })} className="h-7 px-2 text-xs">
                        <Pencil className="h-3 w-3" /> Edit
                      </GhostButton>
                      <GhostButton onClick={() => toggleActive(p)} className="h-7 px-2 text-xs">
                        {p.active ? <EyeOff className="h-3 w-3" /> : <Eye className="h-3 w-3" />}
                        {p.active ? "Hide" : "Show"}
                      </GhostButton>
                    </div>
                  </Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}
      </Panel>

      <Modal title={editing?.isNew ? "Create plan" : `Edit plan — ${editing?.id ?? ""}`} onClose={() => setEditing(null)} open={!!editing}>
        {editing && (
          <div className="space-y-4">
            <Field label="Plan ID" hint={editing.isNew ? "Lowercase key, e.g. starter, pro — used by checkout and the webhook" : "Cannot be changed after creation"}>
              <input
                value={editing.id}
                disabled={!editing.isNew}
                onChange={(e) => setEditing({ ...editing, id: e.target.value.toLowerCase() })}
                className={`${inputClass} disabled:bg-wangari-cream disabled:text-wangari-muted`}
              />
            </Field>
            <Field label="Name">
              <input value={editing.name} onChange={(e) => setEditing({ ...editing, name: e.target.value })} className={inputClass} />
            </Field>
            <Field label="Description" hint="Shown on the checkout pricing cards">
              <input value={editing.description || ""} onChange={(e) => setEditing({ ...editing, description: e.target.value })} className={inputClass} />
            </Field>
            <div className="grid grid-cols-3 gap-3">
              <Field label="Price (KES)">
                <input type="number" min="0" value={editing.amount / 100} onChange={(e) => setEditing({ ...editing, amount: Number(e.target.value) * 100 })} className={inputClass} />
              </Field>
              <Field label="Days">
                <input type="number" min="1" value={editing.days} onChange={(e) => setEditing({ ...editing, days: Number(e.target.value) })} className={inputClass} />
              </Field>
              <Field label="Sort order">
                <input type="number" value={editing.sortOrder} onChange={(e) => setEditing({ ...editing, sortOrder: Number(e.target.value) })} className={inputClass} />
              </Field>
            </div>
            <div className="rounded-xl bg-wangari-cream px-3 py-2.5 text-xs text-wangari-muted">
              Checkout preview: <span className="font-semibold text-wangari-heading">{editing.name || "Plan name"}</span>
              {" — "}KES {(editing.amount / 100).toLocaleString()} / {editing.days} days
            </div>
            <div className="flex justify-end gap-2 pt-1">
              <GhostButton onClick={() => setEditing(null)}>Cancel</GhostButton>
              <PrimaryButton onClick={save} disabled={saving || !editing.name || !editing.id || !(editing.amount >= 0)}>
                {saving ? "Saving…" : editing.isNew ? "Create plan" : "Save changes"}
              </PrimaryButton>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
