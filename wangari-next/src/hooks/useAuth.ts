"use client";

import { useSession, signOut } from "next-auth/react";

export function useAuth() {
  const { data: session, status } = useSession();

  return {
    user: session?.user,
    role: (session?.user as Record<string, unknown>)?.role as string | undefined,
    isAuthenticated: status === "authenticated",
    isLoading: status === "loading",
    signOut: () => signOut({ callbackUrl: "/login" }),
  };
}
