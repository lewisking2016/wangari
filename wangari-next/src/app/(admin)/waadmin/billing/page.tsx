"use client";

import * as React from "react";
import { CreditCard, Search, ChevronLeft, ChevronRight, Ban } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, Toolbar, SearchInput, FilterPill, Loading, ErrorState,
  Flash, EmptyState, GhostButton,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface SubRow {
  id: number;
  userId: number;
  plan: string;
  planName: string;
  amount: string;
  status: string;
  reference: string | null;
  startsAt: string;
  expiresAt: string;
  user: { id: number; name: string; email: string } | null;
}

const STATUSES = ["all", "active", "cancelled", "expired"];

export default function AdminBillingPage() {
  const [rows, setRows] = React.useState<SubRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [status, setStatus] = React.useState("all");
  const [q, setQ] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const pageSize = 20;

  const load = React.useCallback(async () => {
    try {
      const res = await adminApi.get<{ rows: SubRow[]; total: number }>(
        `/billing?status=${encodeURIComponent(status)}&q=${encodeURIComponent(q)}&page=${page}`
      );
      setRows(res.rows);
      setTotal(res.total);
    } catch (e: any) {
      setError(e.message);
    }
  }, [status, q, page]);
  React.useEffect(() => { load(); }, [load]);

  async function cancel(id: number) {
    if (!confirm("Cancel this subscription? The farm loses paid access immediately.")) return;
    try {
      await adminApi.post(`/billing/${id}/cancel`);
      setFlash(`Subscription #${id} cancelled.`);
      setTimeout(() => setFlash(""), 4000);
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  const pages = Math.max(1, Math.ceil(total / pageSize));

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<CreditCard className="h-5 w-5" />}
        title="Billing & Payments"
        description="All subscriptions across the platform with Paystack references."
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      <Panel bodyClassName="p-0">
        <div className="border-b border-wangari-border px-5 py-3">
          <Toolbar>
            <div className="relative">
              <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-wangari-subtle" />
              <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1); }} placeholder="Search owner name or email…" className="pl-8" />
            </div>
            <div className="flex flex-wrap gap-1.5">
              {STATUSES.map((s) => (
                <FilterPill key={s} active={status === s} onClick={() => { setStatus(s); setPage(1); }}>
                  {s}
                </FilterPill>
              ))}
            </div>
            <span className="ml-auto text-xs text-wangari-muted">{total} total</span>
          </Toolbar>
        </div>

        {!rows ? (
          <Loading label="Loading subscriptions…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No subscriptions match" hint="Adjust the search or status filter." icon={<CreditCard className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={860}>
            <thead>
              <tr>
                <Th>Customer</Th>
                <Th>Plan</Th>
                <Th>Amount</Th>
                <Th>Reference</Th>
                <Th>Period</Th>
                <Th>Status</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td>
                    <div className="font-medium text-wangari-heading">{r.user?.name || `User #${r.userId}`}</div>
                    <div className="text-xs text-wangari-subtle">{r.user?.email}</div>
                  </Td>
                  <Td>{r.planName}</Td>
                  <Td className="font-semibold text-wangari-heading">KES {Number(r.amount).toLocaleString()}</Td>
                  <Td className="font-mono text-xs text-wangari-subtle">{r.reference || "—"}</Td>
                  <Td className="whitespace-nowrap text-xs text-wangari-muted">
                    {new Date(r.startsAt).toLocaleDateString()} → {new Date(r.expiresAt).toLocaleDateString()}
                  </Td>
                  <Td>
                    {r.status === "active" ? (
                      <Badge variant="success">active</Badge>
                    ) : r.status === "cancelled" ? (
                      <Badge variant="danger">cancelled</Badge>
                    ) : (
                      <Badge variant="outline">{r.status}</Badge>
                    )}
                  </Td>
                  <Td className="text-right">
                    {r.status === "active" && (
                      <GhostButton
                        onClick={() => cancel(r.id)}
                        className="h-7 px-2 text-xs text-badge-red-text hover:bg-badge-red-bg hover:text-badge-red-text"
                      >
                        <Ban className="h-3 w-3" /> Cancel
                      </GhostButton>
                    )}
                  </Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}

        {pages > 1 && (
          <div className="flex items-center justify-between border-t border-wangari-border px-5 py-3 text-sm text-wangari-muted">
            <span>Page {page} of {pages} · {total} total</span>
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
