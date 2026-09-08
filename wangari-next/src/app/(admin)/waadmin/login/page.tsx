"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { adminApi, setAdminSession } from "@/lib/admin-client";

export default function AdminLoginPage() {
  const router = useRouter();
  const [email, setEmail] = React.useState("");
  const [password, setPassword] = React.useState("");
  const [error, setError] = React.useState("");
  const [busy, setBusy] = React.useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setBusy(true);
    try {
      const res = await adminApi.post<{ token: string; admin: { id: number; name: string; email: string; role: string } }>(
        "/login",
        { email, password }
      );
      setAdminSession(res.token, res.admin);
      router.replace("/waadmin");
    } catch (err: any) {
      setError(err?.message || "Login failed");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
      <div className="w-full max-w-sm">
        <div className="mb-8 text-center">
          <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/15 text-2xl">🛡️</div>
          <h1 className="text-xl font-semibold text-white">Wangari Admin</h1>
          <p className="mt-1 text-sm text-slate-400">Platform mission control — staff only</p>
        </div>
        <form onSubmit={submit} className="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
          <div>
            <label className="mb-1.5 block text-xs font-medium uppercase tracking-wider text-slate-400">Email</label>
            <input
              type="email"
              required
              autoComplete="username"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="h-11 w-full rounded-xl border border-slate-700 bg-slate-800/60 px-4 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
              placeholder="admin@imeantech.com"
            />
          </div>
          <div>
            <label className="mb-1.5 block text-xs font-medium uppercase tracking-wider text-slate-400">Password</label>
            <input
              type="password"
              required
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="h-11 w-full rounded-xl border border-slate-700 bg-slate-800/60 px-4 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
              placeholder="••••••••"
            />
          </div>
          {error && <div className="rounded-lg border border-red-900/60 bg-red-950/40 px-3 py-2 text-xs text-red-300">{error}</div>}
          <button
            type="submit"
            disabled={busy}
            className="h-11 w-full rounded-xl bg-emerald-500 text-sm font-semibold text-slate-950 transition-colors hover:bg-emerald-400 disabled:opacity-60"
          >
            {busy ? "Signing in…" : "Sign in to Admin"}
          </button>
        </form>
        <p className="mt-4 text-center text-[11px] text-slate-500">
          Admin accounts are provisioned internally. Sessions expire after 4 hours.
        </p>
      </div>
    </div>
  );
}
