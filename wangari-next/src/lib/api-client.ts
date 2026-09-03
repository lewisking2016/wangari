/**
 * Centralized API client.
 * All fetch calls should go through this to ensure JWT auth headers are sent.
 */

import { getToken, logout } from "./auth-client";

const API_BASE = "https://api.wangari.imeantech.com";

interface RequestOptions extends RequestInit {
  json?: unknown;
}

async function request<T = any>(path: string, options: RequestOptions = {}): Promise<T> {
  const { json, ...fetchOptions } = options;

  const headers: Record<string, string> = {
    ...(fetchOptions.headers as Record<string, string>),
  };

  // Add JWT token
  const token = getToken();
  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  // Add JSON body
  if (json !== undefined) {
    headers["Content-Type"] = "application/json";
    fetchOptions.body = JSON.stringify(json);
  }

  const res = await fetch(`${API_BASE}${path}`, {
    ...fetchOptions,
    headers,
  });

  // Handle auth errors — only logout on /api/auth routes, not dashboard data
  if (res.status === 401 && path.startsWith("/api/auth")) {
    logout();
    throw new Error("Session expired. Please login again.");
  }

  // Check content type to catch HTML responses (usually means wrong URL)
  const contentType = res.headers.get("content-type") || "";
  if (contentType.includes("text/html")) {
    throw new Error(
      `[Wangari] API returned HTML instead of JSON. ` +
      `URL: ${path}, Status: ${res.status}`
    );
  }

  const data = await res.json();

  if (!res.ok) {
    throw new Error(data.error || `Request failed: ${res.status}`);
  }

  return data as T;
}

// ─── Typed API helpers ────────────────────────────────────

export const api = {
  get: <T = any>(path: string) => request<T>(path),

  post: <T = any>(path: string, body: unknown) =>
    request<T>(path, { method: "POST", json: body }),

  put: <T = any>(path: string, body: unknown) =>
    request<T>(path, { method: "PUT", json: body }),

  patch: <T = any>(path: string, body: unknown) =>
    request<T>(path, { method: "PATCH", json: body }),

  delete: <T = any>(path: string) =>
    request<T>(path, { method: "DELETE" }),

  upload: async <T = any>(path: string, formData: FormData): Promise<T> => {
    const token = getToken();
    const headers: Record<string, string> = {};
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }
    const res = await fetch(`${API_BASE}${path}`, {
      method: "POST",
      headers,
      body: formData,
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || `Upload failed: ${res.status}`);
    return data as T;
  },
};

export default api;
