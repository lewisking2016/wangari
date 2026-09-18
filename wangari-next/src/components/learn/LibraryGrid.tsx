"use client";

import * as React from "react";
import Link from "next/link";
import { motion } from "framer-motion";
import { BookOpen, Clock, Lock, Search, Sparkles } from "lucide-react";
import { LEARN_DOCS, CATEGORIES, type LearnDoc } from "@/lib/learn-library";

/**
 * LibraryGrid — the shared "library shelf" screen.
 *
 * Used by the public /learn (teasers for member-only docs) and the dashboard
 * /learn (everything unlocked). Category filter chips + search. Clicking a
 * card opens OUR document reader — never an external site.
 */

function DocCard({ doc, index, isMember }: { doc: LearnDoc; index: number; isMember: boolean }) {
  const locked = doc.memberOnly && !isMember;
  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: Math.min(index * 0.04, 0.4) }}
    >
      <Link
        href={`/learn/${doc.slug}`}
        className="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white p-5 transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-[0_12px_40px_-12px_rgba(16,185,129,0.35)]"
      >
        {/* spine accent like a book */}
        <div className="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-emerald-500 to-teal-500 opacity-60 transition-opacity group-hover:opacity-100" />

        <div className="flex items-start justify-between">
          <span className="text-3xl">{doc.emoji}</span>
          {locked ? (
            <span className="flex items-center gap-1 rounded-full bg-stone-100 px-2 py-1 text-[10px] font-bold text-stone-500">
              <Lock className="h-3 w-3" /> Members
            </span>
          ) : doc.memberOnly ? (
            <span className="flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-700">
              <Sparkles className="h-3 w-3" /> Member
            </span>
          ) : (
            <span className="rounded-full bg-sky-50 px-2 py-1 text-[10px] font-bold text-sky-600">Free</span>
          )}
        </div>

        <h3 className="mt-3 text-sm font-extrabold leading-snug text-stone-900 group-hover:text-emerald-800">
          {doc.title}
        </h3>
        <p className="mt-1.5 flex-1 text-xs leading-relaxed text-stone-500">
          {locked ? "Full guide inside — varieties, programs and checklists for members. Free account unlocks everything." : doc.summary}
        </p>

        <div className="mt-4 flex items-center gap-3 border-t border-stone-100 pt-3 text-[10px] font-semibold text-stone-400">
          <span className="flex items-center gap-1"><BookOpen className="h-3 w-3" /> {doc.category === "growing-guide" ? "Guide" : doc.category === "farm-type" ? "Farming" : "Rights"}</span>
          <span className="flex items-center gap-1"><Clock className="h-3 w-3" /> {doc.readMinutes} min</span>
          <span className="ml-auto text-emerald-600 opacity-0 transition-opacity group-hover:opacity-100">Read →</span>
        </div>
      </Link>
    </motion.div>
  );
}

export function LibraryGrid({ isMember = false }: { isMember?: boolean }) {
  const [cat, setCat] = React.useState<string>("all");
  const [query, setQuery] = React.useState("");

  const filtered = LEARN_DOCS.filter((d) => {
    const matchCat = cat === "all" || d.category === cat;
    const q = query.trim().toLowerCase();
    const matchQuery = !q || d.title.toLowerCase().includes(q) || d.summary.toLowerCase().includes(q);
    return matchCat && matchQuery;
  });

  return (
    <div>
      {/* Filter + search bar */}
      <div className="mb-6 flex flex-col gap-3 md:flex-row md:items-center">
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setCat("all")}
            className={`rounded-full px-4 py-2 text-xs font-bold transition-all ${
              cat === "all" ? "bg-stone-900 text-white shadow-md" : "bg-white text-stone-600 ring-1 ring-stone-200 hover:ring-emerald-300"
            }`}
          >
            All documents
          </button>
          {CATEGORIES.map((c) => (
            <button
              key={c.id}
              onClick={() => setCat(c.id)}
              className={`rounded-full px-4 py-2 text-xs font-bold transition-all ${
                cat === c.id ? "bg-stone-900 text-white shadow-md" : "bg-white text-stone-600 ring-1 ring-stone-200 hover:ring-emerald-300"
              }`}
            >
              {c.emoji} {c.label}
            </button>
          ))}
        </div>
        <div className="relative md:ml-auto md:w-64">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search the library…"
            className="w-full rounded-full border border-stone-200 bg-white py-2 pl-9 pr-4 text-xs font-medium text-stone-700 outline-none transition-all placeholder:text-stone-400 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
          />
        </div>
      </div>

      {/* Shelf */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {filtered.map((doc, i) => (
          <DocCard key={doc.slug} doc={doc} index={i} isMember={isMember} />
        ))}
      </div>

      {filtered.length === 0 && (
        <p className="py-12 text-center text-sm text-stone-400">No documents match &ldquo;{query}&rdquo;.</p>
      )}
    </div>
  );
}
