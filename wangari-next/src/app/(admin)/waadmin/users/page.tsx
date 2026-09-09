"use client";

import * as React from "react";
import { Users, Search, ChevronLeft, ChevronRight, LogOut, MailCheck } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import {
  PageHeader, Panel, TableShell, Th, Td, Toolbar, SearchInput, Loading, ErrorState, Flash,
  EmptyState, GhostButton,
} from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface UserRow {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: string;
  emailVerified: string | null;
  createdAt: string;
  ownedFarms: { id: number; name: string }[];
}

export default function AdminUsersPage() {
  const [rows, setRows] = React.useState<UserRow[] | null>(null);
  const [total, setTotal] = React.useState(0);
  const [page, setPage] = React.useState(1);
  const [q, setQ] = React.useState("");
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const pageSize = 20;

  const load = React.useCallback(async () => {
    try {
      const res = await adminApi.get<{ rows: UserRow[]; total: number }>(
        `/users?q=${encodeURIComponent(q)}&page=${page}`
      );
      setRows(res.rows);
      setTotal(res.total);
    } catch (e: any) {
      setError(e.message);
    }
  }, [q, page]);
  React.useEffect(() => { load(); }, [load]);

  async function act(id: number, action: "force-logout" | "verify-email", confirmMsg?: string) {
    if (confirmMsg && !confirm(confirmMsg)) return;
    try {
      await adminApi.post(`/users/${id}/${action}`);
      setFlash(action === "force-logout" ? "User signed out of all devices." : "Email marked verified.");
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
        icon={<Users className="h-5 w-5" />}
        title="Users"
        description="All accounts with controlled support actions — every action audited."
      />

      {flash && <Flash message={flash} />}
      {error && <ErrorState message={error} />}

      <Panel bodyClassName="p-0">
        <div className="border-b border-wangari-border px-5 py-3">
          <Toolbar>
            <div className="relative">
              <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-wangari-subtle" />
              <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1); }} placeholder="Search name, email, or phone…" className="pl-8" />
            </div>
            <span className="ml-auto text-xs text-wangari-muted">{total} users</span>
          </Toolbar>
        </div>

        {!rows ? (
          <Loading label="Loading users…" />
        ) : rows.length === 0 ? (
          <EmptyState title="No users match" hint="Try a different search term." icon={<Users className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={720}>
            <thead>
              <tr>
                <Th>User</Th>
                <Th>Farms</Th>
                <Th>Verified</Th>
                <Th>Joined</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {rows.map((u) => (
                <tr key={u.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td>
                    <div className="flex items-center gap-2">
                      <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-wangari-green-100 text-[11px] font-bold text-wangari-green-800">
                        {u.name.slice(0, 2).toUpperCase()}
                      </div>
                      <div className="min-w-0">
                        <div className="flex items-center gap-1.5">
                          <span className="truncate font-medium text-wangari-heading">{u.name}</span>
                          {u.role !== "farm_owner" && <Badge variant="info">{u.role.replace("_", " ")}</Badge>}
                        </div>
                        <div className="truncate text-xs text-wangari-subtle">{u.email}{u.phone ? ` · ${u.phone}` : ""}</div>
                      </div>
                    </div>
                  </Td>
                  <Td className="text-xs text-wangari-muted">
                    {u.ownedFarms.length ? u.ownedFarms.map((f) => f.name).join(", ") : "—"}
                  </Td>
                  <Td>
                    {u.emailVerified ? (
                      <Badge variant="success">verified</Badge>
                    ) : (
                      <Badge variant="warning">unverified</Badge>
                    )}
                  </Td>
                  <Td className="whitespace-nowrap text-xs text-wangari-muted">{new Date(u.createdAt).toLocaleDateString()}</Td>
                  <Td className="text-right">
                    <div className="inline-flex gap-1.5">
                      <GhostButton
                        onClick={() => act(u.id, "force-logout", `Force-logout ${u.email} from all devices?`)}
                        className="h-7 px-2 text-xs text-badge-yellow-text hover:bg-badge-yellow-bg hover:text-badge-yellow-text"
                      >
                        <LogOut className="h-3 w-3" /> Force logout
                      </GhostButton>
                      {!u.emailVerified && (
                        <GhostButton onClick={() => act(u.id, "verify-email")} className="h-7 px-2 text-xs">
                          <MailCheck className="h-3 w-3" /> Verify email
                        </GhostButton>
                      )}
                    </div>
                  </Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}

        {pages > 1 && (
          <div className="flex items-center justify-between border-t border-wangari-border px-5 py-3 text-sm text-wangari-muted">
            <span>Page {page} of {pages} · {total} users</span>
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
