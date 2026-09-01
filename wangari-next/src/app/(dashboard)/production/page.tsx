"use client";
import * as React from "react";
import { ClipboardList, Plus } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/shared/empty-state";

export default function ProductionPage() {
  const [records, setRecords] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch("/api/production").then(r => r.json()).then(d => { setRecords(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  const totalEggs = records.reduce((s, r) => s + r.eggsCollected, 0);
  const totalMortality = records.reduce((s, r) => s + r.mortality, 0);
  const totalFeed = records.reduce((s, r) => s + Number(r.feedUsed), 0);
  const avgEggs = records.length ? Math.round(totalEggs / records.length) : 0;

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader title="Production" description="Daily egg collection and mortality tracking" />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard title="Total Eggs" value={totalEggs.toLocaleString()} icon={<ClipboardList className="h-5 w-5" />} change="All time" changeType="positive" />
        <KpiCard title="Avg Daily" value={avgEggs.toLocaleString()} icon={<ClipboardList className="h-5 w-5" />} change="Per day" changeType="positive" />
        <KpiCard title="Total Feed" value={totalFeed.toFixed(0) + " kg"} icon={<ClipboardList className="h-5 w-5" />} change="All time" changeType="positive" />
        <KpiCard title="Mortality" value={String(totalMortality)} icon={<ClipboardList className="h-5 w-5" />} change="All time" changeType="negative" />
      </div>

      {records.length === 0 ? <EmptyState title="No records" description="Start logging daily production." /> : (
        <Card><CardContent className="p-0"><div className="overflow-x-auto"><table className="w-full text-sm"><thead><tr className="border-b border-[#E5E7EB] bg-[#FAFBFC]">
          <th className="px-4 py-3 text-left font-semibold text-[#64748B]">Date</th>
          <th className="px-4 py-3 text-left font-semibold text-[#64748B]">Flock</th>
          <th className="px-4 py-3 text-right font-semibold text-[#64748B]">Eggs</th>
          <th className="px-4 py-3 text-right font-semibold text-[#64748B]">Mortality</th>
          <th className="px-4 py-3 text-right font-semibold text-[#64748B]">Feed (kg)</th>
        </tr></thead><tbody>
          {records.map(r => (
            <tr key={r.id} className="border-b border-[#E5E7EB] hover:bg-gray-50">
              <td className="px-4 py-3 text-[#0F172A]">{new Date(r.date).toLocaleDateString()}</td>
              <td className="px-4 py-3 text-[#64748B]">{r.flock?.name || "-"}</td>
              <td className="px-4 py-3 text-right font-semibold text-[#166534]">{r.eggsCollected.toLocaleString()}</td>
              <td className="px-4 py-3 text-right font-semibold text-red-600">{r.mortality}</td>
              <td className="px-4 py-3 text-right text-[#0F172A]">{Number(r.feedUsed).toFixed(1)}</td>
            </tr>
          ))}
        </tbody></table></div></CardContent></Card>
      )}
    </div>
  );
}