"use client";

import { BarChart3 } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { EmptyState } from "@/components/shared/empty-state";

export default function ReportsPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader title="Reports" description="Analytics and insights across your farm operations." />
      <EmptyState
        icon={<BarChart3 className="h-8 w-8" />}
        title="Reports coming soon"
        description="Detailed analytics, profit/loss statements, and performance reports will be available here."
      />
    </div>
  );
}
