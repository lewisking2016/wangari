"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  Bird,
  Egg,
  DollarSign,
  TrendingDown,
  TrendingUp,
  ArrowUpRight,
  ArrowDownRight,
  Plus,
  ShoppingCart,
  Activity,
  Clock,
  BarChart3,
  RefreshCw,
} from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import api from "@/lib/api-client";

// Premium components
import { KPICard } from "@/components/dashboard/KPICard";
import { ProductionChart, RevenueChart, FlockChart } from "@/components/dashboard/Charts";
import { WeatherWidget } from "@/components/dashboard/WeatherWidget";
import { AlertsCard } from "@/components/dashboard/Alerts";

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08 } },
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
      console.error("Dashboard fetch error:", err);
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
          <div className="h-16 w-16 rounded-full border-4 border-emerald-200 border-t-emerald-600 animate-spin" />
          <Bird className="absolute left-1/2 top-1/2 h-6 w-6 -translate-x-1/2 -translate-y-1/2 text-emerald-600" />
        </div>
        <p className="text-sm text-gray-500">Loading your farm data...</p>
      </div>
    );

  const txs = data?.recentTransactions || [];
  const prodData = data?.recentProduction || [];
  const mortalityAlerts = data?.mortalityAlerts || [];
  const stockAlerts = data?.stockAlerts || [];
  const vaccinationAlerts = data?.vaccinationAlerts || [];

  // Prepare chart data
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
          <h1 className="text-3xl font-bold text-gray-900 tracking-tight">
            {greeting} 👋
          </h1>
          <p className="text-gray-500 mt-1">
            Here&apos;s what&apos;s happening on your farm today.
          </p>
        </div>
        <Button
          onClick={handleRefresh}
          variant="outline"
          size="sm"
          className="gap-2"
        >
          <RefreshCw className={`h-4 w-4 ${refreshing ? "animate-spin" : ""}`} />
          Refresh
        </Button>
      </motion.div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <KPICard
          title="Total Birds"
          value={(data?.totalBirds || 0).toLocaleString()}
          icon={Bird}
          gradient="bg-gradient-to-br from-emerald-500 to-emerald-600"
          change={data?.totalFlocks > 0 ? 5 : 0}
          changeLabel={`${data?.totalFlocks || 0} active flocks`}
          delay={0}
        />
        <KPICard
          title="Eggs Today"
          value={(data?.eggsToday || 0).toLocaleString()}
          icon={Egg}
          gradient="bg-gradient-to-br from-amber-500 to-orange-500"
          change={data?.mortalityToday > 0 ? -data.mortalityToday : 0}
          changeLabel="mortality today"
          delay={0.1}
        />
        <KPICard
          title="Monthly Revenue"
          value={`KES ${(data?.monthlyRevenue || 0).toLocaleString()}`}
          icon={TrendingUp}
          gradient="bg-gradient-to-br from-blue-500 to-indigo-600"
          change={12}
          changeLabel="vs last month"
          delay={0.2}
        />
        <KPICard
          title="Monthly Expenses"
          value={`KES ${(data?.monthlyExpenses || 0).toLocaleString()}`}
          icon={TrendingDown}
          gradient="bg-gradient-to-br from-rose-500 to-pink-600"
          change={-8}
          changeLabel="vs last month"
          delay={0.3}
        />
      </div>

      {/* Weather + Alerts Row */}
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

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {productionChartData.length > 0 && (
          <ProductionChart data={productionChartData} />
        )}
        {flockChartData.length > 0 && (
          <FlockChart data={flockChartData} />
        )}
      </div>

      {/* Revenue Chart */}
      <RevenueChart data={revenueChartData} />

      {/* Transactions + Quick Actions */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={stagger}
        className="grid lg:grid-cols-3 gap-6"
      >
        <motion.div variants={fadeUp} className="lg:col-span-2">
          <Card className="border-0 shadow-lg">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <div>
                <CardTitle className="text-lg font-semibold">Recent Transactions</CardTitle>
                <p className="text-sm text-gray-500">Latest financial activity</p>
              </div>
              <Link href="/finances" className="text-sm text-emerald-600 font-semibold hover:underline">
                View All
              </Link>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {txs.length === 0 && (
                  <p className="text-sm text-gray-500 py-8 text-center">No transactions yet.</p>
                )}
                {txs.map((tx: any, i: number) => (
                  <motion.div
                    key={tx.id}
                    initial={{ opacity: 0, x: -12 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: i * 0.05 }}
                    className="flex items-center justify-between rounded-xl px-4 py-3 hover:bg-gray-50 transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`flex h-10 w-10 items-center justify-center rounded-xl ${
                          tx.type === "income"
                            ? "bg-emerald-100 text-emerald-600"
                            : "bg-gray-100 text-gray-500"
                        }`}
                      >
                        {tx.type === "income" ? (
                          <ArrowUpRight className="h-5 w-5" />
                        ) : (
                          <ArrowDownRight className="h-5 w-5" />
                        )}
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-gray-900">
                          {tx.description || tx.category}
                        </p>
                        <p className="text-xs text-gray-500">
                          {new Date(tx.date).toLocaleDateString()}
                        </p>
                      </div>
                    </div>
                    <p
                      className={`text-sm font-bold tabular-nums ${
                        tx.type === "income" ? "text-emerald-600" : "text-gray-500"
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

        <motion.div variants={fadeUp}>
          <Card className="border-0 shadow-lg">
            <CardHeader className="pb-2">
              <div>
                <CardTitle className="text-lg font-semibold">Quick Actions</CardTitle>
                <p className="text-sm text-gray-500">Common tasks</p>
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              {[
                { href: "/production", icon: Plus, label: "Log Production", color: "bg-emerald-100 text-emerald-600" },
                { href: "/sales", icon: ShoppingCart, label: "Record Sale", color: "bg-blue-100 text-blue-600" },
                { href: "/finances", icon: DollarSign, label: "Add Expense", color: "bg-amber-100 text-amber-600" },
                { href: "/inventory", icon: BarChart3, label: "Check Inventory", color: "bg-purple-100 text-purple-600" },
              ].map((action) => (
                <Link key={action.href} href={action.href}>
                  <Button
                    className="w-full justify-start gap-3 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200"
                    variant="outline"
                  >
                    <div className={`rounded-lg p-2 ${action.color}`}>
                      <action.icon className="h-4 w-4" />
                    </div>
                    <span className="font-medium">{action.label}</span>
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
