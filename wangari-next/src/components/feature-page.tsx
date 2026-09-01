"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import { ArrowRight, CheckCircle2, ArrowLeft } from "lucide-react";

const fadeUp = { hidden: { opacity: 0, y: 40 }, visible: { opacity: 1, y: 0, transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.1 } } };

interface FeaturePageProps {
  icon: React.ComponentType<{ className?: string }>;
  badge: string;
  title: string;
  subtitle: string;
  description: string;
  highlights: string[];
  capabilities: { title: string; desc: string }[];
  stats: { value: string; label: string }[];
  testimonial: { name: string; role: string; text: string };
}

export function FeaturePage({ icon: Icon, badge, title, subtitle, description, highlights, capabilities, stats, testimonial }: FeaturePageProps) {
  return (
    <div className="min-h-screen">
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] text-white py-28 md:py-36 px-6">
        <div className="absolute inset-0 pointer-events-none overflow-hidden">
          <div className="absolute -top-20 -right-20 h-[500px] w-[500px] rounded-full bg-[#22C55E]/10 blur-[120px]" />
          <div className="absolute -bottom-20 -left-20 h-[400px] w-[400px] rounded-full bg-[#4ADE80]/8 blur-[100px]" />
        </div>
        <div className="relative mx-auto max-w-5xl text-center">
          <motion.div initial="hidden" animate="visible" variants={stagger}>
            <motion.div variants={fadeUp} className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 backdrop-blur-sm px-4 py-2 text-sm font-medium mb-8">
              <Icon className="h-4 w-4" />
              <span>{badge}</span>
            </motion.div>
            <motion.h1 variants={fadeUp} className="text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight leading-tight">
              {title}
            </motion.h1>
            <motion.p variants={fadeUp} className="mt-6 text-lg md:text-xl text-white/60 max-w-2xl mx-auto leading-relaxed">
              {subtitle}
            </motion.p>
            <motion.div variants={fadeUp} className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
              <Link href="/register" className="group inline-flex items-center gap-3 rounded-full bg-white text-[#166534] px-8 py-4 text-base font-bold hover:bg-[#F0FDF4] transition-all duration-300 shadow-2xl hover:-translate-y-1">
                Start Free
                <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1.5" />
              </Link>
              <Link href="/" className="inline-flex items-center gap-2 rounded-full border border-white/25 px-8 py-4 text-base font-semibold text-white hover:bg-white/10 transition-all duration-300">
                <ArrowLeft className="h-4 w-4" />
                Back to Home
              </Link>
            </motion.div>
          </motion.div>
        </div>
      </section>

      {/* Description */}
      <section className="py-20 px-6">
        <div className="mx-auto max-w-4xl text-center">
          <motion.p initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} className="text-lg text-[#64748B] leading-relaxed">
            {description}
          </motion.p>
        </div>
      </section>

      {/* Stats */}
      <section className="py-16 px-6 bg-[#F0FDF4]">
        <div className="mx-auto max-w-5xl grid grid-cols-2 md:grid-cols-4 gap-8">
          {stats.map((s) => (
            <motion.div key={s.label} initial={{ opacity: 0, scale: 0.9 }} whileInView={{ opacity: 1, scale: 1 }} viewport={{ once: true }} className="text-center">
              <p className="text-3xl md:text-4xl font-extrabold text-[#166534]">{s.value}</p>
              <p className="text-sm text-[#64748B] mt-2">{s.label}</p>
            </motion.div>
          ))}
        </div>
      </section>

      {/* Capabilities */}
      <section className="py-24 px-6">
        <div className="mx-auto max-w-6xl">
          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={stagger} className="grid md:grid-cols-2 gap-8">
            {capabilities.map((c) => (
              <motion.div key={c.title} variants={fadeUp} whileHover={{ y: -4 }} className="rounded-2xl border border-[#E5E7EB] bg-white p-8 hover:shadow-xl hover:border-[#BBF7D0] transition-all duration-300">
                <div className="flex items-start gap-4">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]">
                    <CheckCircle2 className="h-5 w-5" />
                  </div>
                  <div>
                    <h3 className="text-lg font-bold text-[#0F172A]">{c.title}</h3>
                    <p className="mt-2 text-sm text-[#64748B] leading-relaxed">{c.desc}</p>
                  </div>
                </div>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* Highlights */}
      <section className="py-20 px-6 bg-[#FAFBFC]">
        <div className="mx-auto max-w-4xl">
          <motion.h2 initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} className="text-2xl md:text-3xl font-extrabold text-[#0F172A] text-center mb-12">
            Why farmers love this
          </motion.h2>
          <div className="space-y-4">
            {highlights.map((h) => (
              <motion.div key={h} initial={{ opacity: 0, x: -20 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} className="flex items-center gap-4 rounded-xl bg-white border border-[#E5E7EB] p-5">
                <CheckCircle2 className="h-5 w-5 text-[#166534] shrink-0" />
                <span className="text-sm font-medium text-[#334155]">{h}</span>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonial */}
      <section className="py-24 px-6">
        <div className="mx-auto max-w-3xl">
          <motion.div initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} className="rounded-2xl border border-[#E5E7EB] bg-white p-10 text-center">
            <p className="text-lg text-[#334155] leading-relaxed italic mb-6">&ldquo;{testimonial.text}&rdquo;</p>
            <p className="font-bold text-[#0F172A]">{testimonial.name}</p>
            <p className="text-sm text-[#64748B]">{testimonial.role}</p>
          </motion.div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 px-6 bg-gradient-to-br from-[#0B1220] to-[#166534] text-white text-center">
        <div className="mx-auto max-w-3xl">
          <motion.h2 initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} className="text-3xl md:text-4xl font-extrabold tracking-tight">
            Ready to try {title.toLowerCase()}?
          </motion.h2>
          <motion.p initial={{ opacity: 0 }} whileInView={{ opacity: 1 }} viewport={{ once: true }} className="mt-5 text-lg text-white/60">
            Start for free. No credit card required.
          </motion.p>
          <motion.div initial={{ opacity: 0, y: 10 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} className="mt-8">
            <Link href="/register" className="group inline-flex items-center gap-3 rounded-full bg-white text-[#166534] px-8 py-4 text-base font-bold hover:bg-[#F0FDF4] transition-all duration-300 shadow-2xl hover:-translate-y-1">
              Get Started Free
              <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1.5" />
            </Link>
          </motion.div>
        </div>
      </section>
    </div>
  );
}
