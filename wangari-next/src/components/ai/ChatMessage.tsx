"use client";

import * as React from "react";
import { Bot, User, Copy, Check, Wrench } from "lucide-react";
import { cn } from "@/lib/utils";

interface ChatMessageProps {
  role: "user" | "assistant";
  content: string;
  toolCalls?: Array<{ function: { name: string; arguments: string } }>;
  toolResults?: Array<{ content: string }>;
  isStreaming?: boolean;
}

/** Simple markdown-like renderer for AI responses */
function renderContent(content: string) {
  // Split by newlines and process each line
  const lines = content.split("\n");
  const elements: React.ReactNode[] = [];

  let inList = false;
  let listItems: string[] = [];

  const flushList = () => {
    if (listItems.length > 0) {
      elements.push(
        <ul key={`list-${elements.length}`} className="list-disc list-inside space-y-1 my-2">
          {listItems.map((item, i) => (
            <li key={i} className="text-sm text-wangari-heading/80">
              {renderInline(item)}
            </li>
          ))}
        </ul>
      );
      listItems = [];
      inList = false;
    }
  };

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const trimmed = line.trim();

    // Empty line
    if (!trimmed) {
      flushList();
      continue;
    }

    // List items
    if (trimmed.startsWith("• ") || trimmed.startsWith("- ") || trimmed.startsWith("* ")) {
      inList = true;
      listItems.push(trimmed.slice(2));
      continue;
    }

    // Numbered list
    if (/^\d+\.\s/.test(trimmed)) {
      inList = true;
      listItems.push(trimmed.replace(/^\d+\.\s/, ""));
      continue;
    }

    flushList();

    // Headers
    if (trimmed.startsWith("### ")) {
      elements.push(
        <h3 key={i} className="text-sm font-bold text-wangari-heading mt-3 mb-1">
          {renderInline(trimmed.slice(4))}
        </h3>
      );
    } else if (trimmed.startsWith("## ")) {
      elements.push(
        <h2 key={i} className="text-base font-bold text-wangari-heading mt-3 mb-1">
          {renderInline(trimmed.slice(3))}
        </h2>
      );
    } else {
      elements.push(
        <p key={i} className="text-sm text-wangari-heading/80 leading-relaxed">
          {renderInline(trimmed)}
        </p>
      );
    }
  }

  flushList();

  return elements;
}

function renderInline(text: string): React.ReactNode {
  // Process bold (**text**) and inline code (`text`)
  const parts = text.split(/(\*\*.*?\*\*|`.*?`)/g);
  return parts.map((part, i) => {
    if (part.startsWith("**") && part.endsWith("**")) {
      return <strong key={i} className="font-semibold text-wangari-heading">{part.slice(2, -2)}</strong>;
    }
    if (part.startsWith("`") && part.endsWith("`")) {
      return (
        <code key={i} className="px-1.5 py-0.5 rounded-md bg-wangari-green-50 text-wangari-green-800 text-xs font-mono">
          {part.slice(1, -1)}
        </code>
      );
    }
    return part;
  });
}

function ToolCallBadge({ name }: { name: string }) {
  const labels: Record<string, string> = {
    get_flock_summary: "📋 Flock Data",
    get_production_data: "🥚 Production Data",
    get_financial_summary: "💰 Financial Data",
    get_inventory_status: "📦 Inventory Data",
    get_worker_info: "👷 Worker Data",
    get_sales_data: "🛒 Sales Data",
    add_flock: "🐔 Adding Flock",
    record_production: "📝 Recording Production",
    add_expense: "💸 Adding Expense",
    get_weather: "🌤️ Weather Data",
    get_vaccination_schedule: "💉 Vaccination Data",
  };

  return (
    <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-wangari-green-50 border border-wangari-green-200 text-xs font-medium text-wangari-green-800 mb-2">
      <Wrench className="h-3 w-3" />
      <span>{labels[name] || name}</span>
      <span className="inline-block h-1.5 w-1.5 rounded-full bg-wangari-green-500 animate-pulse" />
    </div>
  );
}

export function ChatMessage({ role, content, toolCalls, toolResults, isStreaming }: ChatMessageProps) {
  const [copied, setCopied] = React.useState(false);

  const handleCopy = () => {
    navigator.clipboard.writeText(content);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className={cn("flex gap-3 group", role === "user" ? "justify-end" : "justify-start")}>
      {/* Avatar */}
      {role === "assistant" && (
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-wangari-green-700 to-wangari-green-500 text-white shadow-sm mt-0.5">
          <Bot className="h-4 w-4" />
        </div>
      )}

      {/* Message */}
      <div className={cn("max-w-[80%] min-w-0", role === "user" ? "order-1" : "order-2")}>
        {/* Tool calls */}
        {toolCalls && toolCalls.length > 0 && (
          <div className="flex flex-wrap gap-1.5 mb-2">
            {toolCalls.map((tc, i) => (
              <ToolCallBadge key={i} name={tc.function.name} />
            ))}
          </div>
        )}

        {/* Message bubble */}
        <div
          className={cn(
            "rounded-2xl px-4 py-3 text-sm leading-relaxed",
            role === "user"
              ? "bg-wangari-green-800 text-white rounded-br-md"
              : "bg-white border border-wangari-border text-wangari-heading rounded-bl-md shadow-[0_1px_3px_rgba(0,0,0,0.04)]"
          )}
        >
          {role === "assistant" ? (
            <div className="space-y-1">{renderContent(content)}</div>
          ) : (
            <p className="whitespace-pre-wrap">{content}</p>
          )}

          {isStreaming && (
            <span className="inline-block h-4 w-0.5 bg-wangari-green-500 animate-pulse ml-0.5" />
          )}
        </div>

        {/* Copy button */}
        {role === "assistant" && !isStreaming && content && (
          <div className="flex items-center gap-2 mt-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              onClick={handleCopy}
              className="flex items-center gap-1 text-[11px] text-wangari-muted hover:text-wangari-heading transition-colors"
            >
              {copied ? <Check className="h-3 w-3" /> : <Copy className="h-3 w-3" />}
              {copied ? "Copied" : "Copy"}
            </button>
          </div>
        )}
      </div>

      {/* User avatar */}
      {role === "user" && (
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-wangari-green-800 text-white shadow-sm mt-0.5 order-2">
          <User className="h-4 w-4" />
        </div>
      )}
    </div>
  );
}
