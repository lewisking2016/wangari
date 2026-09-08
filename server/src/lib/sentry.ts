/**
 * Sentry bootstrap for the Express API.
 * Active only when SENTRY_DSN is set (staging/production). The npm package
 * @sentry/node is a soft dependency — if not installed, this module no-ops
 * so local dev never requires it.
 *
 * Usage in index.ts (before routes):
 *   import { initSentry, captureError } from "./lib/sentry.js";
 *   initSentry(app);
 * In error handlers:
 *   captureError(err, { path: req.path });
 */
type SentryModule = any;

async function loadSentry(): Promise<SentryModule | null> {
  try {
    // Soft dependency: resolved at runtime, installed only where tracking is wanted.
    const mod = await (new Function("return import('@sentry/node')")() as Promise<SentryModule>);
    return mod?.default || mod;
  } catch {
    return null;
  }
}

export function initSentry(_app?: any): void {
  const dsn = process.env.SENTRY_DSN;
  if (!dsn) return; // local dev — no-op
  loadSentry().then((Sentry) => {
    if (!Sentry) {
      console.warn("[sentry] @sentry/node not installed — error tracking disabled");
      return;
    }
    Sentry.init({ dsn, environment: process.env.NODE_ENV || "development", tracesSampleRate: 0.1 });
    console.log("[sentry] initialized");
  });
}

export function captureError(err: unknown, context?: Record<string, unknown>): void {
  if (!process.env.SENTRY_DSN) return;
  loadSentry().then((Sentry) => Sentry?.captureException(err, { extra: context }));
}
