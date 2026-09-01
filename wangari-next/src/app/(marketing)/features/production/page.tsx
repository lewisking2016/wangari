"use client";

import { ClipboardList } from "lucide-react";
import { FeaturePage } from "@/components/feature-page";

export default function ProductionFeaturePage() {
  return (
    <FeaturePage
      icon={ClipboardList}
      badge="Production Tracking"
      title="Log daily production in 3 taps"
      subtitle="Record egg collection, mortality, feed usage, and weight measurements from the field. Works offline — syncs when you're back online."
      description="Stop guessing and start knowing. Wangari's production tracking module makes it effortless to log daily farm data. Workers can record production from anywhere — even without internet — and everything syncs automatically when they're back online."
      highlights={[
        "3-tap production logging — fastest in the industry",
        "Works completely offline with automatic sync",
        "Daily egg collection, mortality, and feed tracking",
        "Automatic production trend analysis and forecasting",
        "Worker-level accountability with submission tracking",
        "Photo attachments for production records",
        "Multi-flock batch recording in a single session",
        "Historical data comparisons by week, month, or season",
      ]}
      capabilities={[
        { title: "Quick Entry Forms", desc: "Minimal-tap forms designed for field use. Log egg count, feed bags used, mortality, and notes in under 10 seconds. Large buttons and clear labels work even with gloves on." },
        { title: "Offline Mode", desc: "No internet? No problem. All production data is stored locally on the device and automatically syncs when connectivity returns. Never lose a day's data." },
        { title: "Trend Analysis", desc: "See production trends over days, weeks, and months. Identify seasonal patterns, detect production drops early, and forecast future output based on historical data." },
        { title: "Feed Tracking", desc: "Track every bag of feed that goes into each flock. Monitor consumption rates, detect waste, and get alerts when feed usage doesn't match expected patterns." },
        { title: "Mortality Logs", desc: "Record and categorize mortality events in real-time. Track causes, ages, and patterns. The system flags unusual spikes and suggests potential causes." },
        { title: "Worker Reports", desc: "See who logged what and when. Track worker productivity and accountability. Identify training needs based on data quality and submission consistency." },
      ]}
      stats={[
        { value: "10s", label: "Average Log Time" },
        { value: "300K+", label: "Records Logged Monthly" },
        { value: "100%", label: "Offline Reliability" },
        { value: "3x", label: "Faster Than Manual" },
      ]}
      testimonial={{
        name: "Peter Ochieng",
        role: "Broiler Farm, Eldoret",
        text: "Before Wangari, my workers would fill paper forms that often got lost or damaged. Now they log everything on their phones in seconds. The offline mode is a lifesaver — our farm has patchy signal but the data always syncs. I can see production data from my office in Nairobi.",
      }}
    />
  );
}
