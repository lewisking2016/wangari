import { cn } from "@/lib/utils";
import { Package } from "lucide-react";

interface EmptyStateProps {
  icon?: React.ReactNode;
  title: string;
  description: string;
  action?: React.ReactNode;
  className?: string;
}

export function EmptyState({ icon, title, description, action, className }: EmptyStateProps) {
  return (
    <div className={cn("flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-[#E5E7EB] bg-[#FAFBFC] py-12 px-6 text-center", className)}>
      <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#F0FDF4] text-[#166534] mb-4">
        {icon || <Package className="h-8 w-8" />}
      </div>
      <h3 className="text-base font-bold text-[#0F172A]">{title}</h3>
      <p className="mt-1 max-w-xs text-sm text-[#94A3B8]">{description}</p>
      {action && <div className="mt-5">{action}</div>}
    </div>
  );
}
