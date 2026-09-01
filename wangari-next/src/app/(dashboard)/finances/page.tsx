"use client";

import * as React from "react";
import { DollarSign, Plus, ArrowUpRight, ArrowDownRight } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { ChartCard } from "@/components/dashboard/chart-card";
import { formatCurrency } from "@/lib/utils";

const mockData = [
  { month: "Jan", revenue: 120000, expenses: 85000 },
  { month: "Feb", revenue: 145000, expenses: 92000 },
  { month: "Mar", revenue: 168000, expenses: 98000 },
  { month: "Apr", revenue: 155000, expenses: 102000 },
  { month: "May", revenue: 189000, expenses: 110000 },
  { month: "Jun", revenue: 210000, expenses: 115000 },
];

export default function FinancesPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader
        title="Finances"
        description="Track income, expenses, and profit across your farm."
        action={
          <Button>
            <Plus className="h-4 w-4" /> Add Transaction
          </Button>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard title="Income" value={formatCurrency(210000)} icon={<ArrowUpRight className="h-5 w-5" />} change="+18% this month" changeType="positive" />
        <KpiCard title="Expenses" value={formatCurrency(115000)} icon={<ArrowDownRight className="h-5 w-5" />} change="+4% this month" changeType="negative" />
        <KpiCard title="Net Profit" value={formatCurrency(95000)} icon={<DollarSign className="h-5 w-5" />} change="+32% this month" changeType="positive" />
        <KpiCard title="Margin" value="45.2%" icon={<DollarSign className="h-5 w-5" />} change="Healthy" changeType="positive" />
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <ChartCard title="Revenue Trend" data={mockData} dataKey="revenue" xKey="month" color="#166534" />
        <ChartCard title="Expense Trend" data={mockData} dataKey="expenses" xKey="month" color="#DC2626" />
      </div>
    </div>
  );
}
