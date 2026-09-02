"use client";

import { motion } from "framer-motion";
import { LucideIcon } from "lucide-react";
import { cn } from "@/lib/utils";

interface KPICardProps {
  title: string;
  value: string | number;
  change?: number;
  changeLabel?: string;
  icon: LucideIcon;
  gradient: string;
  iconBg?: string;
  delay?: number;
}

export function KPICard({
  title,
  value,
  change,
  changeLabel = "vs last week",
  icon: Icon,
  gradient,
  iconBg = "bg-white/20",
  delay = 0,
}: KPICardProps) {
  const isPositive = change && change > 0;
  const isNegative = change && change < 0;

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay }}
      className={cn(
        "relative overflow-hidden rounded-2xl p-6 text-white shadow-lg",
        gradient
      )}
    >
      {/* Background pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white" />
        <div className="absolute -bottom-4 -left-4 h-24 w-24 rounded-full bg-white" />
      </div>

      <div className="relative flex items-start justify-between">
        <div className="space-y-2">
          <p className="text-sm font-medium text-white/80">{title}</p>
          <p className="text-3xl font-bold tracking-tight">{value}</p>
          {change !== undefined && (
            <div className="flex items-center gap-1.5">
              <span
                className={cn(
                  "text-sm font-semibold",
                  isPositive && "text-emerald-200",
                  isNegative && "text-red-200"
                )}
              >
                {isPositive ? "+" : ""}
                {change}%
              </span>
              <span className="text-xs text-white/60">{changeLabel}</span>
            </div>
          )}
        </div>
        <div className={cn("rounded-xl p-3", iconBg)}>
          <Icon className="h-6 w-6" />
        </div>
      </div>
    </motion.div>
  );
}
