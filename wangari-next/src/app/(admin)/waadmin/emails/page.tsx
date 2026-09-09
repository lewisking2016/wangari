"use client";

import * as React from "react";
import { Mail, Send, RotateCcw, PenLine } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, FilterPill, Field, inputClass, Loading, ErrorState,
  Flash, EmptyState, PrimaryButton, GhostButton, Modal,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

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

const STATUS_VARIANT: Record<string, "success" | "danger" | "warning"> = {
  sent: "success",
  failed: "danger",
  queued: "warning",
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
      <PageHeader
        icon={<Mail className="h-5 w-5" />}
        title="Email Ops"
        description="Every transactional send is logged here — receipts, ticket replies, one-offs."
        actions={
          <PrimaryButton onClick={() => setCompose(true)}>
            <PenLine className="h-4 w-4" /> Compose
          </PrimaryButton>
        }
      />

      {allFailed && (
        <div className="rounded-xl border border-amber-200 bg-badge-yellow-bg px-4 py-3 text-sm font-medium text-badge-yellow-text">
          Every send is failing with &quot;not configured&quot; — add <code className="rounded bg-black/10 px-1">SMTP_HOST / RESEND_API_KEY</code> to the server .env and restart the API. Nothing is lost: failed sends stay in this log and can be re-sent.
        </div>
      )}
      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      <Panel bodyClassName="p-0">
        <div className="border-b border-wangari-border px-5 py-3">
          <div className="flex flex-wrap items-center gap-1.5">
            {["all", "sent", "failed"].map((s) => (
              <FilterPill key={s} active={status === s} onClick={() => setStatus(s)}>
                {s}{s === "failed" && failed > 0 ? ` (${failed})` : ""}
              </FilterPill>
            ))}
          </div>
        </div>

        {!rows ? (
          <Loading label="Loading email log…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No emails yet" hint="Receipts, ticket replies, and one-offs will appear here." icon={<Mail className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={760}>
            <thead>
              <tr>
                <Th>When</Th>
                <Th>To</Th>
                <Th>Subject</Th>
                <Th>Template</Th>
                <Th>Status</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td className="whitespace-nowrap text-xs text-wangari-muted">{new Date(r.createdAt).toLocaleString()}</Td>
                  <Td className="font-medium text-wangari-heading">{r.to}</Td>
                  <Td className="max-w-[240px]"><div className="truncate" title={r.subject}>{r.subject}</div></Td>
                  <Td className="text-xs text-wangari-subtle">{r.template || "—"}</Td>
                  <Td>
                    <Badge variant={STATUS_VARIANT[r.status] || "outline"}>{r.status}</Badge>
                    {r.error && (
                      <div className="mt-1 max-w-[200px] truncate text-[10px] text-badge-red-text" title={r.error}>{r.error}</div>
                    )}
                  </Td>
                  <Td className="text-right">
                    {r.status === "failed" && (
                      <GhostButton onClick={() => resend(r.id)} className="h-7 px-2 text-xs">
                        <RotateCcw className="h-3 w-3" /> Resend
                      </GhostButton>
                    )}
                  </Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}
      </Panel>

      <Modal title="Compose email" onClose={() => setCompose(false)} width="max-w-lg">
        <form onSubmit={sendOne} className="space-y-3">
          <Field label="To">
            <input required type="email" value={form.to} onChange={(e) => setForm({ ...form, to: e.target.value })} className={inputClass} />
          </Field>
          <Field label="Subject">
            <input required value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} className={inputClass} />
          </Field>
          <Field label="Message">
            <textarea
              required
              rows={5}
              value={form.body}
              onChange={(e) => setForm({ ...form, body: e.target.value })}
              className="w-full rounded-lg border border-wangari-border bg-white px-3 py-2 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20"
            />
          </Field>
          <div className="flex justify-end gap-2 pt-1">
            <GhostButton onClick={() => setCompose(false)}>Cancel</GhostButton>
            <PrimaryButton type="submit">
              <Send className="h-3.5 w-3.5" /> Send
            </PrimaryButton>
          </div>
        </form>
      </Modal>
    </div>
  );
}
