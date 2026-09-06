"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import { Leaf, Heart, Globe, Shield, ArrowRight, CheckCircle2, Users, Target, Zap } from "lucide-react";

const fadeUp = { hidden: { opacity: 0, y: 30 }, visible: { opacity: 1, y: 0, transition: { duration: 0.6, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.1 } } };

const values = [
  { icon: Leaf, title: "Sustainability", desc: "We believe in farming that sustains both people and the planet. Our tools help reduce waste and optimize resource use." },
  { icon: Heart, title: "Farmer-First", desc: "Built by people who understand African farming. Every feature is designed for real-world conditions — slow internet, basic phones, and busy schedules." },
  { icon: Globe, title: "African Roots", desc: "Named after Prof. Wangari Maathai, Nobel laureate and environmental champion. We carry her vision of empowering communities through sustainable practices." },
  { icon: Shield, title: "Data Privacy", desc: "Your farm data is yours. We use enterprise-grade security and never sell your information to third parties." },
];

const milestones = [
  { year: "2026", title: "Wangari Founded", desc: "iMeanTech begins building a farm management platform designed specifically for African farmers." },
  { year: "2026", title: "Platform Launch", desc: "Full web platform with flock management, production tracking, inventory, finances, AI assistant, and WhatsApp integration." },
  { year: "2026", title: "Pilot Program", desc: "Onboarding our first farmers across Kenya. Real-world testing with poultry, dairy, and mixed farms." },
  { year: "2027", title: "Scaling Across Kenya", desc: "Targeting 1,000 active farmers through field agents, cooperative partnerships, and agro-vet networks." },
];

const team = [
  { name: "Lewis", role: "Founder & CEO", desc: "Full-stack developer with a passion for agritech and empowering African communities." },
  { name: "The Wangari Team", role: "Engineering & Support", desc: "A dedicated team building tools that make farming smarter, easier, and more profitable." },
];

export default function AboutPage() {
  return (
    <div className="min-h-screen">
      {/* Hero */}
      <section className="relative overflow-hidden py-28 md:py-36 px-6 bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] text-white">
        <div className="absolute inset-0 pointer-events-none">
          <div className="absolute -top-20 -right-20 h-[500px] w-[500px] rounded-full bg-[#22C55E]/10 blur-[120px]" />
        </div>
        <div className="relative mx-auto max-w-4xl text-center">
          <motion.div initial="hidden" animate="visible" variants={stagger}>
            <motion.p variants={fadeUp} className="text-sm font-bold uppercase tracking-widest text-[#4ADE80] mb-3">About Wangari</motion.p>
            <motion.h1 variants={fadeUp} className="text-4xl md:text-6xl font-extrabold tracking-tight leading-tight">
              Empowering African farmers with smart technology
            </motion.h1>
            <motion.p variants={fadeUp} className="mt-6 text-lg text-white/70 max-w-2xl mx-auto leading-relaxed">
              We started Wangari with one goal: make farm management simple enough for every farmer, powerful enough for any scale.
            </motion.p>
          </motion.div>
        </div>
      </section>

      {/* Mission */}
      <section className="py-24 px-6">
        <div className="mx-auto max-w-5xl grid lg:grid-cols-2 gap-16 items-center">
          <motion.div initial={{ opacity: 0, x: -40 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} transition={{ duration: 0.7 }}>
            <div className="flex items-center gap-2 mb-4">
              <Target className="h-5 w-5 text-[#166534]" />
              <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E]">Our Mission</p>
            </div>              <h2 className="text-3xl md:text-4xl font-extrabold text-[#0F172A] tracking-tight leading-tight">
              Technology built for African farmers, from day one
            </h2>
            <p className="mt-5 text-lg text-[#64748B] leading-relaxed">
              Farming feeds Africa. Yet most farmers still track their flocks in notebooks, manage finances in their heads, and guess at feed requirements. The tools that exist are built for Western industrial farms — complex, expensive, and disconnected from the reality of African agriculture.
            </p>
            <p className="mt-4 text-[#334155] leading-relaxed">
              Wangari was born from a simple observation: a farmer in Nakuru with 500 layers has the same needs as a farm manager with 50,000 birds — just different scales. Both need to track production, manage costs, and make informed decisions.
            </p>
          </motion.div>
          <motion.div initial={{ opacity: 0, x: 40 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} transition={{ duration: 0.7 }} className="grid grid-cols-2 gap-4">
            {[
              { icon: Users, value: "7", label: "Hubs Built" },
              { icon: Zap, value: "30s", label: "To See Your Profit" },
              { icon: Target, value: "KES 500", label: "Starting Price/Month" },
              { icon: Shield, value: "Free", label: "14-Day Trial" },
            ].map((s) => (
              <div key={s.label} className="rounded-2xl border border-[#E5E7EB] bg-white p-6 text-center hover:shadow-lg hover:border-[#BBF7D0] transition-all">
                <s.icon className="h-6 w-6 text-[#166534] mx-auto mb-3" />
                <p className="text-2xl font-extrabold text-[#0F172A]">{s.value}</p>
                <p className="text-xs text-[#64748B] mt-1">{s.label}</p>
              </div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* Named after Wangari Maathai */}
      <section className="py-20 px-6 bg-[#F0FDF4]">
        <div className="mx-auto max-w-4xl text-center">
          <img src="/images/wangari-real-logo.png" alt="Wangari" className="h-16 w-16 rounded-full object-cover mx-auto mb-6" />
          <h2 className="text-2xl md:text-3xl font-extrabold text-[#0F172A] tracking-tight">
            Named after Prof. Wangari Maathai
          </h2>
          <p className="mt-4 text-[#64748B] leading-relaxed max-w-2xl mx-auto">
            Nobel Peace Prize laureate. Environmental champion. She proved that empowering individuals at the grassroots level can transform an entire continent. That is exactly what we aim to do with technology.
          </p>
        </div>
      </section>

      {/* Values */}
      <section className="py-24 px-6">
        <div className="mx-auto max-w-7xl">
          <div className="text-center mb-16">
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Our Values</p>
            <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">What drives us</h2>
          </div>
          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={stagger} className="grid md:grid-cols-2 gap-8">
            {values.map((v) => (
              <motion.div key={v.title} variants={fadeUp} whileHover={{ y: -4 }} className="rounded-2xl border border-[#E5E7EB] bg-white p-8 hover:shadow-xl hover:border-[#BBF7D0] transition-all duration-300">
                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]">
                  <v.icon className="h-6 w-6" />
                </div>
                <h3 className="mt-5 text-xl font-bold text-[#0F172A]">{v.title}</h3>
                <p className="mt-3 text-sm text-[#64748B] leading-relaxed">{v.desc}</p>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* Timeline */}
      <section className="py-24 px-6 bg-[#FAFBFC]">
        <div className="mx-auto max-w-3xl">
          <div className="text-center mb-16">
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Our Journey</p>
            <h2 className="text-3xl md:text-4xl font-extrabold text-[#0F172A] tracking-tight">Our Journey So Far</h2>
          </div>
          <div className="space-y-8">
            {milestones.map((m, i) => (
              <motion.div key={m.year + m.title} initial={{ opacity: 0, x: -20 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} transition={{ delay: i * 0.1 }} className="flex gap-6">
                <div className="flex flex-col items-center">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#166534] text-white text-xs font-bold shrink-0">{m.year.slice(-2)}</div>
                  {i < milestones.length - 1 && <div className="w-0.5 flex-1 bg-[#BBF7D0] mt-2" />}
                </div>
                <div className="pb-8">
                  <p className="text-xs font-bold text-[#166534] uppercase tracking-wider">{m.year}</p>
                  <h3 className="text-lg font-bold text-[#0F172A] mt-1">{m.title}</h3>
                  <p className="text-sm text-[#64748B] mt-1 leading-relaxed">{m.desc}</p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Team */}
      <section className="py-24 px-6">
        <div className="mx-auto max-w-4xl">
          <div className="text-center mb-16">
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Our Team</p>
            <h2 className="text-3xl md:text-4xl font-extrabold text-[#0F172A] tracking-tight">The people behind Wangari</h2>
          </div>
          <div className="grid md:grid-cols-2 gap-8">
            {team.map((t) => (
              <div key={t.name} className="rounded-2xl border border-[#E5E7EB] bg-white p-8 text-center hover:shadow-lg transition-all">
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-[#166534] text-white text-xl font-bold mx-auto">
                  {t.name[0]}
                </div>
                <h3 className="mt-4 text-lg font-bold text-[#0F172A]">{t.name}</h3>
                <p className="text-sm text-[#166534] font-medium">{t.role}</p>
                <p className="mt-3 text-sm text-[#64748B] leading-relaxed">{t.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Contact */}
      <section className="py-20 px-6 bg-[#F0FDF4]">
        <div className="mx-auto max-w-4xl text-center">
          <h2 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">Get in touch</h2>
          <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-6 text-[#64748B]">
            <a href="mailto:info@imeantech.com" className="flex items-center gap-2 hover:text-[#166534] transition-colors">
              <span className="text-[#166534]">✉</span> info@imeantech.com
            </a>
            <span className="hidden sm:block">·</span>
            <span className="flex items-center gap-2">
              <span className="text-[#166534]">📞</span> +254 114 971 070
            </span>
            <span className="hidden sm:block">·</span>
            <span className="flex items-center gap-2">
              <span className="text-[#166534]">📍</span> Nairobi, Kenya
            </span>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-24 px-6 bg-gradient-to-br from-[#0B1220] to-[#166534] text-white text-center">
        <div className="mx-auto max-w-3xl">
          <h2 className="text-3xl md:text-4xl font-extrabold tracking-tight">Ready to join us?</h2>
          <p className="mt-5 text-lg text-white/60">Start managing your farm smarter today — for free.</p>
          <Link href="/register" className="mt-8 group inline-flex items-center gap-3 rounded-full bg-white text-[#166534] px-8 py-4 text-base font-bold hover:bg-[#F0FDF4] transition-all duration-300 shadow-2xl hover:-translate-y-1">
            Get Started Free
            <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1.5" />
          </Link>
        </div>
      </section>
    </div>
  );
}
