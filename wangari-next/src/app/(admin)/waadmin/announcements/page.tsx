"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface Announcement {
  id: number;
  message: string;
  link: string | null;
  active: boolean;
  createdAt: string;
}

export default function AdminAnnouncementsPage() {
  const [rows, setRows] = React.useState<Announcement[] | null>(null);
  const [message, setMessage] = React.useState("");
  const [link, setLink] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const [busy, setBusy] = React.useState(false);

  const load = React.useCallback(() => {
    adminApi.get<Announcement[]>("/announcements").then(setRows).catch((e) => setError(e.message));
  }, []);
  React.useEffect(load, [load]);

  async function publish(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      await adminApi.post("/announcements", { message, link: link || null });
      setMessage("");
      setLink("");
      setFlash("Announcement published — it retires any previous banner.");
      setTimeout(() => setFlash(""), 4000);
      load();
    } catch (e: any) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  async function deactivate(id: number) {
    try {
      await adminApi.post(`/announcements/${id}/deactivate`);
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-white">Announcements</h1>
        <p className="mt-1 text-sm text-slate-400">One active banner at a time, shown inside the farm dashboard.</p>
      </div>

      {flash && <div className="rounded-xl border border-emerald-900/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300">{flash}</div>}
      {error && <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

      <form onSubmit={publish} className="space-y-3 rounded-xl border border-slate-800 bg-slate-900/60 p-5">
        <div>
          <label className="mb-1 block text-[11px] text-slate-400">Banner message *</label>
          <textarea required rows={2} value={message} onChange={(e) => setMessage(e.target.value)}
            placeholder="e.g. New: daily egg production reports — try it from your dashboard!"
            className="w-full rounded-xl border border-slate-700 bg-slate-800/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none" />
        </div>
        <div className="flex flex-col gap-3 sm:flex-row">
          <div className="flex-1">
            <label className="mb-1 block text-[11px] text-slate-400">Optional link</label>
            <input value={link} onChange={(e) => setLink(e.target.value)} placeholder="/whatsapp"
              className="h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500 focus:outline-none" />
          </div>
          <div className="flex items-end">
            <button type="submit" disabled={busy} className="h-10 rounded-lg bg-emerald-500 px-6 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
              {busy ? "Publishing…" : "Publish"}
            </button>
          </div>
        </div>
      </form>

      {!rows ? (
        <div className="animate-pulse text-sm text-slate-400">Loading…</div>
      ) : rows.length === 0 ? (
        <div className="rounded-xl border border-slate-800 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-500">No announcements yet.</div>
      ) : (
        <div className="space-y-2">
          {rows.map((a) => (
            <div key={a.id} className={`flex items-center justify-between gap-4 rounded-xl border p-4 ${a.active ? "border-emerald-800 bg-emerald-950/20" : "border-slate-800 bg-slate-900/40"}`}>
              <div className="min-w-0">
                <div className="truncate text-sm text-slate-100">{a.message}</div>
                <div className="text-[11px] text-slate-500">
                  {new Date(a.createdAt).toLocaleString()}{a.link ? ` · links to ${a.link}` : ""}
                </div>
              </div>
              {a.active && (
                <button onClick={() => deactivate(a.id)} className="shrink-0 rounded-lg px-2.5 py-1 text-xs text-amber-400 hover:bg-amber-950/40">
                  Take down
                </button>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
