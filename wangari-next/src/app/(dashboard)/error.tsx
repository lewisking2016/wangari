"use client";

import { useEffect } from "react";

export default function DashboardError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error("Page error:", error);
  }, [error]);

  return (
    <div className="flex items-center justify-center min-h-[60vh] p-6">
      <div className="max-w-md w-full text-center space-y-4">
        <div className="text-5xl">🌱</div>
        <h2 className="text-xl font-bold text-[#0F172A]">Something went wrong</h2>
        <p className="text-sm text-[#64748B]">
          This page hit an unexpected error. Your data is safe — nothing was lost.
        </p>
        <div className="flex gap-2 justify-center">
          <button
            onClick={reset}
            className="px-4 py-2 rounded-xl bg-[#166534] text-white text-sm font-bold hover:bg-[#14532D] cursor-pointer"
          >
            Try again
          </button>
          <button
            onClick={() => (window.location.href = "/dashboard")}
            className="px-4 py-2 rounded-xl bg-[#F1F5F9] text-[#334155] text-sm font-bold hover:bg-[#E2E8F0] cursor-pointer"
          >
            Go to Dashboard
          </button>
        </div>
      </div>
    </div>
  );
}
