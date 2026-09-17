/*
 * Wangari Farm OS — offline service worker.
 *
 * Strategy:
 *  - App shell + icons + fonts: cache-first (instant loads, works offline)
 *  - Navigations (pages):       network-first, fall back to cache, then /offline
 *  - API GETs:                  network-first with cache backup — farmers see
 *                               last-known data when connectivity drops
 *  - API writes:                never cached
 *
 * Version bump clears old caches on deploy.
 */

const VERSION = "wangari-v1";
const SHELL_CACHE = `${VERSION}-shell`;
const PAGE_CACHE = `${VERSION}-pages`;
const API_CACHE = `${VERSION}-api`;

const SHELL_ASSETS = [
  "/offline",
  "/icons/icon-192.png",
  "/icons/icon-512.png",
  "/icons/maskable-192.png",
  "/icons/maskable-512.png",
  "/icons/apple-touch-icon.png",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

function isApiGet(request) {
  return request.method === "GET" && new URL(request.url).pathname.startsWith("/api/");
}

self.addEventListener("fetch", (event) => {
  const { request } = event;
  if (request.method !== "GET") return;

  const url = new URL(request.url);

  // Never cache cross-origin (PostHog etc.) or non-GET.
  if (url.origin !== self.location.origin) return;

  // ── API GETs: network-first, cache backup (stale data beats no data offline) ──
  if (isApiGet(request)) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(API_CACHE).then((cache) => cache.put(request, clone));
          }
          return response;
        })
        .catch(() =>
          caches.match(request).then(
            (cached) =>
              cached ||
              new Response(JSON.stringify({ offline: true, error: "No cached data for this request" }), {
                status: 503,
                headers: { "Content-Type": "application/json" },
              })
          )
        )
    );
    return;
  }

  // ── Page navigations: network-first → cache → /offline ──
  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(PAGE_CACHE).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(async () => {
          const cached = await caches.match(request);
          if (cached) return cached;
          const offline = await caches.match("/offline");
          return offline || new Response("You are offline", { status: 503, headers: { "Content-Type": "text/plain" } });
        })
    );
    return;
  }

  // ── Static assets (/_next/static, images, icons): cache-first ──
  if (
    url.pathname.startsWith("/_next/static/") ||
    url.pathname.startsWith("/icons/") ||
    url.pathname.startsWith("/images/") ||
    /\.(png|jpg|jpeg|svg|webp|ico|woff2?|mp4)$/.test(url.pathname)
  ) {
    event.respondWith(
      caches.match(request).then(
        (cached) =>
          cached ||
          fetch(request).then((response) => {
            if (response.ok) {
              const clone = response.clone();
              caches.open(SHELL_CACHE).then((cache) => cache.put(request, clone));
            }
            return response;
          })
      )
    );
  }
});
