"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface Contact {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  company: string | null;
  type: string;
  stage: string;
  source: string | null;
  noteCount: number;
  updatedAt: string;
}

const STAGES = [
  { key: "lead", label: "Leads", color: "border-wangari-border" },
  { key: "contacted", label: "Contacted", color: "border-sky-800" },
  { key: "demo", label: "Demo", color: "border-violet-800" },
  { key: "trial", label: "Trial", color: "border-amber-800" },
  { key: "customer", label: "Customer", color: "border-emerald-800" },
  { key: "churned", label: "Churned", color: "border-rose-900" },
];

const TYPE_STYLE: Record<string, string> = {
  lead: "bg-wangari-cream text-wangari-muted",
  partner: "bg-badge-blue-bg text-badge-blue-text",
  customer: "bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200",
};

export default function AdminCrmPage() {
  const [contacts, setContacts] = React.useState<Contact[] | null>(null);
  const [error, setError] = React.useState("");
  const [flash, setFlash] = React.useState("");
  const [showAdd, setShowAdd] = React.useState(false);
  const [form, setForm] = React.useState({ name: "", email: "", phone: "", company: "", type: "lead" });
  const [selected, setSelected] = React.useState<Contact | null>(null);
  const [notes, setNotes] = React.useState<{ id: number; body: string; createdAt: string }[]>([]);
  const [noteBody, setNoteBody] = React.useState("");

  const load = React.useCallback(() => {
    adminApi.get<Contact[]>("/crm/contacts").then(setContacts).catch((e) => setError(e.message));
  }, []);
  React.useEffect(load, [load]);

  async function moveStage(c: Contact, stage: string) {
    try {
      await adminApi.patch(`/crm/contacts/${c.id}`, { stage });
      if (selected?.id === c.id) setSelected({ ...c, stage });
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  async function addContact(e: React.FormEvent) {
    e.preventDefault();
    try {
      await adminApi.post("/crm/contacts", form);
      setForm({ name: "", email: "", phone: "", company: "", type: "lead" });
      setShowAdd(false);
      setFlash("Contact added to pipeline.");
      setTimeout(() => setFlash(""), 4000);
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  async function openNotes(c: Contact) {
    setSelected(c);
    try {
      setNotes(await adminApi.get(`/crm/contacts/${c.id}/notes`));
    } catch (e: any) {
      setError(e.message);
    }
  }

  async function addNote() {
    if (!selected || !noteBody.trim()) return;
    try {
      await adminApi.post(`/crm/contacts/${selected.id}/notes`, { body: noteBody });
      setNoteBody("");
      setNotes(await adminApi.get(`/crm/contacts/${selected.id}/notes`));
      load();
    } catch (e: any) {
      setError(e.message);
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">CRM Pipeline</h1>
          <p className="mt-1 text-sm text-wangari-muted">Leads, partners, and customers. Marketing contact form feeds the Leads column automatically.</p>
        </div>
        <button onClick={() => setShowAdd(!showAdd)} className="rounded-lg bg-wangari-green-800 px-4 py-2 text-sm font-semibold text-wangari-heading hover:bg-wangari-green-900">
          {showAdd ? "Close" : "+ Add contact"}
        </button>
      </div>

      {flash && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash}</div>}
      {error && <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>}

      {showAdd && (
        <form onSubmit={addContact} className="grid grid-cols-2 gap-3 rounded-xl border border-wangari-border bg-white p-5 lg:grid-cols-5">
          <input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Name *"
            className="h-10 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
          <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} placeholder="Email"
            className="h-10 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
          <input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} placeholder="Phone"
            className="h-10 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
          <input value={form.company} onChange={(e) => setForm({ ...form, company: e.target.value })} placeholder="Farm / company"
            className="h-10 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
          <div className="flex gap-2">
            <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}
              className="h-10 flex-1 rounded-lg border border-wangari-border bg-white px-2 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none">
              <option value="lead">Lead</option>
              <option value="partner">Partner</option>
              <option value="customer">Customer</option>
            </select>
            <button type="submit" className="rounded-lg bg-wangari-green-800 px-3 text-sm font-semibold text-wangari-heading hover:bg-wangari-green-900">Add</button>
          </div>
        </form>
      )}

      {!contacts ? (
        <div className="animate-pulse text-sm text-wangari-muted">Loading pipeline…</div>
      ) : (
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-6">
          {STAGES.map((col) => {
            const items = contacts.filter((c) => c.stage === col.key);
            return (
              <div key={col.key} className={`rounded-xl border ${col.color} bg-white p-2.5`}>
                <div className="mb-2 flex items-center justify-between px-1">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-wangari-muted">{col.label}</span>
                  <span className="rounded-full bg-wangari-cream px-1.5 text-[10px] text-wangari-muted">{items.length}</span>
                </div>
                <div className="space-y-2">
                  {items.map((c) => (
                    <div key={c.id} className="rounded-lg border border-wangari-border bg-white p-2.5">
                      <div className="text-xs font-semibold text-wangari-heading">{c.name}</div>
                      {c.company && <div className="truncate text-[10px] text-wangari-subtle">{c.company}</div>}
                      {c.email && <div className="truncate text-[10px] text-wangari-subtle">{c.email}</div>}
                      <div className="mt-1.5 flex items-center justify-between">
                        <span className={`rounded px-1.5 py-0.5 text-[9px] font-bold uppercase ${TYPE_STYLE[c.type] || ""}`}>{c.type}</span>
                        <select
                          value={c.stage}
                          onChange={(e) => moveStage(c, e.target.value)}
                          className="rounded bg-wangari-cream px-1 py-0.5 text-[10px] text-wangari-text focus:outline-none"
                        >
                          {STAGES.map((s) => <option key={s.key} value={s.key}>{s.label}</option>)}
                        </select>
                      </div>
                      <button onClick={() => openNotes(c)} className="mt-1.5 w-full text-left text-[10px] text-wangari-subtle hover:text-emerald-700">
                        {c.noteCount > 0 ? `${c.noteCount} note${c.noteCount > 1 ? "s" : ""}` : "+ note"}
                      </button>
                    </div>
                  ))}
                  {items.length === 0 && <div className="px-1 py-2 text-[10px] text-wangari-subtle">empty</div>}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Notes drawer */}
      {selected && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4" onClick={() => setSelected(null)}>
          <div className="w-full max-w-md rounded-2xl border border-wangari-border bg-white p-6" onClick={(e) => e.stopPropagation()}>
            <div className="mb-1 text-lg font-semibold text-wangari-heading">{selected.name}</div>
            <div className="mb-4 text-xs text-wangari-subtle">{selected.email || "no email"} · {selected.source}</div>
            <div className="mb-3 max-h-60 space-y-2 overflow-y-auto">
              {notes.length === 0 && <div className="text-xs text-wangari-subtle">No notes yet.</div>}
              {notes.map((n) => (
                <div key={n.id} className="rounded-lg bg-wangari-cream px-3 py-2 text-xs text-wangari-text">
                  <div className="mb-0.5 text-[10px] text-wangari-subtle">{new Date(n.createdAt).toLocaleString()}</div>
                  {n.body}
                </div>
              ))}
            </div>
            <div className="flex gap-2">
              <input value={noteBody} onChange={(e) => setNoteBody(e.target.value)} placeholder="Add a note…"
                onKeyDown={(e) => e.key === "Enter" && addNote()}
                className="h-10 flex-1 rounded-lg border border-wangari-border bg-white px-3 text-sm text-wangari-heading placeholder:text-wangari-subtle focus:border-wangari-green-500 focus:outline-none" />
              <button onClick={addNote} disabled={!noteBody.trim()} className="rounded-lg bg-wangari-green-800 px-3 text-sm font-semibold text-wangari-heading hover:bg-wangari-green-900 disabled:opacity-50">Save</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
