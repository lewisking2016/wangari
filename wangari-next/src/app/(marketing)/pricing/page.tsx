"use client";

import * as React from "react";
import { motion } from "framer-motion";
import Link from "next/link";
import { Check, ArrowRight, Zap, Shield, Sparkles } from "lucide-react";

const fadeUp = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.6, ease: [0.22, 1, 0.36, 1] } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.12, delayChildren: 0.1 } },
};

const plans = [
  {
    name: "Starter",
    price: 500,
    period: "/month",
    description: "Perfect for small farms just getting started with digital records.",
    icon: Zap,
    popular: false,
    features: [
      "1 hub of your choice",
      "WhatsApp bot for data entry",
      "Daily profit summary",
      "Basic reports",
      "Mobile access",
    ],
    cta: "Start Free Trial",
    ctaHref: "/register",
  },
  {
    name: "Pro",
    price: 1500,
    period: "/month",
    description: "For serious farmers who want real profit visibility across their operation.",
    icon: Shield,
    popular: true,
    features: [
      "3 hubs of your choice",
      "WhatsApp bot + Push alerts",
      "AI assistant (ask questions in plain language)",
      "Advanced reports + PDF export",
      "Vaccination & low-stock reminders",
      "Daily profit reports",
    ],
    cta: "Start Free Trial",
    ctaHref: "/register",
  },
  {
    name: "Enterprise",
    price: 3000,
    period: "/month",
    description: "For large farms, agro-vets, and cooperatives who need everything.",
    icon: Sparkles,
    popular: false,
    features: [
      "All 7 hubs unlocked",
      "Full AI + priority support",
      "Unlimited team members + roles",
      "Cooperative dashboard",
      "API access",
      "WhatsApp bot + Push alerts",
      "Advanced reports + PDF export",
    ],
    cta: "Contact Us",
    ctaHref: "/register",
  },
];

export default function PricingPage() {
  const [annual, setAnnual] = React.useState(false);

  return (
    <div className="min-h-screen bg-[#FAFBFC]">
      {/* Hero */}
      <section className="pt-24 pb-16 px-6">
        <motion.div initial="hidden" animate="visible" variants={stagger} className="mx-auto max-w-4xl text-center">
          <motion.p variants={fadeUp} className="text-sm font-bold uppercase tracking-widest text-wangari-green-800 mb-3">Pricing</motion.p>
          <motion.h1 variants={fadeUp} className="text-4xl md:text-5xl font-extrabold text-[#0F172A] tracking-tight">
            Start free. Upgrade when<br />you see results.
          </motion.h1>
          <motion.p variants={fadeUp} className="mt-5 text-lg text-[#64748B] max-w-2xl mx-auto">
            Choose the plan that fits your farm. Every plan includes the WhatsApp bot, mobile access, and daily profit reports. Start free for 30 days, no credit card required.
          </motion.p>

          {/* Toggle */}
          <motion.div variants={fadeUp} className="mt-8 inline-flex items-center gap-3 bg-white rounded-full p-1.5 border border-[#E5E7EB]">
            <button
              onClick={() => setAnnual(false)}
              className={`px-5 py-2 rounded-full text-sm font-semibold transition-all cursor-pointer ${!annual ? "bg-[#166534] text-white shadow-md" : "text-[#64748B] hover:text-[#0F172A]"}`}
            >
              Monthly
            </button>
            <button
              onClick={() => setAnnual(true)}
              className={`px-5 py-2 rounded-full text-sm font-semibold transition-all cursor-pointer ${annual ? "bg-[#166534] text-white shadow-md" : "text-[#64748B] hover:text-[#0F172A]"}`}
            >
              Annual <span className="text-[#22C55E] font-bold">Save 17%</span>
            </button>
          </motion.div>
        </motion.div>
      </section>

      {/* Plans */}
      <section className="pb-24 px-6">
        <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={stagger} className="mx-auto max-w-6xl grid md:grid-cols-3 gap-8">
          {plans.map((plan) => {
            const monthlyPrice = annual ? Math.round(plan.price * 0.83) : plan.price;
            return (
              <motion.div
                key={plan.name}
                variants={fadeUp}
                whileHover={{ y: -8 }}
                className={`relative rounded-2xl border-2 p-8 transition-all duration-300 ${
                  plan.popular
                    ? "border-[#166534] bg-white shadow-2xl shadow-[#166534]/10"
                    : "border-[#E5E7EB] bg-white hover:border-[#BBF7D0] hover:shadow-xl"
                }`}
              >
                {plan.popular && (
                  <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span className="inline-flex items-center gap-1 px-4 py-1.5 rounded-full bg-[#166534] text-white text-xs font-bold uppercase tracking-wider">
                      Most Popular
                    </span>
                  </div>
                )}

                <div className="mb-6">
                  <div className={`flex h-12 w-12 items-center justify-center rounded-xl mb-4 ${plan.popular ? "bg-[#166534] text-white" : "bg-[#F0FDF4] text-[#166534]"}`}>
                    <plan.icon className="h-6 w-6" />
                  </div>
                  <h3 className="text-xl font-bold text-[#0F172A]">{plan.name}</h3>
                  <div className="mt-3 flex items-baseline gap-1">
                    <span className="text-sm text-[#64748B]">KES</span>
                    <span className="text-4xl font-extrabold text-[#0F172A]">{monthlyPrice.toLocaleString()}</span>
                    <span className="text-sm text-[#64748B]">{plan.period}</span>
                  </div>
                  {annual && (
                    <p className="text-xs text-[#22C55E] font-semibold mt-1">
                      KES {(monthlyPrice * 12).toLocaleString()}/year — save KES {((plan.price - monthlyPrice) * 12).toLocaleString()}
                    </p>
                  )}
                  <p className="mt-3 text-sm text-[#64748B] leading-relaxed">{plan.description}</p>
                </div>

                <ul className="space-y-3 mb-8">
                  {plan.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-3">
                      <Check className="h-5 w-5 text-[#22C55E] shrink-0 mt-0.5" />
                      <span className="text-sm text-[#334155]">{feature}</span>
                    </li>
                  ))}
                </ul>

                <Link
                  href={plan.ctaHref}
                  className={`flex items-center justify-center gap-2 w-full py-3.5 rounded-xl text-sm font-bold transition-all duration-200 ${
                    plan.popular
                      ? "bg-[#166534] text-white hover:bg-[#14532D] hover:shadow-lg hover:shadow-[#166534]/25"
                      : "border-2 border-[#E5E7EB] text-[#0F172A] hover:border-[#166534] hover:text-[#166534] hover:bg-[#F0FDF4]"
                  }`}
                >
                  {plan.cta}
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </motion.div>
            );
          })}
        </motion.div>

        {/* FAQ */}
        <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={stagger} className="mx-auto max-w-3xl mt-16">
          <motion.h2 variants={fadeUp} className="text-2xl font-extrabold text-[#0F172A] text-center mb-10">
            Frequently Asked Questions
          </motion.h2>
          <div className="space-y-6">
            {[
              { q: "Is there a free trial?", a: "Yes! Every plan starts with a 30-day free trial. No credit card required. You can cancel anytime." },
              { q: "Can I switch plans later?", a: "Absolutely. Upgrade or downgrade anytime. Your data is always preserved." },
              { q: "What payment methods do you accept?", a: "We accept M-Pesa, Visa, Mastercard, and bank transfers through Paystack." },
              { q: "What happens to my data if I cancel?", a: "We never delete your data. If you cancel, you get read-only access. Come back anytime and your data is there." },
            ].map((faq) => (
              <motion.div key={faq.q} variants={fadeUp} className="rounded-xl border border-[#E5E7EB] bg-white p-6">
                <h3 className="font-bold text-[#0F172A] mb-2">{faq.q}</h3>
                <p className="text-sm text-[#64748B] leading-relaxed">{faq.a}</p>
              </motion.div>
            ))}
          </div>
        </motion.div>
      </section>
    </div>
  );
}
