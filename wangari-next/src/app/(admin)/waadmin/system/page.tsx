"use client";

import * as React from "react";
import { Activity, Database, Timer, Cpu, Layers } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import { PageHeader, Panel, StatCard, Loading, ErrorState } from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

interface SystemInfo {
  status: string;
  dbLatencyMs: number;
  counts: { farms: number; users: number; subs: number };
  uptimeSec: number;
  nodeVersion: string;
}

export default function AdminSystemPage() {
  const [info, setInfo] = React.useState<SystemInfo | null>(null);
  const [error, setError] = React.useState("");

  const load = React.useCallback(() => {
    adminApi.get<SystemInfo>("/system").then(setInfo).catch((e) => setError(e.message));
  }, []);
  React.useEffect(() => {
    load();
    const t = setInterval(load, 30_000); // refresh every 30s
    return () => clearInterval(t);
  }, [load]);

  if (error) return <ErrorState message={error} />;
  if (!info) return <Panel><Loading label="Checking system health…" /></Panel>;

  const dbHealthy = info.dbLatencyMs < 500;
  const uptimeDays = (info.uptimeSec / 86400).toFixed(1);

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<Activity className="h-5 w-5" />}
        title="System Health"
        description="Live API status — auto-refreshes every 30 seconds."
        actions={
          info.status === "ok" ? (
            <Badge variant="success"><span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-wangari-green-500" /> Operational</Badge>
          ) : (
            <Badge variant="danger"><span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-red-500" /> Degraded</Badge>
          )
        }
      />

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard
          label="Database latency"
          value={`${info.dbLatencyMs} ms`}
          icon={<Database className="h-5 w-5" />}
          accent={dbHealthy ? "green" : "amber"}
          hint={dbHealthy ? "healthy" : "slower than usual"}
        />
        <StatCard label="API uptime" value={`${uptimeDays} d`} icon={<Timer className="h-5 w-5" />} accent="blue" />
        <StatCard label="Runtime" value={info.nodeVersion} icon={<Cpu className="h-5 w-5" />} accent="slate" hint="Node.js" />
        <StatCard
          label="Records"
          value={`${info.counts.farms + info.counts.users + info.counts.subs}`}
          icon={<Layers className="h-5 w-5" />}
          accent="violet"
          hint={`${info.counts.farms} farms · ${info.counts.users} users · ${info.counts.subs} subs`}
        />
      </div>
    </div>
  );
}
