"use client";

import * as React from "react";
import {
  Activity, Database, Timer, Cpu, Layers, RefreshCw,
  MailCheck, MailWarning, ShieldCheck, CreditCard, MemoryStick, ScrollText,
} from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import { PageHeader, Panel, StatCard, Loading, ErrorState, GhostButton } from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface SystemInfo {
  status: string;
  dbLatencyMs: number;
  counts: { farms: number; users: number; subs: number; workers: number; audit24h: number };
  email: { sent: number; failed: number };
  services: { database: string; smtp: string; paystack: string };
  memory: { heapUsedMb: number; heapTotalMb: number; rssMb: number };
  uptimeSec: number;
  nodeVersion: string;
  checkedAt: string;
}

type ServiceState = "ok" | "slow" | "down" | "configured" | "not_configured";

const SERVICE_META: Record<ServiceState, { label: string; variant: "success" | "warning" | "danger" | "outline" }> = {
  ok: { label: "Operational", variant: "success" },
  configured: { label: "Configured", variant: "success" },
  slow: { label: "Degraded", variant: "warning" },
  not_configured: { label: "Not configured", variant: "outline" },
  down: { label: "Down", variant: "danger" },
};

function ServiceCard({ icon, name, state, hint }: { icon: React.ReactNode; name: string; state: ServiceState; hint: string }) {
  const meta = SERVICE_META[state] || SERVICE_META.not_configured;
  return (
    <div className="flex items-center justify-between rounded-2xl border border-wangari-border p-4">
      <div className="flex min-w-0 items-center gap-3">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-wangari-cream text-wangari-muted">{icon}</div>
        <div className="min-w-0">
          <div className="text-sm font-bold text-wangari-heading">{name}</div>
          <div className="truncate text-xs text-wangari-subtle">{hint}</div>
        </div>
      </div>
      <Badge variant={meta.variant}>{meta.label}</Badge>
    </div>
  );
}

export default function AdminSystemPage() {
  const [info, setInfo] = React.useState<SystemInfo | null>(null);
  const [error, setError] = React.useState("");
  const [refreshing, setRefreshing] = React.useState(false);

  const load = React.useCallback(async (showSpinner = false) => {
    if (showSpinner) setRefreshing(true);
    try {
      setInfo(await adminApi.get<SystemInfo>("/system"));
      setError("");
    } catch (e: any) {
      setError(e.message);
    } finally {
      setRefreshing(false);
    }
  }, []);

  React.useEffect(() => {
    load();
    const t = setInterval(() => load(), 30_000);
    return () => clearInterval(t);
  }, [load]);

  if (error && !info) return <ErrorState message={error} />;
  if (!info) return <Panel><Loading label="Checking system health…" /></Panel>;

  const uptimeDays = (info.uptimeSec / 86400).toFixed(1);
  const uptimeHours = Math.floor((info.uptimeSec % 86400) / 3600);
  const heapPct = Math.round((info.memory.heapUsedMb / info.memory.heapTotalMb) * 100);

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<Activity className="h-5 w-5" />}
        title="System Health"
        description={`Live infrastructure status · last checked ${new Date(info.checkedAt).toLocaleTimeString()} · auto-refreshes every 30s`}
        actions={
          <>
            {info.status === "ok" ? (
              <Badge variant="success"><span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-wangari-green-500" /> All systems operational</Badge>
            ) : (
              <Badge variant="danger"><span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-red-500" /> Degraded</Badge>
            )}
            <GhostButton onClick={() => load(true)} disabled={refreshing} className="h-8 px-2.5 text-xs">
              <RefreshCw className={`h-3.5 w-3.5 ${refreshing ? "animate-spin" : ""}`} /> Refresh
            </GhostButton>
          </>
        }
      />

      {error && <ErrorState message={error} />}

      {/* Core stats */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <StatCard
          label="DB latency"
          value={`${info.dbLatencyMs} ms`}
          icon={<Database className="h-5 w-5" />}
          accent={info.dbLatencyMs < 100 ? "green" : info.dbLatencyMs < 500 ? "amber" : "red"}
          hint={info.dbLatencyMs < 100 ? "excellent" : info.dbLatencyMs < 500 ? "healthy" : "slow"}
        />
        <StatCard label="API uptime" value={`${uptimeDays}d ${uptimeHours}h`} icon={<Timer className="h-5 w-5" />} accent="blue" hint="since last restart" />
        <StatCard label="Heap usage" value={`${info.memory.heapUsedMb} MB`} icon={<MemoryStick className="h-5 w-5" />} accent={heapPct < 75 ? "slate" : "amber"} hint={`${heapPct}% of ${info.memory.heapTotalMb} MB heap`} />
        <StatCard label="Emails 24h" value={`${info.email.sent} / ${info.email.sent + info.email.failed}`} icon={<MailCheck className="h-5 w-5" />} accent={info.email.failed > info.email.sent ? "red" : "green"} hint={`${info.email.failed} failed`} />
        <StatCard label="Audit events 24h" value={info.counts.audit24h} icon={<ScrollText className="h-5 w-5" />} accent="violet" />
        <StatCard label="Runtime" value={info.nodeVersion} icon={<Cpu className="h-5 w-5" />} accent="slate" hint="Node.js" />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Service status grid */}
        <Panel title="Services" description="External dependencies and integrations">
          <div className="space-y-3">
            <ServiceCard
              icon={<Database className="h-5 w-5" />}
              name="PostgreSQL Database"
              state={info.services.database as ServiceState}
              hint={`${info.dbLatencyMs}ms round-trip · query check`}
            />
            <ServiceCard
              icon={<MailCheck className="h-5 w-5" />}
              name="Email (SMTP / Mailbux)"
              state={info.services.smtp as ServiceState}
              hint={`${info.email.sent} delivered, ${info.email.failed} failed in 24h`}
            />
            <ServiceCard
              icon={<CreditCard className="h-5 w-5" />}
              name="Paystack Payments"
              state={info.services.paystack as ServiceState}
              hint="checkout + webhook secret key"
            />
          </div>
        </Panel>

        {/* Platform data */}
        <Panel title="Platform data" description="Live record counts across the system">
          <div className="grid grid-cols-2 gap-3">
            {[
              { label: "Farms", value: info.counts.farms, icon: <Layers className="h-4 w-4" /> },
              { label: "Users", value: info.counts.users, icon: <Layers className="h-4 w-4" /> },
              { label: "Subscriptions", value: info.counts.subs, icon: <Layers className="h-4 w-4" /> },
              { label: "Workers", value: info.counts.workers, icon: <Layers className="h-4 w-4" /> },
            ].map((c) => (
              <div key={c.label} className="rounded-2xl border border-wangari-border p-3.5">
                <div className="flex items-center gap-1.5 text-xs font-semibold text-wangari-muted">{c.icon} {c.label}</div>
                <div className="mt-1 text-2xl font-bold text-wangari-heading">{c.value.toLocaleString()}</div>
              </div>
            ))}
            <div className="col-span-2 rounded-2xl bg-wangari-cream px-3.5 py-3 text-xs text-wangari-muted">
              <ShieldCheck className="mr-1 inline h-3.5 w-3.5 text-wangari-green-700" />
              Memory: {info.memory.rssMb} MB RSS · heap {info.memory.heapUsedMb}/{info.memory.heapTotalMb} MB · uptime {uptimeDays} days
            </div>
          </div>
        </Panel>
      </div>
    </div>
  );
}
