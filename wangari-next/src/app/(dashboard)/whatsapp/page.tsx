"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  MessageSquare,
  Send,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  Bell,
  Clock,
  Smartphone,
  Settings,
  ToggleLeft,
  ToggleRight,
  RefreshCw,
  Phone,
  Users,
  BarChart3,
} from "lucide-react";

interface WhatsAppTemplate {
  id: string;
  name: string;
  description: string;
  active: boolean;
}

interface WhatsAppStats {
  sent: number;
  delivered: number;
  failed: number;
}

const fadeUp = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5 } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08 } },
};

export default function WhatsAppPage() {
  const [templates, setTemplates] = React.useState<WhatsAppTemplate[]>([]);
  const [stats, setStats] = React.useState<WhatsAppStats>({ sent: 0, delivered: 0, failed: 0 });
  const [loading, setLoading] = React.useState(true);
  const [connected, setConnected] = React.useState(false);
  const [testPhone, setTestPhone] = React.useState("");
  const [sending, setSending] = React.useState(false);

  React.useEffect(() => {
    const fetchData = async () => {
      try {
        const res = await fetch("/api/whatsapp");
        const data = await res.json();
        setTemplates(data.templates || []);
        setStats(data.stats || { sent: 0, delivered: 0, failed: 0 });
        setConnected(data.connected || false);
      } catch {
        // Use defaults
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  const toggleTemplate = (id: string) => {
    setTemplates((prev) =>
      prev.map((t) => (t.id === id ? { ...t, active: !t.active } : t))
    );
  };

  const sendTestMessage = async () => {
    if (!testPhone) return;
    setSending(true);
    try {
      await fetch("/api/whatsapp", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ phoneNumber: testPhone, templateId: "test", message: "Test message from Wangari" }),
      });
      alert("Message queued! (WhatsApp Business API pending)");
      setTestPhone("");
    } catch {
      alert("Failed to send");
    } finally {
      setSending(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <RefreshCw className="h-8 w-8 text-wangari-green-800 animate-spin" />
      </div>
    );
  }

  return (
    <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-6">
      <motion.div variants={fadeUp}>
        <h1 className="text-2xl font-bold text-wangari-heading">WhatsApp Integration</h1>
        <p className="text-sm text-wangari-muted mt-1">Send farm alerts and reports via WhatsApp</p>
      </motion.div>

      {/* Status Bar */}
      <motion.div variants={fadeUp} className="rounded-2xl border border-wangari-border bg-white p-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className={`flex h-14 w-14 items-center justify-center rounded-2xl ${connected ? "bg-emerald-50" : "bg-amber-50"}`}>
              <MessageSquare className={`h-7 w-7 ${connected ? "text-emerald-600" : "text-amber-600"}`} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-wangari-heading">
                WhatsApp Business API
              </h2>
              <p className="text-sm text-wangari-muted">
                {connected ? "Connected and ready" : "Setup required — Messages will be logged until connected"}
              </p>
            </div>
          </div>
          <div className={`px-4 py-2 rounded-full text-sm font-semibold ${connected ? "bg-emerald-50 text-emerald-700" : "bg-amber-50 text-amber-700"}`}>
            {connected ? "● Connected" : "● Not Connected"}
          </div>
        </div>
      </motion.div>

      {/* Stats */}
      <motion.div variants={fadeUp} className="grid grid-cols-3 gap-4">
        {[
          { label: "Sent", value: stats.sent, icon: Send, color: "text-blue-600 bg-blue-50" },
          { label: "Delivered", value: stats.delivered, icon: CheckCircle2, color: "text-emerald-600 bg-emerald-50" },
          { label: "Failed", value: stats.failed, icon: XCircle, color: "text-red-600 bg-red-50" },
        ].map((stat) => (
          <motion.div key={stat.label} variants={fadeUp} className="rounded-2xl border border-wangari-border bg-white p-5 text-center">
            <div className={`inline-flex h-10 w-10 items-center justify-center rounded-xl mb-3 ${stat.color}`}>
              <stat.icon className="h-5 w-5" />
            </div>
            <p className="text-3xl font-extrabold text-wangari-heading">{stat.value}</p>
            <p className="text-xs font-medium text-wangari-muted mt-1">{stat.label}</p>
          </motion.div>
        ))}
      </motion.div>

      {/* Templates */}
      <motion.div variants={fadeUp} className="rounded-2xl border border-wangari-border bg-white p-6">
        <div className="flex items-center gap-2 mb-4">
          <Bell className="h-5 w-5 text-wangari-green-800" />
          <h2 className="text-lg font-bold text-wangari-heading">Message Templates</h2>
        </div>
        <div className="space-y-3">
          {templates.map((template) => (
            <div key={template.id} className="flex items-center justify-between p-4 rounded-xl border border-wangari-border hover:bg-gray-50 transition-colors">
              <div className="flex items-center gap-4">
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${template.active ? "bg-emerald-50" : "bg-gray-100"}`}>
                  {template.id.includes("alert") || template.id.includes("mortality") || template.id.includes("stock") ? (
                    <AlertTriangle className={`h-5 w-5 ${template.active ? "text-amber-600" : "text-gray-400"}`} />
                  ) : template.id.includes("report") || template.id.includes("summary") ? (
                    <BarChart3 className={`h-5 w-5 ${template.active ? "text-blue-600" : "text-gray-400"}`} />
                  ) : (
                    <Clock className={`h-5 w-5 ${template.active ? "text-violet-600" : "text-gray-400"}`} />
                  )}
                </div>
                <div>
                  <p className="text-sm font-semibold text-wangari-heading">{template.name}</p>
                  <p className="text-xs text-wangari-muted">{template.description}</p>
                </div>
              </div>
              <button onClick={() => toggleTemplate(template.id)} className="cursor-pointer">
                {template.active ? (
                  <ToggleRight className="h-8 w-8 text-wangari-green-800" />
                ) : (
                  <ToggleLeft className="h-8 w-8 text-gray-300" />
                )}
              </button>
            </div>
          ))}
        </div>
      </motion.div>

      {/* Test Message */}
      <motion.div variants={fadeUp} className="rounded-2xl border border-wangari-border bg-white p-6">
        <div className="flex items-center gap-2 mb-4">
          <Smartphone className="h-5 w-5 text-wangari-green-800" />
          <h2 className="text-lg font-bold text-wangari-heading">Send Test Message</h2>
        </div>
        <div className="flex items-center gap-3">
          <div className="relative flex-1">
            <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-wangari-muted" />
            <input
              type="tel"
              placeholder="+254 7XX XXX XXX"
              value={testPhone}
              onChange={(e) => setTestPhone(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-wangari-border text-sm focus:outline-none focus:ring-2 focus:ring-wangari-green-800/20 focus:border-wangari-green-800"
            />
          </div>
          <button
            onClick={sendTestMessage}
            disabled={!testPhone || sending}
            className="flex items-center gap-2 px-5 py-2.5 bg-wangari-green-800 text-white rounded-xl text-sm font-semibold hover:bg-wangari-green-900 transition-colors disabled:opacity-50 cursor-pointer"
          >
            <Send className="h-4 w-4" />
            {sending ? "Sending..." : "Send"}
          </button>
        </div>
        <p className="text-xs text-wangari-subtle mt-2">
          Requires WhatsApp Business API credentials. Currently logging messages for testing.
        </p>
      </motion.div>
    </motion.div>
  );
}
