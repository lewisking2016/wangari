"use client";

import * as React from "react";
import { Sidebar } from "@/components/layout/sidebar";
import { Topbar } from "@/components/layout/topbar";
import { usePathname } from "next/navigation";
import { ErrorBoundary } from "@/components/error-boundary";

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [sidebarOpen, setSidebarOpen] = React.useState(false);
  const pathname = usePathname();
  const isAI = pathname.startsWith("/ai");

  return (
    <div className="min-h-screen bg-wangari-cream">
      {/* Mobile overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar — hidden on AI page */}
      {!isAI && (
        <div
          className={`fixed inset-y-0 left-0 z-40 w-[260px] transform transition-transform duration-200 lg:translate-x-0 ${
            sidebarOpen ? "translate-x-0" : "-translate-x-full"
          }`}
        >
          <Sidebar />
        </div>
      )}

      {/* Main content */}
      <div className={`${isAI ? "" : "lg:pl-[260px]"} min-h-screen flex flex-col`}>
        {!isAI && <Topbar onMenuToggle={() => setSidebarOpen(!sidebarOpen)} />}
        <main className={`${isAI ? "" : "flex-1 p-4 sm:p-6"}`}><ErrorBoundary>{children}</ErrorBoundary></main>
      </div>
    </div>
  );
}
