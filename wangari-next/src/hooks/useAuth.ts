"use client";

import { getUser, logout, isLoggedIn } from "@/lib/auth-client";

export function useAuth() {
  const user = getUser();

  return {
    user,
    role: user?.role,
    isAuthenticated: isLoggedIn(),
    isLoading: false,
    signOut: logout,
  };
}
