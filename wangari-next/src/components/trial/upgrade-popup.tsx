"use client";

import * as React from "react";
import Link from "next/link";
import { X, Lock, Check, ArrowRight } from "lucide-react";

interface UpgradePopupProps {
  open: boolean;
  onClose: () => void;
  moduleName?: string;
}

const plans = [
  {
    name: "Starter",
    price: "KES 1,500",
    period: "/month",
    features: ["1 hub of choice", "Inventory tracking", "WhatsApp bot", "Basic reports"],
  },
  {
    name: "Growth",
    price: "KES 4,500",
    period: "/month",
    popular: true,
    features: ["3 hubs of choice", "Inventory tracking", "AI assistant", "Advanced reports + PDF"],
  },
  {
    name: "Enterprise",
    price: "KES 12,000",
    period: "/month",
    features: ["All 6 hubs", "Individual hosting", "Priority support", "Custom integrations"],
  },
];

export function UpgradePopup({ open, onClose, moduleName }: UpgradePopupProps) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" onClick={onClose}>
      <div
        className="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="relative bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] px-6 py-8 text-center">
          <button
            onClick={onClose}
            className="absolute top-4 right-4 text-white/60 hover:text-white transition-colors cursor-pointer"
          >
            <X className="h-5 w-5" />
          </button>
          <div className="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 mx-auto mb-4">
            <Lock className="h-7 w-7 text-white" />
          </div>
          <h2 className="text-xl font-extrabold text-white">
            {moduleName ? `"${moduleName}" requires a plan` : "Upgrade your plan"}
          </h2>
          <p className="text-white/60 text-sm mt-2">
            Choose a plan to unlock this module and many more features.
          </p>
        </div>

        {/* Plans */}
        <div className="p-6 space-y-3">
          {plans.map((plan) => (
            <div
              key={plan.name}
              className={`flex items-center justify-between p-4 rounded-xl border-2 transition-all ${
                plan.popular
                  ? "border-[#166534] bg-[#F0FDF4]"
                  : "border-[#E5E7EB] hover:border-[#BBF7D0]"
              }`}
            >
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <p className="text-sm font-bold text-[#0F172A]">{plan.name}</p>
                  {plan.popular && (
                    <span className="text-[9px] font-bold text-[#166534] bg-[#F0FDF4] px-2 py-0.5 rounded-full border border-[#BBF7D0]">
                      Popular
                    </span>
                  )}
                </div>
                <p className="text-xs text-[#64748B] mt-0.5">
                  {plan.price}{plan.period}
                </p>
                <div className="flex flex-wrap gap-1 mt-2">
                  {plan.features.slice(0, 3).map((f) => (
                    <span key={f} className="text-[10px] text-[#64748B] bg-[#F1F5F9] px-2 py-0.5 rounded-full">
                      {f}
                    </span>
                  ))}
                </div>
              </div>
              <Link
                href="/pricing"
                onClick={onClose}
                className={`flex items-center gap-1 px-4 py-2 rounded-lg text-xs font-bold transition-colors ${
                  plan.popular
                    ? "bg-[#166534] text-white hover:bg-[#14532D]"
                    : "border border-[#E5E7EB] text-[#0F172A] hover:border-[#166534] hover:text-[#166534]"
                }`}
              >
                View <ArrowRight className="h-3 w-3" />
              </Link>
            </div>
          ))}
        </div>

        {/* Footer */}
        <div className="px-6 pb-6">
          <Link
            href="/pricing"
            onClick={onClose}
            className="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-[#166534] text-white text-sm font-bold hover:bg-[#14532D] transition-colors"
          >
            See All Plans & Pricing
            <ArrowRight className="h-4 w-4" />
          </Link>
        </div>
      </div>
    </div>
  );
}
