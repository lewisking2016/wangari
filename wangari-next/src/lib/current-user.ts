import { getUser } from "@/lib/auth-client";

/**
 * Get the current authenticated user from localStorage JWT.
 * This is a client-side helper — use it in React components and client-side API calls.
 */
export async function getCurrentUser() {
  const user = getUser();
  if (!user) return null;
  return { userId: user.id, farmId: user.farmId ?? null };
}
