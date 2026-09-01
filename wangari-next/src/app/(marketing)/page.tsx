"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import {
  Bird,
  BarChart3,
  Package,
  Users,
  Smartphone,
  Sparkles,
  ArrowRight,
  Shield,
  TrendingUp,
  Heart,
  Leaf,
  Sun,
  CloudRain,
  Droplets,
  CheckCircle2,
  Star,
  Quote,
} from "lucide-react";
import { TextRoll } from "@/components/ui/text-roll";
import { WaveGridBackground } from "@/components/ui/wave-grid-background";

/* ── Animation Variants ── */
const fadeUp = {
  hidden: { opacity: 0, y: 40 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] } },
};
const fadeDown = {
  hidden: { opacity: 0, y: -20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.6 } },
};
const fadeIn = {
  hidden: { opacity: 0 },
  visible: { opacity: 1, transition: { duration: 0.8 } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.12, delayChildren: 0.1 } },
};
const scaleIn = {
  hidden: { opacity: 0, scale: 0.85 },
  visible: { opacity: 1, scale: 1, transition: { duration: 0.5, ease: "easeOut" } },
};
const slideInLeft = {
  hidden: { opacity: 0, x: -60 },
  visible: { opacity: 1, x: 0, transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] } },
};
const slideInRight = {
  hidden: { opacity: 0, x: 60 },
  visible: { opacity: 1, x: 0, transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] } },
};

/* ── Data ── */
const features = [
  { icon: Bird, title: "Flock Management", desc: "Track every bird from hatch date to harvest. Monitor health, feeding, and production in real time.", href: "/features/flocks" },
  { icon: BarChart3, title: "Smart Analytics", desc: "See your costs, revenue, and margins at a glance. Know exactly which flock is most profitable.", href: "/features/analytics" },
  { icon: Package, title: "Inventory Control", desc: "Never run out of feed or medication. Get low-stock alerts and track every bag in and out.", href: "/features/inventory" },
  { icon: Users, title: "Team Management", desc: "Manage workers, attendance, and wages. Assign tasks and track who did what.", href: "/features/team" },
  { icon: Smartphone, title: "Mobile First", desc: "Works on any phone, even with slow internet. Log production from the field in 3 taps.", href: "/register" },
  { icon: Sparkles, title: "AI Assistant", desc: "Ask your farm anything and get instant answers from your data.", href: "/features/ai" },
];

const steps = [
  { num: 1, title: "Create Account", desc: "Sign up in 30 seconds. No credit card needed.", icon: CheckCircle2 },
  { num: 2, title: "Add Your Flock", desc: "Enter breed, count, and start date.", icon: Bird },
  { num: 3, title: "Log Daily", desc: "Record eggs, feed, and health in 3 taps.", icon: ClipboardIcon },
  { num: 4, title: "See Insights", desc: "Watch your farm data come alive with charts.", icon: BarChart3 },
];

function ClipboardIcon({ className }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
      <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
      <path d="M9 14h6" />
      <path d="M9 18h6" />
      <path d="M9 10h6" />
    </svg>
  );
}

const stats = [
  { value: "50K+", label: "Active Farmers", icon: Users },
  { value: "2M+", label: "Birds Tracked", icon: Bird },
  { value: "KES 500M+", label: "Revenue Managed", icon: TrendingUp },
  { value: "99.9%", label: "Uptime", icon: Shield },
];

const testimonials = [
  { name: "Grace Wanjiku", role: "Layer Farmer, Nakuru", text: "Wangari changed how I run my farm. I know exactly how many eggs each flock produces every day. The AI assistant is incredible.", rating: 5 },
  { name: "Peter Ochieng", role: "Broiler Farm, Eldoret", text: "The inventory alerts alone saved me KES 50,000 last month. I never ran out of feed. My workers love using it on their phones.", rating: 5 },
  { name: "Mary Akinyi", role: "Mixed Farm, Kisumu", text: "My workers log everything on their phones. The analytics help me make better decisions about which flocks to expand.", rating: 5 },
];

const benefits = [
  { icon: Leaf, title: "Eco-Friendly", desc: "Reduce waste with smart inventory tracking and data-driven decisions." },
  { icon: Sun, title: "Works Offline", desc: "Log data without internet. Syncs automatically when you're back online." },
  { icon: CloudRain, title: "Weather Aware", desc: "Get weather alerts and adjust feeding schedules automatically." },
  { icon: Droplets, title: "Water Tracking", desc: "Monitor water consumption across all flocks and detect leaks early." },
];

/* ── Page Component ── */
export default function LandingPage() {
  return (
    <div className="min-h-screen">
      {/* ═══════ HERO ═══════ */}
      <section className="relative overflow-hidden text-white min-h-[90vh] -mt-16 pt-16">
        <WaveGridBackground
          colorBase="#0B1220"
          colorHigh="#22C55E"
          gridSize={25}
          waveAmplitude={0.3}
          waveSpeed={5}
          className="absolute inset-0 h-full w-full"
        >
          <div className="absolute inset-0 bg-gradient-to-b from-[#0B1220]/80 via-[#0B1220]/40 to-[#0B1220]/90" />
        </WaveGridBackground>

        <div className="relative mx-auto max-w-7xl px-6 pt-40 pb-28 md:pt-48 md:pb-36 text-center">
          <motion.div initial="hidden" animate="visible" variants={stagger}>
            <motion.div variants={fadeDown} className="inline-flex items-center gap-2.5 rounded-full border border-white/20 bg-white/10 backdrop-blur-sm px-5 py-2.5 text-sm font-medium mb-8">
              <img src="/images/wangari-real-logo.png" alt="" className="h-5 w-5 rounded-full object-cover" />
              <span className="text-white/80">Named after Prof. Wangari Maathai</span>
            </motion.div>            <motion.h1 variants={fadeUp} className="max-w-5xl mx-auto">
              <div className="text-5xl md:text-7xl lg:text-[5.5rem] font-extrabold leading-[1.05] tracking-tight">
                <TextRoll center>Grow smarter.</TextRoll>
              </div>
              <div className="text-5xl md:text-7xl lg:text-[5.5rem] font-extrabold leading-[1.05] tracking-tight mt-2">
                <span className="text-white/40">Rooted in </span>
                <TextRoll center className="bg-gradient-to-r from-[#4ADE80] via-[#22C55E] to-[#86EFAC] bg-clip-text text-transparent">Africa.</TextRoll>
              </div>
            </motion.h1>
            <motion.p variants={fadeUp} className="mt-10 text-lg md:text-xl text-white/60 max-w-2xl mx-auto leading-relaxed">
              The all-in-one farm management platform built for African farmers.
              Track flocks, manage inventory, monitor finances, and make smarter
              decisions — all from your phone.
            </motion.p>

            <motion.div variants={fadeUp} className="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
              <Link
                href="/register"
                className="group inline-flex items-center gap-3 rounded-full bg-white text-[#166534] px-8 py-4 text-base font-bold hover:bg-[#F0FDF4] transition-all duration-300 shadow-2xl shadow-black/20 hover:shadow-3xl hover:-translate-y-1"
              >
                Start Free — No Card Needed
                <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1.5" />
              </Link>
              <Link
                href="/login"
                className="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 px-8 py-4 text-base font-semibold text-white hover:bg-white/20 transition-all duration-300"
              >
                Sign In
              </Link>
            </motion.div>
          </motion.div>

          {/* Stats row */}
          <motion.div
            initial="hidden"
            animate="visible"
            variants={stagger}
            className="mt-24 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto"
          >
            {stats.map((s) => (
              <motion.div
                key={s.label}
                variants={scaleIn}
                whileHover={{ scale: 1.05, y: -4 }}
                className="text-center p-5 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:bg-white/10 transition-colors"
              >
                <s.icon className="h-6 w-6 text-white mx-auto mb-3" />
                <p className="text-2xl md:text-3xl font-extrabold text-white">{s.value}</p>
                <p className="text-xs text-white/50 font-medium mt-1.5">{s.label}</p>
              </motion.div>
            ))}
          </motion.div>
        </div>

        {/* Wave divider */}
        <div className="absolute bottom-0 left-0 right-0">
          <svg viewBox="0 0 1440 120" fill="none" className="w-full h-auto block">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H0Z" fill="#FAFBFC" />
          </svg>
        </div>
      </section>

      {/* ═══════ FEATURES ═══════ */}
      <section className="py-28 px-6">
        <div className="mx-auto max-w-7xl">
          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, margin: "-100px" }} variants={fadeUp} className="text-center mb-16">
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Features</p>
            <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">
              Everything your farm needs
            </h2>
            <p className="mt-5 text-lg text-[#64748B] max-w-2xl mx-auto">
              From one-tap production logging to AI-powered insights — Wangari handles it all.
            </p>
          </motion.div>

          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, margin: "-50px" }} variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {features.map((f) => (
              <motion.div
                key={f.title}
                variants={fadeUp}
                whileHover={{ y: -8, scale: 1.02 }}
              >
                <Link href={f.href} className="group relative block rounded-2xl border border-[#E5E7EB] bg-white p-8 hover:shadow-2xl hover:shadow-[#166534]/5 transition-all duration-500 hover:-translate-y-2 hover:border-[#BBF7D0] overflow-hidden h-full">
                  <div className="absolute inset-0 bg-gradient-to-br from-[#F0FDF4]/0 to-[#F0FDF4]/0 group-hover:from-[#F0FDF4]/50 group-hover:to-white/0 transition-all duration-500" />
                  <div className="relative">
                    <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#166534] text-white shadow-lg group-hover:scale-110 transition-transform duration-300">
                      <f.icon className="h-7 w-7" />
                    </div>
                    <h3 className="mt-5 text-xl font-bold text-[#0F172A]">{f.title}</h3>
                    <p className="mt-3 text-sm text-[#64748B] leading-relaxed">{f.desc}</p>
                    <div className="mt-4 flex items-center gap-1.5 text-sm font-semibold text-[#166534] opacity-0 group-hover:opacity-100 transition-opacity">
                      Learn more <ArrowRight className="h-4 w-4" />
                    </div>
                  </div>
                </Link>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* ═══════ HOW IT WORKS ═══════ */}
      <section className="py-28 px-6 bg-gradient-to-b from-[#F0FDF4] to-white relative overflow-hidden">
        {/* Background decoration */}
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full bg-[#22C55E]/5 blur-[120px] pointer-events-none" />

        <div className="relative mx-auto max-w-5xl text-center">
          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={fadeUp}>
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">How it works</p>
            <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">
              Up and running in 4 steps
            </h2>
            <p className="mt-5 text-lg text-[#64748B]">No training needed. No complex setup.</p>
          </motion.div>

          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={stagger} className="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8">
            {steps.map((s, i) => (
              <motion.div
                key={s.num}
                variants={fadeUp}
                whileHover={{ y: -6 }}
                className="relative flex flex-col items-center text-center group"
              >
                {/* Connector line (except last) */}
                {i < steps.length - 1 && (
                  <div className="hidden md:block absolute top-8 left-[60%] w-[80%] h-[2px] bg-gradient-to-r from-[#166534] to-[#22C55E]/30" />
                )}
                <motion.div
                  whileHover={{ scale: 1.1, rotate: 5 }}
                  className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[#166534] to-[#15803D] text-white shadow-xl shadow-[#166534]/25 mb-5"
                >
                  <s.icon className="h-7 w-7" />
                </motion.div>
                <h3 className="text-lg font-bold text-[#0F172A]">{s.title}</h3>
                <p className="mt-2 text-sm text-[#64748B]">{s.desc}</p>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* ═══════ BENEFITS ═══════ */}
      <section className="py-28 px-6">
        <div className="mx-auto max-w-7xl">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={slideInLeft}>
              <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Why Wangari</p>
              <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight leading-tight">
                Built for African
                <br />
                <span className="text-[#166534]">farm conditions</span>
              </h2>
              <p className="mt-5 text-lg text-[#64748B] leading-relaxed">
                Unlike generic farm software, Wangari understands the unique challenges
                of farming in Africa — from intermittent connectivity to multi-currency support.
              </p>
              <div className="mt-8 space-y-4">
                {["KES currency built-in", "Works offline, syncs later", "Swahili & English", "WhatsApp integration"].map((item) => (
                  <motion.div key={item} variants={fadeUp} className="flex items-center gap-3">
                    <CheckCircle2 className="h-5 w-5 text-[#22C55E] shrink-0" />
                    <span className="text-sm font-medium text-[#334155]">{item}</span>
                  </motion.div>
                ))}
              </div>
            </motion.div>

            <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={slideInRight} className="grid grid-cols-2 gap-4">
              {benefits.map((b) => (
                <motion.div
                  key={b.title}
                  whileHover={{ y: -6, scale: 1.03 }}
                  className="rounded-2xl border border-[#E5E7EB] bg-white p-6 hover:shadow-xl hover:border-[#BBF7D0] transition-all duration-300"
                >
                  <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534] mb-4">
                    <b.icon className="h-6 w-6" />
                  </div>
                  <h3 className="font-bold text-[#0F172A]">{b.title}</h3>
                  <p className="mt-2 text-xs text-[#64748B] leading-relaxed">{b.desc}</p>
                </motion.div>
              ))}
            </motion.div>
          </div>
        </div>
      </section>

      {/* ═══════ TESTIMONIALS ═══════ */}
      <section className="py-28 px-6 bg-gradient-to-b from-white to-[#F0FDF4]">
        <div className="mx-auto max-w-7xl">
          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={fadeUp} className="text-center mb-16">
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Testimonials</p>
            <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">
              Loved by farmers
            </h2>
            <p className="mt-5 text-lg text-[#64748B]">Join thousands of farmers already using Wangari.</p>
          </motion.div>

          <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={stagger} className="grid md:grid-cols-3 gap-8">
            {testimonials.map((t) => (
              <motion.div
                key={t.name}
                variants={fadeUp}
                whileHover={{ y: -6 }}
                className="relative rounded-2xl border border-[#E5E7EB] bg-white p-8 hover:shadow-2xl hover:shadow-[#166534]/5 transition-all duration-500"
              >
                <Quote className="h-8 w-8 text-[#166534]/20 mb-4" />
                <p className="text-sm text-[#334155] leading-relaxed mb-6">{t.text}</p>
                <div className="flex items-center gap-1 mb-3">
                  {[...Array(t.rating)].map((_, i) => (
                    <Star key={i} className="h-4 w-4 fill-[#166534] text-[#166534]" />
                  ))}
                </div>
                <div>
                  <p className="font-bold text-[#0F172A]">{t.name}</p>
                  <p className="text-xs text-[#64748B]">{t.role}</p>
                </div>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* ═══════ CTA ═══════ */}
      <section className="py-28 px-6">
        <div className="mx-auto max-w-4xl">
          <motion.div
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true }}
            variants={scaleIn}
            className="relative rounded-3xl bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] p-12 md:p-16 text-center text-white overflow-hidden"
          >
            <div className="absolute inset-0 pointer-events-none overflow-hidden">
              <div className="absolute -top-20 -right-20 h-[300px] w-[300px] rounded-full bg-[#22C55E]/10 blur-[80px]" />
              <div className="absolute -bottom-20 -left-20 h-[300px] w-[300px] rounded-full bg-[#4ADE80]/10 blur-[80px]" />
            </div>
            <div className="relative">
              <motion.div variants={fadeUp}>
                <h2 className="text-3xl md:text-5xl font-extrabold tracking-tight">
                  Wangari technology by
                  <br />
                  <span className="bg-gradient-to-r from-[#4ADE80] via-[#22C55E] to-[#86EFAC] bg-clip-text text-transparent">iMeanTech</span>
                </h2>
              </motion.div>
              <motion.p variants={fadeUp} className="mt-6 text-lg text-white/70 max-w-xl mx-auto">
                Visit iMeanTech to learn about the technology, services and support behind Wangari.
                Start for free — upgrade when you&apos;re ready.
              </motion.p>
              <motion.div variants={fadeUp} className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a
                  href="https://imeantech.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="group inline-flex items-center gap-3 rounded-full bg-white text-[#166534] px-8 py-4 text-base font-bold hover:bg-[#F0FDF4] transition-all duration-300 shadow-2xl hover:-translate-y-1"
                >
                  Visit iMeanTech.com
                  <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1.5" />
                </a>
                <Link
                  href="/register"
                  className="inline-flex items-center gap-2 rounded-full border-2 border-white/25 px-8 py-4 text-base font-semibold text-white hover:bg-white/10 transition-all duration-300"
                >
                  Get Started Free
                </Link>
              </motion.div>
            </div>
          </motion.div>
        </div>
      </section>


    </div>
  );
}
