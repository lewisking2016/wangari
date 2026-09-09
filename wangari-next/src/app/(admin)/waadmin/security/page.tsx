"use client";

import * as React from "react";
import { Lock } from "lucide-react";

/**
 * Admin security settings — TOTP MFA enrollment & management (Phase 4).
 * QR is rendered client-side from the otpauth URI via a QR image service —
 * no dependency added; the secret is also shown for manual entry.
 */

const API_BASE = process.env.NEXT_PUBLIC_BACKEND_URL || "https://api.wangari.imeantech.com";

function useAdminFetch() {
  return React.useCallback(async <T,>(path: string, init?: RequestInit): Promise<T> => {
    const token = localStorage.getItem("wangari_admin_token");
    const res = await fetch(`${API_BASE}/api/admin${path}`, {
      ...init,
      headers: {
        ...(init?.headers as Record<string, string>),
        Authorization: `Bearer ${token}`,
        ...(init?.body ? { "Content-Type": "application/json" } : {}),
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data?.error || `Request failed (${res.status})`);
    return data as T;
  }, []);
}

export default function AdminSecurityPage() {
  const api = useAdminFetch();
  const [enabled, setEnabled] = React.useState<boolean | null>(null);
  const [setup, setSetup] = React.useState<{ secret: string; otpauthUri: string } | null>(null);
  const [code, setCode] = React.useState("");
  const [recoveryCodes, setRecoveryCodes] = React.useState<string[] | null>(null);
  const [saved, setSaved] = React.useState<string[]>([]); // recovery codes saved by user
  const [confirmSaved, setConfirmSaved] = React.useState(false);
  const [disablePassword, setDisablePassword] = React.useState("");
  const [disableCode, setDisableCode] = React.useState("");
  const [error, setError] = React.useState("");
  const [notice, setNotice] = React.useState("");
  const [busy, setBusy] = React.useState(false);

  React.useEffect(() => {
    api<{ enabled: boolean }>("/mfa/status")
      .then((r) => setEnabled(r.enabled))
      .catch((e) => setError(e.message));
  }, [api]);

  async function startSetup() {
    setError(""); setNotice(""); setBusy(true);
    try {
      const r = await api<{ secret: string; otpauthUri: string }>("/mfa/setup", { method: "POST", body: JSON.stringify({}) });
      setSetup(r);
    } catch (e: any) { setError(e.message); } finally { setBusy(false); }
  }

  async function verifyEnable() {
    setError(""); setNotice(""); setBusy(true);
    try {
      const r = await api<{ enabled: boolean; recoveryCodes: string[] }>("/mfa/verify", {
        method: "POST",
        body: JSON.stringify({ code }),
      });
      setEnabled(true);
      setSetup(null);
      setRecoveryCodes(r.recoveryCodes);
      setSaved([]);
      setConfirmSaved(false);
    } catch (e: any) { setError(e.message); } finally { setBusy(false); }
  }

  async function disable() {
    setError(""); setNotice(""); setBusy(true);
    try {
      await api("/mfa/disable", { method: "POST", body: JSON.stringify({ password: disablePassword, code: disableCode }) });
      setEnabled(false);
      setSetup(null);
      setDisablePassword("");
      setDisableCode("");
      setNotice("MFA disabled. You can re-enroll anytime.");
    } catch (e: any) { setError(e.message); } finally { setBusy(false); }
  }

  function markAllSaved() {
    if (recoveryCodes) {
      setSaved(recoveryCodes);
      setRecoveryCodes(null);
      setConfirmSaved(true);
      setNotice("Recovery codes saved. Store them somewhere safe — they are shown only once.");
    }
  }

  const qrSrc = setup ? `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(setup.otpauthUri)}` : null;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold tracking-tight text-wangari-heading">Security</h1>
        <p className="mt-1 text-sm text-wangari-muted">Two-factor authentication for your admin account.</p>
      </div>

      {error && <div className="rounded-xl border border-badge-red-bg bg-red-50 px-4 py-3 text-sm text-badge-red-text">{error}</div>}
      {notice && <div className="rounded-xl border border-wangari-border bg-wangari-green-50 px-4 py-3 text-sm text-wangari-green-800">{notice}</div>}

      {/* Status card */}
      <div className="rounded-2xl border border-wangari-border bg-white p-6 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
        <div className="flex items-start justify-between gap-4">
          <div>
            <div className="flex items-center gap-2">
              <h2 className="text-base font-semibold text-wangari-heading">Authenticator app (TOTP)</h2>
              {enabled !== null && (
                <span className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${enabled ? "bg-badge-green-bg text-badge-green-text" : "bg-badge-yellow-bg text-badge-yellow-text"}`}>
                  {enabled ? "Enabled" : "Not enabled"}
                </span>
              )}
            </div>
            <p className="mt-1 max-w-xl text-sm text-wangari-muted">
              {enabled
                ? "Your admin sign-in requires a 6-digit code from your authenticator app in addition to your password."
                : "Add a second factor to admin sign-in. Works with Google Authenticator, Authy, 1Password, or any TOTP app."}
            </p>
          </div>
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-wangari-green-50 text-wangari-green-700"><Lock className="h-5 w-5" /></div>
        </div>

        {!enabled && !setup && (
          <button
            onClick={startSetup}
            disabled={busy}
            className="mt-4 rounded-xl bg-wangari-green-800 px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:bg-wangari-green-900 disabled:opacity-60"
          >
            {busy ? "Starting…" : "Set up MFA"}
          </button>
        )}

        {/* Enrollment */}
        {setup && (
          <div className="mt-5 space-y-4 border-t border-wangari-border pt-5">
            <div className="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
              {qrSrc && <img src={qrSrc} alt="Scan to add Wangari Admin to your authenticator" className="h-[200px] w-[200px] rounded-xl border border-wangari-border bg-white p-2" />}
              <div className="min-w-0 space-y-2">
                <p className="text-sm font-medium text-wangari-heading">1. Scan with your authenticator app</p>
                <p className="text-xs text-wangari-muted">
                  Can't scan? Enter this secret manually:
                  <code className="ml-1 rounded bg-wangari-green-50 px-1.5 py-0.5 font-mono text-xs text-wangari-green-800">{setup.secret}</code>
                </p>
              </div>
            </div>
            <div>
              <p className="text-sm font-medium text-wangari-heading">2. Enter the 6-digit code to confirm</p>
              <div className="mt-2 flex max-w-xs gap-2">
                <input
                  value={code}
                  onChange={(e) => setCode(e.target.value)}
                  inputMode="numeric"
                  placeholder="000000"
                  className="h-11 flex-1 rounded-xl border border-wangari-border px-4 text-center font-semibold tracking-[0.3em] text-wangari-heading focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20"
                />
                <button
                  onClick={verifyEnable}
                  disabled={busy || code.replace(/\D/g, "").length !== 6}
                  className="h-11 rounded-xl bg-wangari-green-800 px-4 text-sm font-semibold text-white shadow-md hover:bg-wangari-green-900 disabled:opacity-60"
                >
                  {busy ? "Verifying…" : "Enable"}
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Recovery codes — shown once */}
        {recoveryCodes && (
          <div className="mt-5 space-y-3 border-t border-wangari-border pt-5">
            <p className="text-sm font-semibold text-wangari-heading">3. Save your recovery codes now</p>
            <p className="text-xs text-wangari-muted">Each code works once if you lose your device. They will not be shown again.</p>
            <div className="grid w-fit grid-cols-2 gap-x-8 gap-y-1.5 rounded-xl bg-wangari-cream px-6 py-4 font-mono text-sm text-wangari-heading">
              {recoveryCodes.map((c) => <span key={c}>{c}</span>)}
            </div>
            <div className="flex items-center gap-3">
              <button
                onClick={() => navigator.clipboard.writeText(recoveryCodes.join("\n"))}
                className="rounded-xl border border-wangari-border bg-white px-4 py-2 text-sm font-medium text-wangari-text hover:bg-wangari-green-50"
              >
                Copy codes
              </button>
              <label className="flex items-center gap-2 text-xs text-wangari-muted">
                <input type="checkbox" checked={confirmSaved} onChange={(e) => setConfirmSaved(e.target.checked)} className="accent-wangari-green-700" />
                I've stored them somewhere safe
              </label>
              <button
                onClick={markAllSaved}
                disabled={!confirmSaved}
                className="rounded-xl bg-wangari-green-800 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-wangari-green-900 disabled:opacity-60"
              >
                Done
              </button>
            </div>
          </div>
        )}

        {/* Disable */}
        {enabled && (
          <div className="mt-5 space-y-3 border-t border-wangari-border pt-5">
            <p className="text-sm font-semibold text-wangari-heading">Disable MFA</p>
            <p className="text-xs text-wangari-muted">Requires your password and a current authenticator code.</p>
            <div className="flex flex-wrap gap-2">
              <input
                type="password"
                value={disablePassword}
                onChange={(e) => setDisablePassword(e.target.value)}
                placeholder="Current password"
                autoComplete="current-password"
                className="h-11 w-52 rounded-xl border border-wangari-border px-4 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20"
              />
              <input
                value={disableCode}
                onChange={(e) => setDisableCode(e.target.value)}
                inputMode="numeric"
                placeholder="6-digit code"
                className="h-11 w-40 rounded-xl border border-wangari-border px-4 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20"
              />
              <button
                onClick={disable}
                disabled={busy || !disablePassword || !disableCode}
                className="h-11 rounded-xl bg-red-600 px-4 text-sm font-semibold text-white shadow-md hover:bg-red-700 disabled:opacity-60"
              >
                {busy ? "Working…" : "Disable MFA"}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
