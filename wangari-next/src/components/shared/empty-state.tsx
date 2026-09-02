import { cn } from "@/lib/utils";

interface EmptyStateProps {
  icon?: React.ReactNode;
  title: string;
  description: string;
  action?: React.ReactNode;
  className?: string;
}

export function EmptyState({
  icon,
  title,
  description,
  action,
  className,
}: EmptyStateProps) {
  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-wangari-border bg-white py-16 px-6 text-center",
        className
      )}
    >
      {icon && (
        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-wangari-green-50 text-wangari-green-600 mb-4">
          {icon}
        </div>
      )}
      <h3 className="text-lg font-bold text-wangari-heading">{title}</h3>
      <p className="mt-1 max-w-sm text-sm text-wangari-muted">{description}</p>
      {action && <div className="mt-6">{action}</div>}
    </div>
  );
}
