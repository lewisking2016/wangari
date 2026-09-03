import { Router, Request, Response } from "express";
import { prisma } from "../db.js";

const router = Router();

// ─── Provider Configuration ───────────────────────────────
// Set these in your .env file:
//   AI_PROVIDER=openai|gemini|anthropic|ollama
//   AI_API_KEY=your-api-key
//   AI_MODEL=gpt-4o-mini|gemini-2.0-flash|claude-3-haiku-20240307|qwen2.5:1.5b

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

// ─── Farm Tools Definition ────────────────────────────────
const farmTools = [
  {
    type: "function",
    function: {
      name: "get_flock_summary",
      description: "Get a summary of all flocks including bird count, breed, status, and mortality",
      parameters: { type: "object", properties: {}, required: [] },
    },
  },
  {
    type: "function",
    function: {
      name: "get_production_data",
      description: "Get recent egg production data including eggs collected, mortality, and feed usage",
      parameters: {
        type: "object",
        properties: {
          days: { type: "number", description: "Number of days of data to retrieve (default 7)" },
        },
        required: [],
      },
    },
  },
  {
    type: "function",
    function: {
      name: "get_financial_summary",
      description: "Get financial summary including income, expenses, and profit",
      parameters: {
        type: "object",
        properties: {
          period: { type: "string", description: "Time period: week, month, or year", enum: ["week", "month", "year"] },
        },
        required: [],
      },
    },
  },
  {
    type: "function",
    function: {
      name: "get_inventory_status",
      description: "Get current inventory status including stock levels and low-stock alerts",
      parameters: { type: "object", properties: {}, required: [] },
    },
  },
  {
    type: "function",
    function: {
      name: "get_worker_info",
      description: "Get information about farm workers including roles and wages",
      parameters: { type: "object", properties: {}, required: [] },
    },
  },
  {
    type: "function",
    function: {
      name: "get_sales_data",
      description: "Get recent sales data including amounts, customers, and payment status",
      parameters: {
        type: "object",
        properties: {
          days: { type: "number", description: "Number of days of data to retrieve (default 30)" },
        },
        required: [],
      },
    },
  },
  {
    type: "function",
    function: {
      name: "add_flock",
      description: "Add a new flock to the farm",
      parameters: {
        type: "object",
        properties: {
          name: { type: "string", description: "Name of the flock" },
          breed: { type: "string", description: "Breed of birds" },
          initialCount: { type: "number", description: "Number of birds" },
          type: { type: "string", description: "Type: layer, broiler, or breeder" },
        },
        required: ["name", "initialCount"],
      },
    },
  },
  {
    type: "function",
    function: {
      name: "record_production",
      description: "Record daily egg production data",
      parameters: {
        type: "object",
        properties: {
          flockId: { type: "number", description: "ID of the flock" },
          eggsCollected: { type: "number", description: "Number of eggs collected" },
          mortality: { type: "number", description: "Number of bird deaths" },
          feedUsed: { type: "number", description: "Feed used in kg" },
        },
        required: ["flockId", "eggsCollected"],
      },
    },
  },
  {
    type: "function",
    function: {
      name: "add_expense",
      description: "Record a farm expense",
      parameters: {
        type: "object",
        properties: {
          amount: { type: "number", description: "Amount in KES" },
          category: { type: "string", description: "Category: feed, labor, medication, equipment, transport, other" },
          description: { type: "string", description: "Description of the expense" },
        },
        required: ["amount", "category", "description"],
      },
    },
  },
  {
    type: "function",
    function: {
      name: "get_weather",
      description: "Get current weather and forecast for the farm location",
      parameters: { type: "object", properties: {}, required: [] },
    },
  },
  {
    type: "function",
    function: {
      name: "get_vaccination_schedule",
      description: "Get vaccination schedule and upcoming vaccinations for flocks",
      parameters: {
        type: "object",
        properties: {
          flockId: { type: "number", description: "ID of the flock (optional)" },
        },
        required: [],
      },
    },
  },
];

const SYSTEM_PROMPT = `You are Wangari AI, an intelligent farm management assistant for poultry farms in Kenya.

You have access to the farmer's live data and can perform operations on their farm.
Always be helpful, concise, and provide actionable advice.

When reporting data:
- Use KES (Kenyan Shillings) for all monetary values
- Be specific with numbers and dates
- Highlight any alerts or issues that need attention
- Provide context and recommendations when appropriate

When performing operations:
- Confirm the action before executing
- Show the result after execution
- Suggest follow-up actions if relevant

Farm knowledge:
- Layer chickens typically lay 250-300 eggs per year
- Feed conversion ratio (FCR) of 1.8-2.2 is good for layers
- Mortality rate under 1% per week is acceptable
- Vaccination is critical for disease prevention
- Common vaccines: Marek's, Newcastle (NDV), Infectious Bronchitis (IB), Gumboro (IBD)
`;

// ─── Tool Executor ────────────────────────────────────────
async function executeTool(toolName: string, args: Record<string, any>, farmId: number): Promise<any> {
  switch (toolName) {
    case "get_flock_summary":
      return prisma.flock.findMany({ where: { farmId } });

    case "get_production_data": {
      const days = (args.days as number) || 7;
      const since = new Date();
      since.setDate(since.getDate() - days);
      return prisma.dailyProduction.findMany({
        where: { farmId, date: { gte: since } },
        orderBy: { date: "desc" },
      });
    }

    case "get_financial_summary": {
      const now = new Date();
      let since = new Date();
      const period = (args.period as string) || "month";
      if (period === "week") since.setDate(now.getDate() - 7);
      else if (period === "month") since.setMonth(now.getMonth() - 1);
      else since.setFullYear(now.getFullYear() - 1);

      return prisma.transaction.findMany({
        where: { farmId, date: { gte: since } },
        orderBy: { date: "desc" },
      });
    }

    case "get_inventory_status":
      return prisma.inventory.findMany({ where: { farmId } });

    case "get_worker_info":
      return prisma.worker.findMany({ where: { farmId } });

    case "get_sales_data": {
      const days = (args.days as number) || 30;
      const since = new Date();
      since.setDate(since.getDate() - days);
      return prisma.sale.findMany({
        where: { farmId, date: { gte: since } },
        orderBy: { date: "desc" },
      });
    }

    case "add_flock": {
      const flock = await prisma.flock.create({
        data: {
          name: args.name,
          breed: args.breed,
          currentCount: args.initialCount,
          initialCount: args.initialCount,
          type: args.type || "layer",
          farmId,
          status: "active",
        },
      });
      return { success: true, flock };
    }

    case "record_production": {
      const record = await prisma.dailyProduction.create({
        data: {
          flockId: args.flockId,
          eggsCollected: args.eggsCollected,
          mortality: args.mortality || 0,
          feedUsed: args.feedUsed,
          farmId,
        },
      });
      return { success: true, record };
    }

    case "add_expense": {
      const tx = await prisma.transaction.create({
        data: {
          type: "expense",
          amount: args.amount,
          category: args.category,
          description: args.description,
          farmId,
        },
      });
      return { success: true, transaction: tx };
    }

    case "get_weather":
      return { note: "Weather data available at /api/weather endpoint" };

    case "get_vaccination_schedule": {
      const where: any = { farmId };
      if (args.flockId) where.flockId = args.flockId;
      return prisma.vaccination.findMany({ where, orderBy: { date: "desc" } });
    }

    default:
      return { error: `Unknown tool: ${toolName}` };
  }
}

// ─── AI Provider Adapters ─────────────────────────────────

async function callAI(
  messages: Array<{ role: string; content: string; tool_calls?: any[] }>,
  tools: typeof farmTools
): Promise<{ content: string; tool_calls: any[] }> {
  switch (AI_PROVIDER) {
    case "openai":
      return callOpenAI(messages, tools);
    case "gemini":
      return callGemini(messages, tools);
    case "anthropic":
      return callAnthropic(messages, tools);
    case "ollama":
      return callOllama(messages, tools);
    default:
      return callOpenAI(messages, tools);
  }
}

// ─── OpenAI / OpenAI-compatible ───────────────────────────
async function callOpenAI(
  messages: Array<{ role: string; content: string; tool_calls?: any[] }>,
  tools: typeof farmTools
): Promise<{ content: string; tool_calls: any[] }> {
  const res = await fetch("https://api.openai.com/v1/chat/completions", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${AI_API_KEY}`,
    },
    body: JSON.stringify({
      model: AI_MODEL,
      messages,
      tools: tools.map((t) => ({ type: "function", function: t.function })),
      temperature: 0.7,
      max_tokens: 2048,
    }),
  });

  if (!res.ok) {
    const err = await res.text();
    console.error("OpenAI error:", err);
    throw new Error(`OpenAI API error: ${res.status}`);
  }

  const data = await res.json();
  const choice = data.choices?.[0];
  return {
    content: choice?.message?.content || "",
    tool_calls: choice?.message?.tool_calls || [],
  };
}

// ─── Google Gemini ────────────────────────────────────────
async function callGemini(
  messages: Array<{ role: string; content: string; tool_calls?: any[] }>,
  tools: typeof farmTools
): Promise<{ content: string; tool_calls: any[] }> {
  // Convert messages to Gemini format
  const contents = messages
    .filter((m) => m.role !== "system")
    .map((m) => ({
      role: m.role === "assistant" ? "model" : "user",
      parts: [{ text: m.content }],
    }));

  const systemInstruction = messages.find((m) => m.role === "system")?.content;

  const geminiTools = [
    {
      function_declarations: tools.map((t) => ({
        name: t.function.name,
        description: t.function.description,
        parameters: t.function.parameters,
      })),
    },
  ];

  const res = await fetch(
    `https://generativelanguage.googleapis.com/v1beta/models/${AI_MODEL}:generateContent?key=${AI_API_KEY}`,
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        contents,
        systemInstruction: systemInstruction ? { parts: [{ text: systemInstruction }] } : undefined,
        tools: geminiTools,
        generationConfig: {
          temperature: 0.7,
          maxOutputTokens: 2048,
        },
      }),
    }
  );

  if (!res.ok) {
    const err = await res.text();
    console.error("Gemini error:", err);
    throw new Error(`Gemini API error: ${res.status}`);
  }

  const data = await res.json();
  const candidate = data.candidates?.[0];
  const content = candidate?.content?.parts?.find((p: any) => p.text)?.text || "";

  // Extract tool calls from Gemini response
  const toolCalls = candidate?.content?.parts
    ?.filter((p: any) => p.functionCall)
    ?.map((p: any, i: number) => ({
      id: `call_${Date.now()}_${i}`,
      type: "function",
      function: {
        name: p.functionCall.name,
        arguments: JSON.stringify(p.functionCall.args),
      },
    })) || [];

  return { content, tool_calls: toolCalls };
}

// ─── Anthropic Claude ─────────────────────────────────────
async function callAnthropic(
  messages: Array<{ role: string; content: string; tool_calls?: any[] }>,
  tools: typeof farmTools
): Promise<{ content: string; tool_calls: any[] }> {
  const systemMessage = messages.find((m) => m.role === "system");
  const chatMessages = messages
    .filter((m) => m.role !== "system")
    .map((m) => ({ role: m.role, content: m.content }));

  const res = await fetch("https://api.anthropic.com/v1/messages", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "x-api-key": AI_API_KEY,
      "anthropic-version": "2023-06-01",
    },
    body: JSON.stringify({
      model: AI_MODEL,
      max_tokens: 2048,
      system: systemMessage?.content,
      messages: chatMessages,
      tools: tools.map((t) => ({
        name: t.function.name,
        description: t.function.description,
        input_schema: t.function.parameters,
      })),
    }),
  });

  if (!res.ok) {
    const err = await res.text();
    console.error("Anthropic error:", err);
    throw new Error(`Anthropic API error: ${res.status}`);
  }

  const data = await res.json();
  const contentBlock = data.content?.find((b: any) => b.type === "text");
  const content = contentBlock?.text || "";

  const toolCalls = data.content
    ?.filter((b: any) => b.type === "tool_use")
    ?.map((b: any, i: number) => ({
      id: b.id || `call_${Date.now()}_${i}`,
      type: "function",
      function: {
        name: b.name,
        arguments: JSON.stringify(b.input),
      },
    })) || [];

  return { content, tool_calls: toolCalls };
}

// ─── Ollama (local) ──────────────────────────────────────
async function callOllama(
  messages: Array<{ role: string; content: string; tool_calls?: any[] }>,
  tools: typeof farmTools
): Promise<{ content: string; tool_calls: any[] }> {
  const res = await fetch(`${OLLAMA_URL}/api/chat`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      model: AI_MODEL,
      messages,
      tools,
      stream: false,
      options: { temperature: 0.7, top_p: 0.9, num_ctx: 4096 },
    }),
  });

  if (!res.ok) {
    const err = await res.text();
    console.error("Ollama error:", err);
    throw new Error(`Ollama API error: ${res.status}`);
  }

  const data = await res.json();
  return {
    content: data.message?.content || "",
    tool_calls: data.message?.tool_calls || [],
  };
}

// ─── Chat Endpoint ────────────────────────────────────────
router.post("/chat", async (req: Request, res: Response) => {
  try {
    const { messages, farmId } = req.body;

    if (!messages || !Array.isArray(messages)) {
      return res.status(400).json({ error: "Messages array is required" });
    }

    if (!AI_API_KEY && AI_PROVIDER !== "ollama") {
      return res.status(500).json({
        error: `AI provider "${AI_PROVIDER}" requires an API key. Set AI_API_KEY in your .env file.`,
      });
    }

    // Build messages with system prompt
    const fullMessages = [
      { role: "system", content: SYSTEM_PROMPT },
      ...messages,
    ];

    // Call AI
    const response = await callAI(fullMessages, farmTools);

    // Execute tool calls if any
    const toolResults = [];
    if (response.tool_calls.length > 0 && farmId) {
      for (const tc of response.tool_calls) {
        const args = typeof tc.function.arguments === "string"
          ? JSON.parse(tc.function.arguments)
          : tc.function.arguments;
        const result = await executeTool(tc.function.name, args, farmId);
        toolResults.push({
          tool_call_id: tc.id,
          name: tc.function.name,
          content: JSON.stringify(result),
        });
      }

      // Send tool results back for a final response
      const followUpMessages = [
        ...fullMessages,
        { role: "assistant", content: response.content, tool_calls: response.tool_calls },
        ...toolResults.map((tr) => ({
          role: "tool" as const,
          content: `Tool ${tr.name} result: ${tr.content}`,
        })),
      ];

      const followUpResponse = await callAI(followUpMessages, farmTools);

      return res.json({
        message: { role: "assistant", content: followUpResponse.content || response.content },
        tool_calls: response.tool_calls,
        tool_results: toolResults,
      });
    }

    return res.json({
      message: { role: "assistant", content: response.content },
      tool_calls: response.tool_calls.length > 0 ? response.tool_calls : undefined,
      tool_results: toolResults.length > 0 ? toolResults : undefined,
    });
  } catch (error) {
    console.error("AI chat error:", error);
    const msg = error instanceof Error ? error.message : "Unknown error";
    res.status(500).json({ error: msg });
  }
});

// ─── Health Check ─────────────────────────────────────────
router.get("/health", (_req: Request, res: Response) => {
  res.json({
    status: AI_API_KEY || AI_PROVIDER === "ollama" ? "configured" : "needs_api_key",
    provider: AI_PROVIDER,
    model: AI_MODEL,
    hasApiKey: !!AI_API_KEY,
  });
});

export default router;
