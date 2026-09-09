"use client";

import * as React from "react";
import { Megaphone, Send, Undo2 } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, Field, inputClass, Loading, ErrorState, Flash, EmptyState,
  PrimaryButton, GhostButton,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

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
      <PageHeader
        icon={<Megaphone className="h-5 w-5" />}
        title="Announcements"
        description="One active banner at a time, shown inside the farm dashboard."
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      <Panel title="Publish a banner" description="Appears at the top of every farm-dashboard page until taken down or replaced.">
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
          <div className="flex flex-col gap-3 sm:flex-row">
            <div className="flex-1">
              <Field label="Optional link" hint="Path like /subscription or a full URL">
                <input value={link} onChange={(e) => setLink(e.target.value)} placeholder="/whatsapp" className={inputClass} />
              </Field>
            </div>
            <div className="flex items-end">
              <PrimaryButton type="submit" disabled={busy}>
                <Send className="h-3.5 w-3.5" /> {busy ? "Publishing…" : "Publish"}
              </PrimaryButton>
            </div>
          </div>
        </form>
      </Panel>

      <Panel title="History" bodyClassName="p-0">
        {!rows ? (
          <Loading label="Loading announcements…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No announcements yet" hint="Publish the first banner above." icon={<Megaphone className="h-5 w-5" />} />
        ) : (
          <div className="divide-y divide-wangari-border">
            {rows.map((a) => (
              <div key={a.id} className="flex items-center justify-between gap-4 px-5 py-3.5">
                <div className="min-w-0">
                  <div className="truncate text-sm text-wangari-heading">{a.message}</div>
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
