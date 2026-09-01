"use client";

import * as React from "react";
import { ClipboardList, Plus, Egg, TrendingUp, TrendingDown } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/components/ui/table";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { formatNumber } from "@/lib/utils";

const mockProduction = [
  { id: 1, date: "2026-08-30", flock: "Layer Flock A", eggs: 420, mortality: 1, feed: "25 kg" },
  { id: 2, date: "2026-08-29", flock: "Layer Flock A", eggs: 435, mortality: 0, feed: "24 kg" },
  { id: 3, date: "2026-08-28", flock: "Layer Flock A", eggs: 410, mortality: 2, feed: "26 kg" },
  { id: 4, date: "2026-08-30", flock: "Layer Flock B", eggs: 380, mortality: 0, feed: "22 kg" },
  { id: 5, date: "2026-08-29", flock: "Layer Flock B", eggs: 395, mortality: 1, feed: "23 kg" },
];

export default function ProductionPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader
        title="Production"
        description="Log and track daily egg collection, mortality, and feed usage."
        action={
          <Button>
            <Plus className="h-4 w-4" /> Log Production
          </Button>
        }
      />

      <div className="grid grid-cols-3 gap-4">
        <KpiCard title="Eggs Today" value={formatNumber(800)} icon={<Egg className="h-5 w-5" />} change="+3% vs yesterday" changeType="positive" />
        <KpiCard title="Mortality" value={formatNumber(1)} icon={<TrendingDown className="h-5 w-5" />} change="Low" changeType="positive" />
        <KpiCard title="Feed Used" value="47 kg" icon={<TrendingUp className="h-5 w-5" />} change="Normal" changeType="neutral" />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Recent Production Logs</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Flock</TableHead>
                <TableHead>Eggs</TableHead>
                <TableHead>Mortality</TableHead>
                <TableHead>Feed</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockProduction.map((row) => (
                <TableRow key={row.id}>
                  <TableCell className="font-medium">{row.date}</TableCell>
                  <TableCell>{row.flock}</TableCell>
                  <TableCell className="font-bold text-wangari-green-700">{row.eggs}</TableCell>
                  <TableCell className={row.mortality > 0 ? "text-red-600 font-bold" : ""}>{row.mortality}</TableCell>
                  <TableCell>{row.feed}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
