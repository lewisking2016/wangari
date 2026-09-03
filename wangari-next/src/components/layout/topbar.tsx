"use client";

import { usePathname } from "next/navigation";
import { Menu, Search, Bell } from "lucide-react";
import { useAuth } from "@/hooks/useAuth";
import { Avatar } from "@/components/ui/avatar";

interface TopbarProps {
  onMenuToggle?: () => void;
}

const pageTitles: Record<string, string> = {
  "/dashboard": "Dashboard",
  "/flocks": "Livestock",
  "/production": "Production",
  "/finances": "Finances",
  "/sales": "Sales",
  "/inventory": "Inventory",
  "/workers": "Workers",
  "/reports": "Reports",
  "/settings": "Settings",
  "/ai": "AI Assistant",
  "/vaccinations": "Vaccinations",
  "/attendance": "Attendance",
  "/feed-calculator": "Feed Calculator",
  "/customers": "Customers",
  "/export": "Export Data",
  "/weather": "Weather",
  "/audit": "Activity Log",
  "/whatsapp": "WhatsApp",
  "/invoices": "Invoices",
};

export function Topbar({ onMenuToggle }: TopbarProps) {
  const pathname = usePathname();
  const { user, role } = useAuth();
  const title = pageTitles[pathname] || "Dashboard";

  return (
    <header className="sticky top-0 z-30 flex items-center justify-between gap-4 bg-white/80 backdrop-blur-xl border-b border-wangari-border px-6 py-3">
      {/* Left */}
      <div className="flex items-center gap-4">
        <button
          onClick={onMenuToggle}
          className="lg:hidden flex items-center justify-center h-9 w-9 rounded-xl bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200 hover:bg-wangari-green-100 transition-colors"
          aria-label="Toggle menu"
        >
          <Menu className="h-5 w-5" />
        </button>
        <div>
          <p className="text-[11px] font-semibold uppercase tracking-widest text-wangari-subtle">
            Wangari
          </p>
          <h1 className="text-lg font-bold text-wangari-heading">{title}</h1>
        </div>
      </div>

      {/* Right */}
      <div className="flex items-center gap-3">
        <button
          className="flex items-center justify-center h-9 w-9 rounded-xl bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200 hover:bg-wangari-green-100 transition-colors"
          aria-label="Search"
        >
          <Search className="h-4 w-4" />
        </button>
        <button
          className="relative flex items-center justify-center h-9 w-9 rounded-xl bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200 hover:bg-wangari-green-100 transition-colors"
          aria-label="Notifications"
        >
          <Bell className="h-4 w-4" />
          <span className="absolute -top-0.5 -right-0.5 h-3.5 w-3.5 rounded-full bg-red-500 text-[8px] font-bold text-white flex items-center justify-center">
            3
          </span>
        </button>
        <div className="hidden sm:flex items-center gap-2 pl-3 border-l border-wangari-border">
          <Avatar name={user?.name || "Admin"} size="sm" />
          <div className="hidden md:block">
            <p className="text-sm font-semibold text-wangari-heading">
              {user?.name || "Admin"}
            </p>
            <p className="text-[11px] text-wangari-muted capitalize">
              {role?.replace("_", " ") || "Farm Owner"}
            </p>
          </div>
        </div>
      </div>
    </header>
  );
}
