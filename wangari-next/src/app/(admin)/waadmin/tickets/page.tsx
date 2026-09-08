"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface TicketRow {
  id: number;
  subject: string;
  status: string;
  priority: string;
  user: { name: string; email: string } | null;
  messageCount: number;
  createdAt: string;
  updatedAt: string;
}

interface TicketDetail extends TicketRow {
  messages: { id: number; authorType: string; authorId: number | null; body: string; createdAt: string }[];
}

const STATUS_STYLE: Record<string, string> = {
  open: "bg-amber-500/15 text-amber-400",
  pending: "bg-sky-500/15 text-sky-400",
  solved: "bg-emerald-500/15 text-emerald-400",
  closed: "bg-slate-700/50 text-slate-400",
};

export default function AdminTicketsPage() {
  const [rows, setRows] = React.useState<TicketRow[] | null>(null);
  const [status, setStatus] = React.useState("all");
  const [selected, setSelected] = React.useState<TicketDetail | null>(null);
  const [reply, setReply] = React.useState("");
  const [nextStatus, setNextStatus] = React.useState("pending");
  const [error, setError] = React.useState("");
  const [busy, setBusy] = React.useState(false);

  const load = React.useCallback(() => {
    adminApi.get<TicketRow[]>(`/tickets?status=${status}`).then(setRows).catch((e) => setError(e.message));
  }, [status]);
  React.useEffect(load, [load]);

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
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-white">Support Tickets</h1>
        <p className="mt-1 text-sm text-slate-400">Customer conversations. Replying moves the ticket and is audited.</p>
      </div>
      {error && <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <div className="flex gap-2">
        {["all", "open", "pending", "solved", "closed"].map((s) => (
          <button key={s} onClick={() => setStatus(s)}
            className={`rounded-lg px-3 py-1.5 text-xs font-medium ${status === s ? "bg-emerald-500/20 text-emerald-300" : "text-slate-400 hover:bg-slate-800"}`}>
            {s}
          </button>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {/* List */}
        <div className="lg:col-span-2">
          {!rows ? (
            <div className="animate-pulse text-sm text-slate-400">Loading tickets…</div>
          ) : rows.length === 0 ? (
            <div className="rounded-xl border border-slate-800 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-500">No tickets.</div>
          ) : (
            <div className="space-y-2">
              {rows.map((t) => (
                <button key={t.id} onClick={() => open(t.id)}
                  className={`w-full rounded-xl border p-4 text-left transition-colors ${selected?.id === t.id ? "border-emerald-700 bg-emerald-950/20" : "border-slate-800 bg-slate-900/40 hover:border-slate-700"}`}>
                  <div className="flex items-center justify-between gap-2">
                    <span className="truncate text-sm font-medium text-slate-100">{t.subject}</span>
                    <span className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${STATUS_STYLE[t.status] || ""}`}>{t.status}</span>
                  </div>
                  <div className="mt-1 text-xs text-slate-500">{t.user?.name || t.user?.email || "unknown"} · {t.messageCount} msg · {new Date(t.updatedAt).toLocaleDateString()}</div>
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Thread */}
        <div className="lg:col-span-3">
          {!selected ? (
            <div className="rounded-xl border border-slate-800 bg-slate-900/40 px-4 py-12 text-center text-sm text-slate-500">Select a ticket to view the conversation.</div>
          ) : (
            <div className="space-y-4 rounded-xl border border-slate-800 bg-slate-900/40 p-5">
              <div>
                <div className="flex items-center justify-between">
                  <h2 className="text-lg font-semibold text-white">{selected.subject}</h2>
                  <span className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${STATUS_STYLE[selected.status] || ""}`}>{selected.status}</span>
                </div>
                <div className="text-xs text-slate-500">{selected.user?.name} · {selected.user?.email} · #{selected.id}</div>
              </div>

              <div className="max-h-96 space-y-3 overflow-y-auto">
                {selected.messages.map((m) => (
                  <div key={m.id} className={`rounded-xl px-4 py-3 text-sm ${m.authorType === "admin" ? "ml-8 bg-emerald-950/30 text-slate-200" : "mr-8 bg-slate-800/60 text-slate-200"}`}>
                    <div className="mb-1 text-[11px] text-slate-500">
                      {m.authorType === "admin" ? "Support" : selected.user?.name || "Customer"} · {new Date(m.createdAt).toLocaleString()}
                    </div>
                    {m.body}
                  </div>
                ))}
              </div>

              <form onSubmit={sendReply} className="space-y-3 border-t border-slate-800 pt-4">
                <textarea
                  required
                  rows={3}
                  value={reply}
                  onChange={(e) => setReply(e.target.value)}
                  placeholder="Write a reply…"
                  className="w-full rounded-xl border border-slate-700 bg-slate-800/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none"
                />
                <div className="flex items-center justify-between">
                  <select value={nextStatus} onChange={(e) => setNextStatus(e.target.value)}
                    className="h-9 rounded-lg border border-slate-700 bg-slate-800 px-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                    {["pending", "solved", "closed", "open"].map((s) => <option key={s} value={s}>mark as {s}</option>)}
                  </select>
                  <button type="submit" disabled={busy || !reply.trim()}
                    className="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                    {busy ? "Sending…" : "Send reply"}
                  </button>
                </div>
              </form>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
