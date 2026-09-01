"use client";

import * as React from "react";
import {
  Bird,
  Egg,
  DollarSign,
  TrendingDown,
  ShoppingCart,
  AlertTriangle,
  Plus,
  ArrowUpRight,
  ArrowDownRight,
} from "lucide-react";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { ChartCard } from "@/components/dashboard/chart-card";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { formatCurrency, formatNumber, formatRelative } from "@/lib/utils";

const mockRevenueData = [
  { month: "Jan", revenue: 120000, expenses: 85000 },
  { month: "Feb", revenue: 145000, expenses: 92000 },
  { month: "Mar", revenue: 168000, expenses: 98000 },
  { month: "Apr", revenue: 155000, expenses: 102000 },
  { month: "May", revenue: 189000, expenses: 110000 },
  { month: "Jun", revenue: 210000, expenses: 115000 },
];

const mockTransactions = [
  { id: 1, desc: "Eggs sold — 50 trays", amount: 4500, type: "income", time: "2h ago" },
  { id: 2, desc: "Feed purchase — Kienyeji mash", amount: 12000, type: "expense", time: "5h ago" },
  { id: 3, desc: "Broiler sale — 200 birds", amount: 85000, type: "income", time: "1d ago" },
  { id: 4, desc: "Worker wages — June", amount: 35000, type: "expense", time: "2d ago" },
  { id: 5, desc: "Vaccination — IBD vaccine", amount: 3500, type: "expense", time: "3d ago" },
];

export default function DashboardPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      {/* Welcome */}
      <div>
        <h1 className="text-2xl font-bold text-wangari-heading">
          Good morning 👋
        </h1>
        <p className="text-sm text-wangari-muted mt-1">
          Here&apos;s what&apos;s happening on your farm today.
        </p>
      </div>

      {/* KPI Grid */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard
          title="Total Birds"
          value={formatNumber(2450)}
          icon={<Bird className="h-5 w-5" />}
          change="+12 this week"
          changeType="positive"
        />
        <KpiCard
          title="Eggs Today"
          value={formatNumber(1820)}
          icon={<Egg className="h-5 w-5" />}
          change="+5% vs yesterday"
          changeType="positive"
        />
        <KpiCard
          title="Revenue"
          value={formatCurrency(210000)}
          icon={<DollarSign className="h-5 w-5" />}
          change="+18% this month"
          changeType="positive"
        />
        <KpiCard
          title="Expenses"
          value={formatCurrency(115000)}
          icon={<TrendingDown className="h-5 w-5" />}
          change="+4% this month"
          changeType="negative"
        />
      </div>

      {/* Charts */}
      <div className="grid lg:grid-cols-2 gap-6">
        <ChartCard
          title="Revenue vs Expenses"
          data={mockRevenueData}
          dataKey="revenue"
          xKey="month"
          color="#166534"
        />
        <ChartCard
          title="Expenses Trend"
          data={mockRevenueData}
          dataKey="expenses"
          xKey="month"
          color="#DC2626"
        />
      </div>

      {/* Bottom Row */}
      <div className="grid lg:grid-cols-3 gap-6">
        {/* Recent Transactions */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Recent Transactions</CardTitle>
            <Button variant="ghost" size="sm">
              View All
            </Button>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {mockTransactions.map((tx) => (
                <div
                  key={tx.id}
                  className="flex items-center justify-between rounded-xl px-4 py-3 hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={`flex h-9 w-9 items-center justify-center rounded-lg ${
                        tx.type === "income"
                          ? "bg-wangari-green-50 text-wangari-green-700"
                          : "bg-red-50 text-red-600"
                      }`}
                    >
                      {tx.type === "income" ? (
                        <ArrowUpRight className="h-4 w-4" />
                      ) : (
                        <ArrowDownRight className="h-4 w-4" />
                      )}
                    </div>
                    <div>
                      <p className="text-sm font-medium text-wangari-heading">
                        {tx.desc}
                      </p>
                      <p className="text-xs text-wangari-muted">{tx.time}</p>
                    </div>
                  </div>
                  <p
                    className={`text-sm font-bold ${
                      tx.type === "income"
                        ? "text-wangari-green-700"
                        : "text-red-600"
                    }`}
                  >
                    {tx.type === "income" ? "+" : "-"}
                    {formatCurrency(tx.amount)}
                  </p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Quick Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <Button className="w-full justify-start" variant="outline">
              <Plus className="h-4 w-4" />
              Log Production
            </Button>
            <Button className="w-full justify-start" variant="outline">
              <ShoppingCart className="h-4 w-4" />
              Record Sale
            </Button>
            <Button className="w-full justify-start" variant="outline">
              <DollarSign className="h-4 w-4" />
              Add Expense
            </Button>
            <Button className="w-full justify-start" variant="outline">
              <AlertTriangle className="h-4 w-4" />
              Check Inventory
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
