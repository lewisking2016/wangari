import { Router, Request, Response } from "express";
import { prisma } from "../db.js";

const router = Router();

// ─── Provider Configuration ───────────────────────────────
const AI_PROVIDER = process.env.AI_PROVIDER || "openai";
const AI_API_KEY = process.env.AI_API_KEY || "";
const AI_MODEL = process.env.AI_MODEL || getDefaultModel(AI_PROVIDER);
const OLLAMA_URL = process.env.OLLAMA_URL || "http://127.0.0.1:11434";

function getDefaultModel(provider: string): string {
  switch (provider) {
    case "openai": return "gpt-4o-mini";
    case "gemini": return "gemini-2.0-flash";
    case "anthropic": return "claude-3-haiku-20240307";
    case "ollama": return "qwen2.5:1.5b";
    default: return "gpt-4o-mini";
  }
}

// ─── Complete MCP Tool Definitions ────────────────────────
const mcpTools = [
  // Flocks
  { type: "function", function: { name: "list_flocks", description: "List all flocks with bird count, breed, status, and mortality", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_flock", description: "Add a new flock to the farm", parameters: { type: "object", properties: { name: { type: "string", description: "Flock name" }, breed: { type: "string", description: "Bird breed" }, initialCount: { type: "number", description: "Number of birds" }, type: { type: "string", description: "layer, broiler, or breeder", enum: ["layer", "broiler", "breeder"] } }, required: ["name", "initialCount"] } } },
  { type: "function", function: { name: "delete_flock", description: "Remove a flock from the farm", parameters: { type: "object", properties: { id: { type: "number", description: "Flock ID" } }, required: ["id"] } } },

  // Production
  { type: "function", function: { name: "list_production", description: "Get recent egg production data", parameters: { type: "object", properties: { days: { type: "number", description: "Days to retrieve (default 7)" } }, required: [] } } },
  { type: "function", function: { name: "record_production", description: "Record daily egg production for a flock", parameters: { type: "object", properties: { flockId: { type: "number", description: "Flock ID" }, eggsCollected: { type: "number", description: "Eggs collected" }, mortality: { type: "number", description: "Bird deaths" }, feedUsed: { type: "number", description: "Feed in kg" } }, required: ["flockId", "eggsCollected"] } } },

  // Transactions
  { type: "function", function: { name: "list_transactions", description: "Get financial transactions", parameters: { type: "object", properties: { period: { type: "string", description: "week, month, or year", enum: ["week", "month", "year"] } }, required: [] } } },
  { type: "function", function: { name: "create_transaction", description: "Record a financial transaction (income or expense)", parameters: { type: "object", properties: { type: { type: "string", description: "income or expense", enum: ["income", "expense"] }, amount: { type: "number", description: "Amount in KES" }, category: { type: "string", description: "Category (feed, labor, medication, eggsales, birdsales, etc.)" }, description: { type: "string", description: "Description" } }, required: ["type", "amount", "category", "description"] } } },
  { type: "function", function: { name: "delete_transaction", description: "Delete a transaction", parameters: { type: "object", properties: { id: { type: "number", description: "Transaction ID" } }, required: ["id"] } } },

  // Sales
  { type: "function", function: { name: "list_sales", description: "Get sales records", parameters: { type: "object", properties: { days: { type: "number", description: "Days to retrieve (default 30)" } }, required: [] } } },
  { type: "function", function: { name: "create_sale", description: "Record a new sale", parameters: { type: "object", properties: { totalAmount: { type: "number", description: "Total amount in KES" }, paymentStatus: { type: "string", description: "paid, pending, or partial", enum: ["paid", "pending", "partial"] }, amountPaid: { type: "number", description: "Amount paid in KES" }, notes: { type: "string", description: "Notes" } }, required: ["totalAmount"] } } },
  { type: "function", function: { name: "delete_sale", description: "Delete a sale", parameters: { type: "object", properties: { id: { type: "number", description: "Sale ID" } }, required: ["id"] } } },

  // Inventory
  { type: "function", function: { name: "list_inventory", description: "Get inventory items with stock levels", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_inventory_item", description: "Add item to inventory", parameters: { type: "object", properties: { itemName: { type: "string", description: "Item name" }, category: { type: "string", description: "Category (feed, medication, equipment)" }, quantity: { type: "number", description: "Quantity" }, unit: { type: "string", description: "Unit (kg, bags, pieces)" }, unitCost: { type: "number", description: "Cost per unit in KES" }, reorderLevel: { type: "number", description: "Minimum stock level" } }, required: ["itemName", "category", "quantity", "unit"] } } },
  { type: "function", function: { name: "delete_inventory_item", description: "Remove item from inventory", parameters: { type: "object", properties: { id: { type: "number", description: "Item ID" } }, required: ["id"] } } },

  // Workers
  { type: "function", function: { name: "list_workers", description: "Get all farm workers", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_worker", description: "Add a new worker", parameters: { type: "object", properties: { name: { type: "string", description: "Worker name" }, role: { type: "string", description: "Job role" }, dailyWage: { type: "number", description: "Daily wage in KES" }, phone: { type: "string", description: "Phone number" } }, required: ["name", "role", "dailyWage"] } } },
  { type: "function", function: { name: "delete_worker", description: "Remove a worker", parameters: { type: "object", properties: { id: { type: "number", description: "Worker ID" } }, required: ["id"] } } },

  // Customers
  { type: "function", function: { name: "list_customers", description: "Get all customers", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_customer", description: "Add a new customer", parameters: { type: "object", properties: { name: { type: "string", description: "Customer name" }, phone: { type: "string", description: "Phone" }, email: { type: "string", description: "Email" }, address: { type: "string", description: "Address" } }, required: ["name"] } } },
  { type: "function", function: { name: "delete_customer", description: "Remove a customer", parameters: { type: "object", properties: { id: { type: "number", description: "Customer ID" } }, required: ["id"] } } },

  // Vaccinations
  { type: "function", function: { name: "list_vaccinations", description: "Get vaccination records", parameters: { type: "object", properties: { flockId: { type: "number", description: "Filter by flock" } }, required: [] } } },
  { type: "function", function: { name: "create_vaccination", description: "Record a vaccination", parameters: { type: "object", properties: { flockId: { type: "number", description: "Flock ID" }, vaccineName: { type: "string", description: "Vaccine name" }, dosage: { type: "string", description: "Dosage" }, administeredBy: { type: "string", description: "Who administered" }, notes: { type: "string", description: "Notes" } }, required: ["flockId", "vaccineName"] } } },

  // Attendance
  { type: "function", function: { name: "list_attendance", description: "Get attendance records", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "record_attendance", description: "Record worker attendance", parameters: { type: "object", properties: { workerId: { type: "number", description: "Worker ID" }, status: { type: "string", description: "Status", enum: ["present", "absent", "late", "half_day"] }, notes: { type: "string", description: "Notes" } }, required: ["workerId", "status"] } } },

  // Weather
  { type: "function", function: { name: "get_weather", description: "Get current weather forecast", parameters: { type: "object", properties: {}, required: [] } } },

  // Dashboard
  { type: "function", function: { name: "get_dashboard", description: "Get dashboard summary with KPIs", parameters: { type: "object", properties: {}, required: [] } } },
];

const SYSTEM_PROMPT = `You are Wangari AI, an intelligent farm management assistant for poultry farms in Kenya.

You have FULL ACCESS to the farmer's farm management system. You can read, create, update, and delete any data.

CAPABILITIES:
- View and manage flocks (add/remove birds)
- Track egg production daily
- Manage finances (income/expenses)
- Track sales and customers
- Manage inventory and stock
- Manage workers and attendance
- Track vaccination schedules
- Check weather conditions
- Generate reports

RULES:
- Use KES (Kenyan Shillings) for all monetary values
- Be specific with numbers and dates
- When creating/modifying data, always confirm with the user first
- When deleting data, warn about consequences
- Provide actionable advice based on the data
- If data is missing, suggest what to record
- Always be helpful, concise, and professional

FARM KNOWLEDGE:
- Layer chickens lay 250-300 eggs/year
- FCR of 1.8-2.2 is good for layers
- Mortality under 1%/week is acceptable
- Common vaccines: Marek's, NDV, IB, Gumboro
- Feed is the biggest expense (60-70% of costs)

When the user asks you to do something, DO IT. Use the tools to read data, create records, and manage the farm.
If a user asks to add something, use the create tools. If they ask to see data, use the list tools.`;

// ─── Tool Executor ────────────────────────────────────────
async function executeTool(toolName: string, args: Record<string, any>, farmId: number): Promise<any> {
  switch (toolName) {
    case "list_flocks":
      return prisma.flock.findMany({ where: { farmId } });
    case "create_flock":
      return prisma.flock.create({ data: { name: args.name, breed: args.breed, currentCount: args.initialCount, initialCount: args.initialCount, type: args.type || "layer", farmId, status: "active" } });
    case "delete_flock":
      return prisma.flock.delete({ where: { id: args.id } });

    case "list_production": {
      const days = (args.days as number) || 7;
      const since = new Date(); since.setDate(since.getDate() - days);
      return prisma.dailyProduction.findMany({ where: { farmId, date: { gte: since } }, orderBy: { date: "desc" } });
    }
    case "record_production":
      return prisma.dailyProduction.create({ data: { flockId: args.flockId, eggsCollected: args.eggsCollected, mortality: args.mortality || 0, feedUsed: args.feedUsed, farmId } });

    case "list_transactions": {
      const now = new Date(); let since = new Date();
      const period = (args.period as string) || "month";
      if (period === "week") since.setDate(now.getDate() - 7);
      else if (period === "month") since.setMonth(now.getMonth() - 1);
      else since.setFullYear(now.getFullYear() - 1);
      return prisma.transaction.findMany({ where: { farmId, date: { gte: since } }, orderBy: { date: "desc" } });
    }
    case "create_transaction":
      return prisma.transaction.create({ data: { type: args.type, amount: args.amount, category: args.category, description: args.description, farmId } });
    case "delete_transaction":
      return prisma.transaction.delete({ where: { id: args.id } });

    case "list_sales": {
      const days = (args.days as number) || 30;
      const since = new Date(); since.setDate(since.getDate() - days);
      return prisma.sale.findMany({ where: { farmId, date: { gte: since } }, orderBy: { date: "desc" } });
    }
    case "create_sale":
      return prisma.sale.create({ data: { totalAmount: args.totalAmount, paymentStatus: args.paymentStatus || "pending", amountPaid: args.amountPaid || 0, notes: args.notes, farmId } });
    case "delete_sale":
      return prisma.sale.delete({ where: { id: args.id } });

    case "list_inventory":
      return prisma.inventory.findMany({ where: { farmId } });
    case "create_inventory_item":
      return prisma.inventory.create({ data: { itemName: args.itemName, category: args.category, quantity: args.quantity, unit: args.unit, unitCost: args.unitCost, reorderLevel: args.reorderLevel, farmId } });
    case "delete_inventory_item":
      return prisma.inventory.delete({ where: { id: args.id } });

    case "list_workers":
      return prisma.worker.findMany({ where: { farmId } });
    case "create_worker":
      return prisma.worker.create({ data: { name: args.name, role: args.role, dailyWage: args.dailyWage, phone: args.phone, farmId } });
    case "delete_worker":
      return prisma.worker.delete({ where: { id: args.id } });

    case "list_customers":
      return prisma.customer.findMany({ where: { farmId } });
    case "create_customer":
      return prisma.customer.create({ data: { name: args.name, phone: args.phone, email: args.email, address: args.address, farmId } });
    case "delete_customer":
      return prisma.customer.delete({ where: { id: args.id } });

    case "list_vaccinations": {
      const where: any = { farmId };
      if (args.flockId) where.flockId = args.flockId;
      return prisma.vaccination.findMany({ where, orderBy: { date: "desc" } });
    }
    case "create_vaccination":
      return prisma.vaccination.create({ data: { flockId: args.flockId, vaccineName: args.vaccineName, dosage: args.dosage, administeredBy: args.administeredBy, notes: args.notes, farmId } });

    case "list_attendance":
      return prisma.attendance.findMany({ where: { farmId }, orderBy: { date: "desc" } });
    case "record_attendance":
      return prisma.attendance.create({ data: { workerId: args.workerId, status: args.status, notes: args.notes, farmId } });

    case "get_weather":
      return { note: "Weather available via /api/weather" };

    case "get_dashboard":
      return prisma.flock.findMany({ where: { farmId } });

    default:
      return { error: `Unknown tool: ${toolName}` };
  }
}

// ─── AI Provider Adapters ─────────────────────────────────
async function callAI(messages: any[], tools: any[]): Promise<{ content: string; tool_calls: any[] }> {
  switch (AI_PROVIDER) {
    case "openai": return callOpenAI(messages, tools);
    case "gemini": return callGemini(messages, tools);
    case "anthropic": return callAnthropic(messages, tools);
    case "ollama": return callOllama(messages, tools);
    default: return callOpenAI(messages, tools);
  }
}

async function callOpenAI(messages: any[], tools: any[]): Promise<{ content: string; tool_calls: any[] }> {
  const res = await fetch("https://api.openai.com/v1/chat/completions", {
    method: "POST",
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${AI_API_KEY}` },
    body: JSON.stringify({ model: AI_MODEL, messages, tools, temperature: 0.7, max_tokens: 4096 }),
  });
  if (!res.ok) { const err = await res.text(); console.error("OpenAI:", err); throw new Error(`OpenAI: ${res.status}`); }
  const data = await res.json();
  return { content: data.choices?.[0]?.message?.content || "", tool_calls: data.choices?.[0]?.message?.tool_calls || [] };
}

async function callGemini(messages: any[], tools: any[]): Promise<{ content: string; tool_calls: any[] }> {
  const systemMsg = messages.find((m: any) => m.role === "system");
  const contents = messages.filter((m: any) => m.role !== "system").map((m: any) => ({ role: m.role === "assistant" ? "model" : "user", parts: [{ text: m.content }] }));
  const geminiTools = [{ function_declarations: tools.map((t: any) => ({ name: t.function.name, description: t.function.description, parameters: t.function.parameters })) }];
  const res = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/${AI_MODEL}:generateContent?key=${AI_API_KEY}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ contents, systemInstruction: systemMsg ? { parts: [{ text: systemMsg.content }] } : undefined, tools: geminiTools, generationConfig: { temperature: 0.7, maxOutputTokens: 4096 } }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Gemini:", err); throw new Error(`Gemini: ${res.status}`); }
  const data = await res.json();
  const candidate = data.candidates?.[0];
  const content = candidate?.content?.parts?.find((p: any) => p.text)?.text || "";
  const toolCalls = candidate?.content?.parts?.filter((p: any) => p.functionCall)?.map((p: any, i: number) => ({ id: `call_${Date.now()}_${i}`, type: "function", function: { name: p.functionCall.name, arguments: JSON.stringify(p.functionCall.args) } })) || [];
  return { content, tool_calls: toolCalls };
}

async function callAnthropic(messages: any[], tools: any[]): Promise<{ content: string; tool_calls: any[] }> {
  const sys = messages.find((m: any) => m.role === "system");
  const chatMsgs = messages.filter((m: any) => m.role !== "system").map((m: any) => ({ role: m.role, content: m.content }));
  const res = await fetch("https://api.anthropic.com/v1/messages", {
    method: "POST",
    headers: { "Content-Type": "application/json", "x-api-key": AI_API_KEY, "anthropic-version": "2023-06-01" },
    body: JSON.stringify({ model: AI_MODEL, max_tokens: 4096, system: sys?.content, messages: chatMsgs, tools: tools.map((t: any) => ({ name: t.function.name, description: t.function.description, input_schema: t.function.parameters })) }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Anthropic:", err); throw new Error(`Anthropic: ${res.status}`); }
  const data = await res.json();
  const content = data.content?.find((b: any) => b.type === "text")?.text || "";
  const toolCalls = data.content?.filter((b: any) => b.type === "tool_use")?.map((b: any, i: number) => ({ id: b.id || `call_${Date.now()}_${i}`, type: "function", function: { name: b.name, arguments: JSON.stringify(b.input) } })) || [];
  return { content, tool_calls: toolCalls };
}

async function callOllama(messages: any[], tools: any[]): Promise<{ content: string; tool_calls: any[] }> {
  const res = await fetch(`${OLLAMA_URL}/api/chat`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ model: AI_MODEL, messages, tools, stream: false, options: { temperature: 0.7, num_ctx: 8192 } }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Ollama:", err); throw new Error(`Ollama: ${res.status}`); }
  const data = await res.json();
  return { content: data.message?.content || "", tool_calls: data.message?.tool_calls || [] };
}

// ─── Chat Endpoint ────────────────────────────────────────
router.post("/chat", async (req: Request, res: Response) => {
  try {
    const { messages, farmId } = req.body;
    if (!messages || !Array.isArray(messages)) return res.status(400).json({ error: "Messages array required" });
    if (!AI_API_KEY && AI_PROVIDER !== "ollama") return res.status(500).json({ error: `Set AI_API_KEY for ${AI_PROVIDER}` });

    const fullMessages = [{ role: "system", content: SYSTEM_PROMPT }, ...messages];
    const response = await callAI(fullMessages, mcpTools);

    const toolResults: any[] = [];
    if (response.tool_calls.length > 0 && farmId) {
      for (const tc of response.tool_calls) {
        const args = typeof tc.function.arguments === "string" ? JSON.parse(tc.function.arguments) : tc.function.arguments;
        const result = await executeTool(tc.function.name, args, farmId);
        toolResults.push({ tool_call_id: tc.id, name: tc.function.name, content: JSON.stringify(result) });
      }

      const followUp = [...fullMessages, { role: "assistant", content: response.content, tool_calls: response.tool_calls }, ...toolResults.map((tr: any) => ({ role: "tool", content: `Tool ${tr.name}: ${tr.content}` }))];
      const final = await callAI(followUp, mcpTools);
      return res.json({ message: { role: "assistant", content: final.content || response.content }, tool_calls: response.tool_calls, tool_results: toolResults });
    }

    return res.json({ message: { role: "assistant", content: response.content }, tool_calls: response.tool_calls.length > 0 ? response.tool_calls : undefined, tool_results: toolResults.length > 0 ? toolResults : undefined });
  } catch (error) {
    console.error("AI chat error:", error);
    res.status(500).json({ error: error instanceof Error ? error.message : "Unknown error" });
  }
});

router.get("/health", (_req: Request, res: Response) => {
  res.json({ status: AI_API_KEY || AI_PROVIDER === "ollama" ? "configured" : "needs_api_key", provider: AI_PROVIDER, model: AI_MODEL, hasApiKey: !!AI_API_KEY });
});

export default router;
