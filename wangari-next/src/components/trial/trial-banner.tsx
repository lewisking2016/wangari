"use client";

import * as React from "react";
import Link from "next/link";
import { Clock, AlertTriangle, CreditCard } from "lucide-react";

interface TrialBannerProps {
  trialStatus: "active" | "expired" | "no_trial";
  daysLeft: number;
  subscription: any;
}

export function TrialBanner({ trialStatus, daysLeft, subscription }: TrialBannerProps) {
  const [now, setNow] = React.useState(Date.now());

  // Update countdown every minute
  React.useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 60000);
    return () => clearInterval(timer);
  }, []);

  // Don't show banner if user has an active subscription
  if (subscription?.status === "active") {
    // Show warning if subscription expiring soon
    if (subscription.daysLeft <= 3) {
      return (
        <div className="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <AlertTriangle className="h-5 w-5 text-amber-600" />
            <div>
              <p className="text-sm font-bold text-amber-800">
                Your {subscription.planName} plan expires in {subscription.daysLeft} day{subscription.daysLeft !== 1 ? "s" : ""}
              </p>
              <p className="text-xs text-amber-600">Renew to keep full access to all modules.</p>
            </div>
          </div>
          <Link
            href="/pricing"
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-xs font-bold hover:bg-amber-700 transition-colors"
          >
            <CreditCard className="h-3.5 w-3.5" />
            Renew Plan
          </Link>
        </div>
      );
    }
    return null;
  }

  // Show trial banner
  if (trialStatus === "active" && daysLeft > 0) {
    const isUrgent = daysLeft <= 3;

    return (
      <div className={`rounded-xl px-4 py-3 flex items-center justify-between ${
        isUrgent
          ? "bg-red-50 border border-red-200"
          : "bg-blue-50 border border-blue-200"
      }`}>
        <div className="flex items-center gap-3">
          <Clock className={`h-5 w-5 ${isUrgent ? "text-red-600" : "text-blue-600"}`} />
          <div>
            <p className={`text-sm font-bold ${isUrgent ? "text-red-800" : "text-blue-800"}`}>
              Free trial — {daysLeft} day{daysLeft !== 1 ? "s" : ""} remaining
            </p>
            <p className={`text-xs ${isUrgent ? "text-red-600" : "text-blue-600"}`}>
              {isUrgent
                ? "Your trial is ending soon. Subscribe to keep full access."
                : "All features included. Subscribe anytime to continue after trial."}
            </p>
          </div>
        </div>
        <Link
          href="/pricing"
          className={`flex items-center gap-2 px-4 py-2 rounded-lg text-white text-xs font-bold transition-colors ${
            isUrgent ? "bg-red-600 hover:bg-red-700" : "bg-blue-600 hover:bg-blue-700"
          }`}
        >
          <CreditCard className="h-3.5 w-3.5" />
          Subscribe Now
        </Link>
      </div>
    );
  }

  // Trial expired, no subscription — show paywall banner
  if (trialStatus === "expired" && !subscription) {
    return (
      <div className="rounded-xl bg-red-50 border border-red-200 px-4 py-3 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <AlertTriangle className="h-5 w-5 text-red-600" />
          <div>
            <p className="text-sm font-bold text-red-800">Your free trial has ended</p>
            <p className="text-xs text-red-600">Subscribe to continue using Wangari. Your data remains safe.</p>
          </div>
        </div>
        <Link
          href="/pricing"
          className="flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition-colors"
        >
          <CreditCard className="h-3.5 w-3.5" />
          Subscribe Now
        </Link>
      </div>
    );
  }

  return null;
}
