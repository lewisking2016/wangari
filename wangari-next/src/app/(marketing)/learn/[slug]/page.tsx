import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { DocReader } from "@/components/learn/DocReader";
import { LEARN_DOCS, getDoc } from "@/lib/learn-library";

/** Public document reader — each guide is its own in-app screen, no external redirects. */

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

  const idx = LEARN_DOCS.findIndex((d) => d.slug === slug);
  const prevDoc = idx > 0 ? LEARN_DOCS[idx - 1] : undefined;
  const nextDoc = idx < LEARN_DOCS.length - 1 ? LEARN_DOCS[idx + 1] : undefined;

  return <DocReader doc={doc} prevDoc={prevDoc} nextDoc={nextDoc} />;
}
