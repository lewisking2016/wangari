import { proxyToBackend } from "@/lib/api-proxy";
import { ToolCall, ToolResult } from "@/types/ai";

/**
 * Executes a tool call by proxying to the Express backend.
 * Returns the tool result as a string.
 */
export async function executeTool(
  toolCall: ToolCall,
  authToken: string
): Promise<ToolResult> {
  const { name, arguments: argsStr } = toolCall.function;
  const args = JSON.parse(argsStr || "{}");

  try {
    const result = await callToolEndpoint(name, args, authToken);
    return {
      tool_call_id: toolCall.id,
      content: JSON.stringify(result),
    };
  } catch (error) {
    const message = error instanceof Error ? error.message : "Unknown error";
    return {
      tool_call_id: toolCall.id,
      content: JSON.stringify({ error: message }),
    };
  }
}

async function callToolEndpoint(
  toolName: string,
  args: Record<string, unknown>,
  authToken: string
): Promise<unknown> {
  const headers = { Authorization: `Bearer ${authToken}` };

  switch (toolName) {
    case "get_flock_summary":
      return proxyToBackend("/api/flocks", { headers }).then((r) => r.json());

    case "get_production_data": {
      const days = (args.days as number) || 7;
      return proxyToBackend(`/api/production?days=${days}`, { headers }).then((r) => r.json());
    }

    case "get_financial_summary": {
      const period = (args.period as string) || "month";
      return proxyToBackend(`/api/transactions?period=${period}`, { headers }).then((r) => r.json());
    }

    case "get_inventory_status":
      return proxyToBackend("/api/inventory", { headers }).then((r) => r.json());

    case "get_worker_info":
      return proxyToBackend("/api/workers", { headers }).then((r) => r.json());

    case "get_sales_data": {
      const days = (args.days as number) || 30;
      return proxyToBackend(`/api/sales?days=${days}`, { headers }).then((r) => r.json());
    }

    case "add_flock":
      return proxyToBackend("/api/flocks", {
        method: "POST",
        headers,
        body: JSON.stringify({
          name: args.name,
          breed: args.breed,
          initialCount: args.initialCount,
          type: args.type || "layer",
        }),
      }).then((r) => r.json());

    case "record_production":
      return proxyToBackend("/api/production", {
        method: "POST",
        headers,
        body: JSON.stringify({
          flockId: args.flockId,
          eggsCollected: args.eggsCollected,
          mortality: args.mortality || 0,
          feedUsed: args.feedUsed,
        }),
      }).then((r) => r.json());

    case "add_expense":
      return proxyToBackend("/api/transactions", {
        method: "POST",
        headers,
        body: JSON.stringify({
          type: "expense",
          amount: args.amount,
          category: args.category,
          description: args.description,
        }),
      }).then((r) => r.json());

    case "get_weather":
      return proxyToBackend("/api/weather", { headers }).then((r) => r.json());

    case "get_vaccination_schedule": {
      const flockId = args.flockId ? `?flockId=${args.flockId}` : "";
      return proxyToBackend(`/api/vaccinations${flockId}`, { headers }).then((r) => r.json());
    }

    default:
      throw new Error(`Unknown tool: ${toolName}`);
  }
}
