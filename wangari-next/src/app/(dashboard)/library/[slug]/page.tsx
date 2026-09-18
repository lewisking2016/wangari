import { notFound } from "next/navigation";
import { DocReader } from "@/components/learn/DocReader";
import { LEARN_DOCS, getDoc } from "@/lib/learn-library";

/**
 * In-dashboard document reader — SAME DocReader component as the public site
 * but rendered inside the dashboard chrome (sidebar + topbar, no marketing
 * navbar). The full member experience: live blocks unlocked, everything
 * readable on OUR screens.
 */

export function generateStaticParams() {
  return LEARN_DOCS.map((d) => ({ slug: d.slug }));
}

export default async function DashboardDocPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const doc = getDoc(slug);
  if (!doc) notFound();

  const idx = LEARN_DOCS.findIndex((d) => d.slug === slug);
  const prevDoc = idx > 0 ? LEARN_DOCS[idx - 1] : undefined;
  const nextDoc = idx < LEARN_DOCS.length - 1 ? LEARN_DOCS[idx + 1] : undefined;

  return <DocReader doc={doc} prevDoc={prevDoc} nextDoc={nextDoc} />;
}
