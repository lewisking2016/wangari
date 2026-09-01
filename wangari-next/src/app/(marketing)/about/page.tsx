import Link from "next/link";
import { Leaf, Heart, Globe, Shield } from "lucide-react";

const values = [
  { icon: Leaf, title: "Sustainability", desc: "We believe in farming that sustains both people and the planet. Our tools help reduce waste and optimize resource use." },
  { icon: Heart, title: "Farmer-First", desc: "Built by people who understand African farming. Every feature is designed for real-world conditions — slow internet, basic phones, and busy schedules." },
  { icon: Globe, title: "African Roots", desc: "Named after Prof. Wangari Maathai, Nobel laureate and environmental champion. We carry her vision of empowering communities through sustainable practices." },
  { icon: Shield, title: "Data Privacy", desc: "Your farm data is yours. We use enterprise-grade security and never sell your information to third parties." },
];

export default function AboutPage() {
  return (
    <div className="min-h-screen">
      {/* Hero */}
      <section className="py-24 px-6 bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] text-white">
        <div className="mx-auto max-w-4xl text-center">
          <p className="text-sm font-bold uppercase tracking-widest text-[#4ADE80] mb-3">About Wangari</p>
          <h1 className="text-4xl md:text-5xl font-extrabold tracking-tight">Empowering African farmers with smart technology</h1>
          <p className="mt-6 text-lg text-white/70 max-w-2xl mx-auto leading-relaxed">We started Wangari with one goal: make farm management simple enough for every farmer, powerful enough for any scale.</p>
        </div>
      </section>

      {/* Story */}
      <section className="py-24 px-6">
        <div className="mx-auto max-w-4xl">
          <h2 className="text-3xl font-extrabold text-[#0F172A] tracking-tight mb-8">Our Story</h2>
          <div className="space-y-6 text-lg text-[#334155] leading-relaxed">
            <p>Farming feeds Africa. Yet most farmers still track their flocks in notebooks, manage finances in their heads, and guess at feed requirements. The tools that exist are built for Western industrial farms — complex, expensive, and disconnected from the reality of African agriculture.</p>
            <p>Wangari was born from a simple observation: a farmer in Nakuru with 500 layers has the same needs as a farm manager with 50,000 birds — just different scales. Both need to track production, manage costs, and make informed decisions.</p>
            <p>We named the platform after Prof. Wangari Maathai, because she proved that empowering individuals at the grassroots level can transform an entire continent. That is exactly what we aim to do with technology.</p>
          </div>
        </div>
      </section>

      {/* Values */}
      <section className="py-24 px-6 bg-gradient-to-b from-[#F0FDF4] to-white">
        <div className="mx-auto max-w-7xl">
          <div className="text-center mb-16">
            <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Our Values</p>
            <h2 className="text-3xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">What drives us</h2>
          </div>
          <div className="grid md:grid-cols-2 gap-8">
            {values.map((v) => (
              <div key={v.title} className="rounded-2xl border border-[#E5E7EB] bg-white p-8 hover:shadow-lg transition-all duration-300">
                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]">
                  <v.icon className="h-6 w-6" />
                </div>
                <h3 className="mt-5 text-xl font-bold text-[#0F172A]">{v.title}</h3>
                <p className="mt-3 text-sm text-[#64748B] leading-relaxed">{v.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-24 px-6">
        <div className="mx-auto max-w-4xl text-center">
          <h2 className="text-3xl md:text-4xl font-extrabold text-[#0F172A] tracking-tight">Ready to join us?</h2>
          <p className="mt-4 text-lg text-[#64748B]">Start managing your farm smarter today.</p>
          <Link href="/register" className="mt-8 inline-flex items-center gap-2 rounded-full bg-[#166534] text-white px-8 py-4 text-base font-bold hover:bg-[#14532D] transition-all duration-200 shadow-lg">
            Get Started Free
          </Link>
        </div>
      </section>
    </div>
  );
}