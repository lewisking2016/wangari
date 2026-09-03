import { Router, Request, Response } from "express";
import { prisma } from "../db.js";

const router = Router();

const OLLAMA_URL = process.env.OLLAMA_URL || "http://127.0.0.1:11434";
const MODEL = process.env.OLLAMA_MODEL || "qwen2.5:1.5b";

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

// ─── Chat Endpoint ────────────────────────────────────────
router.post("/chat", async (req: Request, res: Response) => {
  try {
    const { messages, farmId } = req.body;

    if (!messages || !Array.isArray(messages)) {
      return res.status(400).json({ error: "Messages array is required" });
    }

    // Build Ollama messages
    const ollamaMessages = [
      { role: "system", content: SYSTEM_PROMPT },
      ...messages,
    ];

    // Call Ollama
    const ollamaRes = await fetch(`${OLLAMA_URL}/api/chat`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        model: MODEL,
        messages: ollamaMessages,
        tools: farmTools,
        stream: false,
        options: {
          temperature: 0.7,
          top_p: 0.9,
          num_ctx: 4096,
        },
      }),
    });

    if (!ollamaRes.ok) {
      const err = await ollamaRes.text();
      console.error("Ollama error:", err);
      return res.status(502).json({ error: `AI service error: ${ollamaRes.status}` });
    }

    const data = await ollamaRes.json();
    const content = data.message?.content || "";
    const toolCalls = data.message?.tool_calls || [];

    // Execute tool calls if any
    const toolResults = [];
    if (toolCalls.length > 0 && farmId) {
      for (const tc of toolCalls) {
        const result = await executeTool(tc.function.name, tc.function.arguments, farmId);
        toolResults.push({
          tool_call_id: `call_${Date.now()}`,
          name: tc.function.name,
          content: JSON.stringify(result),
        });
      }

      // Send tool results back to Ollama for a final response
      const followUpMessages = [
        { role: "system", content: SYSTEM_PROMPT },
        ...messages,
        { role: "assistant", content, tool_calls: toolCalls },
        ...toolResults.map((tr: any) => ({
          role: "tool" as const,
          content: `Tool ${tr.name} result: ${tr.content}`,
        })),
      ];

      const followUpRes = await fetch(`${OLLAMA_URL}/api/chat`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          model: MODEL,
          messages: followUpMessages,
          tools: farmTools,
          stream: false,
          options: { temperature: 0.7, top_p: 0.9, num_ctx: 4096 },
        }),
      });

      if (followUpRes.ok) {
        const followUpData = await followUpRes.json();
        return res.json({
          message: { role: "assistant", content: followUpData.message?.content || content },
          tool_calls: toolCalls,
          tool_results: toolResults,
        });
      }
    }

    return res.json({
      message: { role: "assistant", content },
      tool_calls: toolCalls.length > 0 ? toolCalls : undefined,
      tool_results: toolResults.length > 0 ? toolResults : undefined,
    });
  } catch (error) {
    console.error("AI chat error:", error);
    const msg = error instanceof Error ? error.message : "Unknown error";
    res.status(500).json({ error: msg });
  }
});

// ─── Health Check ─────────────────────────────────────────
router.get("/health", async (_req: Request, res: Response) => {
  try {
    const ollamaRes = await fetch(`${OLLAMA_URL}/api/tags`);
    if (ollamaRes.ok) {
      const data = await ollamaRes.json();
      res.json({
        status: "ok",
        ollama: "connected",
        model: MODEL,
        models: data.models?.map((m: any) => m.name) || [],
      });
    } else {
      res.json({ status: "degraded", ollama: "unreachable" });
    }
  } catch {
    res.json({ status: "degraded", ollama: "not running" });
  }
});

export default router;
