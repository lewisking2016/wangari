"use client";

import * as React from "react";
import { CheckCircle2 } from "lucide-react";

export default function ContactPage() {
  const [form, setForm] = React.useState({ name: "", email: "", phone: "", company: "", message: "" });
  const [busy, setBusy] = React.useState(false);
  const [done, setDone] = React.useState(false);
  const [error, setError] = React.useState("");

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_BACKEND_URL || "https://api.wangari.imeantech.com"}/api/contact`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed to send");
      setDone(true);
      setForm({ name: "", email: "", phone: "", company: "", message: "" });
    } catch (err: any) {
      setError(err?.message || "Failed to send message");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-16">
      <h1 className="text-3xl font-extrabold tracking-tight text-[#0F172A]">Talk to us</h1>
      <p className="mt-2 text-sm text-[#64748B]">
        Questions about Wangari, partnership opportunities, or enterprise hosting — we read every message.
      </p>

      {done ? (
        <div className="mt-8 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
          <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
          <p className="text-sm font-semibold text-emerald-800">Message received — we'll get back to you within one business day.</p>
        </div>
      ) : (
        <form onSubmit={submit} className="mt-8 space-y-4 rounded-2xl border border-[#E5E7EB] bg-white p-6 shadow-sm">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-xs font-semibold text-[#64748B]">Name *</label>
              <input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })}
                className="h-11 w-full rounded-xl border border-[#E5E7EB] px-4 text-sm focus:border-[#166534] focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-[#64748B]">Email *</label>
              <input required type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })}
                className="h-11 w-full rounded-xl border border-[#E5E7EB] px-4 text-sm focus:border-[#166534] focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-[#64748B]">Phone</label>
              <input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })}
                className="h-11 w-full rounded-xl border border-[#E5E7EB] px-4 text-sm focus:border-[#166534] focus:outline-none" />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-[#64748B]">Farm / company</label>
              <input value={form.company} onChange={(e) => setForm({ ...form, company: e.target.value })}
                className="h-11 w-full rounded-xl border border-[#E5E7EB] px-4 text-sm focus:border-[#166534] focus:outline-none" />
            </div>
          </div>
          <div>
            <label className="mb-1 block text-xs font-semibold text-[#64748B]">Message *</label>
            <textarea required rows={5} value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })}
              className="w-full rounded-xl border border-[#E5E7EB] px-4 py-3 text-sm focus:border-[#166534] focus:outline-none" />
          </div>
          {error && <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{error}</div>}
          <button type="submit" disabled={busy}
            className="h-12 w-full rounded-xl bg-[#166534] text-sm font-bold text-white hover:bg-[#14532D] disabled:opacity-60 sm:w-auto sm:px-8">
            {busy ? "Sending…" : "Send message"}
          </button>
        </form>
      )}
    </div>
  );
}
