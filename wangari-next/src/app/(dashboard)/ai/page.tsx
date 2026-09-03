"use client";

import * as React from "react";
import {
  Send,
  Sparkles,
  Loader2,
  RotateCcw,
  Bird,
  Egg,
  TrendingUp,
  Package,
  Users,
  ShoppingCart,
  Wheat,
  DollarSign,
  Syringe,
  CloudSun,
  Zap,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { ChatMessage } from "@/components/ai/ChatMessage";

interface Message {
  role: "user" | "assistant";
  content: string;
  toolCalls?: Array<{ function: { name: string; arguments: string } }>;
  toolResults?: Array<{ content: string }>;
  id: string;
}

const QUICK_ACTIONS = [
  { icon: Bird, label: "Flock Overview", prompt: "Give me a summary of all my flocks" },
  { icon: Egg, label: "Egg Production", prompt: "How is my egg production this week?" },
  { icon: TrendingUp, label: "Financial Summary", prompt: "Show me my profit and expenses this month" },
  { icon: Package, label: "Inventory Status", prompt: "What items are running low in stock?" },
  { icon: Users, label: "Worker Report", prompt: "What are my current labor costs?" },
  { icon: ShoppingCart, label: "Sales Report", prompt: "Show me recent sales and pending payments" },
  { icon: Wheat, label: "Feed Analysis", prompt: "Analyze my feed costs and usage" },
  { icon: Syringe, label: "Vaccination", prompt: "What vaccinations are due for my flocks?" },
  { icon: CloudSun, label: "Weather", prompt: "What's the weather forecast for my farm?" },
  { icon: DollarSign, label: "Add Expense", prompt: "I want to record an expense" },
  { icon: Zap, label: "Farm Tips", prompt: "Give me tips to improve my farm productivity" },
  { icon: Bird, label: "Add Flock", prompt: "I want to add a new flock" },
];

export default function AIAssistantPage() {
  const [messages, setMessages] = React.useState<Message[]>([]);
  const [input, setInput] = React.useState("");
  const [loading, setLoading] = React.useState(false);
  const messagesEndRef = React.useRef<HTMLDivElement>(null);
  const inputRef = React.useRef<HTMLTextAreaElement>(null);
  const [sessionId] = React.useState(() => `session_${Date.now()}`);

  React.useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  // Auto-resize textarea
  React.useEffect(() => {
    if (inputRef.current) {
      inputRef.current.style.height = "auto";
      inputRef.current.style.height = `${Math.min(inputRef.current.scrollHeight, 200)}px`;
    }
  }, [input]);

  const handleSend = async (text?: string) => {
    const msg = text || input.trim();
    if (!msg || loading) return;

    const userMsg: Message = {
      role: "user",
      content: msg,
      id: `user_${Date.now()}`,
    };

    setMessages((prev) => [...prev, userMsg]);
    setInput("");
    setLoading(true);

    try {
      const res = await fetch("/api/ai/chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: msg, sessionId }),
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || "Failed to get response");
      }

      const assistantMsg: Message = {
        role: "assistant",
        content: data.message?.content || "I couldn't generate a response.",
        toolCalls: data.tool_calls,
        toolResults: data.tool_results,
        id: `assistant_${Date.now()}`,
      };

      setMessages((prev) => [...prev, assistantMsg]);
    } catch (error) {
      const errorMsg: Message = {
        role: "assistant",
        content: `Sorry, I encountered an error: ${error instanceof Error ? error.message : "Unknown error"}. Please make sure the AI service is running.`,
        id: `error_${Date.now()}`,
      };
      setMessages((prev) => [...prev, errorMsg]);
    } finally {
      setLoading(false);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  const handleReset = async () => {
    await fetch("/api/ai/chat", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sessionId }),
    });
    setMessages([]);
  };

  const isEmpty = messages.length === 0;

  return (
    <div className="flex flex-col h-[calc(100vh-4rem)] bg-gradient-to-b from-wangari-green-50/30 to-white">
      {/* Header */}
      <div className="flex items-center justify-between px-6 py-3 border-b border-wangari-border bg-white/80 backdrop-blur-xl">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-wangari-green-700 to-wangari-green-500 text-white shadow-sm">
            <Sparkles className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-lg font-bold text-wangari-heading">Wangari AI</h1>
            <p className="text-xs text-wangari-muted">Powered by local AI • Your data stays on your server</p>
          </div>
        </div>
        {messages.length > 0 && (
          <button
            onClick={handleReset}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-wangari-muted hover:text-wangari-heading hover:bg-wangari-green-50 transition-colors"
          >
            <RotateCcw className="h-3.5 w-3.5" />
            New Chat
          </button>
        )}
      </div>

      {/* Messages or Welcome */}
      {isEmpty ? (
        <div className="flex-1 flex flex-col items-center justify-center px-6">
          <div className="max-w-2xl w-full text-center mb-8">
            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-wangari-green-700 to-wangari-green-500 text-white shadow-lg mx-auto mb-4">
              <Sparkles className="h-8 w-8" />
            </div>
            <h2 className="text-2xl font-bold text-wangari-heading mb-2">
              Hi there! I&apos;m your farm AI assistant.
            </h2>
            <p className="text-sm text-wangari-muted max-w-md mx-auto">
              I can analyze your farm data, track production, manage finances, and help you make better decisions.
              Ask me anything or try a quick action below.
            </p>
          </div>

          {/* Quick Actions Grid */}
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 max-w-3xl w-full">
            {QUICK_ACTIONS.map((action) => (
              <button
                key={action.label}
                onClick={() => handleSend(action.prompt)}
                className="flex items-center gap-3 rounded-xl border border-wangari-border bg-white p-3 text-left hover:border-wangari-green-300 hover:bg-wangari-green-50 transition-all duration-200 group cursor-pointer"
              >
                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-wangari-green-50 text-wangari-green-700 group-hover:bg-wangari-green-100 transition-colors">
                  <action.icon className="h-4 w-4" />
                </div>
                <span className="text-sm font-medium text-wangari-heading truncate">{action.label}</span>
              </button>
            ))}
          </div>
        </div>
      ) : (
        <div className="flex-1 overflow-y-auto px-6 py-6">
          <div className="max-w-3xl mx-auto space-y-6">
            {messages.map((msg) => (
              <ChatMessage
                key={msg.id}
                role={msg.role}
                content={msg.content}
                toolCalls={msg.toolCalls}
                toolResults={msg.toolResults}
              />
            ))}
            {loading && (
              <div className="flex gap-3 justify-start">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-wangari-green-700 to-wangari-green-500 text-white shadow-sm">
                  <Sparkles className="h-4 w-4" />
                </div>
                <div className="rounded-2xl rounded-bl-md bg-white border border-wangari-border px-4 py-3 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                  <div className="flex items-center gap-2">
                    <Loader2 className="h-4 w-4 text-wangari-green-600 animate-spin" />
                    <span className="text-sm text-wangari-muted">Thinking...</span>
                  </div>
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>
        </div>
      )}

      {/* Input Area */}
      <div className={cn(
        "border-t border-wangari-border bg-white/80 backdrop-blur-xl",
        isEmpty ? "px-6 py-4" : "px-6 py-3"
      )}>
        <div className="max-w-3xl mx-auto">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              handleSend();
            }}
            className="flex items-end gap-3"
          >
            <div className="flex-1 relative">
              <textarea
                ref={inputRef}
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Ask about your farm..."
                disabled={loading}
                rows={1}
                className={cn(
                  "w-full resize-none rounded-2xl border border-wangari-border bg-white px-4 py-3 pr-12 text-sm text-wangari-heading",
                  "placeholder:text-wangari-subtle focus:outline-none focus:border-wangari-green-500 focus:ring-2 focus:ring-wangari-green-500/20",
                  "transition-all disabled:opacity-50",
                  isEmpty ? "text-base" : ""
                )}
              />
            </div>
            <button
              type="submit"
              disabled={loading || !input.trim()}
              className={cn(
                "flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-all duration-200",
                input.trim() && !loading
                  ? "bg-wangari-green-800 text-white shadow-md hover:bg-wangari-green-900 hover:shadow-lg cursor-pointer"
                  : "bg-gray-100 text-gray-400 cursor-not-allowed"
              )}
            >
              {loading ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <Send className="h-4 w-4" />
              )}
            </button>
          </form>
          <p className="text-[11px] text-wangari-subtle text-center mt-2">
            AI responses are generated from your farm data. Always verify important decisions.
          </p>
        </div>
      </div>
    </div>
  );
}
