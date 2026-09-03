/**
 * Proxies API requests to the Express backend server.
 * Used because Vercel can't reach PostgreSQL directly (Azure NSG blocks port 5432).
 */

const BACKEND_URL = process.env.BACKEND_URL || "https://api.wangari.imeantech.com";

export async function proxyToBackend(
  path: string,
  options: RequestInit = {}
): Promise<Response> {
  const url = `${BACKEND_URL}${path}`;

  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    ...(options.headers as Record<string, string> || {}),
  };

  // Forward Authorization header if present
  // (useful for authenticated proxy calls)

  const res = await fetch(url, {
    ...options,
    headers,
  });

  return res;
}
