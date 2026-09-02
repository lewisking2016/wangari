/**
 * Client-side JWT auth utility.
 * Stores the token in localStorage and provides helpers for login/register/logout.
 */

const TOKEN_KEY = "wangari_token";
const USER_KEY = "wangari_user";

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role?: string;
  farmId?: number | null;
}

export interface AuthResponse {
  token: string;
  user: AuthUser;
  farmId?: number | null;
  farm?: { id: number; name: string };
}

// ─── Token Management ─────────────────────────────────────

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token);
}

export function removeToken(): void {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

// ─── User Management ──────────────────────────────────────

export function getUser(): AuthUser | null {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem(USER_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

export function setUser(user: AuthUser): void {
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function isLoggedIn(): boolean {
  return !!getToken();
}

// ─── Auth Actions ─────────────────────────────────────────

const BACKEND = process.env.NEXT_PUBLIC_BACKEND_URL || "";

export async function login(email: string, password: string): Promise<AuthResponse> {
  const res = await fetch(`${BACKEND}/api/auth/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password }),
  });

  const data = await res.json();
  if (!res.ok) throw new Error(data.error || "Login failed");

  setToken(data.token);
  setUser(data.user);
  return data;
}

export async function register(
  name: string,
  email: string,
  password: string,
  phone?: string
): Promise<AuthResponse> {
  const res = await fetch(`${BACKEND}/api/auth/register`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ name, email, password, phone }),
  });

  const data = await res.json();
  if (!res.ok) throw new Error(data.error || "Registration failed");

  setToken(data.token);
  setUser(data.user);
  return data;
}

export function logout(): void {
  removeToken();
  if (typeof window !== "undefined") {
    window.location.href = "/login";
  }
}
