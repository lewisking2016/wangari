import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Public routes that don't need auth
  const publicPaths = ["/", "/login", "/register", "/pricing", "/about", "/api/auth", "/_next"];
  const isPublic = publicPaths.some((p) => pathname.startsWith(p));

  // For now, allow all requests (auth will be enforced when PostgreSQL is connected)
  // TODO: Re-enable when NextAuth is fully configured with Prisma adapter
  // const session = request.cookies.get("next-auth.session-token");
  // if (!session && !isPublic) {
  //   const loginUrl = new URL("/login", request.url);
  //   loginUrl.searchParams.set("callbackUrl", pathname);
  //   return NextResponse.redirect(loginUrl);
  // }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/((?!_next/static|_next/image|favicon.ico|.*\.(?:svg|png|jpg|jpeg|gif|webp)$).*)",
  ],
};
