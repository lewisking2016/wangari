import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Public routes that don't need auth
  const publicPaths = ["/", "/login", "/register", "/pricing", "/about", "/api/auth", "/_next"];
  const isPublic = publicPaths.some((p) => pathname.startsWith(p));

  // Auth is now handled client-side via JWT in localStorage
  // The VPS backend validates the token on every API request
  return NextResponse.next();
}

export const config = {
  matcher: [
    "/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)",
  ],
};
