/**
 * Server-side JWT decode helper.
 * Decodes the JWT payload without verifying the signature (the VPS backend
 * handles verification). Used by Next.js API routes that query Prisma directly.
 */

export interface JwtPayload {
  userId?: number;
  farmId?: number;
  email?: string;
  role?: string;
  iat?: number;
  exp?: number;
}

/**
 * Extract user info from a Bearer token string.
 * Returns null if the token is missing or malformed.
 */
export function decodeToken(authHeader: string | null): JwtPayload | null {
  try {
    if (!authHeader?.startsWith("Bearer ")) return null;
    const token = authHeader.slice(7);
    const payload = token.split(".")[1];
    if (!payload) return null;
    return JSON.parse(Buffer.from(payload, "base64url").toString());
  } catch {
    return null;
  }
}

/**
 * Extract and validate a Bearer token from a Request object.
 * Returns { user, error } — if error is set, return it as a NextResponse.
 */
export function requireAuth(req: Request): { user: JwtPayload; error?: never } | { user?: never; error: Response } {
  const user = decodeToken(req.headers.get("authorization"));
  if (!user?.userId) {
    return {
      error: new Response(
        JSON.stringify({ error: "Unauthorized" }),
        { status: 401, headers: { "Content-Type": "application/json" } }
      ),
    };
  }
  return { user };
}
