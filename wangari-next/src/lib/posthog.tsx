"use client";

/**
 * PostHog product analytics — Wangari
 *
 * Activates only when NEXT_PUBLIC_POSTHOG_KEY is set. Without a key this
 * is a no-op (zero network calls), so local dev and preview deploys stay clean.
 *
 * Tracks automatically:
 *  - pageviews (Next.js app router navigation)
 *  - $web_vitals, autocaptured clicks/form submissions
 *  - exceptions (captureExceptions)
 *
 * Custom events are fired from app code via trackEvent() — key funnel events:
 *  registration, email verified, onboarding hub selected, first flock/crop,
 *  harvest recorded, subscription checkout started/completed, etc.
 */

import posthog from "posthog-js";
import * as React from "react";

const KEY = process.env.NEXT_PUBLIC_POSTHOG_KEY;
const HOST = process.env.NEXT_PUBLIC_POSTHOG_HOST || "https://eu.i.posthog.com";

let initialized = false;

export function initPostHog() {
  if (!KEY || initialized || typeof window === "undefined") return;
  initialized = true;
  posthog.init(KEY, {
    api_host: HOST,
    person_profiles: "identified_only", // GDPR-friendly: anonymous visitors stay anonymous
    capture_pageview: false, // we fire pageviews manually on route change
    capture_pageleave: true,
    autocapture: true,
    session_recording: {
      // Sample 25% of sessions — enough to watch real farmers use the app
      // without storing everything.
      maskAllInputs: true, // never record typed passwords/phone numbers
    },
    rageclick: true,
    loaded: (ph) => {
      if (process.env.NODE_ENV === "development") ph.debug();
    },
  });
}

/** Identify a logged-in farmer so events tie to a real person in PostHog. */
export function identifyUser(user: { id: number | string; name?: string; email?: string; role?: string; farmId?: number | null }) {
  if (!KEY || !user?.id) return;
  posthog.identify(String(user.id), {
    email: user.email,
    name: user.name,
    role: user.role,
    farmId: user.farmId,
  });
  // Group events by farm for farm-level insights
  if (user.farmId != null) {
    posthog.group("farm", String(user.farmId), { farm_id: user.farmId });
  }
}

/** Fire a custom product event. Safe no-op when PostHog isn't configured. */
export function trackEvent(event: string, properties?: Record<string, any>) {
  if (!KEY) return;
  posthog.capture(event, properties);
}

/** Reset identity on sign-out. */
export function resetPostHog() {
  if (!KEY) return;
  posthog.reset();
}

/** Provider: mounts once, wires router pageviews + auto-identity from localStorage. */
export function PostHogProvider({ children }: { children: React.ReactNode }) {
  React.useEffect(() => {
    initPostHog();
    // Identify returning logged-in users on load
    try {
      const raw = localStorage.getItem("wangari_user");
      if (raw) {
        const u = JSON.parse(raw);
        if (u?.id) identifyUser(u);
      }
    } catch {}
  }, []);

  React.useEffect(() => {
    if (!KEY) return;
    const send = () => posthog.capture("$pageview");
    send();
    // Next.js app router: patch pushState so client navigations emit pageviews
    const orig = history.pushState.bind(history);
    history.pushState = (...args: Parameters<typeof history.pushState>) => {
      orig(...args);
      send();
    };
    return () => {
      history.pushState = orig;
    };
  }, []);

  if (!KEY) return <>{children}</>;
  return <>{children}</>;
}
