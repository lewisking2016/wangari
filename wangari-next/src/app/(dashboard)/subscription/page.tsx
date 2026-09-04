"use client";

import * as React from "react";
import { motion } from "framer-motion";
import { CreditCard, Calendar, CheckCircle2, AlertTriangle, Clock, ArrowUpRight, ExternalLink } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/shared/page-header";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4 } } };

const PLANS = [
  { key: "starter_monthly", name: "Starter", price: "KES 1,500/mo", hubs: "1 hub + Inventory", features: ["1 hub of choice", "Inventory (always)", "Dashboard", "Feed Calculator", "Weather"] },
  { key: "growth_monthly", name: "Growth", price: "KES 4,500/mo", hubs: "3 hubs + Inventory", features: ["3 hubs of choice", "Inventory (always)", "AI Assistant", "PDF Reports", "Priority support"] },
  { key: "starter_annual", name: "Starter Annual", price: "KES 12,000/yr", hubs: "1 hub + Inventory", features: ["Same as Starter monthly", "2 months free"] },
  { key: "growth_annual", name: "Growth Annual", price: "KES 36,000/yr", hubs: "3 hubs + Inventory", features: ["Same as Growth monthly", "2 months free"] },
];

export default function SubscriptionPage() {
  const [sub, setSub] = React.useState<any>(null);
  const [trial, setTrial] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [purchasing, setPurchasing] = React.useState<string | null>(null);

  React.useEffect(() => {
    api.get("/api/trial/status")
      .then((d: any) => { setSub(d.subscription); setTrial(d.trial); })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const handleSubscribe = async (planKey: string) => {
    setPurchasing(planKey);
    try {
      const res = await api.post("/api/paystack", {
        email: "", // filled by backend from JWT
        plan: planKey,
        callback_url: `${window.location.origin}/subscription?payment=success`,
      });
      if (res.authorization_url) {
        window.location.href = res.authorization_url;
      }
    } catch (err) {
      console.error(err);
    } finally {
      setPurchasing(null);
    }
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  const isActive = sub?.status === "active";
  const isPending = sub?.status === "pending";
  const isTrial = trial?.status === "active";

  return (
    <div className="space-y-6 max-w-3xl">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="Subscription" description="Manage your Wangari plan" />
      </motion.div>

      {/* Current status */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#166534] text-white">
                <CreditCard className="h-5 w-5" />
              </div>
              <div>
                <p className="text-sm font-bold text-[#0F172A]">Current Plan</p>
                <p className="text-xs text-[#64748B]">
                  {isActive ? sub.plan_name || "Active plan" : isPending ? "Pending — starts after trial" : isTrial ? "Free trial" : "No active plan"}
                </p>
              </div>
            </div>

            {isActive && (
              <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-4 space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-[#64748B]">Plan</span>
                  <span className="font-bold text-[#0F172A]">{sub.plan_name}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-[#64748B]">Status</span>
                  <Badge className="bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]">✅ Active</Badge>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-[#64748B]">Valid until</span>
                  <span className="font-bold text-[#0F172A]">{new Date(sub.expires_at).toLocaleDateString()}</span>
                </div>
              </div>
            )}

            {isPending && (
              <div className="rounded-xl bg-amber-50 border border-amber-200 p-4">
                <div className="flex items-center gap-2 mb-1">
                  <Clock className="h-4 w-4 text-amber-600" />
                  <p className="text-sm font-bold text-amber-800">{sub.plan_name} — Pending</p>
                </div>
                <p className="text-xs text-amber-600">Your subscription will start when your free trial ends on {trial?.endsAt ? new Date(trial.endsAt).toLocaleDateString() : "—"}</p>
              </div>
            )}

            {!isActive && !isPending && isTrial && (
              <div className="rounded-xl bg-blue-50 border border-blue-200 p-4">
                <div className="flex items-center gap-2 mb-1">
                  <AlertTriangle className="h-4 w-4 text-blue-600" />
                  <p className="text-sm font-bold text-blue-800">Free Trial — {trial?.daysLeft || 0} days left</p>
                </div>
                <p className="text-xs text-blue-600">Subscribe now to keep full access after your trial ends.</p>
              </div>
            )}

            {!isActive && !isPending && !isTrial && (
              <div className="rounded-xl bg-red-50 border border-red-200 p-4">
                <div className="flex items-center gap-2 mb-1">
                  <AlertTriangle className="h-4 w-4 text-red-600" />
                  <p className="text-sm font-bold text-red-800">No active plan</p>
                </div>
                <p className="text-xs text-red-600">Subscribe to access all modules and features.</p>
              </div>
            )}
          </CardContent>
        </Card>
      </motion.div>

      {/* Available plans */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <h3 className="text-sm font-bold text-[#0F172A] mb-3">Available Plans</h3>
        <div className="grid gap-4">
          {PLANS.filter(p => !p.key.includes("annual") || true).map(plan => (
            <Card key={plan.key} className="border border-[#E5E7EB] hover:border-[#166534] transition-colors">
              <CardContent className="p-5">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-sm font-bold text-[#0F172A]">{plan.name}</p>
                    <p className="text-lg font-extrabold text-[#166534] mt-1">{plan.price}</p>
                    <p className="text-xs text-[#64748B] mt-0.5">{plan.hubs}</p>
                    <div className="flex flex-wrap gap-1.5 mt-2">
                      {plan.features.map(f => (
                        <span key={f} className="text-[10px] bg-[#F0FDF4] text-[#166534] px-2 py-0.5 rounded-full">{f}</span>
                      ))}
                    </div>
                  </div>
                  <Button
                    onClick={() => handleSubscribe(plan.key)}
                    disabled={purchasing === plan.key || (isActive && sub?.plan_name?.toLowerCase().includes(plan.name.toLowerCase()))}
                    className="bg-[#166534] hover:bg-[#14532D] cursor-pointer shrink-0"
                    size="sm"
                  >
                    {purchasing === plan.key ? "Loading..." : "Subscribe"}
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </motion.div>

      {/* Enterprise CTA */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB] bg-[#F8FAFC]">
          <CardContent className="p-5 text-center">
            <p className="text-sm font-bold text-[#0F172A]">Need Enterprise?</p>
            <p className="text-xs text-[#64748B] mt-1">Custom hosting, installation, and dedicated support for large farms.</p>
            <a href="mailto:sales@imeantech.com" className="inline-flex items-center gap-1 mt-3 text-sm font-bold text-[#166534] hover:underline">
              Contact Sales <ExternalLink className="h-3.5 w-3.5" />
            </a>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}
