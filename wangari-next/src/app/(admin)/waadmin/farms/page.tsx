"use client";

import * as React from "react";
import { Building2, Search, ChevronLeft, ChevronRight, CalendarPlus } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, Toolbar, SearchInput, Loading, ErrorState, Flash,
  EmptyState, Modal, Field, inputClass, PrimaryButton, GhostButton,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface FarmRow {
  id: number;
  name: string;
  code: string | null;
  location: string | null;
  county: string | null;
  owner: { id: number; name: string; email: string } | null;
  workers: number;
  flocks: number;
  plan: { name: string; status: string; expiresAt: string } | null;
  createdAt: string;
}

export default function AdminFarmsPage() {
  const [rows, setRows] = React.useState<FarmRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [q, setQ] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const [extending, setExtending] = React.useState<FarmRow | null>(null);
  const [days, setDays] = React.useState("30");
  const [busy, setBusy] = React.useState(false);
  const pageSize = 20;

  const load = React.useCallback(async () => {
    try {
      const res = await adminApi.get<{ rows: FarmRow[]; total: number }>(
        `/farms?q=${encodeURIComponent(q)}&page=${page}`
      );
      setRows(res.rows);
      setTotal(res.total);
    } catch (e: any) {
      setError(e.message);
    }
  }, [q, page]);
  React.useEffect(() => { load(); }, [load]);

  async function doExtend() {
    if (!extending) return;
    setBusy(true);
    setError("");
    try {
      await adminApi.post(`/farms/${extending.id}/extend`, { days: Number(days) });
      setFlash(`"${extending.name}" subscription extended by ${days} days.`);
      setTimeout(() => setFlash(""), 4000);
      setExtending(null);
      load();
    } catch (e: any) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  const pages = Math.max(1, Math.ceil(total / pageSize));

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<Building2 className="h-5 w-5" />}
        title="Farms"
        description="All tenant farms with owner, plan state, and workforce size."
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      <Panel bodyClassName="p-0">
        <div className="border-b border-wangari-border px-5 py-3">
          <Toolbar>
            <div className="relative">
              <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-wangari-subtle" />
              <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1); }} placeholder="Search farm name or code…" className="pl-8" />
            </div>
            <span className="ml-auto text-xs text-wangari-muted">{total} farms</span>
          </Toolbar>
        </div>

        {!rows ? (
          <Loading label="Loading farms…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No farms match" hint="Try a different search term." icon={<Building2 className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={760}>
            <thead>
              <tr>
                <Th>Farm</Th>
                <Th>Owner</Th>
                <Th>Workforce</Th>
                <Th>Plan</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {rows.map((f) => (
                <tr key={f.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td>
                    <div className="font-medium text-wangari-heading">{f.name}</div>
                    <div className="text-xs text-wangari-subtle">
                      {f.code || "no code"}{f.county ? ` · ${f.county}` : ""}
                    </div>
                  </Td>
                  <Td>
                    <div className="text-wangari-text">{f.owner?.name || "—"}</div>
                    <div className="text-xs text-wangari-subtle">{f.owner?.email}</div>
                  </Td>
                  <Td>
                    <span className="text-wangari-text">{f.workers} workers</span>
                    <span className="text-wangari-subtle"> · {f.flocks} flocks</span>
                  </Td>
                  <Td>
                    {f.plan ? (
                      <div>
                        <Badge variant="default">{f.plan.name}</Badge>
                        <div className="mt-1 text-[11px] text-wangari-subtle">till {new Date(f.plan.expiresAt).toLocaleDateString()}</div>
                      </div>
                    ) : (
                      <Badge variant="outline">trial / none</Badge>
                    )}
                  </Td>
                  <Td className="text-right">
                    <GhostButton onClick={() => { setExtending(f); setDays("30"); }} className="h-8 px-2.5 text-xs">
                      <CalendarPlus className="h-3.5 w-3.5" /> Extend
                    </GhostButton>
                  </Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}

        {pages > 1 && (
          <div className="flex items-center justify-between border-t border-wangari-border px-5 py-3 text-sm text-wangari-muted">
            <span>Page {page} of {pages} · {total} farms</span>
            <div className="flex gap-2">
              <GhostButton onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="h-8 px-2.5 text-xs">
                <ChevronLeft className="h-3.5 w-3.5" /> Prev
              </GhostButton>
              <GhostButton onClick={() => setPage((p) => Math.min(pages, p + 1))} disabled={page >= pages} className="h-8 px-2.5 text-xs">
                Next <ChevronRight className="h-3.5 w-3.5" />
              </GhostButton>
            </div>
          </div>
        )}
      </Panel>

      <Modal title={`Extend subscription — ${extending?.name ?? ""}`} onClose={() => setExtending(null)}>
        {extending && (
          <div className="space-y-4">
            <Field label="Days to extend" hint="Extends from the current expiry date. Logged to the audit trail.">
              <input type="number" min={1} value={days} onChange={(e) => setDays(e.target.value)} className={inputClass} />
            </Field>
            <div className="flex justify-end gap-2">
              <GhostButton onClick={() => setExtending(null)}>Cancel</GhostButton>
              <PrimaryButton onClick={doExtend} disabled={busy || !(Number(days) > 0)}>
                {busy ? "Extending…" : "Extend subscription"}
              </PrimaryButton>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
