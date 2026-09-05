"use client";

import * as React from "react";
import { motion } from "framer-motion";
import { MessageCircle, PhoneCall, Bot, Copy, Clock, ShieldCheck, CheckCircle2 } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { PageHeader } from "@/components/shared/page-header";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4 } } };

const COMMANDS = [
  { cmd: "eggs 50", desc: "Record 50 eggs harvest" },
  { cmd: "milk 10", desc: "Record 10 Litres milked" },
  { cmd: "mortality 3", desc: "Record 3 animal deaths" },
  { cmd: "stock", desc: "Check current feed & inventory" },
  { cmd: "feed", desc: "View feed consumption balance" },
  { cmd: "sales", desc: "View today's sales summary" },
  { cmd: "summary", desc: "Get overall farm daily report" },
  { cmd: "help", desc: "Show all commands" },
];

export default function WhatsAppPage() {
  const [data, setData] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [copied, setCopied] = React.useState(false);

  React.useEffect(() => {
    api.get("/api/whatsapp")
      .then((d: any) => setData(d))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const handleCopy = (text: string) => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6 max-w-4xl">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader
          title="Multi-Channel Mobile Access (WhatsApp & USSD)"
          description="Built-in platform service for farmers & workers who use mobile phones without continuous internet access"
        />
      </motion.div>

      {/* 2 Channel Overview Cards: WhatsApp & USSD */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="grid md:grid-cols-2 gap-4">
        {/* WhatsApp Channel */}
        <Card className="border border-emerald-200 bg-emerald-50/40 rounded-3xl p-5 shadow-xs">
          <CardContent className="p-0 space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex h-11 w-11 items-center justify-center rounded-full bg-[#25D366] text-white">
                <MessageCircle className="h-6 w-6" />
              </div>
              <Badge className="bg-amber-100 text-amber-800 border-amber-200 font-bold text-xs">
                Coming Soon / Beta
              </Badge>
            </div>
            <div>
              <h3 className="text-lg font-black text-[#0F172A]">WhatsApp Bot Service</h3>
              <p className="text-xs text-[#64748B] mt-1 leading-relaxed">
                Log farm output, check feed inventory, and get daily summaries directly on WhatsApp. No laptop required.
              </p>
            </div>
            <div className="pt-2 border-t border-emerald-200/60 flex items-center justify-between text-xs font-bold text-[#166534]">
              <span>WhatsApp Cloud API</span>
              <span>Setup in progress</span>
            </div>
          </CardContent>
        </Card>

        {/* USSD Channel */}
        <Card className="border border-sky-200 bg-sky-50/40 rounded-3xl p-5 shadow-xs">
          <CardContent className="p-0 space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex h-11 w-11 items-center justify-center rounded-full bg-sky-600 text-white">
                <PhoneCall className="h-6 w-6" />
              </div>
              <Badge className="bg-amber-100 text-amber-800 border-amber-200 font-bold text-xs">
                Coming Soon
              </Badge>
            </div>
            <div>
              <h3 className="text-lg font-black text-[#0F172A]">USSD Shortcode (*384*55#)</h3>
              <p className="text-xs text-[#64748B] mt-1 leading-relaxed">
                Dial shortcode on any basic feature phone (no smartphone/data needed) to log eggs, milk, and view tasks.
              </p>
            </div>
            <div className="pt-2 border-t border-sky-200/60 flex items-center justify-between text-xs font-bold text-sky-800">
              <span>Shortcode: *384*55#</span>
              <span>Telco Integration</span>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* WhatsApp Commands Reference */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB] rounded-3xl">
          <CardContent className="p-6">
            <h3 className="text-sm font-bold text-[#0F172A] mb-1 flex items-center gap-2">
              <Bot className="h-4 w-4 text-[#166534]" /> Available Text Commands
            </h3>
            <p className="text-xs text-[#64748B] mb-4">
              When WhatsApp Bot goes live, workers can log data by texting these quick commands:
            </p>
            <div className="grid sm:grid-cols-2 gap-2">
              {COMMANDS.map(c => (
                <div key={c.cmd} className="flex items-center gap-3 p-3 rounded-2xl bg-[#F8FAFC] border border-[#E5E7EB] hover:bg-[#F0FDF4] transition-colors">
                  <div className="flex-1">
                    <code className="text-xs font-bold text-[#166534] bg-[#F0FDF4] px-2 py-0.5 rounded border border-[#BBF7D0]">
                      {c.cmd}
                    </code>
                    <p className="text-[11px] text-[#64748B] mt-1">{c.desc}</p>
                  </div>
                  <button onClick={() => handleCopy(c.cmd)} className="text-[#94A3B8] hover:text-[#166534] cursor-pointer">
                    <Copy className="h-3.5 w-3.5" />
                  </button>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Webhook Technical Config */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB] rounded-3xl">
          <CardContent className="p-5 flex items-center justify-between">
            <div>
              <p className="text-xs font-bold text-[#0F172A]">System Service Endpoint</p>
              <p className="text-[11px] text-[#64748B] mt-0.5">Meta Cloud API Webhook URL</p>
            </div>
            <div className="flex items-center gap-2 bg-[#F8FAFC] border border-[#E5E7EB] px-3 py-1.5 rounded-xl text-xs font-mono text-[#0F172A]">
              <span>https://api.wangari.imeantech.com/api/whatsapp/webhook</span>
              <button onClick={() => handleCopy("https://api.wangari.imeantech.com/api/whatsapp/webhook")} className="text-[#166534] hover:text-emerald-800 cursor-pointer">
                <Copy className="h-3.5 w-3.5" />
              </button>
            </div>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}
