"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { getAdminToken, getAdminSession, clearAdminSession, AdminSession } from "@/lib/admin-client";

/**
 * Admin shell for the super-admin dashboard, styled with the same wangari
 * design system as the farm dashboards (light theme, green accents, same
 * card/border tokens). UX-only guard: presence of an admin token gates
 * rendering — authorization is enforced server-side on every /api/admin call.
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
      <div className="flex min-h-screen items-center justify-center bg-wangari-cream">
        <div className="animate-pulse text-sm text-wangari-muted">Checking admin session…</div>
      </div>
    );
  }

  const isActive = (href: string, end?: boolean) =>
    end ? pathname === href : pathname === href || pathname.startsWith(href + "/");

  return (
    <div className="flex min-h-screen bg-wangari-cream text-wangari-text">
      {/* Sidebar — same structure as the farm app's */}
      <aside className="hidden w-[260px] shrink-0 flex-col border-r border-wangari-border bg-white md:sticky md:top-0 md:flex md:h-screen">
        <div className="flex items-center gap-2.5 px-5 py-5">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-wangari-green-50 text-lg">🛡️</div>
          <div>
            <div className="text-sm font-bold tracking-tight text-wangari-heading">Wangari Admin</div>
            <div className="text-[11px] uppercase tracking-widest text-wangari-muted">Mission Control</div>
          </div>
        </div>
        <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-4">
          {NAV.map((group) => (
            <div key={group.section}>
              <div className="px-2 pb-2 text-[11px] font-bold uppercase tracking-widest text-wangari-subtle">{group.section}</div>
              <div className="space-y-1">
                {group.items.map((item) => (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={`flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-colors ${
                      isActive(item.href, item.end)
                        ? "bg-wangari-green-800 text-white shadow-md"
                        : "text-wangari-text hover:bg-wangari-green-50 hover:text-wangari-green-800"
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
        <div className="border-t border-wangari-border px-4 py-4">
          <div className="flex items-center justify-between gap-2">
            <div className="min-w-0">
              <div className="truncate text-sm font-semibold text-wangari-heading">{admin.name}</div>
              <div className="truncate text-[11px] font-medium capitalize text-wangari-green-700">{admin.role.replace("_", " ")}</div>
            </div>
            <button
              onClick={() => { clearAdminSession(); router.replace("/waadmin/login"); }}
              className="rounded-lg px-2.5 py-1.5 text-xs font-medium text-wangari-muted hover:bg-wangari-green-50 hover:text-wangari-green-800"
            >
              Sign out
            </button>
          </div>
        </div>
      </aside>

      {/* Main */}
      <div className="flex min-w-0 flex-1 flex-col">
        {/* Mobile topbar */}
        <div className="sticky top-0 z-30 flex items-center justify-between border-b border-wangari-border bg-white/80 px-4 py-3 backdrop-blur-xl md:hidden">
          <div className="text-sm font-bold text-wangari-heading">Wangari Admin</div>
          <button
            onClick={() => { clearAdminSession(); router.replace("/waadmin/login"); }}
            className="text-xs font-medium text-wangari-muted hover:text-wangari-green-800"
          >
            Sign out
          </button>
        </div>
        <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">{children}</main>
      </div>
    </div>
  );
}
