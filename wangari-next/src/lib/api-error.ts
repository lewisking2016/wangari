import { NextResponse } from "next/server";

/**
 * Safe error response — never leaks internal details to the client.
 */
export function apiError(message: string, status: number = 500): NextResponse {
  return NextResponse.json({ error: message }, { status });
}

/**
 * Log the real error internally, return a safe message to the client.
 */
export function apiCatch(error: unknown, context: string): NextResponse {
  console.error(`${context}:`, error);
  return apiError("Something went wrong. Please try again.", 500);
}
