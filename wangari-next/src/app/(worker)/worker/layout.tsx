"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { Sprout, RefreshCw, LogOut } from "lucide-react";
import { isWorkerSession, getUser, logout } from "@/lib/auth-client";

/**
 * Isolated worker shell — no owner sidebar, no owner navigation.
 * Workers who are not authenticated get bounced to /login.
 * Owner tokens hitting /worker get bounced to /dashboard.
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
      {/* Minimal worker topbar — no farm switcher, no owner nav, no finance links */}
      <header className="sticky top-0 z-40 bg-white border-b border-wangari-border">
        <div className="max-w-2xl mx-auto flex items-center justify-between px-4 h-14">
          <div className="flex items-center gap-2">
            <div className="h-8 w-8 rounded-xl bg-wangari-green-800 flex items-center justify-center">
              <Sprout className="h-4 w-4 text-white" />
            </div>
            <div className="leading-tight">
              <p className="text-sm font-black text-[#0F172A]">Wangari</p>
              <p className="text-[10px] font-bold text-wangari-muted uppercase tracking-wider">Worker Portal</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <span className="text-xs font-extrabold text-[#334155] max-w-[120px] truncate">
              {worker?.name || "Worker"}
            </span>
            <button
              onClick={() => logout()}
              aria-label="Sign out"
              className="p-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors cursor-pointer"
            >
              <LogOut className="h-4 w-4" />
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-2xl mx-auto p-4">{children}</main>
    </div>
  );
}
