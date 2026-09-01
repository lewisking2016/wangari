"use client";

import { BarChart3 } from "lucide-react";
import { FeaturePage } from "@/components/feature-page";

export default function AnalyticsFeaturePage() {
  return (
    <FeaturePage
      icon={BarChart3}
      badge="Smart Analytics"
      title="See your costs, revenue, and margins at a glance"
      subtitle="Know exactly which flock is most profitable. Make data-driven decisions that grow your bottom line — not just your bird count."
      description="Wangari's analytics engine transforms raw farm data into actionable business insights. See your profitability per flock, track cost trends, forecast revenue, and make smarter decisions backed by real numbers — not gut feelings."
      highlights={[
        "Real-time revenue and expense dashboards",
        "Per-flock profitability analysis with ROI calculations",
        "Feed cost optimization recommendations",
        "Revenue forecasting based on production trends",
        "Comparative analysis across flocks, seasons, and years",
        "Export-ready financial reports for investors or banks",
        "Cost breakdown by category (feed, labor, medication, etc.)",
        "Break-even analysis for new flock investments",
      ]}
      capabilities={[
        { title: "Revenue Dashboard", desc: "See total revenue, revenue per flock, and revenue trends at a glance. Track income from egg sales, bird sales, manure, and other sources with automatic categorization." },
        { title: "Cost Analysis", desc: "Break down every shilling spent on your farm. See where your money goes — feed, labor, medication, infrastructure — and identify opportunities to cut costs without cutting quality." },
        { title: "Profitability Reports", desc: "Calculate exact profit margins per flock, per kg, per egg, or per bird. Compare profitability across different breeds, ages, and seasons to optimize your strategy." },
        { title: "Forecasting Engine", desc: "AI-powered predictions for future production, revenue, and costs. Plan inventory, staffing, and investments based on data-driven forecasts instead of guesswork." },
        { title: "Custom Reports", desc: "Generate custom reports for any time period, flock, or metric. Share reports with partners, investors, or financial institutions with one tap." },
        { title: "Benchmark Comparisons", desc: "Compare your farm's performance against industry averages and your own historical data. See where you excel and where there's room for improvement." },
      ]}
      stats={[
        { value: "Real-Time", label: "Revenue Tracking" },
        { value: "40%", label: "Better Decision Making" },
        { value: "15%", label: "Average Cost Reduction" },
        { value: "Real-time", label: "Data Updates" },
      ]}
      testimonial={{
        name: "Mary Akinyi",
        role: "Mixed Farm, Kisumu",
        text: "The analytics showed me that one of my flocks was costing me more in feed than it was producing in eggs. I would never have caught that without Wangari. I restructured that flock and saved KES 200,000 last quarter. The forecasting feature helps me plan purchases ahead of time.",
      }}
    />
  );
}
