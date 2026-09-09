"use client";

import * as React from "react";
import {
  Ticket as TicketIcon, Send, MessageSquare, UserRound, Headset,
  Inbox, Timer, Flame, CheckCircle2, AlarmClock,
} from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, FilterPill, Loading, ErrorState, EmptyState, PrimaryButton, GhostButton, StatCard,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface TicketRow {
  id: number;
  subject: string;
  status: string;
  priority: string;
  user: { name: string; email: string } | null;
  messageCount: number;
  createdAt: string;
  updatedAt: string;
  summary?: TicketSummary;
}

interface TicketSummary {
  total: number;
  open: number;
  pending: number;
  solved: number;
  closed: number;
  high: number;
  avgResolutionH: number;
  unresolvedOver24h: number;
}

interface TicketDetail extends TicketRow {
  messages: { id: number; authorType: string; authorId: number | null; body: string; createdAt: string }[];
}

const STATUS_VARIANT: Record<string, "warning" | "info" | "success" | "outline"> = {
  open: "warning",
  pending: "info",
  solved: "success",
  closed: "outline",
};

const PRIORITY_VARIANT: Record<string, "danger" | "warning" | "outline"> = {
  high: "danger",
  normal: "outline",
  low: "outline",
};

function ageHours(iso: string): number {
  return Math.floor((Date.now() - new Date(iso).getTime()) / 3_600_000);
}

export default function AdminTicketsPage() {
  const [rows, setRows] = React.useState<TicketRow[] | null>(null);
  const [status, setStatus] = React.useState("all");
  const [priority, setPriority] = React.useState("all");
  const [selected, setSelected] = React.useState<TicketDetail | null>(null);
  const [reply, setReply] = React.useState("");
  const [nextStatus, setNextStatus] = React.useState("pending");
  const [error, setError] = React.useState("");
  const [busy, setBusy] = React.useState(false);

  const load = React.useCallback(() => {
    adminApi.get<TicketRow[]>(`/tickets?status=${status}&priority=${priority}`)
      .then(setRows)
      .catch((e) => setError(e.message));
  }, [status, priority]);
  React.useEffect(load, [load]);

  const summary = rows?.[0]?.summary ?? null;

  async function open(id: number) {
    try {
      const t = await adminApi.get<TicketDetail>(`/tickets/${id}`);
      setSelected(t);
      setNextStatus(t.status === "open" ? "pending" : t.status);
    } catch (e: any) {
      setError(e.message);
    }
  }

  async function sendReply(e: React.FormEvent) {
    e.preventDefault();
    if (!selected) return;
    setBusy(true);
    try {
      await adminApi.post(`/tickets/${selected.id}/reply`, { body: reply, status: nextStatus });
      setReply("");
      await open(selected.id);
      load();
    } catch (e: any) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<TicketIcon className="h-5 w-5" />}
        title="Support Tickets"
        description="Customer conversations from the in-app help form — reply moves the ticket and emails the customer."
      />

      {error && <ErrorState message={error} />}

      {/* Support-desk stats */}
      {summary && (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
          <StatCard label="Total tickets" value={summary.total} icon={<Inbox className="h-5 w-5" />} accent="green" />
          <StatCard label="Open" value={summary.open} icon={<MessageSquare className="h-5 w-5" />} accent={summary.open > 0 ? "amber" : "slate"} hint="never answered" />
          <StatCard label="Pending" value={summary.pending} icon={<Timer className="h-5 w-5" />} accent="blue" hint="awaiting customer" />
          <StatCard
            label="High priority"
            value={summary.high}
            icon={<Flame className="h-5 w-5" />}
            accent={summary.high > 0 ? "red" : "slate"}
            hint="unresolved"
          />
          <StatCard label="Avg resolution" value={summary.avgResolutionH > 0 ? `${summary.avgResolutionH}h` : "—"} icon={<CheckCircle2 className="h-5 w-5" />} accent="green" hint={`${summary.solved} solved`} />
          <StatCard
            label="Over 24h"
            value={summary.unresolvedOver24h}
            icon={<AlarmClock className="h-5 w-5" />}
            accent={summary.unresolvedOver24h > 0 ? "red" : "slate"}
            hint="SLA breach"
          />
        </div>
      )}

      <div className="flex flex-wrap items-center gap-1.5">
        {["all", "open", "pending", "solved", "closed"].map((s) => (
          <FilterPill key={s} active={status === s} onClick={() => setStatus(s)}>{s}</FilterPill>
        ))}
        <span className="mx-1 w-px self-stretch bg-wangari-border" />
        {["all", "high", "normal", "low"].map((p) => (
          <FilterPill key={p} active={priority === p} onClick={() => setPriority(p)}>{p === "all" ? "any priority" : p}</FilterPill>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {/* List */}
        <div className="lg:col-span-2">
          {!rows ? (
            <Panel><Loading label="Loading tickets…" /></Panel>
          ) : rows.length === 0 ? (
            <Panel>
              <EmptyState title="No tickets" hint="Customer submissions from the in-app help form appear here." icon={<TicketIcon className="h-5 w-5" />} />
            </Panel>
          ) : (
            <div className="space-y-2">
              {rows.map((t) => {
                const age = ageHours(t.createdAt);
                const unresolved = t.status === "open" || t.status === "pending";
                return (
                  <button
                    key={t.id}
                    onClick={() => open(t.id)}
                    className={`w-full rounded-2xl border p-4 text-left transition-all ${selected?.id === t.id
                      ? "border-wangari-green-300 bg-wangari-green-50 shadow-sm"
                      : "border-wangari-border bg-white shadow-[0_1px_3px_rgba(0,0,0,0.04)] hover:border-wangari-green-300 hover:shadow-[0_4px_12px_rgba(0,0,0,0.06)]"}`}
                  >
                    <div className="flex items-center justify-between gap-2">
                      <span className="truncate text-sm font-semibold text-wangari-heading">{t.subject}</span>
                      <Badge variant={STATUS_VARIANT[t.status] || "outline"}>{t.status}</Badge>
                    </div>
                    <div className="mt-1 text-xs text-wangari-subtle">
                      {t.user?.name || t.user?.email || "unknown"} · {t.messageCount} messages · {new Date(t.updatedAt).toLocaleDateString()}
                    </div>
                    <div className="mt-1.5 flex items-center gap-1.5">
                      {t.priority === "high" && <Badge variant="danger" className="!px-1.5 !py-0 !text-[10px]">high</Badge>}
                      {unresolved && age > 24 && (
                        <span className="inline-flex items-center gap-0.5 text-[10px] font-semibold text-badge-red-text">
                          <AlarmClock className="h-3 w-3" /> {age}h unresolved
                        </span>
                      )}
                      {unresolved && age <= 24 && (
                        <span className="text-[10px] text-wangari-subtle">{age}h old</span>
                      )}
                    </div>
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {/* Thread */}
        <div className="lg:col-span-3">
          {!selected ? (
            <Panel className="h-full">
              <EmptyState title="Select a ticket" hint="Pick a conversation on the left to view and reply." icon={<MessageSquare className="h-5 w-5" />} />
            </Panel>
          ) : (
            <Panel>
              <div className="flex items-start justify-between gap-3 border-b border-wangari-border pb-4">
                <div className="min-w-0">
                  <h2 className="truncate text-lg font-bold text-wangari-heading">{selected.subject}</h2>
                  <div className="mt-0.5 text-xs text-wangari-subtle">
                    {selected.user?.name} · {selected.user?.email} · #{selected.id} · opened {new Date(selected.createdAt).toLocaleString()}
                  </div>
                  <div className="mt-1.5 flex gap-1.5">
                    <Badge variant={STATUS_VARIANT[selected.status] || "outline"}>{selected.status}</Badge>
                    {selected.priority !== "normal" && (
                      <Badge variant={PRIORITY_VARIANT[selected.priority] || "outline"}>{selected.priority} priority</Badge>
                    )}
                  </div>
                </div>
              </div>

              <div className="max-h-[420px] space-y-3 overflow-y-auto py-4">
                {selected.messages.map((m) => {
                  const isAdmin = m.authorType === "admin";
                  return (
                    <div key={m.id} className={`flex gap-2.5 ${isAdmin ? "flex-row-reverse" : ""}`}>
                      <div className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${isAdmin ? "bg-wangari-green-100 text-wangari-green-800" : "bg-wangari-cream text-wangari-muted"}`}>
                        {isAdmin ? <Headset className="h-3.5 w-3.5" /> : <UserRound className="h-3.5 w-3.5" />}
                      </div>
                      <div className={`max-w-[80%] rounded-2xl px-4 py-2.5 text-sm text-wangari-heading ${isAdmin ? "bg-wangari-green-50" : "bg-wangari-cream"}`}>
                        <div className="mb-1 text-[11px] font-medium text-wangari-subtle">
                          {isAdmin ? "Support" : selected.user?.name || "Customer"} · {new Date(m.createdAt).toLocaleString()}
                        </div>
                        {m.body}
                      </div>
                    </div>
                  );
                })}
              </div>

              <form onSubmit={sendReply} className="space-y-3 border-t border-wangari-border pt-4">
                <textarea
                  required
                  rows={3}
                  value={reply}
                  onChange={(e) => setReply(e.target.value)}
                  placeholder="Write a reply… (an email notification is sent to the customer)"
                  className="w-full rounded-xl border border-wangari-border bg-white px-4 py-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20"
                />
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <select
                    value={nextStatus}
                    onChange={(e) => setNextStatus(e.target.value)}
                    className="h-9 rounded-lg border border-wangari-border bg-white px-2 text-xs font-medium text-wangari-heading focus:border-wangari-green-500 focus:outline-none"
                  >
                    {["pending", "solved", "closed", "open"].map((s) => (
                      <option key={s} value={s}>mark as {s}</option>
                    ))}
                  </select>
                  <div className="flex gap-2">
                    <GhostButton onClick={() => { setReply(""); setSelected(null); }}>Close</GhostButton>
                    <PrimaryButton type="submit" disabled={busy || !reply.trim()}>
                      <Send className="h-3.5 w-3.5" /> {busy ? "Sending…" : "Send reply"}
                    </PrimaryButton>
                  </div>
                </div>
              </form>
            </Panel>
          )}
        </div>
      </div>
    </div>
  );
}
