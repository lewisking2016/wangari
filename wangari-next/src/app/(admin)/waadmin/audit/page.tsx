"use client";

import * as React from "react";
import { ShieldCheck, ChevronLeft, ChevronRight } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, FilterPill, Loading, ErrorState, EmptyState, GhostButton,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface AuditRow {
  id: number;
  userId: number | null;
  farmId: number | null;
  action: string;
  entityType: string | null;
  entityId: number | null;
  details: any;
  createdAt: string;
  user: { name: string; email: string } | null;
}

const FILTERS = ["all", "admin", "money"];

export default function AdminAuditPage() {
  const [rows, setRows] = React.useState<AuditRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [filter, setFilter] = React.useState("all");
  const [error, setError] = React.useState("");
  const pageSize = 30;

  React.useEffect(() => {
    adminApi.get<{ rows: AuditRow[]; total: number }>(`/audit?page=${page}`)
      .then((r) => { setRows(r.rows); setTotal(r.total); })
      .catch((e) => setError(e.message));
  }, [page]);

  const visible = React.useMemo(() => {
    if (!rows) return [];
    if (filter === "admin") return rows.filter((r) => r.action.startsWith("admin."));
    if (filter === "money") return rows.filter((r) => /transaction|payment|subscription|billing|extend|comp|promo/.test(r.action));
    return rows;
  }, [rows, filter]);

  const pages = Math.max(1, Math.ceil(total / pageSize));

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<ShieldCheck className="h-5 w-5" />}
        title="Audit Log"
        description="Immutable trail of admin actions and money-path mutations. Append-only."
      />

      {error && <ErrorState message={error} />}

      <Panel bodyClassName="p-0">
        <div className="border-b border-wangari-border px-5 py-3">
          <div className="flex flex-wrap items-center gap-1.5">
            {FILTERS.map((f) => (
              <FilterPill key={f} active={filter === f} onClick={() => setFilter(f)}>{f}</FilterPill>
            ))}
            <span className="ml-auto text-xs text-wangari-muted">{total} entries</span>
          </div>
        </div>

        {!rows ? (
          <Loading label="Loading audit trail…" />
        ) : visible.length === 0 ? (
          <EmptyState title="Nothing here" hint="No entries match this filter on this page." icon={<ShieldCheck className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={760}>
            <thead>
              <tr>
                <Th>When</Th>
                <Th>Actor</Th>
                <Th>Action</Th>
                <Th>Entity</Th>
                <Th>Details</Th>
              </tr>
            </thead>
            <tbody>
              {visible.map((r) => (
                <tr key={r.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td className="whitespace-nowrap text-xs text-wangari-muted">{new Date(r.createdAt).toLocaleString()}</Td>
                  <Td className="text-xs">
                    {r.details?._actor ? (
                      <Badge variant="default">{String(r.details._actor)}</Badge>
                    ) : r.user ? (
                      <span className="text-wangari-text">{r.user.name}</span>
                    ) : (
                      <span className="text-wangari-subtle">user #{r.userId ?? "?"}</span>
                    )}
                  </Td>
                  <Td>
                    <code className="rounded bg-wangari-cream px-1.5 py-0.5 font-mono text-[11px] font-medium text-wangari-heading">{r.action}</code>
                  </Td>
                  <Td className="text-xs text-wangari-muted">
                    {r.entityType
                      ? `${r.entityType}${r.details?._entityIdStr != null ? `#${r.details._entityIdStr}` : r.entityId != null ? `#${r.entityId}` : ""}`
                      : "—"}
                  </Td>
                  <Td className="max-w-[260px]">
                    <div className="truncate font-mono text-[11px] text-wangari-subtle" title={JSON.stringify(r.details)}>
                      {r.details ? JSON.stringify(r.details) : "—"}
                    </div>
                  </Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}

        {pages > 1 && (
          <div className="flex items-center justify-between border-t border-wangari-border px-5 py-3 text-sm text-wangari-muted">
            <span>Page {page} of {pages} · {total} entries</span>
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
    </div>
  );
}
