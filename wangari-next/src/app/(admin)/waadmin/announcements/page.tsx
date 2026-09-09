"use client";

import * as React from "react";
import {
  Megaphone, Send, Undo2, ExternalLink,
  Globe, Building2, Users, Clock, Radio,
} from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, Field, inputClass, Loading, ErrorState, Flash, EmptyState,
  PrimaryButton, GhostButton, StatCard,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface Announcement {
  id: number;
  message: string;
  link: string | null;
  active: boolean;
  createdAt: string;
}

interface AnnSummary {
  total: number;
  live: number;
  liveMessage: string | null;
  farmReach: number;
  ownerReach: number;
  lastPublishedAt: string | null;
  retiredLast7d: number;
}

export default function AdminAnnouncementsPage() {
  const [rows, setRows] = React.useState<Announcement[] | null>(null);
  const [summary, setSummary] = React.useState<AnnSummary | null>(null);
  const [message, setMessage] = React.useState("");
  const [link, setLink] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const [busy, setBusy] = React.useState(false);

  const load = React.useCallback(() => {
    adminApi.get<{ rows: Announcement[]; summary: AnnSummary }>("/announcements")
      .then((d) => { setRows(d.rows); setSummary(d.summary); })
      .catch((e) => setError(e.message));
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
      setFlash("Announcement published — it's live in every farm dashboard and retires the previous banner.");
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

  const isExternal = (l: string) => l.startsWith("http://") || l.startsWith("https://");

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<Megaphone className="h-5 w-5" />}
        title="Announcements"
        description="One live banner at a time, shown at the top of every farm dashboard until taken down or replaced."
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      {/* Reach stats */}
      {summary && (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <StatCard
            label="Live banner"
            value={summary.live ? "1 live" : "none"}
            icon={<Radio className="h-5 w-5" />}
            accent={summary.live ? "green" : "slate"}
            hint={summary.liveMessage ? `"${summary.liveMessage.slice(0, 40)}${summary.liveMessage.length > 40 ? "…" : ""}"` : "publish one below"}
          />
          <StatCard label="Farm reach" value={summary.farmReach} icon={<Building2 className="h-5 w-5" />} accent="blue" hint="dashboards showing it" />
          <StatCard label="Owner reach" value={summary.ownerReach} icon={<Users className="h-5 w-5" />} accent="violet" hint="accounts that see it" />
          <StatCard
            label="Last published"
            value={summary.lastPublishedAt ? new Date(summary.lastPublishedAt).toLocaleDateString() : "—"}
            icon={<Clock className="h-5 w-5" />}
            accent="slate"
            hint={summary.retiredLast7d > 0 ? `${summary.retiredLast7d} retired this week` : undefined}
          />
        </div>
      )}

      {/* Live banner status */}
      {summary?.live && summary.liveMessage && (
        <Panel className="border-wangari-green-300 bg-wangari-green-50/50">
          <div className="flex items-start justify-between gap-3">
            <div className="flex min-w-0 items-start gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-wangari-green-100 text-wangari-green-700">
                <Radio className="h-5 w-5" />
              </div>
              <div className="min-w-0">
                <div className="flex items-center gap-2">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-wangari-green-700">Live now</span>
                  <Badge variant="success">visible to all farms</Badge>
                </div>
                <div className="mt-1 text-sm font-medium text-wangari-heading">{summary.liveMessage}</div>
              </div>
            </div>
            {rows?.find((a) => a.active) && (
              <GhostButton onClick={() => deactivate(rows.find((a) => a.active)!.id)} className="shrink-0 text-xs">
                <Undo2 className="h-3 w-3" /> Take down
              </GhostButton>
            )}
          </div>
        </Panel>
      )}

      {/* Compose with live customer-facing preview */}
      <Panel title="Publish a banner" description="Replaces any live banner the moment you publish.">
        <form onSubmit={publish} className="space-y-4">
          <Field label="Banner message">
            <textarea
              required
              rows={2}
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              placeholder="e.g. New: daily egg production reports — try it from your dashboard!"
              className="w-full rounded-lg border border-wangari-border bg-white px-3 py-2 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20"
            />
          </Field>
          <Field label="Optional link" hint="Path like /subscription or a full URL — shown as a 'Learn more' action">
            <input value={link} onChange={(e) => setLink(e.target.value)} placeholder="/whatsapp" className={inputClass} />
          </Field>

          {/* Exact customer-facing preview */}
          {(message.trim() || link.trim()) && (
            <div>
              <div className="mb-1.5 text-[11px] font-bold uppercase tracking-wider text-wangari-subtle">Preview — exactly what farmers will see</div>
              <div className="flex items-center gap-2.5 rounded-xl border border-wangari-green-200 bg-wangari-green-50 px-4 py-3">
                <Megaphone className="h-4 w-4 shrink-0 text-wangari-green-700" />
                <span className="min-w-0 flex-1 truncate text-sm font-medium text-wangari-green-900">
                  {message.trim() || "Your banner message…"}
                </span>
                {link.trim() && (
                  <span className="flex shrink-0 items-center gap-1 text-xs font-semibold text-wangari-green-700">
                    Learn more
                    {isExternal(link.trim()) ? <ExternalLink className="h-3 w-3" /> : "→"}
                  </span>
                )}
              </div>
            </div>
          )}

          <div className="flex justify-end">
            <PrimaryButton type="submit" disabled={busy || !message.trim()}>
              <Send className="h-3.5 w-3.5" /> {busy ? "Publishing…" : "Publish to all farms"}
            </PrimaryButton>
          </div>
        </form>
      </Panel>

      {/* History */}
      <Panel title="History" description="Newest first — publishing retires the previous banner" bodyClassName="p-0">
        {!rows ? (
          <Loading label="Loading announcements…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No announcements yet" hint="Publish the first banner above." icon={<Megaphone className="h-5 w-5" />} />
        ) : (
          <div className="divide-y divide-wangari-border">
            {rows.map((a) => (
              <div key={a.id} className="flex items-center justify-between gap-4 px-5 py-3.5">
                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="truncate text-sm text-wangari-heading">{a.message}</span>
                    {a.link && (
                      <a
                        href={isExternal(a.link) ? a.link : undefined}
                        className="flex shrink-0 items-center gap-0.5 text-[11px] font-medium text-wangari-green-700 hover:text-wangari-green-800"
                        title={a.link}
                      >
                        {isExternal(a.link) && <ExternalLink className="h-3 w-3" />}
                      </a>
                    )}
                  </div>
                  <div className="text-[11px] text-wangari-subtle">
                    {new Date(a.createdAt).toLocaleString()}{a.link ? ` · links to ${a.link}` : ""}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  {a.active ? (
                    <>
                      <Badge variant="success">live</Badge>
                      <GhostButton onClick={() => deactivate(a.id)} className="h-7 px-2 text-xs">
                        <Undo2 className="h-3 w-3" /> Take down
                      </GhostButton>
                    </>
                  ) : (
                    <Badge variant="outline">retired</Badge>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </Panel>
    </div>
  );
}
