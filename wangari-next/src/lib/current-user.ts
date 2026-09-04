import { decodeToken } from "@/lib/jwt";

/**
 * Get the current authenticated user from the Authorization header.
 * Works server-side in API routes.
 */
export async function getCurrentUser(req?: Request) {
  // Try request header first (server-side API routes)
  if (req) {
    const payload = decodeToken(req.headers.get("authorization"));
    if (payload?.userId) {
      return { userId: payload.userId, farmId: payload.farmId ?? null };
    }
  }
  return null;
}
