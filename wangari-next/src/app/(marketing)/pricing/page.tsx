import Link from "next/link";
import { Check, ArrowRight } from "lucide-react";

const plans = [
  {
    name: "Starter",
    price: "Free",
    period: "forever",
    description: "Perfect for small farms getting started",
    features: ["1 farm", "Up to 5 flocks", "Basic production tracking", "Simple expense logging", "Mobile access"],
    cta: "Start Free",
    href: "/register",
    highlight: false,
  },
  {
    name: "Growth",
    price: "KES 1,500",
    period: "per month",
    description: "For farmers ready to scale and optimize",
    features: ["Up to 3 farms", "Unlimited flocks", "Full analytics dashboard", "Inventory management", "Worker management", "Sales tracking", "AI Assistant", "Priority support"],
    cta: "Start 14-day Trial",
    href: "/register",
    highlight: true,
  },
  {
    name: "Enterprise",
    price: "KES 5,000",
    period: "per month",
    description: "For large operations and cooperatives",
    features: ["Unlimited farms", "Everything in Growth", "Multi-user access", "Advanced reports", "API access", "WhatsApp bot integration", "Custom branding", "Dedicated support"],
    cta: "Contact Sales",
    href: "/about",
    highlight: false,
  },
];

export default function PricingPage() {
  return (
    <div className="min-h-screen py-24 px-6">
      <div className="mx-auto max-w-7xl">
        <div className="text-center mb-16">
          <p className="text-sm font-bold uppercase tracking-widest text-[#22C55E] mb-3">Pricing</p>
          <h1 className="text-4xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">Simple, transparent pricing</h1>
          <p className="mt-5 text-lg text-[#64748B] max-w-2xl mx-auto">Start free, upgrade when you need more. No hidden fees, no surprises.</p>
        </div>
        <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
          {plans.map((plan) => (
            <div key={plan.name} className={`rounded-2xl p-8 border-2 transition-all duration-300 ${plan.highlight ? "border-[#166534] bg-[#F0FDF4] shadow-xl shadow-[#166534]/10 relative" : "border-[#E5E7EB] bg-white hover:border-[#BBF7D0]"}`}>
              {plan.highlight && <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-[#166534] px-4 py-1 text-xs font-bold text-white uppercase tracking-wider">Most Popular</div>}
              <h3 className="text-xl font-bold text-[#0F172A]">{plan.name}</h3>
              <div className="mt-4 flex items-baseline gap-1">
                <span className="text-4xl font-extrabold text-[#0F172A]">{plan.price}</span>
                <span className="text-sm text-[#64748B]">/{plan.period}</span>
              </div>
              <p className="mt-3 text-sm text-[#64748B]">{plan.description}</p>
              <ul className="mt-8 space-y-3">
                {plan.features.map((f) => (
                  <li key={f} className="flex items-start gap-3 text-sm text-[#334155]">
                    <Check className="h-4 w-4 text-[#22C55E] mt-0.5 shrink-0" />
                    {f}
                  </li>
                ))}
              </ul>
              <Link href={plan.href} className={`mt-8 flex items-center justify-center gap-2 w-full rounded-full py-3 text-sm font-bold transition-all duration-200 ${plan.highlight ? "bg-[#166534] text-white hover:bg-[#14532D] shadow-lg" : "bg-[#0F172A] text-white hover:bg-[#1E293B]"}`}>
                {plan.cta} <ArrowRight className="h-4 w-4" />
              </Link>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}