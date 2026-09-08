"use client";

import * as React from "react";
import { useRouter, usePathname } from "next/navigation";
import { isWorkerSession, getUser } from "@/lib/auth-client";

/**
 * Redirects worker tokens away from owner-only pages.
 * Owners/guests are unaffected. Renders children while redirecting.
 */
export function WorkerBlock({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();

  React.useEffect(() => {
    if (typeof window === "undefined") return;
    if (isWorkerSession()) {
      router.replace("/worker");
    }
  }, [router, pathname]);

  return <>{children}</>;
}
