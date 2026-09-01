import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

const badgeVariants = cva(
  "inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors",
  {
    variants: {
      variant: {
        default: "bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200",
        success: "bg-wangari-green-100 text-wangari-green-800",
        warning: "bg-badge-yellow-bg text-badge-yellow-text",
        danger: "bg-badge-red-bg text-badge-red-text",
        info: "bg-badge-blue-bg text-badge-blue-text",
        orange: "bg-badge-orange-bg text-badge-orange-text",
        outline: "border border-wangari-border text-wangari-muted bg-transparent",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return (
    <div className={cn(badgeVariants({ variant }), className)} {...props} />
  );
}

export { Badge, badgeVariants };
