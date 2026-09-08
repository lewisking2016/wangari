"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { RefreshCw, LogOut, HardHat } from "lucide-react";
import { isWorkerSession, getUser, logout } from "@/lib/auth-client";
import { Avatar } from "@/components/ui/avatar";

/**
 * Isolated worker shell — no owner sidebar, no owner navigation.
 * Workers who are not authenticated get bounced to /login.
 * Owner tokens hitting /worker get bounced to /dashboard.
 * Desktop header mirrors the owner Topbar (glass blur, avatar, role line).
 */
export default function WorkerLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [checked, setChecked] = React.useState(false);

  React.useEffect(() => {
    if (typeof window === "undefined") return;
    if (!isWorkerSession()) {
      // Not a worker token: owners go to their dashboard, guests to login
      router.replace(getUser() ? "/dashboard" : "/login");
      return;
    }
    setChecked(true);
  }, [router]);

  const worker = getUser();

  if (!checked) {
    return (
      <div className="min-h-screen bg-wangari-cream flex items-center justify-center">
        <RefreshCw className="h-8 w-8 text-[#166534] animate-spin" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-wangari-cream">
      {/* Minimal worker topbar — no farm switcher, no owner nav, no finance links.
          Visual language matches the owner Topbar (glass blur, avatar, role line). */}
      <header className="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-wangari-border px-4 sm:px-6 py-3 flex items-center justify-between gap-2">
        <div className="flex items-center gap-2 sm:gap-3 min-w-0">
          <div className="h-9 w-9 rounded-xl bg-wangari-green-800 flex items-center justify-center shrink-0">
            <HardHat className="h-4.5 w-4.5 text-white" />
          </div>
          <div className="min-w-0 leading-tight">
            <p className="text-[10px] font-semibold uppercase tracking-widest text-wangari-subtle hidden sm:block">
              Wangari
            </p>
            <h1 className="text-base sm:text-lg font-bold text-wangari-heading truncate">Worker Portal</h1>
          </div>
        </div>
        <div className="flex items-center gap-2 sm:gap-3 shrink-0">
          <div className="text-right hidden sm:block">
            <p className="text-sm font-semibold text-wangari-heading max-w-[160px] truncate">
              {worker?.name || "Worker"}
            </p>
            <p className="text-[11px] text-wangari-muted capitalize">Worker</p>
          </div>
          <div className="pl-2 sm:pl-3 border-l border-wangari-border flex items-center gap-2">
            <Avatar name={worker?.name || "Worker"} size="sm" />
            <button
              onClick={() => logout()}
              aria-label="Sign out"
              title="Sign out"
              className="flex items-center justify-center h-9 w-9 rounded-xl bg-red-50 text-red-500 border border-red-100 hover:bg-red-100 transition-colors cursor-pointer"
            >
              <LogOut className="h-4 w-4" />
            </button>
          </div>
        </div>
      </header>

      <main className="w-full max-w-6xl mx-auto p-4 sm:p-6 lg:py-8">{children}</main>
    </div>
  );
}
