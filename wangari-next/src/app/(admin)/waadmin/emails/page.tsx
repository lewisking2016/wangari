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
  sent: "bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200",
  failed: "bg-badge-red-bg text-badge-red-text",
  queued: "bg-badge-yellow-bg text-badge-yellow-text",
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
          <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">Email Ops</h1>
          <p className="mt-1 text-sm text-wangari-muted">Every transactional send is logged here — receipts, ticket replies, one-offs.</p>
        </div>
        <button onClick={() => setCompose(!compose)} className="rounded-lg bg-wangari-green-800 px-4 py-2 text-sm font-semibold text-wangari-heading hover:bg-wangari-green-900">
          {compose ? "Close" : "Compose"}
        </button>
      </div>

      {allFailed && (
        <div className="rounded-xl border border-amber-200 bg-badge-yellow-bg px-4 py-3 text-sm font-medium text-badge-yellow-text">
          Every send is failing with "RESEND_API_KEY not configured" — add <code className="rounded bg-black/30 px-1">RESEND_API_KEY</code> to the server .env (free account at resend.com, verify your domain) and restart the API. Nothing is lost: failed sends stay in this log and can be re-sent.
        </div>
      )}
      {flash && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash}</div>}
      {error && <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>}

      {compose && (
        <form onSubmit={sendOne} className="space-y-3 rounded-xl border border-wangari-border bg-white p-5">
          <div className="grid gap-3 sm:grid-cols-2">
            <input required type="email" value={form.to} onChange={(e) => setForm({ ...form, to: e.target.value })} placeholder="To (email)"
              className="h-10 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
            <input required value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} placeholder="Subject"
              className="h-10 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
          </div>
          <textarea required rows={4} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} placeholder="Message"
            className="w-full rounded-xl border border-wangari-border bg-white px-4 py-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
          <button type="submit" className="rounded-lg bg-wangari-green-800 px-4 py-2 text-sm font-semibold text-wangari-heading hover:bg-wangari-green-900">Send</button>
        </form>
      )}

      <div className="flex gap-2">
        {["all", "sent", "failed"].map((s) => (
          <button key={s} onClick={() => setStatus(s)}
            className={`rounded-lg px-3 py-1.5 text-xs font-medium ${status === s ? "bg-wangari-green-100 text-wangari-green-800" : "text-wangari-muted hover:bg-wangari-cream"}`}>
            {s}{s === "failed" && failed > 0 ? ` (${failed})` : ""}
          </button>
        ))}
      </div>

      {!rows ? (
        <div className="animate-pulse text-sm text-wangari-muted">Loading email log…</div>
      ) : rows.length === 0 ? (
        <div className="rounded-xl border border-wangari-border bg-white px-4 py-8 text-center text-sm text-wangari-subtle">No emails yet — receipts and ticket replies will appear here.</div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-wangari-border">
          <table className="w-full text-sm">
            <thead className="bg-wangari-green-50/60 text-left text-[11px] font-bold uppercase tracking-wider text-wangari-muted">
              <tr>
                <th className="px-4 py-3">When</th>
                <th className="px-4 py-3">To</th>
                <th className="px-4 py-3">Subject</th>
                <th className="px-4 py-3">Template</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-wangari-border bg-white">
              {rows.map((r) => (
                <tr key={r.id}>
                  <td className="whitespace-nowrap px-4 py-3 text-xs text-wangari-muted">{new Date(r.createdAt).toLocaleString()}</td>
                  <td className="px-4 py-3 text-wangari-heading">{r.to}</td>
                  <td className="max-w-xs truncate px-4 py-3 text-wangari-text" title={r.subject}>{r.subject}</td>
                  <td className="px-4 py-3 text-xs text-wangari-subtle">{r.template || "—"}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${STATUS_STYLE[r.status] || ""}`}>{r.status}</span>
                    {r.error && <div className="mt-0.5 max-w-[200px] truncate text-[10px] text-red-500" title={r.error}>{r.error}</div>}
                  </td>
                  <td className="px-4 py-3 text-right">
                    {r.status === "failed" && (
                      <button onClick={() => resend(r.id)} className="rounded-lg px-2.5 py-1 text-xs text-badge-yellow-text hover:bg-badge-yellow-bg">Resend</button>
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
