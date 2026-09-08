"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { getAdminToken, getAdminSession, clearAdminSession, AdminSession } from "@/lib/admin-client";

/**
 * Admin shell for the super-admin dashboard. UX-only guard: presence of an
 * admin token gates rendering — actual authorization is enforced server-side
 * on every /api/admin request (requireAdmin + role map). A customer or worker
 * token stored in the farm app's keys is ignored entirely here.
 */

const NAV = [
  { section: "Platform", items: [
    { href: "/waadmin", label: "Overview", icon: "📊", end: true },
    { href: "/waadmin/farms", label: "Farms", icon: "🏡" },
    { href: "/waadmin/users", label: "Users", icon: "👥" },
    { href: "/waadmin/billing", label: "Billing", icon: "💳" },
    { href: "/waadmin/plans", label: "Plans & Pricing", icon: "🏷️" },
  ]},
  { section: "Growth", items: [
    { href: "/waadmin/promos", label: "Promo Codes", icon: "🎟️" },
    { href: "/waadmin/crm", label: "CRM Pipeline", icon: "🤝" },
    { href: "/waadmin/announcements", label: "Announcements", icon: "📢" },
  ]},
  { section: "Support", items: [
    { href: "/waadmin/tickets", label: "Tickets", icon: "🎫" },
    { href: "/waadmin/emails", label: "Email Ops", icon: "📧" },
    { href: "/waadmin/audit", label: "Audit Log", icon: "🛡️" },
    { href: "/waadmin/system", label: "System Health", icon: "🖥️" },
  ]},
];

export default function WaAdminLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const [ready, setReady] = React.useState(false);
  const [admin, setAdmin] = React.useState<AdminSession | null>(null);

  const isLoginPage = pathname === "/waadmin/login";

  React.useEffect(() => {
    if (isLoginPage) return; // login renders without the shell
    const token = getAdminToken();
    const session = getAdminSession();
    if (!token || !session) {
      router.replace("/waadmin/login");
      return;
    }
    setAdmin(session);
    setReady(true);
  }, [router, isLoginPage]);

  if (isLoginPage) return <>{children}</>;

  if (!ready || !admin) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-950">
        <div className="animate-pulse text-sm text-slate-400">Checking admin session…</div>
      </div>
    );
  }

  const isActive = (href: string, end?: boolean) =>
    end ? pathname === href : pathname === href || pathname.startsWith(href + "/");

  return (
    <div className="flex min-h-screen bg-slate-950 text-slate-100">
      {/* Sidebar */}
      <aside className="hidden w-60 shrink-0 flex-col border-r border-slate-800 bg-slate-900/60 md:flex">
        <div className="flex items-center gap-2 px-5 py-5">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/15 text-lg">🛡️</div>
          <div>
            <div className="text-sm font-semibold tracking-tight">Wangari Admin</div>
            <div className="text-[11px] uppercase tracking-widest text-slate-500">Mission Control</div>
          </div>
        </div>
        <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-4">
          {NAV.map((group) => (
            <div key={group.section}>
              <div className="px-2 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">{group.section}</div>
              <div className="space-y-1">
                {group.items.map((item) => (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={`flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors ${
                      isActive(item.href, item.end)
                        ? "bg-emerald-500/15 text-emerald-300"
                        : "text-slate-300 hover:bg-slate-800/60 hover:text-white"
                    }`}
                  >
                    <span className="text-base leading-none">{item.icon}</span>
                    {item.label}
                  </Link>
                ))}
              </div>
            </div>
          ))}
        </nav>
        <div className="border-t border-slate-800 px-4 py-4">
          <div className="flex items-center justify-between gap-2">
            <div className="min-w-0">
              <div className="truncate text-sm font-medium">{admin.name}</div>
              <div className="truncate text-[11px] text-emerald-400">{admin.role.replace("_", " ")}</div>
            </div>
            <button
              onClick={() => { clearAdminSession(); router.replace("/waadmin/login"); }}
              className="rounded-lg px-2.5 py-1.5 text-xs text-slate-400 hover:bg-slate-800 hover:text-white"
            >
              Sign out
            </button>
          </div>
        </div>
      </aside>

      {/* Main */}
      <div className="flex min-w-0 flex-1 flex-col">
        {/* Mobile topbar */}
        <div className="flex items-center justify-between border-b border-slate-800 px-4 py-3 md:hidden">
          <div className="text-sm font-semibold">Wangari Admin</div>
          <button
            onClick={() => { clearAdminSession(); router.replace("/waadmin/login"); }}
            className="text-xs text-slate-400 hover:text-white"
          >
            Sign out
          </button>
        </div>
        <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">{children}</main>
      </div>
    </div>
  );
}
