import { cn } from "@/lib/utils";

interface KpiCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  change?: string;
  changeType?: "positive" | "negative" | "neutral";
  className?: string;
}

export function KpiCard({
  title,
  value,
  icon,
  change,
  changeType = "neutral",
  className,
}: KpiCardProps) {
  const changeColors = {
    positive: "text-wangari-green-600 bg-wangari-green-50",
    negative: "text-red-600 bg-red-50",
    neutral: "text-wangari-muted bg-gray-50",
  };

  return (
    <div
      className={cn(
        "rounded-2xl border border-wangari-border bg-white p-5 shadow-[0_1px_3px_rgba(0,0,0,0.04)] transition-all duration-300 hover:shadow-[0_4px_12px_rgba(0,0,0,0.06)] hover:-translate-y-0.5",
        className
      )}
    >
      <div className="flex items-start justify-between">
        <div className="flex-1">
          <p className="text-[11px] font-bold uppercase tracking-widest text-wangari-muted">
            {title}
          </p>
          <p className="mt-2 text-3xl font-bold text-wangari-heading font-serif">
            {value}
          </p>
          {change && (
            <p
              className={cn(
                "mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold",
                changeColors[changeType]
              )}
            >
              {change}
            </p>
          )}
        </div>
        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-800">
          {icon}
        </div>
      </div>
    </div>
  );
}
