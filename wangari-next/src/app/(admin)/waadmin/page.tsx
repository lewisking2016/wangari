"use client";

import * as React from "react";
import Link from "next/link";
import {
  Landmark, Building2, Users, Sprout, Wallet, TicketCheck, ArrowRight, UserPlus, ReceiptText,
} from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import { PageHeader, Panel, StatCard, TableShell, Th, Td, Loading, ErrorState, EmptyState } from "@/components/admin/ui";
import { TrendAreaChart, DailyBarChart, DonutChart } from "@/components/admin/charts";

interface Overview {
  totals: { farms: number; users: number; workers: number; activeSubscriptions: number; mrrKes: number; openTickets: number };
  byPlan: Record<string, { count: number; revenue: number }>;
  signups: { date: string; count: number }[];
  revenueTrend: { date: string; revenue: number }[];
  recentUsers: { id: number; name: string; email: string; createdAt: string }[];
  recentPayments: { id: number; planName: string; amount: string; startsAt: string; reference: string | null; user: { name: string; email: string } }[];
}

export default function AdminOverviewPage() {
  const [data, setData] = React.useState<Overview | null>(null);
  const [error, setError] = React.useState("");

  React.useEffect(() => {
    adminApi.get<Overview>("/overview").then(setData).catch((e) => setError(e.message));
  }, []);

  if (error) return <ErrorState message={error} />;
  if (!data) return <Loading label="Loading overview…" />;

  const t = data.totals;
  const planRows = Object.entries(data.byPlan)
    .map(([name, v]) => ({ name, value: v.count, revenue: v.revenue }))
    .sort((a, b) => b.value - a.value);

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<Landmark className="h-5 w-5" />}
        title="Overview"
        description="Platform-wide health at a glance — revenue, growth, and live operations."
      />

      {/* KPI row */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <StatCard label="MRR (active)" value={`KES ${t.mrrKes.toLocaleString()}`} icon={<Wallet className="h-5 w-5" />} accent="green" hint="Active subscriptions" />
        <StatCard label="Active subs" value={t.activeSubscriptions} icon={<ReceiptText className="h-5 w-5" />} accent="blue" />
        <StatCard label="Farms" value={t.farms} icon={<Building2 className="h-5 w-5" />} accent="violet" />
        <StatCard label="Farm owners" value={t.users} icon={<Users className="h-5 w-5" />} accent="slate" />
        <StatCard label="Workers" value={t.workers} icon={<Sprout className="h-5 w-5" />} accent="amber" />
        <StatCard
          label="Open tickets"
          value={t.openTickets}
          icon={<TicketCheck className="h-5 w-5" />}
          accent={t.openTickets > 0 ? "red" : "slate"}
        />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <TrendAreaChart
            title="Revenue — last 30 days"
            subtitle="New subscription payments per day (KES)"
            data={data.revenueTrend}
            dataKey="revenue"
          />
        </div>
        <DonutChart
          title="Active subs by plan"
          subtitle="Distribution across pricing tiers"
          data={planRows}
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <DailyBarChart
          title="Signups — last 7 days"
          subtitle="New farm-owner accounts per day"
          data={data.signups.map((s) => ({ date: s.date, count: s.count }))}
          dataKey="count"
        />

        <Panel
          title="Recent payments"
          description="Latest activated subscriptions"
          actions={
            <Link href="/waadmin/billing" className="flex items-center gap-1 text-xs font-semibold text-wangari-green-700 hover:text-wangari-green-800">
              View billing <ArrowRight className="h-3.5 w-3.5" />
            </Link>
          }
        >
          {data.recentPayments.length === 0 ? (
            <EmptyState title="No payments yet" hint="Payments appear here as soon as subscriptions activate." />
          ) : (
            <div className="divide-y divide-wangari-border">
              {data.recentPayments.map((p) => (
                <div key={p.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                  <div className="flex min-w-0 items-center gap-2.5">
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-wangari-green-50 text-wangari-green-700">
                      <ReceiptText className="h-4 w-4" />
                    </div>
                    <div className="min-w-0">
                      <div className="truncate font-medium text-wangari-heading">{p.user?.name || p.user?.email || `User #${p.id}`}</div>
                      <div className="truncate text-xs text-wangari-subtle">{p.planName} · {p.reference || "no reference"}</div>
                    </div>
                  </div>
                  <div className="shrink-0 font-semibold text-wangari-green-700">KES {Number(p.amount).toLocaleString()}</div>
                </div>
              ))}
            </div>
          )}
        </Panel>
      </div>

      <Panel
        title="Recent signups"
        description="Newest farm-owner accounts"
        actions={
          <Link href="/waadmin/users" className="flex items-center gap-1 text-xs font-semibold text-wangari-green-700 hover:text-wangari-green-800">
            All users <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        }
      >
        {data.recentUsers.length === 0 ? (
          <EmptyState title="No signups in the last 7 days" icon={<UserPlus className="h-5 w-5" />} />
        ) : (
          <TableShell minWidth={520}>
            <thead>
              <tr>
                <Th>Name</Th>
                <Th>Email</Th>
                <Th className="text-right">Joined</Th>
              </tr>
            </thead>
            <tbody>
              {data.recentUsers.map((u) => (
                <tr key={u.id} className="transition-colors hover:bg-wangari-green-50/40">
                  <Td className="font-medium text-wangari-heading">{u.name}</Td>
                  <Td>{u.email}</Td>
                  <Td className="text-right text-wangari-muted">{new Date(u.createdAt).toLocaleDateString()}</Td>
                </tr>
              ))}
            </tbody>
          </TableShell>
        )}
      </Panel>
    </div>
  );
}
