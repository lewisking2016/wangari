"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  Bird,
  Egg,
  TrendingUp,
  TrendingDown,
  ArrowUpRight,
  ArrowDownRight,
  Plus,
  ShoppingCart,
  DollarSign,
  BarChart3,
  RefreshCw,
  Activity,
} from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import api from "@/lib/api-client";

// Existing harmonious components
import { KpiCard } from "@/components/dashboard/kpi-card";
import { ProductionChart, RevenueChart, FlockChart } from "@/components/dashboard/Charts";
import { WeatherWidget } from "@/components/dashboard/WeatherWidget";
import { AlertsCard } from "@/components/dashboard/Alerts";

const fadeUp = {
  hidden: { opacity: 0, y: 16 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.4, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.06 } },
};

export default function DashboardPage() {
  const [data, setData] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [refreshing, setRefreshing] = React.useState(false);

  const fetchData = React.useCallback(async () => {
    try {
      const d = await api.get("/api/dashboard");
      setData(d);
    } catch (err) {
      console.error("Dashboard error:", err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  React.useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleRefresh = () => {
    setRefreshing(true);
    fetchData();
  };

  const hour = new Date().getHours();
  const greeting =
    hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";

  if (loading)
    return (
      <div className="flex flex-col items-center justify-center h-96 gap-4">
        <div className="relative">
          <div className="h-12 w-12 rounded-full border-[3px] border-wangari-green-200 border-t-wangari-green-600 animate-spin" />
          <Bird className="absolute left-1/2 top-1/2 h-5 w-5 -translate-x-1/2 -translate-y-1/2 text-wangari-green-600" />
        </div>
        <p className="text-sm text-wangari-muted">Loading your farm...</p>
      </div>
    );

  const txs = data?.recentTransactions || [];
  const prodData = data?.recentProduction || [];
  const mortalityAlerts = data?.mortalityAlerts || [];
  const stockAlerts = data?.stockAlerts || [];
  const vaccinationAlerts = data?.vaccinationAlerts || [];

  // Chart data
  const productionChartData = prodData.slice(-7).map((r: any) => ({
    date: new Date(r.date).toLocaleDateString("en-KE", { weekday: "short" }),
    eggs: r.eggsCollected || 0,
    mortality: r.mortality || 0,
  }));

  const revenueChartData = [
    { month: "Jan", income: data?.monthlyRevenue || 0, expenses: data?.monthlyExpenses || 0 },
    { month: "Feb", income: 0, expenses: 0 },
    { month: "Mar", income: 0, expenses: 0 },
    { month: "Apr", income: 0, expenses: 0 },
    { month: "May", income: 0, expenses: 0 },
    { month: "Jun", income: 0, expenses: 0 },
  ];

  const flockChartData = data?.flocks?.map((f: any) => ({
    name: f.name,
    value: f.totalBirds || 0,
  })) || [];

  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={fadeUp}
        className="flex items-center justify-between"
      >
        <div>
          <h1 className="text-2xl font-bold text-wangari-heading tracking-tight">
            {greeting} 👋
          </h1>
          <p className="text-sm text-wangari-muted mt-0.5">
            Here&apos;s what&apos;s happening on your farm today.
          </p>
        </div>
        <Button
          onClick={handleRefresh}
          variant="ghost"
          size="sm"
          className="gap-1.5 text-wangari-muted"
        >
          <RefreshCw className={`h-4 w-4 ${refreshing ? "animate-spin" : ""}`} />
          <span className="hidden sm:inline">Refresh</span>
        </Button>
      </motion.div>

      {/* KPI Cards — using existing harmonious component */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={stagger}
        className="grid grid-cols-2 lg:grid-cols-4 gap-4"
      >
        <motion.div variants={fadeUp}>
          <KpiCard
            title="Total Birds"
            value={(data?.totalBirds || 0).toLocaleString()}
            icon={<Bird className="h-5 w-5" />}
            change={`${data?.totalFlocks || 0} active flocks`}
            changeType="positive"
          />
        </motion.div>
        <motion.div variants={fadeUp}>
          <KpiCard
            title="Eggs Today"
            value={(data?.eggsToday || 0).toLocaleString()}
            icon={<Egg className="h-5 w-5" />}
            change={data?.mortalityToday > 0 ? `${data.mortalityToday} mortality` : "On track"}
            changeType={data?.mortalityToday > 0 ? "negative" : "positive"}
          />
        </motion.div>
        <motion.div variants={fadeUp}>
          <KpiCard
            title="Revenue"
            value={`KES ${(data?.monthlyRevenue || 0).toLocaleString()}`}
            icon={<TrendingUp className="h-5 w-5" />}
            change="This month"
            changeType="positive"
          />
        </motion.div>
        <motion.div variants={fadeUp}>
          <KpiCard
            title="Expenses"
            value={`KES ${(data?.monthlyExpenses || 0).toLocaleString()}`}
            icon={<TrendingDown className="h-5 w-5" />}
            change="This month"
            changeType="negative"
          />
        </motion.div>
      </motion.div>

      {/* Weather + Alerts — side by side */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <WeatherWidget
          data={data?.weather || null}
          location={data?.farmName || "Your Farm"}
        />
        <AlertsCard
          mortalityAlerts={mortalityAlerts}
          stockAlerts={stockAlerts}
          vaccinationAlerts={vaccinationAlerts}
        />
      </div>

      {/* Charts — Production + Flock Distribution */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {productionChartData.length > 0 && (
          <ProductionChart data={productionChartData} />
        )}
        {flockChartData.length > 0 && (
          <FlockChart data={flockChartData} />
        )}
      </div>

      {/* Revenue Chart — full width */}
      <RevenueChart data={revenueChartData} />

      {/* Transactions + Quick Actions */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={stagger}
        className="grid lg:grid-cols-3 gap-6"
      >
        {/* Recent Transactions */}
        <motion.div variants={fadeUp} className="lg:col-span-2">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <div>
                <CardTitle className="text-base font-bold text-wangari-heading">
                  Recent Transactions
                </CardTitle>
                <p className="text-xs text-wangari-muted">Latest financial activity</p>
              </div>
              <Link
                href="/finances"
                className="text-xs font-semibold text-wangari-green-700 hover:underline"
              >
                View all
              </Link>
            </CardHeader>
            <CardContent>
              <div className="space-y-1">
                {txs.length === 0 && (
                  <p className="text-sm text-wangari-muted py-8 text-center">
                    No transactions yet.
                  </p>
                )}
                {txs.map((tx: any, i: number) => (
                  <motion.div
                    key={tx.id}
                    initial={{ opacity: 0, x: -8 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: i * 0.04 }}
                    className="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-wangari-green-50/50 transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`flex h-9 w-9 items-center justify-center rounded-xl ${
                          tx.type === "income"
                            ? "bg-wangari-green-50 text-wangari-green-700"
                            : "bg-gray-50 text-wangari-muted"
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
                          {tx.description || tx.category}
                        </p>
                        <p className="text-[11px] text-wangari-subtle">
                          {new Date(tx.date).toLocaleDateString()}
                        </p>
                      </div>
                    </div>
                    <p
                      className={`text-sm font-semibold tabular-nums ${
                        tx.type === "income"
                          ? "text-wangari-green-700"
                          : "text-wangari-muted"
                      }`}
                    >
                      {tx.type === "income" ? "+" : "-"}KES {Number(tx.amount).toLocaleString()}
                    </p>
                  </motion.div>
                ))}
              </div>
            </CardContent>
          </Card>
        </motion.div>

        {/* Quick Actions */}
        <motion.div variants={fadeUp}>
          <Card>
            <CardHeader className="pb-2">
              <div>
                <CardTitle className="text-base font-bold text-wangari-heading">
                  Quick Actions
                </CardTitle>
                <p className="text-xs text-wangari-muted">Common tasks</p>
              </div>
            </CardHeader>
            <CardContent className="space-y-2.5">
              {[
                { href: "/production", icon: Plus, label: "Log Production" },
                { href: "/sales", icon: ShoppingCart, label: "Record Sale" },
                { href: "/finances", icon: DollarSign, label: "Add Expense" },
                { href: "/inventory", icon: BarChart3, label: "Check Inventory" },
              ].map((action) => (
                <Link key={action.href} href={action.href}>
                  <Button
                    className="w-full justify-start gap-3 bg-white border border-wangari-border text-wangari-text hover:bg-wangari-green-50 hover:border-wangari-green-300 hover:text-wangari-green-800 transition-all"
                    variant="outline"
                  >
                    <action.icon className="h-4 w-4" />
                    <span className="font-medium text-sm">{action.label}</span>
                  </Button>
                </Link>
              ))}
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>
    </div>
  );
}
