/**
 * Server-side PostHog client (free EU plan, same project as the browser SDK).
 *
 * Backend exceptions and events are captured here so the waadmin analytics
 * dashboard sees the whole picture: browser errors from the client SDK plus
 * API errors from the server, distinguishable by `$source: "backend"`.
 *
 * Distinct ID "backend-server" keeps server events separate from real users.
 * Never blocks: a PostHog outage must not slow the API (fire-and-forget,
 * errors swallowed).
 */

import { PostHog } from "posthog-node";

const KEY = process.env.NEXT_PUBLIC_POSTHOG_KEY || "";
const HOST = process.env.NEXT_PUBLIC_POSTHOG_HOST || "https://eu.i.posthog.com";

let client: PostHog | null = null;

function getClient(): PostHog | null {
  if (!KEY) return null;
  if (!client) {
    client = new PostHog(KEY, { host: HOST, flushAt: 20, flushInterval: 10_000 });
  }
  return client;
}

export function captureBackendError(err: unknown, context?: Record<string, unknown>): void {
  const ph = getClient();
  if (!ph) return;
  const message = err instanceof Error ? err.message : String(err);
  const stack = err instanceof Error ? err.stack : undefined;
  try {
    ph.capture({
      distinctId: "backend-server",
      event: "$exception",
      properties: {
        $exception_type: err instanceof Error ? err.name : "Error",
        $exception_message: message,
        $exception_stack: stack,
        $exception_source: "backend",
        $source: "backend",
        ...context,
      },
    });
  } catch {
    // never throw from telemetry
  }
}

/** Capture a custom backend product event (e.g. quote responses from public links). */
export function captureBackendEvent(event: string, properties?: Record<string, unknown>): void {
  const ph = getClient();
  if (!ph) return;
  try {
    ph.capture({ distinctId: "backend-server", event, properties: { $source: "backend", ...properties } });
  } catch {
    // never throw from telemetry
  }
}

export async function shutdownPosthog(): Promise<void> {
  try {
    await client?.shutdown();
  } catch {
    // best effort
  }
}
