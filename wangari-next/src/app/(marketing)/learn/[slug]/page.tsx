import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { PublicDocGate } from "./doc-view";
import { LEARN_DOCS, getDoc } from "@/lib/learn-library";

/**
 * Public document route — gated: visitors get a 2-chapter teaser + member
 * upsell; logged-in members get the full live document (rendered inside the
 * dashboard library context).
 */

export function generateStaticParams() {
  return LEARN_DOCS.map((d) => ({ slug: d.slug }));
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const doc = getDoc(slug);
  if (!doc) return { title: "Not found — Wangari Learn" };
  return {
    title: `${doc.title} — Wangari Learn Center`,
    description: doc.summary,
  };
}

export default async function DocPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const doc = getDoc(slug);
  if (!doc) notFound();

  return <PublicDocGate doc={doc} />;
}
