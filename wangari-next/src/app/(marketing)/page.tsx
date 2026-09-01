import Link from "next/link";
import { Bird, BarChart3, Package, Users, Smartphone, Sparkles, ArrowRight, Leaf } from "lucide-react";

const features = [
  { icon: Bird, title: "Flock Management", desc: "Track every bird from hatch date to harvest. Monitor health, feeding, and production in real time." },
  { icon: BarChart3, title: "Smart Analytics", desc: "See your costs, revenue, and margins at a glance. Know exactly which flock is most profitable." },
  { icon: Package, title: "Inventory Control", desc: "Never run out of feed or medication. Get low-stock alerts and track every bag in and out." },
  { icon: Users, title: "Team Management", desc: "Manage workers, attendance, and wages. Assign tasks and track who did what." },
  { icon: Smartphone, title: "Mobile First", desc: "Works on any phone, even with slow internet. Log production from the field in 3 taps." },
  { icon: Sparkles, title: "AI Assistant", desc: "Ask your farm anything and get instant answers from your data." },
];

const steps = ["Create your free account", "Add your first flock", "Log daily production", "See insights & grow"];

export default function LandingPage() {
  return (
    <div className="min-h-screen">      <section className="relative overflow-hidden bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] text-white">
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute -top-24 -left-24 h-[500px] w-[500px] rounded-full bg-[#22C55E]/10 blur-[120px]" />
          <div className="absolute -bottom-32 -right-32 h-[600px] w-[600px] rounded-full bg-[#D0F24C]/8 blur-[140px]" />
        </div>
        <div className="relative mx-auto max-w-7xl px-6 pt-36 pb-28 md:pt-44 md:pb-36 text-center">
          <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 backdrop-blur-sm px-5 py-2.5 text-sm font-medium mb-8">
            <Leaf className="h-4 w-4 text-[#4ADE80]" /> Named after Prof. Wangari Maathai
          </div>
          <h1 className="text-5xl md:text-7xl lg:text-8xl font-extrabold leading-[1.05] tracking-tight max-w-5xl mx-auto">
            Grow smarter.<br />
            <span className="bg-gradient-to-r from-[#4ADE80] via-[#22C55E] to-[#86EFAC] bg-clip-text text-transparent">
              Rooted in Africa.
            </span>
          </h1>
          <p className="mt-8 text-lg md:text-xl text-white/70 max-w-2xl mx-auto leading-relaxed">
            The all-in-one farm management platform. Track flocks, manage inventory, monitor finances, and make smarter decisions from your phone.
          </p>
          <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link href="/register" className="group inline-flex items-center gap-2 rounded-full bg-white text-[#166534] px-8 py-4 text-base font-bold hover:bg-[#F0FDF4] transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
              Start Free <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
            </Link>
            <Link href="/login" className="inline-flex items-center gap-2 rounded-full border-2 border-white/25 px-8 py-4 text-base font-semibold text-white hover:bg-white/10 hover:border-white/40 transition-all duration-200">
              Sign In
            </Link>
          </div>
          <div className="mt-16 grid grid-cols-3 gap-8 max-w-lg mx-auto">
            <div><p className="text-3xl md:text-4xl font-extrabold text-white">50K+</p><p className="mt-1 text-sm text-white/50 font-medium">Active Farmers</p></div>
            <div><p className="text-3xl md:text-4xl font-extrabold text-white">2M+</p><p className="mt-1 text-sm text-white/50 font-medium">Birds Tracked</p></div>
            <div><p className="text-3xl md:text-4xl font-extrabold text-white">KES 500M+</p><p className="mt-1 text-sm text-white/50 font-medium">Revenue Managed</p></div>
          </div>
        </div>
        <div className="absolute bottom-0 left-0 right-0">
          <svg viewBox="0 0 1440 120" fill="none" className="w-full h-auto block"><path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H0Z" fill="#FAFBFC" /></svg>
        </div>
      </section>
      <section className="py-24 px-6">
        <div className="mx-auto max-w-7xl">
          <div className="text-center mb-16">
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Features</p>
            <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">Everything your farm needs</h2>
            <p className="mt-5 text-lg text-[#64748B] max-w-2xl mx-auto leading-relaxed">From one-tap production logging to AI-powered insights - Wangari replaces spreadsheets, notebooks, and guesswork.</p>
          </div>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {features.map((f) => (
              <div key={f.title} className="group rounded-2xl border border-[#E5E7EB] bg-white p-7 hover:shadow-xl hover:shadow-black/5 transition-all duration-300 hover:-translate-y-1 hover:border-[#BBF7D0]">
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F0FDF4] text-[#166534] group-hover:bg-[#166534] group-hover:text-white transition-colors duration-300">
                  <f.icon className="h-7 w-7" />
                </div>
                <h3 className="mt-5 text-xl font-bold text-[#0F172A]">{f.title}</h3>
                <p className="mt-3 text-sm text-[#64748B] leading-relaxed">{f.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>
      <section className="py-24 px-6 bg-gradient-to-b from-[#F0FDF4] to-white">
        <div className="mx-auto max-w-4xl text-center">
          <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">How it works</p>
          <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">Up and running in 4 steps</h2>
          <p className="mt-5 text-lg text-[#64748B]">No training needed. No complex setup.</p>
          <div className="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8">
            {steps.map((step, i) => (
              <div key={step} className="flex flex-col items-center">
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-[#166534] to-[#15803D] text-white text-xl font-extrabold shadow-lg shadow-[#166534]/25">{i + 1}</div>
                <p className="mt-4 text-sm font-semibold text-[#0F172A] max-w-[140px]">{step}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-24 px-6">
        <div className="mx-auto max-w-4xl text-center">
          <div className="rounded-3xl bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] p-12 md:p-16 text-white relative overflow-hidden">
            <div className="absolute inset-0 overflow-hidden pointer-events-none">
              <div className="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-[#22C55E]/15 blur-[80px]" />
              <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-[#D0F24C]/10 blur-[80px]" />
            </div>
            <div className="relative z-10">
              <h2 className="text-3xl md:text-4xl font-extrabold tracking-tight">Ready to grow smarter?</h2>
              <p className="mt-4 text-lg text-white/70">Join 50,000+ farmers already using Wangari to manage their farms.</p>
              <Link href="/register" className="mt-8 inline-flex items-center gap-2 rounded-full bg-white text-[#166534] px-8 py-4 text-base font-bold hover:bg-[#F0FDF4] transition-all duration-200 shadow-lg">
                Create Free Account <ArrowRight className="h-4 w-4" />
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}