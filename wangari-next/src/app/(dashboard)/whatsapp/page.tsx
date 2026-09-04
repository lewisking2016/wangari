"use client";

import * as React from "react";
import { motion } from "framer-motion";
import { MessageCircle, Phone, CheckCircle2, Clock, AlertTriangle, ExternalLink, Copy, Bot } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/shared/page-header";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4 } } };

const COMMANDS = [
  { cmd: "eggs 50", desc: "Record 50 eggs", icon: "🥚" },
  { cmd: "milk 10", desc: "Record 10L milk", icon: "🥛" },
  { cmd: "mortality 3", desc: "Record 3 deaths", icon: "📉" },
  { cmd: "stock", desc: "Check inventory", icon: "📦" },
  { cmd: "feed", desc: "Check feed levels", icon: "🌾" },
  { cmd: "balance", desc: "View finances", icon: "💰" },
  { cmd: "sales", desc: "Recent sales", icon: "🛒" },
  { cmd: "summary", desc: "Today's summary", icon: "📊" },
  { cmd: "help", desc: "Show all commands", icon: "❓" },
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
    <div className="space-y-6 max-w-3xl">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <PageHeader title="WhatsApp Bot" description="Manage your farm via WhatsApp messages" />
      </motion.div>

      {/* Status card */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-6">
            <div className="flex items-center gap-4 mb-4">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#25D366] text-white">
                <MessageCircle className="h-7 w-7" />
              </div>
              <div>
                <p className="text-base font-bold text-[#0F172A]">Wangari Farm Bot</p>
                <p className="text-xs text-[#64748B]">Send messages from your phone to record data</p>
              </div>
              <Badge className={`ml-auto ${data?.connected ? "bg-[#F0FDF4] text-[#166534] border-[#BBF7D0]" : "bg-amber-50 text-amber-700 border-amber-200"}`}>
                {data?.connected ? "🟢 Connected" : "🟡 Setup needed"}
              </Badge>
            </div>

            {!data?.connected && (
              <div className="rounded-xl bg-blue-50 border border-blue-200 p-4 mb-4">
                <p className="text-sm font-bold text-blue-800 mb-2">Setup WhatsApp Bot</p>
                <ol className="text-xs text-blue-700 space-y-1.5 list-decimal list-inside">
                  <li>Create a <a href="https://business.facebook.com" target="_blank" className="underline font-semibold">Meta Business account</a></li>
                  <li>Set up WhatsApp Business API</li>
                  <li>Get your <strong>Access Token</strong> and <strong>Phone Number ID</strong></li>
                  <li>Add them to your VPS <code className="bg-blue-100 px-1 rounded">.env</code></li>
                  <li>Set webhook URL in Meta Dashboard</li>
                </ol>
                <div className="mt-3 p-2 bg-white rounded-lg border border-blue-100">
                  <p className="text-[10px] text-blue-500 mb-1">Webhook URL (set in Meta Dashboard):</p>
                  <div className="flex items-center gap-2">
                    <code className="text-xs text-blue-800 font-mono flex-1 truncate">https://api.wangari.imeantech.com/api/whatsapp/webhook</code>
                    <button onClick={() => handleCopy("https://api.wangari.imeantech.com/api/whatsapp/webhook")} className="text-blue-500 hover:text-blue-700 cursor-pointer">
                      <Copy className="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>
              </div>
            )}

            <div className="grid grid-cols-3 gap-3">
              <div className="rounded-xl bg-[#F0FDF4] p-3 text-center">
                <p className="text-lg font-extrabold text-[#166534]">{data?.stats?.sent || 0}</p>
                <p className="text-[10px] text-[#64748B]">Sent</p>
              </div>
              <div className="rounded-xl bg-blue-50 p-3 text-center">
                <p className="text-lg font-extrabold text-blue-700">{data?.stats?.delivered || 0}</p>
                <p className="text-[10px] text-[#64748B]">Delivered</p>
              </div>
              <div className="rounded-xl bg-red-50 p-3 text-center">
                <p className="text-lg font-extrabold text-red-600">{data?.stats?.failed || 0}</p>
                <p className="text-[10px] text-[#64748B]">Failed</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Available commands */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-6">
            <h3 className="text-sm font-bold text-[#0F172A] mb-3 flex items-center gap-2">
              <Bot className="h-4 w-4 text-[#166534]" /> Available Commands
            </h3>
            <p className="text-xs text-[#64748B] mb-4">Send these messages to the bot WhatsApp number:</p>
            <div className="space-y-2">
              {COMMANDS.map(c => (
                <div key={c.cmd} className="flex items-center gap-3 p-2.5 rounded-xl bg-[#F8FAFC] hover:bg-[#F0FDF4] transition-colors">
                  <span className="text-lg">{c.icon}</span>
                  <div className="flex-1">
                    <code className="text-xs font-bold text-[#166534] bg-[#F0FDF4] px-2 py-0.5 rounded">{c.cmd}</code>
                    <p className="text-[11px] text-[#64748B] mt-0.5">{c.desc}</p>
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

      {/* Message history */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-6">
            <h3 className="text-sm font-bold text-[#0F172A] mb-3">Recent Messages</h3>
            {(!data?.messages || data.messages.length === 0) ? (
              <div className="text-center py-8">
                <MessageCircle className="h-8 w-8 text-[#CBD5E1] mx-auto mb-2" />
                <p className="text-xs text-[#94A3B8]">No messages yet. Send a command to get started!</p>
              </div>
            ) : (
              <div className="space-y-2">
                {data.messages.slice(0, 20).map((msg: any) => (
                  <div key={msg.id} className="flex items-start gap-3 p-2.5 rounded-xl bg-[#F8FAFC]">
                    <div className={`flex h-7 w-7 items-center justify-center rounded-full ${msg.action === "send" ? "bg-[#F0FDF4] text-[#166534]" : msg.action === "failed" ? "bg-red-50 text-red-500" : "bg-blue-50 text-blue-500"}`}>
                      {msg.action === "send" ? <CheckCircle2 className="h-3.5 w-3.5" /> : msg.action === "failed" ? <AlertTriangle className="h-3.5 w-3.5" /> : <Clock className="h-3.5 w-3.5" />}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-xs font-medium text-[#0F172A] truncate">
                        {typeof msg.details === "object" ? msg.details?.message || msg.action : msg.action}
                      </p>
                      <p className="text-[10px] text-[#94A3B8]">{new Date(msg.createdAt).toLocaleString()}</p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}
