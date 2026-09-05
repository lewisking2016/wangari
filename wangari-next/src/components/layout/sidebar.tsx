"use client";

import * as React from "react";
import Link from "next/link";
import { Logo } from "@/components/ui/logo";
import { usePathname } from "next/navigation";
import {
  LayoutDashboard,
  PawPrint,
  ClipboardList,
  DollarSign,
  ShoppingCart,
  Package,
  Users,
  BarChart3,
  Settings,
  Sparkles,
  MessageCircle,
  ChevronDown,
  LogOut,
  Syringe,
  Calculator,
  CloudSun,
  Leaf,
  Lock,
  CreditCard,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuth } from "@/hooks/useAuth";
import { useFarm } from "@/hooks/useFarm";
import { Tractor } from "lucide-react";
import { Avatar } from "@/components/ui/avatar";
import api from "@/lib/api-client";
import { UpgradePopup } from "@/components/trial/upgrade-popup";

interface NavItem {
  label: string;
  href: string;
  icon: React.ReactNode;
}

interface NavGroup {
  title: string;
  items: NavItem[];
}

const MODULE_MAP: Record<string, string> = {
  "Livestock": "module_livestock",
  "Crops": "module_crops",
  "Production": "module_production",
  "Sales": "module_sales",
  "Inventory": "module_inventory",
  "Finances": "module_finances",
  "Workers": "module_workers",
  "Vaccinations": "module_vaccinations",
  "Feed Calculator": "module_feed_calculator",
  "Weather": "module_weather",
  "Reports": "module_reports",
};

const navGroups: NavGroup[] = [
  {
    title: "",
    items: [
      { label: "Home", href: "/dashboard", icon: <LayoutDashboard className="h-5 w-5" /> },
    ],
  },
  {
    title: "My Farm",
    items: [
      { label: "My Animals", href: "/flocks", icon: <PawPrint className="h-5 w-5" /> },
      { label: "My Crops", href: "/crops", icon: <Leaf className="h-5 w-5" /> },
      { label: "Daily Output", href: "/production", icon: <ClipboardList className="h-5 w-5" /> },
      { label: "Health & Vaccines", href: "/vaccinations", icon: <Syringe className="h-5 w-5" /> },
    ],
  },
  {
    title: "Money & Workers",
    items: [
      { label: "Income & Expenses", href: "/finances", icon: <DollarSign className="h-5 w-5" /> },
      { label: "Sales", href: "/sales", icon: <ShoppingCart className="h-5 w-5" /> },
      { label: "Store / Inventory", href: "/inventory", icon: <Package className="h-5 w-5" /> },
      { label: "My Workers", href: "/workers", icon: <Users className="h-5 w-5" /> },
      { label: "Worker Attendance", href: "/attendance", icon: <Users className="h-5 w-5" /> },
      { label: "Worker View", href: "/worker", icon: <ClipboardList className="h-5 w-5" /> },
    ],
  },
  {
    title: "Tools",
    items: [
      { label: "Feed Helper", href: "/feed-calculator", icon: <Calculator className="h-5 w-5" /> },
      { label: "Weather", href: "/weather", icon: <CloudSun className="h-5 w-5" /> },
      { label: "Reports", href: "/reports", icon: <BarChart3 className="h-5 w-5" /> },
      { label: "AI Assistant", href: "/ai", icon: <Sparkles className="h-5 w-5" /> },
      { label: "WhatsApp Bot", href: "/whatsapp", icon: <MessageCircle className="h-5 w-5" /> },
    ],
  },
];

export function Sidebar() {
  const pathname = usePathname();
  const { user, role, signOut } = useAuth();
  const { farm, farms, selectFarm } = useFarm();
  const [showFarmPicker, setShowFarmPicker] = React.useState(false);
  const [moduleSettings, setModuleSettings] = React.useState<Record<string, string>>({});
  const [moduleAccess, setModuleAccess] = React.useState<Record<string, boolean>>({});
  const [lockedModule, setLockedModule] = React.useState<string>("");
  const [trialInfo, setTrialInfo] = React.useState<any>(null);

  React.useEffect(() => {
    api.get("/api/settings").then((d: any) => setModuleSettings(d.settings || {})).catch(() => {});
    // Fetch trial/subscription status
    api.get("/api/trial/status").then((d: any) => {
      setModuleAccess(d.modules || {});
      setTrialInfo(d);
    }).catch(() => {});
  }, []);

  const isModuleEnabled = (key: string) => moduleSettings[key] !== "false";
  const isModuleLocked = (label: string) => {
    const key = label.toLowerCase().replace(/\s+/g, "-");
    return moduleAccess[key] === false;
  };
  const [openGroups, setOpenGroups] = React.useState<Record<string, boolean>>({
    "": true,
    "My Farm": true,
    "Money & Workers": true,
    Tools: true,
  });

  const toggleGroup = (title: string) => {
    setOpenGroups((prev) => ({ ...prev, [title]: !prev[title] }));
  };

  return (
    <aside className="fixed left-0 top-0 z-40 h-screen w-[260px] bg-white border-r border-wangari-border flex flex-col">
      {/* Brand */}
      <div className="flex items-center px-5 py-5 border-b border-wangari-border">
        <Link href="/"><Logo size="lg" /></Link>
      </div>

      {/* Farm Switcher */}
      <div className="px-3 py-3 border-b border-wangari-border">
        <button onClick={() => setShowFarmPicker(!showFarmPicker)} className="flex items-center gap-2 w-full px-3 py-2.5 rounded-xl bg-wangari-green-50 border border-wangari-green-200 hover:bg-wangari-green-100 transition-colors cursor-pointer">
          <Tractor className="h-4 w-4 text-wangari-green-800" />
          <span className="flex-1 text-left text-sm font-semibold text-wangari-green-800 truncate">{farm?.name || "Select Farm"}</span>
          <ChevronDown className={cn("h-3.5 w-3.5 text-wangari-green-800 transition-transform", showFarmPicker && "rotate-180")} />
        </button>
        {showFarmPicker && farms.length > 1 && (
          <div className="mt-1 space-y-0.5">
            {farms.map(f => (
              <button key={f.id} onClick={() => { selectFarm(f.id); setShowFarmPicker(false); }}
                className={cn("w-full text-left px-3 py-2 rounded-lg text-xs font-medium transition-colors cursor-pointer", f.id === farm?.id ? "bg-wangari-green-800 text-white" : "text-wangari-text hover:bg-wangari-green-50")}>{f.name}</button>
            ))}
          </div>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {navGroups.map((group) => (
          <div key={group.title}>
            {group.title && (
              <button
                onClick={() => toggleGroup(group.title)}
                className="flex items-center justify-between w-full px-3 py-2 text-[12px] font-bold text-wangari-text hover:text-wangari-green-800 transition-colors"
              >
                {group.title}
                <ChevronDown
                  className={cn(
                    "h-3.5 w-3.5 transition-transform duration-200",
                    openGroups[group.title] ? "rotate-180" : ""
                  )}
                />
              </button>
            )}
            {openGroups[group.title] && (
              <div className="space-y-0.5">
                {group.items.filter(item => {
                  const moduleKey = MODULE_MAP[item.label];
                  return !moduleKey || isModuleEnabled(moduleKey);
                }).map((item) => {
                  const isActive =
                    item.href === "/dashboard"
                      ? pathname === "/dashboard"
                      : pathname.startsWith(item.href);
                  const locked = isModuleLocked(item.label);
                  return (
                    <Link
                      key={item.href}
                      href={locked ? "#" : item.href}
                      onClick={locked ? (e) => { e.preventDefault(); setLockedModule(item.label); } : undefined}
                      className={cn(
                        "flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150",
                        locked
                          ? "text-wangari-subtle opacity-60 cursor-pointer"
                          : isActive
                            ? "bg-wangari-green-800 text-white shadow-md"
                            : "text-wangari-text hover:bg-wangari-green-50 hover:text-wangari-green-800"
                      )}
                    >
                      {item.icon}
                      <span className="flex-1">{item.label}</span>
                      {locked && <Lock className="h-3.5 w-3.5 text-wangari-subtle" />}
                    </Link>
                  );
                })}
              </div>
            )}
          </div>
        ))}
      </nav>

      {/* Quick links footer */}
      <div className="border-t border-wangari-border px-3 py-2 space-y-0.5">
        <Link href="/" className="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-wangari-muted hover:bg-wangari-green-50 hover:text-wangari-green-800 transition-all">
          <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Back to Website
        </Link>
        <Link href="/subscription" className="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-wangari-muted hover:bg-wangari-green-50 hover:text-wangari-green-800 transition-all">
          <CreditCard className="h-4 w-4" />
          Subscription
        </Link>
        {role === "farm_owner" && (
          <Link href="/admin" className="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-wangari-muted hover:bg-wangari-green-50 hover:text-wangari-green-800 transition-all">
            <BarChart3 className="h-4 w-4" />
            Admin
          </Link>
        )}
        <Link href="/settings" className="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-wangari-muted hover:bg-wangari-green-50 hover:text-wangari-green-800 transition-all">
          <Settings className="h-4 w-4" />
          Settings
        </Link>
      </div>

      {/* User footer */}
      <div className="border-t border-wangari-border p-4 space-y-3">
        <div className="flex items-center gap-3">
          <Avatar name={user?.name || "Admin"} src={user?.avatar || undefined} size="sm" />
          <div className="flex-1 min-w-0">
            <p className="text-sm font-semibold text-wangari-heading truncate">
              {user?.name || "Admin"}
            </p>
            <p className="text-[11px] text-wangari-muted capitalize">
              {role?.replace("_", " ") || "Farm Owner"}
            </p>
          </div>
        </div>
        <button
          onClick={signOut}
          className="flex items-center justify-center gap-2 w-full rounded-xl px-3 py-2.5 text-sm font-semibold text-red-600 bg-red-50 border border-red-100 hover:bg-red-100 transition-colors cursor-pointer"
        >
          <LogOut className="h-4 w-4" />
          Sign Out
        </button>
      </div>
      <UpgradePopup
        open={!!lockedModule}
        onClose={() => setLockedModule("")}
        moduleName={lockedModule}
      />
    </aside>
  );
}
