"use client";

import * as React from "react";
import Link from "next/link";
import { Logo } from "@/components/ui/logo";
import { usePathname } from "next/navigation";
import {
  LayoutDashboard,
  Bird,
  ClipboardList,
  DollarSign,
  ShoppingCart,
  Package,
  Users,
  BarChart3,
  Settings,
  Sparkles,
  ChevronDown,
  LogOut,
  Syringe,
  Clock,
  Calculator,
  UserCheck,
  Download,
  CloudSun,
  History,
  MessageSquare,
  FileText,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuth } from "@/hooks/useAuth";
import { Avatar } from "@/components/ui/avatar";

interface NavItem {
  label: string;
  href: string;
  icon: React.ReactNode;
}

interface NavGroup {
  title: string;
  items: NavItem[];
}

const navGroups: NavGroup[] = [
  {
    title: "Overview",
    items: [
      { label: "Dashboard", href: "/dashboard", icon: <LayoutDashboard className="h-5 w-5" /> },
      { label: "AI Assistant", href: "/ai", icon: <Sparkles className="h-5 w-5" /> },
    ],
  },
  {
    title: "Farm Operations",
    items: [
      { label: "Flocks", href: "/flocks", icon: <Bird className="h-5 w-5" /> },
      { label: "Production", href: "/production", icon: <ClipboardList className="h-5 w-5" /> },
      { label: "Vaccinations", href: "/vaccinations", icon: <Syringe className="h-5 w-5" /> },
      { label: "Feed Calculator", href: "/feed-calculator", icon: <Calculator className="h-5 w-5" /> },
      { label: "Weather", href: "/weather", icon: <CloudSun className="h-5 w-5" /> },
    ],
  },
  {
    title: "Sales & Finance",
    items: [
      { label: "Finances", href: "/finances", icon: <DollarSign className="h-5 w-5" /> },
      { label: "Sales", href: "/sales", icon: <ShoppingCart className="h-5 w-5" /> },
      { label: "Invoices", href: "/invoices", icon: <FileText className="h-5 w-5" /> },
      { label: "Customers", href: "/customers", icon: <UserCheck className="h-5 w-5" /> },
    ],
  },
  {
    title: "Operations",
    items: [
      { label: "Inventory", href: "/inventory", icon: <Package className="h-5 w-5" /> },
      { label: "Workers", href: "/workers", icon: <Users className="h-5 w-5" /> },
      { label: "Attendance", href: "/attendance", icon: <Clock className="h-5 w-5" /> },
    ],
  },
  {
    title: "Communications",
    items: [
      { label: "WhatsApp", href: "/whatsapp", icon: <MessageSquare className="h-5 w-5" /> },
    ],
  },
  {
    title: "System",
    items: [
      { label: "Reports", href: "/reports", icon: <BarChart3 className="h-5 w-5" /> },
      { label: "Activity Log", href: "/audit", icon: <History className="h-5 w-5" /> },
      { label: "Export Data", href: "/export", icon: <Download className="h-5 w-5" /> },
      { label: "Settings", href: "/settings", icon: <Settings className="h-5 w-5" /> },
    ],
  },
];

export function Sidebar() {
  const pathname = usePathname();
  const { user, role, signOut } = useAuth();
  const [openGroups, setOpenGroups] = React.useState<Record<string, boolean>>({
    Overview: true,
    "Farm Operations": true,
    "Sales & Finance": true,
    Operations: true,
    System: true,
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

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {navGroups.map((group) => (
          <div key={group.title}>
            <button
              onClick={() => toggleGroup(group.title)}
              className="flex items-center justify-between w-full px-3 py-2 text-[11px] font-bold uppercase tracking-widest text-wangari-subtle hover:text-wangari-muted transition-colors"
            >
              {group.title}
              <ChevronDown
                className={cn(
                  "h-3.5 w-3.5 transition-transform duration-200",
                  openGroups[group.title] ? "rotate-180" : ""
                )}
              />
            </button>
            {openGroups[group.title] && (
              <div className="space-y-0.5">
                {group.items.map((item) => {
                  const isActive =
                    item.href === "/dashboard"
                      ? pathname === "/dashboard"
                      : pathname.startsWith(item.href);
                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      className={cn(
                        "flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150",
                        isActive
                          ? "bg-wangari-green-800 text-white shadow-md"
                          : "text-wangari-text hover:bg-wangari-green-50 hover:text-wangari-green-800"
                      )}
                    >
                      {item.icon}
                      {item.label}
                    </Link>
                  );
                })}
              </div>
            )}
          </div>
        ))}
      </nav>

      {/* User footer */}
      <div className="border-t border-wangari-border p-4 space-y-3">
        <div className="flex items-center gap-3">
          <Avatar name={user?.name || "Admin"} size="sm" />
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
    </aside>
  );
}
