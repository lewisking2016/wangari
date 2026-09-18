/**
 * Error-tracking bootstrap for the Express API — two layers, both free:
 *
 *  1. Sentry (Developer tier, 5k errors/mo) — full issue grouping, stack
 *     traces and email alerts to admin@imeantech.com. Active only when
 *     SENTRY_DSN is set; no-ops in local dev.
 *  2. PostHog (existing free plan) — every backend exception is captured
 *     as a `$exception` event so error KPIs appear in the waadmin
 *     analytics dashboard alongside browser errors. Active when
 *     NEXT_PUBLIC_POSTHOG_KEY is set.
 *
 * Express is instrumented with Sentry's request/error handlers and Prisma
 * errors flow through the global error handler in index.ts via captureError().
 */

type SentryModule = any;

let sentryReady: Promise<SentryModule | null> | null = null;

async function loadSentry(): Promise<SentryModule | null> {
  if (!sentryReady) {
    sentryReady = import("@sentry/node")
      .then((m: any) => m?.default || m)
      .catch(() => null);
  }
  return sentryReady;
}

/**
 * Express middleware note: modern @sentry/node v8+ auto-instruments http
 * when Sentry.init runs — no requestHandler/errorHandler middleware needed.
 * captureError() in the global error handler adds request context.
 */

/** Init Sentry. Call at startup — fire-and-forget, no-op without DSN. */
export function initSentry(): void {
  const dsn = process.env.SENTRY_DSN;
  if (!dsn) return; // local dev / not configured — no-op

  loadSentry().then((Sentry) => {
    if (!Sentry) {
      console.warn("[sentry] @sentry/node not installed — error tracking disabled");
      return;
    }

    Sentry.init({
      dsn,
      environment: process.env.NODE_ENV || "development",
      // Developer tier: 5k errors/mo is plenty; sample performance lightly.
      tracesSampleRate: 0.1,
      profilesSampleRate: 0,
      // Don't spam Sentry with expected 4xx noise.
      ignoreErrors: [
        /Unauthorized/i,
        /jwt.*expired/i,
      ],
      beforeSend(event) {
        // Keep PII minimal — never send request bodies (may contain passwords).
        if (event.request?.data) delete event.request.data;
        return event;
      },
    });

    console.log("[sentry] initialized");
  });
}

/** Fire-and-forget exception capture into Sentry + PostHog. */
export function captureError(err: unknown, context?: Record<string, unknown>): void {
  // Sentry
  if (process.env.SENTRY_DSN) {
    loadSentry().then((Sentry) =>
      Sentry?.captureException(err, context ? { extra: context } : undefined)
    );
  }
  // PostHog — same event stream the browser SDK writes to, tagged as backend.
  if (process.env.NEXT_PUBLIC_POSTHOG_KEY) {
    import("./posthog-server.js")
      .then(({ captureBackendError }) => captureBackendError(err, context))
      .catch(() => {});
  }
}

/** Flush pending telemetry before shutdown. */
export async function flushTelemetry(): Promise<void> {
  try {
    const Sentry = await loadSentry();
    await Sentry?.flush?.(2000);
    const { shutdownPosthog } = await import("./posthog-server.js");
    await shutdownPosthog();
  } catch {
    // best effort
  }
}
