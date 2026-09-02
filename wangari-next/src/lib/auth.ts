/**
 * Server-side auth helper.
 * Since we're now using client-side JWT (localStorage), the server-side auth
 * is handled by the VPS backend. This file provides a minimal shim so
 * existing imports don't break.
 */

export async function auth() {
  // Server-side: no session available (JWT is in localStorage on the client)
  // API routes that need auth should validate the JWT token from the Authorization header
  return null;
}
