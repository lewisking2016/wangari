"use client";

import * as React from "react";
import Link from "next/link";
import { Check, ArrowRight, Loader2 } from "lucide-react";
import { motion } from "framer-motion";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.1 } } };

const plans = [
  {
    name: "Starter",
    price: "Free",
    period: "forever",
    paystackPlan: null,
    description: "Perfect for small farms getting started",
    features: [
      "1 farm",
      "Up to 5 flocks",
      "Basic production tracking",
      "Simple expense logging",
      "Mobile access",
    ],
    cta: "Start Free",
    href: "/register",
    highlight: false,
  },
  {
    name: "Growth",
    monthlyPrice: 1500,
    annualPrice: 15000,
    period: "month",
    paystackPlan: "growth_monthly",
    paystackAnnual: "growth_annual",
    description: "For farmers ready to scale and optimize",
    features: [
      "Up to 3 farms",
      "Unlimited flocks",
      "Full analytics dashboard",
      "Inventory management",
      "Worker management",
      "Sales tracking",
      "AI Assistant",
      "Priority support",
    ],
    cta: "Start 14-day Trial",
    highlight: true,
  },
  {
    name: "Enterprise",
    monthlyPrice: 5000,
    annualPrice: 50000,
    period: "month",
    paystackPlan: "enterprise_monthly",
    paystackAnnual: "enterprise_annual",
    description: "For large operations and cooperatives",
    features: [
      "Unlimited farms",
      "Everything in Growth",
      "Multi-user access",
      "Advanced reports",
      "API access",
      "WhatsApp bot integration",
      "Custom branding",
      "Dedicated support",
    ],
    cta: "Start 14-day Trial",
    highlight: false,
  },
];

export default function PricingPage() {
  const [billing, setBilling] = React.useState<"monthly" | "annual">("monthly");
  const [loading, setLoading] = React.useState<string | null>(null);

  const handleCheckout = async (plan: typeof plans[1]) => {
    if (!plan.paystackPlan) return;

    setLoading(plan.name);
    try {
      const planKey = billing === "annual" ? plan.paystackAnnual : plan.paystackPlan;
      const res = await fetch("/api/paystack", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          email: "user@example.com", // Will be replaced with actual user email
          plan: planKey,
        }),
      });

      const data = await res.json();

      if (data.authorization_url) {
        window.location.href = data.authorization_url;
      } else {
        alert("Payment initialization failed. Please try again.");
      }
    } catch {
      alert("Something went wrong. Please try again.");
    } finally {
      setLoading(null);
    }
  };

  return (
    <div className="min-h-screen py-24 px-6">
      <div className="mx-auto max-w-7xl">
        <motion.div initial="hidden" animate="visible" variants={fadeUp} className="text-center mb-16">
          <p className="text-sm font-bold uppercase tracking-widest text-[#166534] mb-3">Pricing</p>
          <h1 className="text-4xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">Simple, transparent pricing</h1>
          <p className="mt-5 text-lg text-[#64748B] max-w-2xl mx-auto">Start free, upgrade when you need more. No hidden fees, no surprises.</p>

          {/* Billing toggle */}
          <div className="mt-8 inline-flex items-center gap-3 rounded-full border border-[#E5E7EB] bg-white p-1">
            <button
              onClick={() => setBilling("monthly")}
              className={`rounded-full px-5 py-2 text-sm font-semibold transition-all ${billing === "monthly" ? "bg-[#166534] text-white" : "text-[#64748B] hover:text-[#0F172A]"}`}
            >
              Monthly
            </button>
            <button
              onClick={() => setBilling("annual")}
              className={`rounded-full px-5 py-2 text-sm font-semibold transition-all ${billing === "annual" ? "bg-[#166534] text-white" : "text-[#64748B] hover:text-[#0F172A]"}`}
            >
              Annual <span className="text-[#166534] font-bold">Save 17%</span>
            </button>
          </div>
        </motion.div>

        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
          {plans.map((plan) => {
            const price = plan.paystackPlan
              ? billing === "annual"
                ? `KES ${(plan.annualPrice || 0).toLocaleString()}`
                : `KES ${(plan.monthlyPrice || 0).toLocaleString()}`
              : "Free";
            const period = plan.paystackPlan
              ? billing === "annual" ? "/year" : "/month"
              : "/forever";

            return (
              <motion.div key={plan.name} variants={fadeUp} whileHover={{ y: -6 }}>
                <div className={`rounded-2xl p-8 border-2 transition-all duration-300 h-full flex flex-col ${plan.highlight ? "border-[#166534] bg-[#F0FDF4] shadow-xl shadow-[#166534]/10 relative" : "border-[#E5E7EB] bg-white hover:border-[#BBF7D0]"}`}>
                  {plan.highlight && <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-[#166534] px-4 py-1 text-xs font-bold text-white uppercase tracking-wider">Most Popular</div>}
                  <h3 className="text-xl font-bold text-[#0F172A]">{plan.name}</h3>
                  <div className="mt-4 flex items-baseline gap-1">
                    <span className="text-4xl font-extrabold text-[#0F172A]">{price}</span>
                    <span className="text-sm text-[#64748B]">{period}</span>
                  </div>
                  {plan.paystackPlan && billing === "annual" && (
                    <p className="mt-1 text-xs text-[#166534] font-semibold">That&apos;s KES {Math.round((plan.annualPrice || 0) / 12).toLocaleString()}/month</p>
                  )}
                  <p className="mt-3 text-sm text-[#64748B]">{plan.description}</p>
                  <ul className="mt-8 space-y-3 flex-1">
                    {plan.features.map((f) => (
                      <li key={f} className="flex items-start gap-3 text-sm text-[#334155]">
                        <Check className="h-4 w-4 text-[#166534] mt-0.5 shrink-0" />
                        {f}
                      </li>
                    ))}
                  </ul>
                  {plan.paystackPlan ? (
                    <button
                      onClick={() => handleCheckout(plan)}
                      disabled={loading === plan.name}
                      className={`mt-8 flex items-center justify-center gap-2 w-full rounded-full py-3 text-sm font-bold transition-all duration-200 cursor-pointer ${plan.highlight ? "bg-[#166534] text-white hover:bg-[#14532D] shadow-lg" : "bg-[#0F172A] text-white hover:bg-[#1E293B]"}`}
                    >
                      {loading === plan.name ? (
                        <><Loader2 className="h-4 w-4 animate-spin" /> Processing...</>
                      ) : (
                        <>{plan.cta} <ArrowRight className="h-4 w-4" /></>
                      )}
                    </button>
                  ) : (
                    <Link href={plan.href} className={`mt-8 flex items-center justify-center gap-2 w-full rounded-full py-3 text-sm font-bold transition-all duration-200 ${plan.highlight ? "bg-[#166534] text-white hover:bg-[#14532D] shadow-lg" : "bg-[#0F172A] text-white hover:bg-[#1E293B]"}`}>
                      {plan.cta} <ArrowRight className="h-4 w-4" />
                    </Link>
                  )}
                </div>
              </motion.div>
            );
          })}
        </motion.div>

        {/* FAQ */}
        <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={fadeUp} className="mt-24 max-w-3xl mx-auto">
          <h2 className="text-2xl font-extrabold text-[#0F172A] text-center mb-12">Frequently asked questions</h2>
          <div className="space-y-6">
            {[
              { q: "Can I switch plans anytime?", a: "Yes, you can upgrade or downgrade at any time. Changes take effect at the start of your next billing cycle." },
              { q: "What payment methods do you accept?", a: "We accept M-Pesa, credit/debit cards, and bank transfers through Paystack. All payments are processed in KES." },
              { q: "Is there a free trial?", a: "Yes! The Growth and Enterprise plans come with a 14-day free trial. No credit card required to start." },
              { q: "What happens when my trial ends?", a: "You can choose to subscribe to a paid plan or continue with the free Starter plan. Your data is preserved." },
            ].map((faq) => (
              <div key={faq.q} className="rounded-xl border border-[#E5E7EB] bg-white p-6">
                <h3 className="font-bold text-[#0F172A]">{faq.q}</h3>
                <p className="mt-2 text-sm text-[#64748B] leading-relaxed">{faq.a}</p>
              </div>
            ))}
          </div>
        </motion.div>
      </div>
    </div>
  );
}
