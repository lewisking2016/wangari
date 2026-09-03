import { proxyToBackend } from "@/lib/api-proxy";
import { MCPTool, mcpTools } from "@/lib/mcp-tools";

export interface ToolExecution {
  id: string;
  toolName: string;
  args: Record<string, any>;
  status: "pending" | "approved" | "running" | "completed" | "failed" | "rejected";
  result?: any;
  error?: string;
  timestamp: number;
}

/**
 * Execute an MCP tool against the backend.
 * Returns the tool result as a string.
 */
export async function executeMCPTool(
  toolName: string,
  args: Record<string, any>,
  authToken: string
): Promise<string> {
  const tool = mcpTools.find((t) => t.name === toolName);
  if (!tool) {
    return JSON.stringify({ error: `Unknown tool: ${toolName}` });
  }

  // Handle navigation (frontend action)
  if (tool.endpoint === "__navigate__") {
    return JSON.stringify({
      success: true,
      action: "navigate",
      page: args.page,
      url: `/${args.page}`,
    });
  }

  try {
    // Build URL with path params
    let endpoint = tool.endpoint;
    if (endpoint.includes(":id")) {
      const id = args.id || Object.values(args).find((v) => typeof v === "number");
      endpoint = endpoint.replace(":id", String(id));
    }

    // Build query params for GET requests
    const queryParams = new URLSearchParams();
    if (tool.method === "GET") {
      Object.entries(args).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== "") {
          queryParams.set(key, String(value));
        }
      });
    }

    const url = queryParams.toString() ? `${endpoint}?${queryParams}` : endpoint;

    const headers: Record<string, string> = {
      "Content-Type": "application/json",
    };
    if (authToken) {
      headers["Authorization"] = `Bearer ${authToken}`;
    }

    const res = await proxyToBackend(url, {
      method: tool.method,
      headers,
      ...(tool.method !== "GET" ? { body: JSON.stringify(args) } : {}),
    });

    const data = await res.json();

    if (!res.ok) {
      return JSON.stringify({
        error: data.error || `Request failed with status ${res.status}`,
        status: res.status,
      });
    }

    return JSON.stringify(data);
  } catch (error) {
    const msg = error instanceof Error ? error.message : "Unknown error";
    return JSON.stringify({ error: msg });
  }
}

/**
 * Get tool definition by name
 */
export function getToolDef(name: string): MCPTool | undefined {
  return mcpTools.find((t) => t.name === name);
}

/**
 * Check if a tool requires confirmation before execution
 */
export function requiresConfirmation(toolName: string): boolean {
  const tool = mcpTools.find((t) => t.name === toolName);
  return tool?.confirmationRequired ?? false;
}

/**
 * Format tool result for display
 */
export function formatToolResult(toolName: string, resultStr: string): string {
  try {
    const result = JSON.parse(resultStr);

    if (result.error) {
      return `❌ Error: ${result.error}`;
    }

    // Format based on tool type
    if (toolName.startsWith("list_")) {
      return formatListResult(toolName, result);
    }

    if (toolName.startsWith("create_")) {
      return formatCreateResult(toolName, result);
    }

    if (toolName.startsWith("delete_")) {
      return `✅ Deleted successfully`;
    }

    if (toolName === "navigate_to") {
      return `🔗 Navigating to ${result.page}...`;
    }

    if (toolName === "get_weather") {
      return formatWeatherResult(result);
    }

    if (toolName === "get_dashboard") {
      return formatDashboardResult(result);
    }

    return JSON.stringify(result, null, 2);
  } catch {
    return resultStr;
  }
}

function formatListResult(toolName: string, data: any): string {
  if (!Array.isArray(data)) return JSON.stringify(data);

  if (data.length === 0) return "No records found.";

  const count = data.length;

  if (toolName === "list_flocks") {
    const totalBirds = data.reduce((s: number, f: any) => s + (f.currentCount || 0), 0);
    const active = data.filter((f: any) => f.status === "active").length;
    const items = data.map((f: any) =>
      `• **${f.name}** (${f.breed || f.type || "unknown"}): ${f.currentCount} birds — ${f.status || "active"}`
    ).join("\n");
    return `🐔 **${count} flocks** (${totalBirds} total birds, ${active} active)\n\n${items}`;
  }

  if (toolName === "list_workers") {
    const totalWages = data.reduce((s: number, w: any) => s + Number(w.dailyWage || w.wage || 0), 0);
    const items = data.map((w: any) =>
      `• **${w.name}** — ${w.role} — KES ${Number(w.dailyWage || w.wage || 0).toLocaleString()}/day`
    ).join("\n");
    return `👷 **${count} workers** (KES ${totalWages.toLocaleString()}/day)\n\n${items}`;
  }

  if (toolName === "list_inventory") {
    const totalValue = data.reduce((s: number, i: any) => s + Number(i.quantity) * Number(i.unitCost || 0), 0);
    const lowStock = data.filter((i: any) => i.reorderLevel && Number(i.quantity) <= i.reorderLevel);
    const items = data.map((i: any) =>
      `• **${i.itemName || i.name}**: ${i.quantity} ${i.unit || ""} — KES ${Number(i.unitCost || 0).toLocaleString()}/unit`
    ).join("\n");
    return `📦 **${count} items** (KES ${totalValue.toLocaleString()} total)${lowStock.length > 0 ? `\n⚠️ ${lowStock.length} low stock` : ""}\n\n${items}`;
  }

  if (toolName === "list_transactions") {
    const income = data.filter((t: any) => t.type === "income").reduce((s: number, t: any) => s + Number(t.amount), 0);
    const expenses = data.filter((t: any) => t.type === "expense").reduce((s: number, t: any) => s + Number(t.amount), 0);
    const items = data.slice(0, 10).map((t: any) =>
      `• ${t.type === "income" ? "+" : "-"}KES ${Number(t.amount).toLocaleString()} — ${t.description || t.category}`
    ).join("\n");
    return `💰 **${count} transactions**\nIncome: KES ${income.toLocaleString()} | Expenses: KES ${expenses.toLocaleString()}\nNet: KES ${(income - expenses).toLocaleString()}\n\n${items}`;
  }

  if (toolName === "list_sales") {
    const total = data.reduce((s: number, sale: any) => s + Number(sale.totalAmount || sale.amount || 0), 0);
    const items = data.slice(0, 10).map((s: any) =>
      `• KES ${Number(s.totalAmount || s.amount || 0).toLocaleString()} — ${s.paymentStatus || "unknown"}`
    ).join("\n");
    return `🛒 **${count} sales** (KES ${total.toLocaleString()} total)\n\n${items}`;
  }

  // Generic list
  return `📋 **${count} records**\n\n${data.slice(0, 10).map((r: any) => `• ${JSON.stringify(r)}`).join("\n")}`;
}

function formatCreateResult(toolName: string, data: any): string {
  if (data.error) return `❌ Error: ${data.error}`;

  if (toolName === "create_flock") {
    return `✅ Created flock **${data.name || data.flock?.name}** with ${data.initialCount || data.flock?.initialCount} birds`;
  }
  if (toolName === "create_transaction") {
    return `✅ Recorded ${data.type} of KES ${Number(data.amount).toLocaleString()} — ${data.description}`;
  }
  if (toolName === "create_sale") {
    return `✅ Recorded sale of KES ${Number(data.totalAmount || data.amount).toLocaleString()}`;
  }
  if (toolName === "create_worker") {
    return `✅ Added worker **${data.name}** as ${data.role}`;
  }
  if (toolName === "create_customer") {
    return `✅ Added customer **${data.name}**`;
  }
  if (toolName === "create_inventory_item") {
    return `✅ Added **${data.itemName || data.name}** to inventory`;
  }
  if (toolName === "record_production") {
    return `✅ Recorded ${data.eggsCollected} eggs collected${data.mortality ? `, ${data.mortality} mortality` : ""}`;
  }
  if (toolName === "create_vaccination") {
    return `✅ Recorded vaccination: ${data.vaccineName}`;
  }
  if (toolName === "record_attendance") {
    return `✅ Recorded attendance for worker`;
  }

  return `✅ Created successfully`;
}

function formatWeatherResult(data: any): string {
  if (data.error) return `❌ Weather data unavailable`;
  if (!data.temperature) return JSON.stringify(data);

  return `🌤️ **Weather** — ${data.location || "Farm Location"}\n\n` +
    `🌡️ ${data.temperature}°C (feels like ${data.feelsLike || data.temperature}°C)\n` +
    `💧 Humidity: ${data.humidity}%\n` +
    `🌬️ Wind: ${data.windSpeed} km/h\n` +
    `☁️ ${data.condition || data.description || ""}`;
}

function formatDashboardResult(data: any): string {
  if (!data) return "No dashboard data available.";

  const parts = [];
  if (data.totalBirds) parts.push(`🐔 **${data.totalBirds}** birds`);
  if (data.eggsToday) parts.push(`🥚 **${data.eggsToday}** eggs today`);
  if (data.monthlyRevenue) parts.push(`💰 Revenue: **KES ${data.monthlyRevenue.toLocaleString()}**`);
  if (data.mortalityRate) parts.push(`💀 Mortality: **${data.mortalityRate}%**`);

  return parts.length > 0
    ? `📊 **Dashboard Summary**\n\n${parts.join("\n")}`
    : JSON.stringify(data, null, 2);
}
