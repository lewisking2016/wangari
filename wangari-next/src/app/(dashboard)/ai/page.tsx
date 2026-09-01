"use client";

import { useState } from "react";
import { Send, Sparkles, Bot, User } from "lucide-react";

interface Message {
  role: "user" | "assistant";
  content: string;
}

const suggestedQuestions = [
  "How many eggs did my layers produce this week?",
  "What is my total feed cost this month?",
  "Which flock is most profitable?",
  "When should I vaccinate my broilers?",
];

export default function AIAssistantPage() {
  const [messages, setMessages] = useState<Message[]>([
    { role: "assistant", content: "Hello! I am your Wangari AI Assistant. Ask me anything about your farm — production data, costs, health alerts, or best practices." },
  ]);
  const [input, setInput] = useState("");

  const handleSend = () => {
    if (!input.trim()) return;
    const userMsg: Message = { role: "user", content: input };
    setMessages((prev) => [...prev, userMsg]);
    setInput("");
    setTimeout(() => {
      setMessages((prev) => [...prev, { role: "assistant", content: "I am still being trained on your farm data. Once connected to PostgreSQL, I will be able to answer real questions about your flocks, production, finances, and more." }]);
    }, 1000);
  };

  return (
    <div className="flex flex-col h-[calc(100vh-4rem)]">
      {/* Header */}
      <div className="px-6 py-4 border-b border-[#E5E7EB] bg-white">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]">
            <Sparkles className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-lg font-bold text-[#0F172A]">AI Assistant</h1>
            <p className="text-xs text-[#64748B]">Ask anything about your farm</p>
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
            <div className={`max-w-[70%] rounded-2xl px-4 py-3 text-sm leading-relaxed ${msg.role === "user" ? "bg-[#166534] text-white" : "bg-[#F0FDF4] text-[#334155] border border-[#E5E7EB]"}`}>
              {msg.content}
            </div>
            {msg.role === "user" && (
              <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0F172A] text-white">
                <User className="h-4 w-4" />
              </div>
            )}
          </div>
        ))}
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
            className="flex-1 rounded-full border border-[#E5E7EB] bg-white px-5 py-3 text-sm text-[#334155] placeholder:text-[#94A3B8] focus:outline-none focus:border-[#166534] focus:ring-2 focus:ring-[#166534]/20 transition-all"
          />
          <button type="submit" className="flex h-12 w-12 items-center justify-center rounded-full bg-[#166534] text-white hover:bg-[#14532D] transition-colors shadow-lg cursor-pointer">
            <Send className="h-4 w-4" />
          </button>
        </form>
      </div>
    </div>
  );
}