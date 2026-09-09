"use client";

import * as React from "react";
import { Globe, Save, Plus, Trash2, RefreshCw, ExternalLink, Eye } from "lucide-react";
import { adminApi } from "@/lib/admin-client";
import { PageHeader, Panel, Loading, ErrorState, GhostButton, FilterPill } from "@/components/admin/ui";
import { Badge } from "@/components/ui/badge";

/**
 * Website module — edit the public marketing pages (Pricing, Contact).
 * Each page's content is a JSON blob in site_content; this editor renders
 * structured form fields for it (no raw JSON editing) with a live preview
 * link. Saving is audited server-side; the public pages pick changes up on
 * their next load.
 */

interface Faq { q: string; a: string }
interface Plan {
  name: string;
  paystackKey: string | null;
  price: number;
  annualPrice: number | null;
  period: string;
  description: string;
  icon: string;
  popular: boolean;
  features: string[];
  cta: string;
  ctaHref: string;
}
interface PricingData {
  heroKicker: string;
  heroTitle: string;
  heroSubtitle: string;
  annualBadge: string;
  plans: Plan[];
  faqs: Faq[];
}
interface ContactData {
  title: string;
  subtitle: string;
  successMessage: string;
  contactEmail: string;
  contactPhone: string;
  location: string;
  hours: string;
}

type PageKey = "pricing" | "contact";

const inputCls =
  "h-10 w-full rounded-xl border border-wangari-border px-3.5 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20";
const areaCls =
  "w-full rounded-xl border border-wangari-border px-3.5 py-2.5 text-sm text-wangari-heading focus:border-wangari-green-500 focus:outline-none focus:ring-2 focus:ring-wangari-green-500/20";

function Field({ label, children, hint }: { label: string; children: React.ReactNode; hint?: string }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-bold uppercase tracking-wider text-wangari-subtle">{label}</span>
      {children}
      {hint && <span className="mt-1 block text-[11px] text-wangari-muted">{hint}</span>}
    </label>
  );
}

// ─── Pricing editor ─────────────────────────────────────────

function PricingEditor({ data, onChange }: { data: PricingData; onChange: (d: PricingData) => void }) {
  const set = (patch: Partial<PricingData>) => onChange({ ...data, ...patch });
  const setPlan = (i: number, patch: Partial<Plan>) => {
    const plans = data.plans.map((p, idx) => (idx === i ? { ...p, ...patch } : p));
    set({ plans });
  };

  return (
    <div className="space-y-6">
      <Panel title="Hero section" description="The heading and intro at the top of the Pricing page">
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Kicker"><input className={inputCls} value={data.heroKicker} onChange={(e) => set({ heroKicker: e.target.value })} /></Field>
          <Field label="Annual badge"><input className={inputCls} value={data.annualBadge} onChange={(e) => set({ annualBadge: e.target.value })} /></Field>
          <div className="sm:col-span-2"><Field label="Title"><input className={inputCls} value={data.heroTitle} onChange={(e) => set({ heroTitle: e.target.value })} /></Field></div>
          <div className="sm:col-span-2"><Field label="Subtitle"><textarea rows={2} className={areaCls} value={data.heroSubtitle} onChange={(e) => set({ heroSubtitle: e.target.value })} /></Field></div>
        </div>
      </Panel>

      {data.plans.map((plan, i) => (
        <Panel key={i} title={`Plan: ${plan.name}`} description="Card shown on the pricing grid">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Name"><input className={inputCls} value={plan.name} onChange={(e) => setPlan(i, { name: e.target.value })} /></Field>
            <Field label="Paystack plan key" hint="Used for checkout routing — null/empty = contact-tier">
              <input className={inputCls} value={plan.paystackKey ?? ""} onChange={(e) => setPlan(i, { paystackKey: e.target.value || null })} />
            </Field>
            <Field label="Monthly price (KES)"><input type="number" className={inputCls} value={plan.price} onChange={(e) => setPlan(i, { price: Number(e.target.value) || 0 })} /></Field>
            <Field label="Annual price (KES)" hint="Empty = no annual option">
              <input type="number" className={inputCls} value={plan.annualPrice ?? ""} onChange={(e) => setPlan(i, { annualPrice: e.target.value ? Number(e.target.value) : null })} />
            </Field>
            <Field label="Period label"><input className={inputCls} value={plan.period} onChange={(e) => setPlan(i, { period: e.target.value })} /></Field>
            <Field label="CTA label"><input className={inputCls} value={plan.cta} onChange={(e) => setPlan(i, { cta: e.target.value })} /></Field>
            <Field label="CTA link" hint="e.g. /register or mailto:sales@imeantech.com"><input className={inputCls} value={plan.ctaHref} onChange={(e) => setPlan(i, { ctaHref: e.target.value })} /></Field>
            <Field label="Icon"><select className={inputCls} value={plan.icon} onChange={(e) => setPlan(i, { icon: e.target.value })}>
              <option value="zap">Zap</option><option value="shield">Shield</option><option value="sparkles">Sparkles</option>
            </select></Field>
            <div className="sm:col-span-2"><Field label="Description"><textarea rows={2} className={areaCls} value={plan.description} onChange={(e) => setPlan(i, { description: e.target.value })} /></Field></div>
            <label className="flex items-center gap-2 text-sm text-wangari-text">
              <input type="checkbox" checked={plan.popular} onChange={(e) => setPlan(i, { popular: e.target.checked })} className="accent-wangari-green-700" />
              Mark as “Most Popular”
            </label>
          </div>

          <div className="mt-4">
            <span className="mb-2 block text-xs font-bold uppercase tracking-wider text-wangari-subtle">Features</span>
            <div className="space-y-2">
              {plan.features.map((f, fi) => (
                <div key={fi} className="flex gap-2">
                  <input className={inputCls} value={f} onChange={(e) => { const features = [...plan.features]; features[fi] = e.target.value; setPlan(i, { features }); }} />
                  <button
                    onClick={() => setPlan(i, { features: plan.features.filter((_, x) => x !== fi) })}
                    className="shrink-0 rounded-xl px-3 text-badge-red-text hover:bg-badge-red-bg"
                    title="Remove feature"
                  ><Trash2 className="h-4 w-4" /></button>
                </div>
              ))}
              <GhostButton onClick={() => setPlan(i, { features: [...plan.features, ""] })}><Plus className="h-3.5 w-3.5" /> Add feature</GhostButton>
            </div>
          </div>
        </Panel>
      ))}

      <Panel title="FAQs" description="Questions under the pricing grid">
        <div className="space-y-4">
          {data.faqs.map((faq, i) => (
            <div key={i} className="rounded-xl bg-wangari-cream p-4">
              <div className="flex items-center justify-between gap-2 pb-2">
                <span className="text-xs font-bold uppercase tracking-wider text-wangari-subtle">FAQ {i + 1}</span>
                <button onClick={() => set({ faqs: data.faqs.filter((_, x) => x !== i) })} className="rounded-lg p-1.5 text-badge-red-text hover:bg-badge-red-bg" title="Remove FAQ">
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
              <div className="space-y-2">
                <input className={inputCls} placeholder="Question" value={faq.q} onChange={(e) => { const faqs = [...data.faqs]; faqs[i] = { ...faq, q: e.target.value }; set({ faqs }); }} />
                <textarea rows={2} className={areaCls} placeholder="Answer" value={faq.a} onChange={(e) => { const faqs = [...data.faqs]; faqs[i] = { ...faq, a: e.target.value }; set({ faqs }); }} />
              </div>
            </div>
          ))}
          <GhostButton onClick={() => set({ faqs: [...data.faqs, { q: "", a: "" }] })}><Plus className="h-3.5 w-3.5" /> Add FAQ</GhostButton>
        </div>
      </Panel>
    </div>
  );
}

// ─── Contact editor ─────────────────────────────────────────

function ContactEditor({ data, onChange }: { data: ContactData; onChange: (d: ContactData) => void }) {
  const set = (patch: Partial<ContactData>) => onChange({ ...data, ...patch });
  return (
    <Panel title="Contact page" description="Heading, intro, and the contact-detail cards shown above the form">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2"><Field label="Title"><input className={inputCls} value={data.title} onChange={(e) => set({ title: e.target.value })} /></Field></div>
        <div className="sm:col-span-2"><Field label="Subtitle"><textarea rows={2} className={areaCls} value={data.subtitle} onChange={(e) => set({ subtitle: e.target.value })} /></Field></div>
        <Field label="Contact email"><input type="email" className={inputCls} value={data.contactEmail} onChange={(e) => set({ contactEmail: e.target.value })} /></Field>
        <Field label="Contact phone" hint="Leave empty to hide the card"><input className={inputCls} value={data.contactPhone} onChange={(e) => set({ contactPhone: e.target.value })} /></Field>
        <Field label="Location" hint="Leave empty to hide"><input className={inputCls} value={data.location} onChange={(e) => set({ location: e.target.value })} /></Field>
        <Field label="Hours" hint="e.g. Mon–Fri, 8am–5pm EAT"><input className={inputCls} value={data.hours} onChange={(e) => set({ hours: e.target.value })} /></Field>
        <div className="sm:col-span-2"><Field label="Success message" hint="Shown after the form is sent"><input className={inputCls} value={data.successMessage} onChange={(e) => set({ successMessage: e.target.value })} /></Field></div>
      </div>
    </Panel>
  );
}

// ─── Page ───────────────────────────────────────────────────

const DEFAULT_PRICING: PricingData = {
  heroKicker: "Pricing", heroTitle: "", heroSubtitle: "", annualBadge: "Save 17%", plans: [], faqs: [],
};
const DEFAULT_CONTACT: ContactData = {
  title: "Talk to us", subtitle: "", successMessage: "", contactEmail: "sales@imeantech.com", contactPhone: "", location: "", hours: "",
};

export default function AdminWebsitePage() {
  const [page, setPage] = React.useState<PageKey>("pricing");
  const [pricing, setPricing] = React.useState<PricingData | null>(null);
  const [contact, setContact] = React.useState<ContactData | null>(null);
  const [meta, setMeta] = React.useState<Record<string, string | null>>({});
  const [error, setError] = React.useState("");
  const [notice, setNotice] = React.useState("");
  const [busy, setBusy] = React.useState(false);
  const [dirty, setDirty] = React.useState(false);

  const load = React.useCallback(async () => {
    try {
      const r = await adminApi.get<{ pages: Record<string, { data: unknown; updatedAt: string | null }> }>("/site-content");
      const p = (r.pages.pricing?.data as PricingData) || null;
      const c = (r.pages.contact?.data as ContactData) || null;
      setPricing(p ? { ...DEFAULT_PRICING, ...p } : DEFAULT_PRICING);
      setContact(c ? { ...DEFAULT_CONTACT, ...c } : DEFAULT_CONTACT);
      setMeta({ pricing: r.pages.pricing?.updatedAt ?? null, contact: r.pages.contact?.updatedAt ?? null });
      setDirty(false);
      setError("");
    } catch (e: any) {
      setError(e.message);
    }
  }, []);

  React.useEffect(() => { load(); }, [load]);

  async function save() {
    setBusy(true); setError(""); setNotice("");
    try {
      const body = page === "pricing" ? pricing : contact;
      await adminApi.put(`/site-content/${page}`, { data: body });
      setNotice(page === "pricing" ? "Pricing page updated — live on the website now." : "Contact page updated — live on the website now.");
      setDirty(false);
      load();
    } catch (e: any) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  if (!pricing || !contact) return <Panel><Loading label="Loading website content…" /></Panel>;

  return (
    <div className="space-y-6">
      <PageHeader
        icon={<Globe className="h-5 w-5" />}
        title="Website"
        description="Edit the public marketing pages — changes go live immediately on save"
        actions={
          <>
            <a
              href={page === "pricing" ? "/pricing" : "/contact"}
              target="_blank"
              rel="noreferrer"
              className="inline-flex h-8 items-center gap-1.5 rounded-xl border border-wangari-border bg-white px-2.5 text-xs font-medium text-wangari-text hover:bg-wangari-green-50"
            >
              <Eye className="h-3.5 w-3.5" /> View page <ExternalLink className="h-3 w-3" />
            </a>
            <button
              onClick={save}
              disabled={busy || !dirty}
              className="inline-flex h-8 items-center gap-1.5 rounded-xl bg-wangari-green-800 px-3 text-xs font-semibold text-white shadow-md hover:bg-wangari-green-900 disabled:opacity-50"
            >
              <Save className="h-3.5 w-3.5" /> {busy ? "Saving…" : dirty ? "Save & publish" : "Saved"}
            </button>
          </>
        }
      />

      {error && <ErrorState message={error} />}
      {notice && (
        <div className="rounded-xl border border-wangari-border bg-wangari-green-50 px-4 py-3 text-sm font-medium text-wangari-green-800">{notice}</div>
      )}

      <div className="flex flex-wrap items-center gap-2">
        {(["pricing", "contact"] as PageKey[]).map((p) => (
          <FilterPill key={p} active={page === p} onClick={() => { setPage(p); setNotice(""); }}>
            {p === "pricing" ? "Pricing page" : "Contact page"}
            {meta[p] && <span className="ml-1.5 text-[10px] font-normal opacity-70">edited</span>}
          </FilterPill>
        ))}
        {meta[page] && (
          <Badge variant="outline">last published {new Date(meta[page]!).toLocaleString()}</Badge>
        )}
        <GhostButton onClick={load} className="ml-auto h-8 px-2.5 text-xs"><RefreshCw className="h-3.5 w-3.5" /> Reload</GhostButton>
      </div>

      {page === "pricing"
        ? <PricingEditor data={pricing} onChange={(d) => { setPricing(d); setDirty(true); }} />
        : <ContactEditor data={contact} onChange={(d) => { setContact(d); setDirty(true); }} />}
    </div>
  );
}
