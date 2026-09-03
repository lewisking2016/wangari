import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { AI_PROVIDERS, getProvider, type AIProviderConfig } from "../ai-providers.js";

const router = Router();

// ─── Provider Configuration ───────────────────────────────
const AI_PROVIDER = process.env.AI_PROVIDER || "gemini";
const AI_API_KEY = process.env.AI_API_KEY || "";
const AI_BASE_URL = process.env.AI_BASE_URL || "";
const AI_MODEL = process.env.AI_MODEL || "";
const OLLAMA_URL = process.env.OLLAMA_URL || "http://127.0.0.1:11434";

function getProviderConfig(): AIProviderConfig & { model: string; baseUrl: string } {
  const provider = getProvider(AI_PROVIDER) || getProvider("gemini")!;
  return {
    ...provider,
    model: AI_MODEL || provider.defaultModel,
    baseUrl: AI_BASE_URL || provider.baseUrl,
  };
}

// ─── Complete MCP Tool Definitions ────────────────────────
const mcpTools = [
  { type: "function", function: { name: "list_flocks", description: "List all flocks with bird count, breed, status, and mortality", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_flock", description: "Add a new flock to the farm", parameters: { type: "object", properties: { name: { type: "string", description: "Flock name" }, breed: { type: "string", description: "Bird breed" }, initialCount: { type: "number", description: "Number of birds" }, type: { type: "string", description: "layer, broiler, or breeder", enum: ["layer", "broiler", "breeder"] } }, required: ["name", "initialCount"] } } },
  { type: "function", function: { name: "delete_flock", description: "Remove a flock from the farm", parameters: { type: "object", properties: { id: { type: "number", description: "Flock ID" } }, required: ["id"] } } },
  { type: "function", function: { name: "list_production", description: "Get recent egg production data", parameters: { type: "object", properties: { days: { type: "number", description: "Days to retrieve (default 7)" } }, required: [] } } },
  { type: "function", function: { name: "record_production", description: "Record daily egg production for a flock", parameters: { type: "object", properties: { flockId: { type: "number", description: "Flock ID" }, eggsCollected: { type: "number", description: "Eggs collected" }, mortality: { type: "number", description: "Bird deaths" }, feedUsed: { type: "number", description: "Feed in kg" } }, required: ["flockId", "eggsCollected"] } } },
  { type: "function", function: { name: "list_transactions", description: "Get financial transactions", parameters: { type: "object", properties: { period: { type: "string", description: "week, month, or year", enum: ["week", "month", "year"] } }, required: [] } } },
  { type: "function", function: { name: "create_transaction", description: "Record a financial transaction", parameters: { type: "object", properties: { type: { type: "string", description: "income or expense", enum: ["income", "expense"] }, amount: { type: "number", description: "Amount in KES" }, category: { type: "string", description: "Category" }, description: { type: "string", description: "Description" } }, required: ["type", "amount", "category", "description"] } } },
  { type: "function", function: { name: "delete_transaction", description: "Delete a transaction", parameters: { type: "object", properties: { id: { type: "number", description: "Transaction ID" } }, required: ["id"] } } },
  { type: "function", function: { name: "list_sales", description: "Get sales records", parameters: { type: "object", properties: { days: { type: "number", description: "Days (default 30)" } }, required: [] } } },
  { type: "function", function: { name: "create_sale", description: "Record a new sale", parameters: { type: "object", properties: { totalAmount: { type: "number", description: "Total in KES" }, paymentStatus: { type: "string", description: "paid, pending, partial", enum: ["paid", "pending", "partial"] }, amountPaid: { type: "number", description: "Amount paid" }, notes: { type: "string", description: "Notes" } }, required: ["totalAmount"] } } },
  { type: "function", function: { name: "delete_sale", description: "Delete a sale", parameters: { type: "object", properties: { id: { type: "number", description: "Sale ID" } }, required: ["id"] } } },
  { type: "function", function: { name: "list_inventory", description: "Get inventory items", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_inventory_item", description: "Add inventory item", parameters: { type: "object", properties: { itemName: { type: "string", description: "Item name" }, category: { type: "string", description: "Category" }, quantity: { type: "number", description: "Quantity" }, unit: { type: "string", description: "Unit" }, unitCost: { type: "number", description: "Cost per unit" }, reorderLevel: { type: "number", description: "Min stock level" } }, required: ["itemName", "category", "quantity", "unit"] } } },
  { type: "function", function: { name: "delete_inventory_item", description: "Remove inventory item", parameters: { type: "object", properties: { id: { type: "number", description: "Item ID" } }, required: ["id"] } } },
  { type: "function", function: { name: "list_workers", description: "Get all workers", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_worker", description: "Add a worker", parameters: { type: "object", properties: { name: { type: "string", description: "Name" }, role: { type: "string", description: "Role" }, dailyWage: { type: "number", description: "Daily wage KES" }, phone: { type: "string", description: "Phone" } }, required: ["name", "role", "dailyWage"] } } },
  { type: "function", function: { name: "delete_worker", description: "Remove a worker", parameters: { type: "object", properties: { id: { type: "number", description: "Worker ID" } }, required: ["id"] } } },
  { type: "function", function: { name: "list_customers", description: "Get customers", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_customer", description: "Add a customer", parameters: { type: "object", properties: { name: { type: "string", description: "Name" }, phone: { type: "string", description: "Phone" }, email: { type: "string", description: "Email" }, address: { type: "string", description: "Address" } }, required: ["name"] } } },
  { type: "function", function: { name: "delete_customer", description: "Remove a customer", parameters: { type: "object", properties: { id: { type: "number", description: "Customer ID" } }, required: ["id"] } } },
  { type: "function", function: { name: "list_vaccinations", description: "Get vaccination records", parameters: { type: "object", properties: { flockId: { type: "number", description: "Filter by flock" } }, required: [] } } },
  { type: "function", function: { name: "create_vaccination", description: "Record a vaccination", parameters: { type: "object", properties: { flockId: { type: "number", description: "Flock ID" }, vaccineName: { type: "string", description: "Vaccine name" }, dosage: { type: "string", description: "Dosage" }, administeredBy: { type: "string", description: "Administered by" }, notes: { type: "string", description: "Notes" } }, required: ["flockId", "vaccineName"] } } },
  { type: "function", function: { name: "list_attendance", description: "Get attendance records", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "record_attendance", description: "Record attendance", parameters: { type: "object", properties: { workerId: { type: "number", description: "Worker ID" }, status: { type: "string", description: "Status", enum: ["present", "absent", "late", "half_day"] }, notes: { type: "string", description: "Notes" } }, required: ["workerId", "status"] } } },
  { type: "function", function: { name: "list_crops", description: "List all crops with type, area, and growth stage", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "create_crop", description: "Register a new crop field", parameters: { type: "object", properties: { fieldName: { type: "string", description: "Field name" }, cropType: { type: "string", description: "Crop type" }, variety: { type: "string", description: "Variety" }, areaHectares: { type: "number", description: "Area in hectares" } }, required: ["fieldName", "cropType"] } } },
  { type: "function", function: { name: "get_weather", description: "Get weather forecast", parameters: { type: "object", properties: {}, required: [] } } },
  { type: "function", function: { name: "get_dashboard", description: "Get dashboard summary", parameters: { type: "object", properties: {}, required: [] } } },
];

const SYSTEM_PROMPT = `You are Wangari AI, an intelligent farm management assistant for mixed farms (livestock and crops) in Kenya.

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

When the user asks you to do something, DO IT. Use the tools to read data, create records, and manage the farm.`;

// ─── Tool Executor ────────────────────────────────────────
async function executeTool(toolName: string, args: Record<string, any>, farmId: number): Promise<any> {
  switch (toolName) {
    case "list_flocks": return prisma.flock.findMany({ where: { farmId } });
    case "create_flock": return prisma.flock.create({ data: { name: args.name, breed: args.breed, currentCount: args.initialCount, initialCount: args.initialCount, type: args.type || "layer", farmId, status: "active" } });
    case "delete_flock": return prisma.flock.delete({ where: { id: args.id } });
    case "list_production": { const d = (args.days as number) || 7; const s = new Date(); s.setDate(s.getDate() - d); return prisma.dailyProduction.findMany({ where: { farmId, date: { gte: s } }, orderBy: { date: "desc" } }); }
    case "record_production": return prisma.dailyProduction.create({ data: { flockId: args.flockId, eggsCollected: args.eggsCollected, mortality: args.mortality || 0, feedUsed: args.feedUsed, farmId } });
    case "list_transactions": { const now = new Date(); let s = new Date(); const p = (args.period as string) || "month"; if (p === "week") s.setDate(now.getDate() - 7); else if (p === "month") s.setMonth(now.getMonth() - 1); else s.setFullYear(now.getFullYear() - 1); return prisma.transaction.findMany({ where: { farmId, date: { gte: s } }, orderBy: { date: "desc" } }); }
    case "create_transaction": return prisma.transaction.create({ data: { type: args.type, amount: args.amount, category: args.category, description: args.description, farmId } });
    case "delete_transaction": return prisma.transaction.delete({ where: { id: args.id } });
    case "list_sales": { const d = (args.days as number) || 30; const s = new Date(); s.setDate(s.getDate() - d); return prisma.sale.findMany({ where: { farmId, date: { gte: s } }, orderBy: { date: "desc" } }); }
    case "create_sale": return prisma.sale.create({ data: { totalAmount: args.totalAmount, paymentStatus: args.paymentStatus || "pending", amountPaid: args.amountPaid || 0, notes: args.notes, farmId } });
    case "delete_sale": return prisma.sale.delete({ where: { id: args.id } });
    case "list_inventory": return prisma.inventory.findMany({ where: { farmId } });
    case "create_inventory_item": return prisma.inventory.create({ data: { itemName: args.itemName, category: args.category, quantity: args.quantity, unit: args.unit, unitCost: args.unitCost, reorderLevel: args.reorderLevel, farmId } });
    case "delete_inventory_item": return prisma.inventory.delete({ where: { id: args.id } });
    case "list_workers": return prisma.worker.findMany({ where: { farmId } });
    case "create_worker": return prisma.worker.create({ data: { name: args.name, role: args.role, dailyWage: args.dailyWage, phone: args.phone, farmId } });
    case "delete_worker": return prisma.worker.delete({ where: { id: args.id } });
    case "list_customers": return prisma.customer.findMany({ where: { farmId } });
    case "create_customer": return prisma.customer.create({ data: { name: args.name, phone: args.phone, email: args.email, address: args.address, farmId } });
    case "delete_customer": return prisma.customer.delete({ where: { id: args.id } });
    case "list_vaccinations": { const w: any = { farmId }; if (args.flockId) w.flockId = args.flockId; return prisma.vaccination.findMany({ where: w, orderBy: { date: "desc" } }); }
    case "create_vaccination": return prisma.vaccination.create({ data: { flockId: args.flockId, vaccineName: args.vaccineName, dosage: args.dosage, administeredBy: args.administeredBy, notes: args.notes, farmId } });
    case "list_attendance": return prisma.attendance.findMany({ where: { farmId }, orderBy: { date: "desc" } });
    case "record_attendance": return prisma.attendance.create({ data: { workerId: args.workerId, status: args.status, notes: args.notes, farmId } });
    case "list_crops": return prisma.crop.findMany({ where: { farmId }, include: { harvests: true } });
    case "create_crop": return prisma.crop.create({ data: { fieldName: args.fieldName, cropType: args.cropType, variety: args.variety, areaHectares: args.areaHectares ? Number(args.areaHectares) : null, farmId } });
    case "get_weather": return { note: "Weather available via /api/weather" };
    case "get_dashboard": return prisma.flock.findMany({ where: { farmId } });
    default: return { error: `Unknown tool: ${toolName}` };
  }
}

// ─── Unified AI Caller ────────────────────────────────────
async function callAI(messages: any[], tools: any[]): Promise<{ content: string; tool_calls: any[] }> {
  const config = getProviderConfig();

  // Special handling for non-OpenAI-compatible providers
  if (config.id === "gemini") return callGemini(messages, tools, config);
  if (config.id === "anthropic") return callAnthropic(messages, tools, config);
  if (config.id === "cohere") return callCohere(messages, tools, config);
  if (config.id === "cloudflare") return callCloudflare(messages, tools, config);
  if (config.id === "ollama") return callOllama(messages, tools, config);

  // All OpenAI-compatible providers (OpenRouter, Groq, Cerebras, Mistral, GitHub, NVIDIA, DeepSeek, OpenAI)
  return callOpenAICompatible(messages, tools, config);
}

// ─── OpenAI-Compatible (most providers) ───────────────────
async function callOpenAICompatible(messages: any[], tools: any[], config: ReturnType<typeof getProviderConfig>): Promise<{ content: string; tool_calls: any[] }> {
  const res = await fetch(`${config.baseUrl}/chat/completions`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${AI_API_KEY}` },
    body: JSON.stringify({ model: config.model, messages, tools, temperature: 0.7, max_tokens: 4096 }),
  });
  if (!res.ok) { const err = await res.text(); console.error(`${config.name}:`, err); throw new Error(`${config.name}: ${res.status}`); }
  const data: any = await res.json();
  return { content: data.choices?.[0]?.message?.content || "", tool_calls: data.choices?.[0]?.message?.tool_calls || [] };
}

// ─── Gemini ───────────────────────────────────────────────
async function callGemini(messages: any[], tools: any[], config: ReturnType<typeof getProviderConfig>): Promise<{ content: string; tool_calls: any[] }> {
  const sys = messages.find((m: any) => m.role === "system");
  const contents = messages.filter((m: any) => m.role !== "system").map((m: any) => ({ role: m.role === "assistant" ? "model" : "user", parts: [{ text: m.content }] }));
  const geminiTools = [{ function_declarations: tools.map((t: any) => ({ name: t.function.name, description: t.function.description, parameters: t.function.parameters })) }];
  const res = await fetch(`${config.baseUrl}/models/${config.model}:generateContent?key=${AI_API_KEY}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ contents, systemInstruction: sys ? { parts: [{ text: sys.content }] } : undefined, tools: geminiTools, generationConfig: { temperature: 0.7, maxOutputTokens: 4096 } }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Gemini:", err); throw new Error(`Gemini: ${res.status}`); }
  const data: any = await res.json();
  const c = data.candidates?.[0];
  const content = c?.content?.parts?.find((p: any) => p.text)?.text || "";
  const toolCalls = c?.content?.parts?.filter((p: any) => p.functionCall)?.map((p: any, i: number) => ({ id: `call_${Date.now()}_${i}`, type: "function", function: { name: p.functionCall.name, arguments: JSON.stringify(p.functionCall.args) } })) || [];
  return { content, tool_calls: toolCalls };
}

// ─── Anthropic ────────────────────────────────────────────
async function callAnthropic(messages: any[], tools: any[], config: ReturnType<typeof getProviderConfig>): Promise<{ content: string; tool_calls: any[] }> {
  const sys = messages.find((m: any) => m.role === "system");
  const chatMsgs = messages.filter((m: any) => m.role !== "system").map((m: any) => ({ role: m.role, content: m.content }));
  const res = await fetch(`${config.baseUrl}/messages`, {
    method: "POST",
    headers: { "Content-Type": "application/json", "x-api-key": AI_API_KEY, "anthropic-version": "2023-06-01" },
    body: JSON.stringify({ model: config.model, max_tokens: 4096, system: sys?.content, messages: chatMsgs, tools: tools.map((t: any) => ({ name: t.function.name, description: t.function.description, input_schema: t.function.parameters })) }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Anthropic:", err); throw new Error(`Anthropic: ${res.status}`); }
  const data: any = await res.json();
  const content = data.content?.find((b: any) => b.type === "text")?.text || "";
  const toolCalls = data.content?.filter((b: any) => b.type === "tool_use")?.map((b: any, i: number) => ({ id: b.id || `call_${Date.now()}_${i}`, type: "function", function: { name: b.name, arguments: JSON.stringify(b.input) } })) || [];
  return { content, tool_calls: toolCalls };
}

// ─── Cohere ───────────────────────────────────────────────
async function callCohere(messages: any[], tools: any[], config: ReturnType<typeof getProviderConfig>): Promise<{ content: string; tool_calls: any[] }> {
  const sys = messages.find((m: any) => m.role === "system");
  const chatMsgs = messages.filter((m: any) => m.role !== "system").map((m: any) => ({ role: m.role === "assistant" ? "CHATBOT" : "USER", message: m.content }));
  const cohereTools = tools.map((t: any) => ({ name: t.function.name, description: t.function.description, parameter_definitions: t.function.parameters.properties }));
  const res = await fetch(`${config.baseUrl}/chat`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${AI_API_KEY}` },
    body: JSON.stringify({ model: config.model, messages: chatMsgs, preamble: sys?.content, tools: cohereTools }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Cohere:", err); throw new Error(`Cohere: ${res.status}`); }
  const data: any = await res.json();
  const content = data.message?.content?.[0]?.text || "";
  const toolCalls = data.message?.tool_calls?.map((tc: any, i: number) => ({ id: `call_${Date.now()}_${i}`, type: "function", function: { name: tc.name, arguments: JSON.stringify(tc.parameters) } })) || [];
  return { content, tool_calls: toolCalls };
}

// ─── Cloudflare Workers AI ────────────────────────────────
async function callCloudflare(messages: any[], tools: any[], config: ReturnType<typeof getProviderConfig>): Promise<{ content: string; tool_calls: any[] }> {
  // Cloudflare doesn't support tool calling, so we just do chat
  const sys = messages.find((m: any) => m.role === "system");
  const userMsg = messages.filter((m: any) => m.role !== "system").map((m: any) => m.content).join("\n");
  const input = sys ? `${sys.content}\n\n${userMsg}` : userMsg;
  const accountId = process.env.CLOUDFLARE_ACCOUNT_ID || "";
  const res = await fetch(`${config.baseUrl.replace("{account_id}", accountId)}/${config.model}`, {
    method: "POST",
    headers: { Authorization: `Bearer ${AI_API_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({ messages: [{ role: "user", content: input }] }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Cloudflare:", err); throw new Error(`Cloudflare: ${res.status}`); }
  const data: any = await res.json();
  return { content: data.result?.response || "", tool_calls: [] };
}

// ─── Ollama (local) ──────────────────────────────────────
async function callOllama(messages: any[], tools: any[], _config: ReturnType<typeof getProviderConfig>): Promise<{ content: string; tool_calls: any[] }> {
  const res = await fetch(`${OLLAMA_URL}/api/chat`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ model: AI_MODEL || "qwen2.5:1.5b", messages, tools, stream: false, options: { temperature: 0.7, num_ctx: 8192 } }),
  });
  if (!res.ok) { const err = await res.text(); console.error("Ollama:", err); throw new Error(`Ollama: ${res.status}`); }
  const data: any = await res.json();
  return { content: data.message?.content || "", tool_calls: data.message?.tool_calls || [] };
}

// ─── Chat Endpoint ────────────────────────────────────────
router.post("/chat", async (req: Request, res: Response) => {
  try {
    const { messages, farmId } = req.body;
    if (!messages || !Array.isArray(messages)) return res.status(400).json({ error: "Messages array required" });
    if (!AI_API_KEY && AI_PROVIDER !== "ollama") return res.status(500).json({ error: `Set AI_API_KEY for ${AI_PROVIDER}. Get a free key at ${getProvider(AI_PROVIDER)?.setupUrl || ""}` });

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

// ─── Providers List ───────────────────────────────────────
router.get("/providers", (_req: Request, res: Response) => {
  const providers = Object.values(AI_PROVIDERS).map((p) => ({
    id: p.id,
    name: p.name,
    description: p.description,
    freeModels: p.freeModels,
    rateLimit: p.rateLimit,
    creditCard: p.creditCard,
    website: p.website,
    setupUrl: p.setupUrl,
    configured: p.id === AI_PROVIDER && !!AI_API_KEY,
  }));
  res.json(providers);
});

router.get("/health", (_req: Request, res: Response) => {
  const config = getProviderConfig();
  res.json({
    status: AI_API_KEY || AI_PROVIDER === "ollama" ? "configured" : "needs_api_key",
    provider: config.id,
    providerName: config.name,
    model: config.model,
    hasApiKey: !!AI_API_KEY,
    setupUrl: config.setupUrl,
  });
});

export default router;
