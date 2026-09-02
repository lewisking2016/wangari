"use client";

import * as React from "react";
import { Send, Sparkles, Bot, User, Loader2 } from "lucide-react";
import api from "@/lib/api-client";

interface Message {
  role: "user" | "assistant";
  content: string;
}

interface FarmData {
  flocks: any[];
  production: any[];
  transactions: any[];
  inventory: any[];
  workers: any[];
  sales: any[];
}

function generateResponse(input: string, data: FarmData): string {
  const lower = input.toLowerCase();

  // Egg production queries
  if (lower.includes("egg") && (lower.includes("produce") || lower.includes("today") || lower.includes("week") || lower.includes("collection"))) {
    const total = data.production.reduce((s, r) => s + r.eggsCollected, 0);
    const recent = data.production.slice(-7);
    const weekTotal = recent.reduce((s, r) => s + r.eggsCollected, 0);
    return `🥚 **Egg Production Summary**\n\nTotal eggs collected: **${total.toLocaleString()}** eggs\nLast 7 days: **${weekTotal.toLocaleString()}** eggs (avg ${Math.round(weekTotal / 7)}/day)\n\nYour layer flocks are producing well. Keep up the consistent feeding schedule!`;
  }

  // Feed cost queries
  if (lower.includes("feed") || lower.includes("cost")) {
    const feedItems = data.inventory.filter(i => i.category?.toLowerCase().includes("feed"));
    const feedValue = feedItems.reduce((s, i) => s + Number(i.quantity) * Number(i.unitCost), 0);
    const feedCosts = data.transactions.filter((t: any) => t.category?.toLowerCase().includes("feed"));
    const totalFeedCost = feedCosts.reduce((s: number, t: any) => s + Number(t.amount), 0);
    return `🌾 **Feed Cost Analysis**\n\nCurrent feed inventory value: **KES ${feedValue.toLocaleString()}**\nTotal feed expenses recorded: **KES ${totalFeedCost.toLocaleString()}**\nFeed items in stock: **${feedItems.length}**\n\nTip: Monitor your feed conversion ratio — aim for 2.0-2.2 kg feed per kg body weight for broilers.`;
  }

  // Flock queries
  if (lower.includes("flock") || lower.includes("bird") || lower.includes("chicken")) {
    const totalBirds = data.flocks.reduce((s, f) => s + (f.currentCount || 0), 0);
    const activeFlocks = data.flocks.filter(f => f.status === "active");
    const flockList = activeFlocks.map(f => `• **${f.name}** (${f.breed || f.type}): ${f.currentCount} birds`).join("\n");
    return `🐔 **Flock Overview**\n\nTotal birds: **${totalBirds.toLocaleString()}** across **${activeFlocks.length}** active flocks\n\n${flockList || "No active flocks found."}\n\nMortality rate: **${data.flocks.length > 0 ? ((data.flocks.reduce((s, f) => s + f.mortality, 0) / (totalBirds + data.flocks.reduce((s, f) => s + f.mortality, 0)) * 100)).toFixed(1) : 0}%**\n\nYour flocks are healthy! Keep monitoring daily mortality.`;
  }

  // Profit / financial queries
  if (lower.includes("profit") || lower.includes("revenue") || lower.includes("income") || lower.includes("money")) {
    const income = data.transactions.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
    const expenses = data.transactions.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);
    const profit = income - expenses;
    const topCategories = data.transactions.filter((t: any) => t.type === "expense").reduce((acc: Record<string, number>, t: any) => {
      acc[t.category || "Other"] = (acc[t.category || "Other"] || 0) + Number(t.amount);
      return acc;
    }, {});
    const topExpense = Object.entries(topCategories).sort((a, b) => b[1] - a[1])[0];
    return `💰 **Financial Summary**\n\nTotal revenue: **KES ${income.toLocaleString()}**\nTotal expenses: **KES ${expenses.toLocaleString()}**\nNet profit: **KES ${profit.toLocaleString()}** ${profit >= 0 ? "✅" : "⚠️"}\n\nBiggest expense: **${topExpense ? topExpense[0] : "N/A"}** at KES ${(topExpense ? topExpense[1] : 0).toLocaleString()}\n\n${profit >= 0 ? "Your farm is profitable! Consider reinvesting in better feed or expanding your flock." : "You're running at a loss. Review your expenses — especially feed costs — and consider pricing adjustments."}`;
  }

  // Mortality / health queries
  if (lower.includes("mortality") || lower.includes("death") || lower.includes("disease") || lower.includes("health")) {
    const totalMortality = data.flocks.reduce((s, f) => s + f.mortality, 0);
    const totalBirds = data.flocks.reduce((s, f) => s + (f.currentCount || 0), 0);
    const prodMortality = data.production.reduce((s, r) => s + r.mortality, 0);
    const recentMortality = data.production.slice(-3).reduce((s, r) => s + r.mortality, 0);
    return `🩺 **Health & Mortality Report**\n\nTotal mortality: **${totalMortality + prodMortality}** birds\nRecent 3 days: **${recentMortality}** deaths\nMortality rate: **${totalBirds > 0 ? (((totalMortality + prodMortality) / (totalBirds + totalMortality + prodMortality)) * 100).toFixed(1) : 0}%**\n\n${recentMortality > 5 ? "⚠️ **Alert:** Elevated mortality detected in recent days. Check for:\n• Water supply issues\n• Feed quality problems\n• Temperature stress\n• Disease symptoms\n\nConsider consulting a veterinarian if this continues." : "✅ Mortality is within normal range (< 1% per week). Keep up the good management practices."}`;
  }

  // Worker queries
  if (lower.includes("worker") || lower.includes("team") || lower.includes("staff") || lower.includes("wage")) {
    const totalWages = data.workers.reduce((s, w) => s + Number(w.dailyWage || w.wage || 0), 0);
    const workerList = data.workers.map(w => `• **${w.name}** — ${w.role} — KES ${Number(w.dailyWage || w.wage || 0).toLocaleString()}/day`).join("\n");
    return `👷 **Team Overview**\n\nTotal workers: **${data.workers.length}**\nDaily wages: **KES ${totalWages.toLocaleString()}**\nMonthly cost estimate: **KES ${(totalWages * 30).toLocaleString()}**\n\n${workerList || "No workers registered yet."}\n\nTip: Track attendance to ensure accountability and optimize labor costs.`;
  }

  // Sales queries
  if (lower.includes("sale") || lower.includes("customer") || lower.includes("order")) {
    const totalSales = data.sales.reduce((s, sale) => s + Number(sale.totalAmount), 0);
    const paidSales = data.sales.filter(s => s.paymentStatus === "paid").reduce((s, sale) => s + Number(sale.amountPaid), 0);
    const pendingSales = totalSales - paidSales;
    return `🛒 **Sales Summary**\n\nTotal sales: **KES ${totalSales.toLocaleString()}** (${data.sales.length} orders)\nPaid: **KES ${paidSales.toLocaleString()}** ✅\nPending: **KES ${pendingSales.toLocaleString()}** ⏳\n\n${pendingSales > 0 ? "Follow up on pending payments to improve cash flow." : "All payments collected! Great job!"}`;
  }

  // Inventory queries
  if (lower.includes("inventory") || lower.includes("stock") || lower.includes("supply")) {
    const lowStock = data.inventory.filter(i => Number(i.quantity) <= i.reorderLevel);
    const totalValue = data.inventory.reduce((s, i) => s + Number(i.quantity) * Number(i.unitCost), 0);
    return `📦 **Inventory Status**\n\nTotal items: **${data.inventory.length}**\nInventory value: **KES ${totalValue.toLocaleString()}**\nLow stock items: **${lowStock.length}**\n\n${lowStock.length > 0 ? "⚠️ **Reorder needed:**\n" + lowStock.map(i => `• ${i.itemName}: ${i.quantity} ${i.unit} remaining (reorder at ${i.reorderLevel})`).join("\n") : "✅ All items are well-stocked."}`;
  }

  // Vaccination queries
  if (lower.includes("vaccin") || lower.includes("medication") || lower.includes("medicine")) {
    return `💉 **Vaccination Schedule**\n\nFor layer flocks in Kenya, follow this standard schedule:\n\n• **Day 1:** Marek's disease (usually done at hatchery)\n• **Day 7:** Newcastle disease (NDV — Hitchner B1)\n• **Day 14:** Infectious bronchitis (IB)\n• **Day 21:** NDV booster\n• **Day 28:** Gumboro (IBD)\n• **Week 8:** Fowl pox\n• **Week 10:** NDV + IBD booster\n• **Week 16:** NDV Lasota before laying\n\nAlways consult your local veterinarian for the best schedule for your area. Keep vaccination records in Wangari for tracking.`;
  }

  // Greeting
  if (lower.includes("hello") || lower.includes("hi") || lower.includes("hey") || lower === "hi") {
    return `Hello! 👋 I'm your Wangari AI Assistant. I can help you with:\n\n• 🐔 Flock health and management\n• 🥚 Production analysis\n• 💰 Financial summaries\n• 📦 Inventory status\n• 👷 Worker management\n• 💉 Vaccination schedules\n\nWhat would you like to know about your farm?`;
  }

  // Help
  if (lower.includes("help") || lower.includes("what can you")) {
    return `I can analyze your farm data and answer questions about:\n\n• **Production** — egg counts, mortality, feed usage\n• **Financials** — revenue, expenses, profit margins\n• **Flocks** — bird counts, health, growth\n• **Inventory** — stock levels, reorder alerts\n• **Workers** — team size, wages, costs\n• **Sales** — orders, payments, customers\n• **Health** — mortality trends, vaccination schedules\n\nJust ask me anything in plain English!`;
  }

  // Default
  return `I understand you're asking about "${input}". Here's what I can help with:\n\n• **Production data** — "How many eggs this week?"\n• **Financial analysis** — "What's my profit this month?"\n• **Flock overview** — "How many birds do I have?"\n• **Health status** — "Any mortality concerns?"\n• **Inventory** — "What's running low?"\n• **Workers** — "What are my labor costs?"\n• **Vaccination** — "When should I vaccinate?"\n\nTry asking one of these questions and I'll give you real data from your farm!`;
}

export default function AIAssistantPage() {
  const [messages, setMessages] = React.useState<Message[]>([
    { role: "assistant", content: "Hello! 👋 I'm your Wangari AI Assistant. Ask me anything about your farm — production data, costs, health alerts, or best practices.\n\nI have access to your live farm data including flocks, production records, transactions, inventory, workers, and sales." },
  ]);
  const [input, setInput] = React.useState("");
  const [loading, setLoading] = React.useState(false);
  const [farmData, setFarmData] = React.useState<FarmData | null>(null);
  const messagesEndRef = React.useRef<HTMLDivElement>(null);

  React.useEffect(() => {
    Promise.all([
      api.get("/api/flocks"),
      api.get("/api/production"),
      api.get("/api/transactions"),
      api.get("/api/inventory"),
      api.get("/api/workers"),
      api.get("/api/sales"),
    ]).then(([flocks, production, transactions, inventory, workers, sales]) => {
      setFarmData({ flocks, production, transactions, inventory, workers, sales });
    }).catch(() => {});
  }, []);

  React.useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const handleSend = () => {
    if (!input.trim() || loading) return;
    const userMsg: Message = { role: "user", content: input };
    setMessages((prev) => [...prev, userMsg]);
    setInput("");
    setLoading(true);

    setTimeout(() => {
      const response = farmData
        ? generateResponse(input, farmData)
        : "I'm still loading your farm data. Please try again in a moment.";
      setMessages((prev) => [...prev, { role: "assistant", content: response }]);
      setLoading(false);
    }, 800);
  };

  const suggestedQuestions = [
    "How many eggs this week?",
    "What's my profit this month?",
    "How many birds do I have?",
    "What's running low in inventory?",
    "When should I vaccinate?",
    "What are my labor costs?",
  ];

  return (
    <div className="flex flex-col h-[calc(100vh-4rem)]">
      {/* Header */}
      <div className="px-6 py-4 border-b border-[#E5E7EB] bg-white">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#166534] text-white">
            <Sparkles className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-lg font-bold text-[#0F172A]">AI Assistant</h1>
            <p className="text-xs text-[#64748B]">
              {farmData ? "Connected to your farm data" : "Loading farm data..."}
            </p>
          </div>
        </div>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto px-6 py-6 space-y-4">
        {messages.map((msg, i) => (
          <div key={i} className={`flex gap-3 ${msg.role === "user" ? "justify-end" : "justify-start"}`}>
            {msg.role === "assistant" && (
              <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#166534] text-white">
                <Bot className="h-4 w-4" />
              </div>
            )}
            <div className={`max-w-[75%] rounded-2xl px-4 py-3 text-sm leading-relaxed whitespace-pre-line ${
              msg.role === "user"
                ? "bg-[#166534] text-white"
                : "bg-[#F0FDF4] text-[#334155] border border-[#E5E7EB]"
            }`}>
              {msg.content.split("**").map((part, j) =>
                j % 2 === 1 ? <strong key={j}>{part}</strong> : part
              )}
            </div>
            {msg.role === "user" && (
              <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0F172A] text-white">
                <User className="h-4 w-4" />
              </div>
            )}
          </div>
        ))}
        {loading && (
          <div className="flex gap-3 justify-start">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#166534] text-white">
              <Bot className="h-4 w-4" />
            </div>
            <div className="bg-[#F0FDF4] border border-[#E5E7EB] rounded-2xl px-4 py-3">
              <Loader2 className="h-4 w-4 text-[#166534] animate-spin" />
            </div>
          </div>
        )}
        <div ref={messagesEndRef} />
      </div>

      {/* Suggested Questions */}
      {messages.length <= 1 && (
        <div className="px-6 pb-4">
          <p className="text-xs font-semibold text-[#64748B] mb-2">Try asking:</p>
          <div className="flex flex-wrap gap-2">
            {suggestedQuestions.map((q) => (
              <button key={q} onClick={() => { setInput(q); }} className="rounded-full border border-[#E5E7EB] bg-white px-4 py-2 text-xs font-medium text-[#334155] hover:border-[#166534] hover:text-[#166534] transition-colors cursor-pointer">
                {q}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Input */}
      <div className="px-6 py-4 border-t border-[#E5E7EB] bg-white">
        <form onSubmit={(e) => { e.preventDefault(); handleSend(); }} className="flex gap-3">
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Ask about your farm..."
            disabled={loading}
            className="flex-1 rounded-full border border-[#E5E7EB] bg-white px-5 py-3 text-sm text-[#334155] placeholder:text-[#94A3B8] focus:outline-none focus:border-[#166534] focus:ring-2 focus:ring-[#166534]/20 transition-all disabled:opacity-50"
          />
          <button type="submit" disabled={loading || !input.trim()} className="flex h-12 w-12 items-center justify-center rounded-full bg-[#166534] text-white hover:bg-[#14532D] transition-colors shadow-lg cursor-pointer disabled:opacity-50">
            <Send className="h-4 w-4" />
          </button>
        </form>
      </div>
    </div>
  );
}
