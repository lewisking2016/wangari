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
  AlertCircle,
  PanelRightOpen,
  PanelRightClose,
  Activity,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { ChatMessage } from "@/components/ai/ChatMessage";
import { TaskPanel, TaskItem } from "@/components/ai/TaskPanel";

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
  const [tasks, setTasks] = React.useState<TaskItem[]>([]);
  const [showTaskPanel, setShowTaskPanel] = React.useState(true);
  const [providerStatus, setProviderStatus] = React.useState<any>(null);
  const [providers, setProviders] = React.useState<any[]>([]);
  const [mounted, setMounted] = React.useState(false);
  const messagesEndRef = React.useRef<HTMLDivElement>(null);
  const inputRef = React.useRef<HTMLTextAreaElement>(null);
  const [sessionId] = React.useState(() => `session_${Date.now()}`);

  React.useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  React.useEffect(() => {
    if (inputRef.current) {
      inputRef.current.style.height = "auto";
      inputRef.current.style.height = `${Math.min(inputRef.current.scrollHeight, 200)}px`;
    }
  }, [input]);

  React.useEffect(() => {
    setMounted(true);
    fetch("/api/ai/health")
      .then((r) => r.json())
      .then(setProviderStatus)
      .catch(() => {});
    fetch("/api/ai/providers")
      .then((r) => r.json())
      .then((data) => {
        if (Array.isArray(data)) {
          setProviders(data);
        } else if (data && typeof data === "object") {
          // Handle case where API returns object instead of array
          setProviders([]);
        }
      })
      .catch(() => setProviders([]));
  }, []);

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
      if (!res.ok) throw new Error(data.error || "Failed to get response");

      // Add tool calls as tasks
      if (data.tool_calls && data.tool_calls.length > 0) {
        const newTasks: TaskItem[] = data.tool_calls.map((tc: any, i: number) => {
          const args = typeof tc.function.arguments === "string"
            ? JSON.parse(tc.function.arguments)
            : tc.function.arguments || {};
          return {
            id: tc.id || `task_${Date.now()}_${i}`,
            toolName: tc.function.name,
            args,
            status: "completed" as const,
            result: data.tool_results?.[i]?.content,
            timestamp: Date.now(),
          };
        });
        setTasks((prev) => [...newTasks, ...prev]);
        setShowTaskPanel(true);
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
        content: `Sorry, I encountered an error: ${error instanceof Error ? error.message : "Unknown error"}.`,
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
    setTasks([]);
  };

  const isEmpty = messages.length === 0;

  return (
    <div className="flex h-[calc(100vh-4rem)]">
      {/* Main Chat Area */}
      <div className="flex-1 flex flex-col bg-gradient-to-b from-wangari-green-50/30 to-white min-w-0">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-3 border-b border-wangari-border bg-white/80 backdrop-blur-xl">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-wangari-green-700 to-wangari-green-500 text-white shadow-sm">
              <Sparkles className="h-5 w-5" />
            </div>
            <div>
              <h1 className="text-lg font-bold text-wangari-heading">Wangari AI Workspace</h1>
              <p className="text-xs text-wangari-muted">
                {providerStatus?.status === "configured"
                  ? `${providerStatus.providerName || providerStatus.provider} • ${providerStatus.model} • 28 farm tools`
                  : "Choose a free AI provider below"}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            {messages.length > 0 && (
              <button
                onClick={handleReset}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-wangari-muted hover:text-wangari-heading hover:bg-wangari-green-50 transition-colors"
              >
                <RotateCcw className="h-3.5 w-3.5" />
                New Chat
              </button>
            )}
            <button
              onClick={() => setShowTaskPanel(!showTaskPanel)}
              className={cn(
                "flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors",
                showTaskPanel
                  ? "bg-wangari-green-50 text-wangari-green-800"
                  : "text-wangari-muted hover:text-wangari-heading hover:bg-wangari-green-50"
              )}
            >
              {showTaskPanel ? <PanelRightClose className="h-3.5 w-3.5" /> : <PanelRightOpen className="h-3.5 w-3.5" />}
              Actions
              {tasks.length > 0 && (
                <span className="ml-1 h-4 w-4 rounded-full bg-wangari-green-600 text-white text-[10px] font-bold flex items-center justify-center">
                  {tasks.length}
                </span>
              )}
            </button>
          </div>
        </div>

        {/* Provider Warning */}
        {mounted && providerStatus && providerStatus.status !== "configured" && messages.length === 0 && (
          <div className="mx-6 mt-4 max-w-3xl">
            <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
              <AlertCircle className="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
              <div className="flex-1">
                <p className="text-sm font-semibold text-amber-800">AI provider not configured</p>
                        <p className="text-xs text-amber-700 mt-1">
                  Free providers (no credit card needed):
                </p>
                <div className="grid grid-cols-2 gap-2 mt-2">
                  {(Array.isArray(providers) ? providers : []).filter((p: any) => !p.creditCard).map((p: any) => (
                    <a key={p.id} href={p.setupUrl} target="_blank" rel="noopener noreferrer"
                      className="flex flex-col rounded-lg bg-white border border-amber-200 px-3 py-2 hover:border-amber-400 transition-colors">
                      <span className="text-xs font-semibold text-amber-800">{p.name}</span>
                      <span className="text-[10px] text-amber-600 mt-0.5">{p.rateLimit}</span>
                      <span className="text-[10px] text-amber-500 mt-0.5">{p.freeModels?.slice(0, 2).join(", ")}{p.freeModels?.length > 2 ? ` +${p.freeModels.length - 2} more` : ""}</span>
                    </a>
                  ))}
                </div>
                <p className="text-xs text-amber-600 mt-2 font-mono bg-white rounded-lg px-2 py-1 border border-amber-200">
                  AI_PROVIDER=gemini AI_API_KEY=your-key AI_MODEL=gemini-2.0-flash
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Messages or Welcome */}
        {isEmpty ? (
          <div className="flex-1 flex flex-col items-center justify-center px-6">
            <div className="max-w-2xl w-full text-center mb-8">
              <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-wangari-green-700 to-wangari-green-500 text-white shadow-lg mx-auto mb-4">
                <Sparkles className="h-8 w-8" />
              </div>
              <h2 className="text-2xl font-bold text-wangari-heading mb-2">
                Your AI Farm Workspace
              </h2>
              <p className="text-sm text-wangari-muted max-w-md mx-auto">
                I have full access to your farm management system. I can view, create, and manage
                flocks, production, finances, inventory, workers, and more.
              </p>
            </div>

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

            {/* Capabilities */}
            <div className="mt-8 max-w-2xl w-full">
              <p className="text-xs font-bold uppercase tracking-widest text-wangari-muted text-center mb-3">
                What I can do
              </p>
              <div className="flex flex-wrap justify-center gap-2">
                {[
                  "🐔 Manage Flocks", "🥚 Track Production", "💰 Handle Finances",
                  "🛒 Process Sales", "📦 Manage Inventory", "👷 Manage Workers",
                  "💉 Track Vaccinations", "📋 Record Attendance", "🌤️ Check Weather",
                ].map((cap) => (
                  <span key={cap} className="rounded-full border border-wangari-border bg-white px-3 py-1 text-xs text-wangari-heading">
                    {cap}
                  </span>
                ))}
              </div>
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
                      <span className="text-sm text-wangari-muted">Thinking & executing...</span>
                    </div>
                  </div>
                </div>
              )}
              <div ref={messagesEndRef} />
            </div>
          </div>
        )}

        {/* Input Area */}
        <div className="border-t border-wangari-border bg-white/80 backdrop-blur-xl px-6 py-3">
          <div className="max-w-3xl mx-auto">
            <form
              onSubmit={(e) => { e.preventDefault(); handleSend(); }}
              className="flex items-end gap-3"
            >
              <div className="flex-1 relative">
                <textarea
                  ref={inputRef}
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  onKeyDown={handleKeyDown}
                  placeholder="Ask me to do anything on your farm..."
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
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
              </button>
            </form>
            <p className="text-[11px] text-wangari-subtle text-center mt-2">
              AI has access to 28 farm tools • Actions are logged in the task panel
            </p>
          </div>
        </div>
      </div>

      {/* Task Panel (Right Side) */}
      {showTaskPanel && (
        <div className="w-[320px] border-l border-wangari-border bg-white flex flex-col">
          <TaskPanel tasks={tasks} onClear={() => setTasks([])} />
        </div>
      )}
    </div>
  );
}
