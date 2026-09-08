"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface EmailRow {
  id: number;
  to: string;
  subject: string;
  template: string | null;
  status: string;
  provider: string | null;
  error: string | null;
  createdAt: string;
}

const STATUS_STYLE: Record<string, string> = {
  sent: "bg-emerald-500/15 text-emerald-400",
  failed: "bg-red-500/15 text-red-400",
  queued: "bg-amber-500/15 text-amber-400",
};

export default function AdminEmailsPage() {
  const [rows, setRows] = React.useState<EmailRow[] | null>(null);
  const [failed, setFailed] = React.useState(0);
  const [status, setStatus] = React.useState("all");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const [compose, setCompose] = React.useState(false);
  const [form, setForm] = React.useState({ to: "", subject: "", body: "" });

  const load = React.useCallback(() => {
    adminApi.get<{ rows: EmailRow[]; failed: number }>(`/emails?status=${status}`)
      .then((d) => { setRows(d.rows); setFailed(d.failed); })
      .catch((e) => setError(e.message));
  }, [status]);
  React.useEffect(load, [load]);

  async function resend(id: number) {
    try {
      await adminApi.post(`/emails/${id}/resend`);
      setFlash("Re-sent.");
      setTimeout(() => setFlash(""), 3000);
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  async function sendOne(e: React.FormEvent) {
    e.preventDefault();
    try {
      await adminApi.post("/emails/send", form);
      setForm({ to: "", subject: "", body: "" });
      setCompose(false);
      setFlash("Email sent.");
      setTimeout(() => setFlash(""), 3000);
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  const allFailed = rows && rows.length > 0 && rows.every((r) => r.status === "failed") && rows.some((r) => r.error?.includes("not configured"));

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-white">Email Ops</h1>
          <p className="mt-1 text-sm text-slate-400">Every transactional send is logged here — receipts, ticket replies, one-offs.</p>
        </div>
        <button onClick={() => setCompose(!compose)} className="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400">
          {compose ? "Close" : "Compose"}
        </button>
      </div>

      {allFailed && (
        <div className="rounded-xl border border-amber-900/60 bg-amber-950/30 px-4 py-3 text-sm text-amber-300">
          Every send is failing with "RESEND_API_KEY not configured" — add <code className="rounded bg-black/30 px-1">RESEND_API_KEY</code> to the server .env (free account at resend.com, verify your domain) and restart the API. Nothing is lost: failed sends stay in this log and can be re-sent.
        </div>
      )}
      {flash && <div className="rounded-xl border border-emerald-900/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300">{flash}</div>}
      {error && <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      {compose && (
        <form onSubmit={sendOne} className="space-y-3 rounded-xl border border-slate-800 bg-slate-900/60 p-5">
          <div className="grid gap-3 sm:grid-cols-2">
            <input required type="email" value={form.to} onChange={(e) => setForm({ ...form, to: e.target.value })} placeholder="To (email)"
              className="h-10 rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:outline-none" />
            <input required value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} placeholder="Subject"
              className="h-10 rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:outline-none" />
          </div>
          <textarea required rows={4} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} placeholder="Message"
            className="w-full rounded-xl border border-slate-700 bg-slate-800/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none" />
          <button type="submit" className="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400">Send</button>
        </form>
      )}

      <div className="flex gap-2">
        {["all", "sent", "failed"].map((s) => (
          <button key={s} onClick={() => setStatus(s)}
            className={`rounded-lg px-3 py-1.5 text-xs font-medium ${status === s ? "bg-emerald-500/20 text-emerald-300" : "text-slate-400 hover:bg-slate-800"}`}>
            {s}{s === "failed" && failed > 0 ? ` (${failed})` : ""}
          </button>
        ))}
      </div>

      {!rows ? (
        <div className="animate-pulse text-sm text-slate-400">Loading email log…</div>
      ) : rows.length === 0 ? (
        <div className="rounded-xl border border-slate-800 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-500">No emails yet — receipts and ticket replies will appear here.</div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-slate-800">
          <table className="w-full text-sm">
            <thead className="bg-slate-900/80 text-left text-[11px] uppercase tracking-wider text-slate-500">
              <tr>
                <th className="px-4 py-3">When</th>
                <th className="px-4 py-3">To</th>
                <th className="px-4 py-3">Subject</th>
                <th className="px-4 py-3">Template</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800 bg-slate-900/40">
              {rows.map((r) => (
                <tr key={r.id}>
                  <td className="whitespace-nowrap px-4 py-3 text-xs text-slate-400">{new Date(r.createdAt).toLocaleString()}</td>
                  <td className="px-4 py-3 text-slate-200">{r.to}</td>
                  <td className="max-w-xs truncate px-4 py-3 text-slate-300" title={r.subject}>{r.subject}</td>
                  <td className="px-4 py-3 text-xs text-slate-500">{r.template || "—"}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${STATUS_STYLE[r.status] || ""}`}>{r.status}</span>
                    {r.error && <div className="mt-0.5 max-w-[200px] truncate text-[10px] text-red-400/70" title={r.error}>{r.error}</div>}
                  </td>
                  <td className="px-4 py-3 text-right">
                    {r.status === "failed" && (
                      <button onClick={() => resend(r.id)} className="rounded-lg px-2.5 py-1 text-xs text-amber-400 hover:bg-amber-950/40">Resend</button>
                    )}
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
