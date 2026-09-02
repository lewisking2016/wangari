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
  Wheat,
  Target,
  AlertTriangle,
  Heart,
} from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import api from "@/lib/api-client";

// Components
import { KpiCard } from "@/components/dashboard/kpi-card";
import { ProductionChart, RevenueChart, FlockChart, HDPTrendChart } from "@/components/dashboard/Charts";
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

// ─── FCR Rating ──────────────────────────────────────────
function getFCRRating(fcr: number): { label: string; color: string; bg: string } {
  if (fcr === 0) return { label: "No data", color: "text-wangari-muted", bg: "bg-gray-50" };
  if (fcr <= 1.8) return { label: "Excellent", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (fcr <= 2.2) return { label: "Good", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (fcr <= 2.5) return { label: "Fair", color: "text-badge-yellow-text", bg: "bg-badge-yellow-bg" };
  return { label: "Poor", color: "text-badge-red-text", bg: "bg-badge-red-bg" };
}

// ─── HDP Rating ──────────────────────────────────────────
function getHDPRating(hdp: number): { label: string; color: string; bg: string } {
  if (hdp === 0) return { label: "No data", color: "text-wangari-muted", bg: "bg-gray-50" };
  if (hdp >= 85) return { label: "Excellent", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (hdp >= 75) return { label: "Good", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (hdp >= 60) return { label: "Fair", color: "text-badge-yellow-text", bg: "bg-badge-yellow-bg" };
  return { label: "Low", color: "text-badge-red-text", bg: "bg-badge-red-bg" };
}

// ─── Mortality Rating ────────────────────────────────────
function getMortalityRating(rate: number): { label: string; color: string; bg: string } {
  if (rate <= 1) return { label: "Normal", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (rate <= 3) return { label: "Acceptable", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (rate <= 5) return { label: "Watch", color: "text-badge-yellow-text", bg: "bg-badge-yellow-bg" };
  return { label: "Critical", color: "text-badge-red-text", bg: "bg-badge-red-bg" };
}

export default function DashboardPage() {
  const [data, setData] = React.useState<any>(null);
  const [weather, setWeather] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [refreshing, setRefreshing] = React.useState(false);

  const fetchData = React.useCallback(async () => {
    try {
      const [dashboardData, weatherData] = await Promise.all([
        api.get("/api/dashboard"),
        api.get("/api/weather").catch(() => null),
      ]);
      setData(dashboardData);
      setWeather(weatherData);
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
  const recentProd = data?.recentProduction || [];
  const mortalityAlerts = data?.mortalityAlerts || [];
  const stockAlerts = data?.stockAlerts || [];
  const vaccinationAlerts = data?.vaccinationAlerts || [];
  const feedItems = data?.feedItems || [];

  // Ratings
  const fcrRating = getFCRRating(data?.fcr || 0);
  const hdpRating = getHDPRating(data?.henDayProduction || 0);
  const mortalityRating = getMortalityRating(data?.mortalityRate || 0);

  // Chart data
  const productionChartData = recentProd.map((r: any) => ({
    date: new Date(r.date).toLocaleDateString("en-KE", { weekday: "short" }),
    eggs: r.eggsCollected || 0,
    mortality: r.mortality || 0,
  }));

  // Revenue chart - show last 6 months
  const revenueChartData = data?.recentProduction?.length > 0
    ? (() => {
        const months = [];
        const now = new Date();
        for (let i = 5; i >= 0; i--) {
          const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
          months.push({
            month: d.toLocaleDateString("en-KE", { month: "short" }),
            income: i === 0 ? (data?.monthlyRevenue || 0) : 0,
            expenses: i === 0 ? (data?.monthlyExpenses || 0) : 0,
          });
        }
        return months;
      })()
    : [
        { month: "Jan", income: 0, expenses: 0 },
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

      {/* ═══════════════════════════════════════════════════════
          ROW 1: What the farmer needs FIRST
          Eggs today, birds alive, feed stock, money
          ═══════════════════════════════════════════════════════ */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={stagger}
        className="grid grid-cols-2 lg:grid-cols-4 gap-4"
      >
        <motion.div variants={fadeUp}>
          <KpiCard
            title="Eggs Today"
            value={(data?.eggsToday || 0).toLocaleString()}
            icon={<Egg className="h-5 w-5" />}
            change={`${data?.henDayProduction || 0}% hen-day`}
            changeType="positive"
          />
        </motion.div>
        <motion.div variants={fadeUp}>
          <KpiCard
            title="Total Birds"
            value={(data?.totalBirds || 0).toLocaleString()}
            icon={<Bird className="h-5 w-5" />}
            change={`${data?.totalFlocks || 0} flocks`}
            changeType="positive"
          />
        </motion.div>
        <motion.div variants={fadeUp}>
          <KpiCard
            title="Feed Stock"
            value={data?.feedStock ? `${data.feedStock}` : "—"}
            icon={<Wheat className="h-5 w-5" />}
            change={
              stockAlerts.some((a: any) => a.itemName.toLowerCase().includes("feed"))
                ? "Low — reorder"
                : "Sufficient"
            }
            changeType={
              stockAlerts.some((a: any) => a.itemName.toLowerCase().includes("feed"))
                ? "negative"
                : "positive"
            }
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
      </motion.div>

      {/* ═══════════════════════════════════════════════════════
          ROW 2: Performance Metrics
          FCR, Mortality Rate, Cost per Egg, Feed per Bird
          ═══════════════════════════════════════════════════════ */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={stagger}
        className="grid grid-cols-2 lg:grid-cols-4 gap-4"
      >
        {/* FCR */}
        <motion.div variants={fadeUp}>
          <Card className="border border-wangari-border bg-white p-5">
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <p className="text-[11px] font-bold uppercase tracking-widest text-wangari-muted">
                  Feed Conversion
                </p>
                <p className="mt-2 text-3xl font-bold text-wangari-heading font-serif">
                  {data?.fcr || "—"}
                </p>
                <Badge variant="default" className={`mt-2 ${fcrRating.bg} ${fcrRating.color}`}>
                  {fcrRating.label}
                </Badge>
              </div>
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-800">
                <Target className="h-5 w-5" />
              </div>
            </div>
            <p className="mt-2 text-[11px] text-wangari-subtle">
              Feed (kg) per egg produced
            </p>
          </Card>
        </motion.div>

        {/* Mortality Rate */}
        <motion.div variants={fadeUp}>
          <Card className="border border-wangari-border bg-white p-5">
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <p className="text-[11px] font-bold uppercase tracking-widest text-wangari-muted">
                  Mortality Rate
                </p>
                <p className="mt-2 text-3xl font-bold text-wangari-heading font-serif">
                  {data?.mortalityRate || 0}%
                </p>
                <Badge variant="default" className={`mt-2 ${mortalityRating.bg} ${mortalityRating.color}`}>
                  {mortalityRating.label}
                </Badge>
              </div>
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-800">
                <Heart className="h-5 w-5" />
              </div>
            </div>
            <p className="mt-2 text-[11px] text-wangari-subtle">
              Deaths vs starting count
            </p>
          </Card>
        </motion.div>

        {/* Cost Per Egg */}
        <motion.div variants={fadeUp}>
          <Card className="border border-wangari-border bg-white p-5">
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <p className="text-[11px] font-bold uppercase tracking-widest text-wangari-muted">
                  Cost Per Egg
                </p>
                <p className="mt-2 text-3xl font-bold text-wangari-heading font-serif">
                  {data?.costPerEgg ? `KES ${data.costPerEgg}` : "—"}
                </p>
                <p className="mt-2 text-[11px] text-wangari-subtle">
                  Monthly cost ÷ eggs
                </p>
              </div>
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-800">
                <DollarSign className="h-5 w-5" />
              </div>
            </div>
          </Card>
        </motion.div>

        {/* Feed Per Bird */}
        <motion.div variants={fadeUp}>
          <Card className="border border-wangari-border bg-white p-5">
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <p className="text-[11px] font-bold uppercase tracking-widest text-wangari-muted">
                  Feed / Bird
                </p>
                <p className="mt-2 text-3xl font-bold text-wangari-heading font-serif">
                  {data?.feedPerBird ? `${data.feedPerBird}g` : "—"}
                </p>
                <p className="mt-2 text-[11px] text-wangari-subtle">
                  Grams per bird today
                </p>
              </div>
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-800">
                <Wheat className="h-5 w-5" />
              </div>
            </div>
          </Card>
        </motion.div>
      </motion.div>

      {/* ═══════════════════════════════════════════════════════
          ROW 3: Weather + Alerts
          ═══════════════════════════════════════════════════════ */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <WeatherWidget
          data={weather || data?.weather || null}
          location={data?.farmName || "Your Farm"}
        />
        <AlertsCard
          mortalityAlerts={mortalityAlerts}
          stockAlerts={stockAlerts}
          vaccinationAlerts={vaccinationAlerts}
        />
      </div>

      {/* ═══════════════════════════════════════════════════════
          ROW 4: Charts
          ═══════════════════════════════════════════════════════ */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {productionChartData.length > 0 && (
          <ProductionChart data={productionChartData} />
        )}
        {data?.hdps && data.hdps.length > 0 && (
          <HDPTrendChart data={data.hdps} />
        )}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <RevenueChart data={revenueChartData} />
        {flockChartData.length > 0 && (
          <FlockChart data={flockChartData} />
        )}
      </div>

      {/* ═══════════════════════════════════════════════════════
          ROW 5: Feed Stock Detail + Transactions
          ═══════════════════════════════════════════════════════ */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={stagger}
        className="grid lg:grid-cols-3 gap-6"
      >
        {/* Feed Stock Detail */}
        <motion.div variants={fadeUp}>
          <Card>
            <CardHeader className="pb-2">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base font-bold text-wangari-heading">
                    Feed Stock
                  </CardTitle>
                  <p className="text-xs text-wangari-muted">Current inventory</p>
                </div>
                <Link
                  href="/inventory"
                  className="text-xs font-semibold text-wangari-green-700 hover:underline"
                >
                  Manage
                </Link>
              </div>
            </CardHeader>
            <CardContent>
              {feedItems.length === 0 ? (
                <p className="text-sm text-wangari-muted py-6 text-center">
                  No feed items tracked yet
                </p>
              ) : (
                <div className="space-y-3">
                  {feedItems.map((item: any, i: number) => (
                    <div key={i} className="rounded-xl border border-wangari-border p-3">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-wangari-heading">
                          {item.name}
                        </p>
                        {item.reorderLevel > 0 && item.quantity <= item.reorderLevel && (
                          <Badge variant="danger">Low</Badge>
                        )}
                      </div>
                      <div className="mt-1 flex items-center justify-between text-xs text-wangari-muted">
                        <span>
                          {item.quantity} {item.unit}
                        </span>
                        {item.daysLeft !== null && (
                          <span>~{item.daysLeft} days left</span>
                        )}
                      </div>
                      {item.reorderLevel > 0 && (
                        <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-wangari-border">
                          <div
                            className="h-full rounded-full bg-wangari-green-500"
                            style={{
                              width: `${Math.min((item.quantity / item.reorderLevel) * 100, 100)}%`,
                            }}
                          />
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </motion.div>

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
      </motion.div>

      {/* ═══════════════════════════════════════════════════════
          ROW 6: Quick Actions
          ═══════════════════════════════════════════════════════ */}
      <motion.div
        initial="hidden"
        animate="visible"
        variants={fadeUp}
      >
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base font-bold text-wangari-heading">
              Quick Actions
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              {[
                { href: "/production", icon: Plus, label: "Log Production", desc: "Eggs & mortality" },
                { href: "/inventory", icon: Wheat, label: "Feed Stock", desc: "Track feed usage" },
                { href: "/sales", icon: ShoppingCart, label: "Record Sale", desc: "Eggs or birds" },
                { href: "/finances", icon: DollarSign, label: "Expenses", desc: "Add costs" },
              ].map((action) => (
                <Link key={action.href} href={action.href}>
                  <div className="flex flex-col items-center gap-2 rounded-xl border border-wangari-border p-4 text-center transition-all hover:border-wangari-green-300 hover:bg-wangari-green-50 cursor-pointer">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-700">
                      <action.icon className="h-5 w-5" />
                    </div>
                    <div>
                      <p className="text-sm font-semibold text-wangari-heading">{action.label}</p>
                      <p className="text-[11px] text-wangari-muted">{action.desc}</p>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}
