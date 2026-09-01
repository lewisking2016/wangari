import Link from "next/link";
import { Shield, Zap, TrendingUp } from "lucide-react";

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen flex">
      {/* Left — Brand panel */}
      <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] relative overflow-hidden flex-col justify-between p-12">
        {/* Decorative blobs */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute -top-20 -left-20 h-[400px] w-[400px] rounded-full bg-[#22C55E]/15 blur-[120px]" />
          <div className="absolute -bottom-20 -right-20 h-[500px] w-[500px] rounded-full bg-[#4ADE80]/10 blur-[140px]" />
          {[...Array(4)].map((_, i) => (
            <div
              key={i}
              className="absolute w-1.5 h-1.5 rounded-full bg-[#4ADE80]/30"
              style={{ top: `${25 + i * 18}%`, left: `${15 + i * 20}%` }}
            />
          ))}
        </div>

        {/* Logo */}
        <div className="relative z-10">
          <Link href="/" className="flex items-center gap-3 text-white">
            <img
              src="/images/wangari-mark.svg"
              alt="Wangari"
              className="h-10 w-10"
            />
            <span className="text-xl font-extrabold">Wangari</span>
          </Link>
        </div>

        {/* Tagline */}
        <div className="relative z-10 text-white">
          <h2 className="text-4xl font-extrabold leading-tight tracking-tight">
            Grow smarter.
            <br />
            <span className="text-[#4ADE80]">Rooted in Africa.</span>
          </h2>
          <p className="mt-4 text-white/60 text-lg max-w-md leading-relaxed">
            The all-in-one farm management platform. Track flocks, manage
            inventory, monitor finances, and grow your farm with confidence.
          </p>
          {/* Feature bullets */}
          <div className="mt-8 space-y-3">
            {[
              { icon: Shield, text: "Bank-level security for your data" },
              { icon: Zap, text: "Works offline on any device" },
              { icon: TrendingUp, text: "AI-powered farm insights" },
            ].map((item) => (
              <div key={item.text} className="flex items-center gap-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 backdrop-blur-sm">
                  <item.icon className="h-4 w-4 text-[#4ADE80]" />
                </div>
                <span className="text-sm text-white/70">{item.text}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Stats */}
        <div className="relative z-10 flex gap-8">
          {[
            { value: "50K+", label: "Farmers" },
            { value: "2M+", label: "Birds Tracked" },
            { value: "99.9%", label: "Uptime" },
          ].map((stat) => (
            <div key={stat.label}>
              <p className="text-2xl font-extrabold text-white">{stat.value}</p>
              <p className="text-xs text-white/50 mt-0.5">{stat.label}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Right — Form panel */}
      <div className="flex-1 flex items-center justify-center p-6 bg-white">
        <div className="w-full max-w-md">{children}</div>
      </div>
    </div>
  );
}
