"use client";

import * as React from "react";
import Link from "next/link";
import { Clock, AlertTriangle, CreditCard, Sparkles } from "lucide-react";

interface TrialBannerProps {
  trialStatus: "active" | "expired" | "no_trial";
  daysLeft: number;
  endsAt?: string | null;
  subscription?: any;
}

export function TrialBanner({ trialStatus, daysLeft, endsAt, subscription }: TrialBannerProps) {
  const [timeLeft, setTimeLeft] = React.useState({ days: daysLeft, hours: 0, minutes: 0, seconds: 0, isExpired: false });

  React.useEffect(() => {
    if (!endsAt) {
      setTimeLeft({ days: daysLeft, hours: 0, minutes: 0, seconds: 0, isExpired: trialStatus === "expired" });
      return;
    }

    const updateTimer = () => {
      const target = new Date(endsAt).getTime();
      const now = Date.now();
      const diff = target - now;

      if (diff <= 0) {
        setTimeLeft({ days: 0, hours: 0, minutes: 0, seconds: 0, isExpired: true });
        return;
      }

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((diff % (1000 * 60)) / 1000);

      setTimeLeft({ days, hours, minutes, seconds, isExpired: false });
    };

    updateTimer();
    const interval = setInterval(updateTimer, 1000);
    return () => clearInterval(interval);
  }, [endsAt, daysLeft, trialStatus]);

  // Active Subscription
  if (subscription?.status === "active") {
    const isExpiringSoon = subscription.daysLeft <= 7;
    if (isExpiringSoon) {
      return (
        <div className="rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-white p-4 sm:p-5 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
              <AlertTriangle className="h-5 w-5 text-white" />
            </div>
            <div>
              <p className="text-sm sm:text-base font-bold">
                Your {subscription.planName || "Active"} plan expires in {subscription.daysLeft} day{subscription.daysLeft !== 1 ? "s" : ""}
              </p>
              <p className="text-xs text-amber-100 mt-0.5">Renew today to maintain uninterrupted access to all modules and automated features.</p>
            </div>
          </div>
          <Link
            href="/subscription"
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-amber-900 text-xs sm:text-sm font-extrabold hover:bg-amber-50 transition-all shadow-md shrink-0 cursor-pointer"
          >
            <CreditCard className="h-4 w-4 text-amber-700" />
            Renew Subscription Now
          </Link>
        </div>
      );
    }
    return null;
  }

  // Active Free Trial — Live Timer (Days, Hours, Minutes, Seconds)
  if (trialStatus === "active" && !timeLeft.isExpired) {
    const isUrgent = timeLeft.days <= 3;

    return (
      <div className={`rounded-2xl p-4 sm:p-5 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-all ${
        isUrgent
          ? "bg-gradient-to-r from-red-600 via-red-500 to-amber-600 text-white"
          : "bg-gradient-to-r from-emerald-800 via-emerald-700 to-teal-800 text-white"
      }`}>
        <div className="flex items-center gap-3.5">
          <div className={`h-11 w-11 rounded-2xl flex items-center justify-center shrink-0 ${
            isUrgent ? "bg-white/20 text-white" : "bg-emerald-600/50 text-emerald-200"
          }`}>
            <Clock className="h-6 w-6 animate-pulse" />
          </div>
          <div>
            <div className="flex items-center gap-2 flex-wrap">
              <span className="text-xs uppercase font-extrabold tracking-wider px-2 py-0.5 rounded-full bg-white/20 text-white">
                Free Trial Active
              </span>
              {/* Live Digital Countdown */}
              <div className="flex items-center gap-1 text-xs font-mono font-bold bg-black/25 px-2.5 py-0.5 rounded-lg text-white border border-white/20">
                <span>{timeLeft.days}d</span>:
                <span>{String(timeLeft.hours).padStart(2, "0")}h</span>:
                <span>{String(timeLeft.minutes).padStart(2, "0")}m</span>:
                <span>{String(timeLeft.seconds).padStart(2, "0")}s</span>
              </div>
            </div>
            <p className="text-sm sm:text-base font-bold mt-1">
              {isUrgent
                ? `Trial ending soon! Only ${timeLeft.days}d ${timeLeft.hours}h left.`
                : `Enjoying Wangari? You have ${timeLeft.days} days remaining on your trial.`}
            </p>
            <p className="text-xs text-white/80 mt-0.5">
              Subscribe anytime to unlock unlimited records and keep full access after trial ends.
            </p>
          </div>
        </div>

        <Link
          href="/subscription"
          className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-[#166534] text-xs sm:text-sm font-extrabold hover:bg-emerald-50 transition-all shadow-md shrink-0 cursor-pointer"
        >
          <Sparkles className="h-4 w-4 text-emerald-600" />
          Subscribe Now
        </Link>
      </div>
    );
  }

  // Trial Expired — Paywall banner
  if (trialStatus === "expired" || timeLeft.isExpired) {
    return (
      <div className="rounded-2xl bg-gradient-to-r from-red-700 to-red-800 text-white p-4 sm:p-5 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div className="flex items-center gap-3.5">
          <div className="h-11 w-11 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
            <AlertTriangle className="h-6 w-6 text-white" />
          </div>
          <div>
            <p className="text-sm sm:text-base font-extrabold">Your Free Trial Has Ended</p>
            <p className="text-xs text-red-100 mt-0.5">
              Subscribe now to reactivate access to your farm records, reports, and team tools. Your data is safely saved.
            </p>
          </div>
        </div>
        <Link
          href="/subscription"
          className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-red-700 text-xs sm:text-sm font-extrabold hover:bg-red-50 transition-all shadow-md shrink-0 cursor-pointer"
        >
          <CreditCard className="h-4 w-4 text-red-700" />
          Subscribe Now
        </Link>
      </div>
    );
  }

  return null;
}
