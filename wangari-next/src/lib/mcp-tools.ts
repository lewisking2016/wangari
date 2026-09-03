/**
 * MCP (Model Context Protocol) Tool Registry
 *
 * Comprehensive tool definitions for ALL farm operations.
 * This gives the AI full access to perform any action on the system.
 */

export interface MCPTool {
  name: string;
  description: string;
  category: "read" | "write" | "delete" | "compute";
  endpoint: string;
  method: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  parameters: Record<string, {
    type: string;
    description: string;
    required: boolean;
    enum?: string[];
    default?: any;
  }>;
  confirmationRequired?: boolean;
}

// ─── All Farm Operations ──────────────────────────────────
export const mcpTools: MCPTool[] = [
  // ── Flocks ──────────────────────────────────────────────
  {
    name: "list_flocks",
    description: "List all flocks with bird count, breed, status, and mortality data",
    category: "read",
    endpoint: "/api/flocks",
    method: "GET",
    parameters: {},
  },
  {
    name: "create_flock",
    description: "Add a new flock to the farm",
    category: "write",
    endpoint: "/api/flocks",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      name: { type: "string", description: "Name of the flock (e.g., 'Layer Block A')", required: true },
      breed: { type: "string", description: "Breed of birds (e.g., 'Kenbro', 'Kienyeji', 'ISA Brown')", required: false },
      initialCount: { type: "number", description: "Number of birds in the flock", required: true },
      type: { type: "string", description: "Type of flock", required: false, enum: ["layer", "broiler", "breeder"] },
    },
  },
  {
    name: "delete_flock",
    description: "Remove a flock from the farm",
    category: "delete",
    endpoint: "/api/flocks/:id",
    method: "DELETE",
    confirmationRequired: true,
    parameters: {
      id: { type: "number", description: "ID of the flock to delete", required: true },
    },
  },

  // ── Production ──────────────────────────────────────────
  {
    name: "list_production",
    description: "Get recent egg production data including eggs collected, mortality, and feed usage",
    category: "read",
    endpoint: "/api/production",
    method: "GET",
    parameters: {
      days: { type: "number", description: "Number of days to retrieve (default: 7)", required: false, default: 7 },
    },
  },
  {
    name: "record_production",
    description: "Record daily egg production data for a flock",
    category: "write",
    endpoint: "/api/production",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      flockId: { type: "number", description: "ID of the flock", required: true },
      eggsCollected: { type: "number", description: "Number of eggs collected today", required: true },
      mortality: { type: "number", description: "Number of bird deaths today", required: false, default: 0 },
      feedUsed: { type: "number", description: "Feed consumed in kg", required: false },
    },
  },

  // ── Transactions / Finances ─────────────────────────────
  {
    name: "list_transactions",
    description: "Get financial transactions (income and expenses)",
    category: "read",
    endpoint: "/api/transactions",
    method: "GET",
    parameters: {
      period: { type: "string", description: "Time period filter", required: false, enum: ["week", "month", "year"] },
    },
  },
  {
    name: "create_transaction",
    description: "Record a financial transaction (income or expense)",
    category: "write",
    endpoint: "/api/transactions",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      type: { type: "string", description: "Transaction type", required: true, enum: ["income", "expense"] },
      amount: { type: "number", description: "Amount in KES", required: true },
      category: { type: "string", description: "Category (e.g., feed, labor, medication, eggsales, birdsales)", required: true },
      description: { type: "string", description: "Description of the transaction", required: true },
    },
  },
  {
    name: "delete_transaction",
    description: "Delete a financial transaction",
    category: "delete",
    endpoint: "/api/transactions/:id",
    method: "DELETE",
    confirmationRequired: true,
    parameters: {
      id: { type: "number", description: "ID of the transaction to delete", required: true },
    },
  },

  // ── Sales ───────────────────────────────────────────────
  {
    name: "list_sales",
    description: "Get sales records including amounts, customers, and payment status",
    category: "read",
    endpoint: "/api/sales",
    method: "GET",
    parameters: {
      days: { type: "number", description: "Number of days to retrieve (default: 30)", required: false, default: 30 },
    },
  },
  {
    name: "create_sale",
    description: "Record a new sale (eggs, birds, or other products)",
    category: "write",
    endpoint: "/api/sales",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      customerId: { type: "number", description: "ID of the customer", required: false },
      items: { type: "string", description: "JSON string of sale items [{name, quantity, unitPrice}]", required: true },
      totalAmount: { type: "number", description: "Total sale amount in KES", required: true },
      paymentStatus: { type: "string", description: "Payment status", required: false, enum: ["paid", "pending", "partial"] },
      amountPaid: { type: "number", description: "Amount already paid in KES", required: false },
      notes: { type: "string", description: "Additional notes", required: false },
    },
  },
  {
    name: "delete_sale",
    description: "Delete a sale record",
    category: "delete",
    endpoint: "/api/sales/:id",
    method: "DELETE",
    confirmationRequired: true,
    parameters: {
      id: { type: "number", description: "ID of the sale to delete", required: true },
    },
  },

  // ── Inventory ───────────────────────────────────────────
  {
    name: "list_inventory",
    description: "Get current inventory items with stock levels and reorder alerts",
    category: "read",
    endpoint: "/api/inventory",
    method: "GET",
    parameters: {},
  },
  {
    name: "create_inventory_item",
    description: "Add a new item to inventory or update stock",
    category: "write",
    endpoint: "/api/inventory",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      itemName: { type: "string", description: "Name of the inventory item", required: true },
      category: { type: "string", description: "Category (e.g., feed, medication, equipment, packaging)", required: true },
      quantity: { type: "number", description: "Current quantity in stock", required: true },
      unit: { type: "string", description: "Unit of measurement (e.g., kg, bags, pieces)", required: true },
      unitCost: { type: "number", description: "Cost per unit in KES", required: false },
      reorderLevel: { type: "number", description: "Minimum stock level before reorder alert", required: false },
    },
  },
  {
    name: "delete_inventory_item",
    description: "Remove an item from inventory",
    category: "delete",
    endpoint: "/api/inventory/:id",
    method: "DELETE",
    confirmationRequired: true,
    parameters: {
      id: { type: "number", description: "ID of the inventory item to delete", required: true },
    },
  },

  // ── Workers ─────────────────────────────────────────────
  {
    name: "list_workers",
    description: "Get all farm workers with roles, wages, and contact info",
    category: "read",
    endpoint: "/api/workers",
    method: "GET",
    parameters: {},
  },
  {
    name: "create_worker",
    description: "Add a new worker to the farm",
    category: "write",
    endpoint: "/api/workers",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      name: { type: "string", description: "Full name of the worker", required: true },
      role: { type: "string", description: "Job role (e.g., farmhand, supervisor, security)", required: true },
      dailyWage: { type: "number", description: "Daily wage in KES", required: true },
      phone: { type: "string", description: "Phone number", required: false },
    },
  },
  {
    name: "delete_worker",
    description: "Remove a worker from the farm",
    category: "delete",
    endpoint: "/api/workers/:id",
    method: "DELETE",
    confirmationRequired: true,
    parameters: {
      id: { type: "number", description: "ID of the worker to delete", required: true },
    },
  },

  // ── Customers ───────────────────────────────────────────
  {
    name: "list_customers",
    description: "Get all customers with contact info and order history",
    category: "read",
    endpoint: "/api/customers",
    method: "GET",
    parameters: {},
  },
  {
    name: "create_customer",
    description: "Add a new customer",
    category: "write",
    endpoint: "/api/customers",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      name: { type: "string", description: "Customer name or business name", required: true },
      phone: { type: "string", description: "Phone number", required: false },
      email: { type: "string", description: "Email address", required: false },
      address: { type: "string", description: "Physical address", required: false },
    },
  },
  {
    name: "delete_customer",
    description: "Remove a customer",
    category: "delete",
    endpoint: "/api/customers/:id",
    method: "DELETE",
    confirmationRequired: true,
    parameters: {
      id: { type: "number", description: "ID of the customer to delete", required: true },
    },
  },

  // ── Vaccinations ────────────────────────────────────────
  {
    name: "list_vaccinations",
    description: "Get vaccination records and upcoming schedules",
    category: "read",
    endpoint: "/api/vaccinations",
    method: "GET",
    parameters: {
      flockId: { type: "number", description: "Filter by flock ID", required: false },
    },
  },
  {
    name: "create_vaccination",
    description: "Record a vaccination event",
    category: "write",
    endpoint: "/api/vaccinations",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      flockId: { type: "number", description: "ID of the flock", required: true },
      vaccineName: { type: "string", description: "Name of the vaccine (e.g., 'Newcastle', 'Gumboro')", required: true },
      dosage: { type: "string", description: "Dosage administered", required: false },
      administeredBy: { type: "string", description: "Who administered the vaccine", required: false },
      notes: { type: "string", description: "Additional notes", required: false },
    },
  },
  {
    name: "update_vaccination",
    description: "Update a vaccination record",
    category: "write",
    endpoint: "/api/vaccinations/:id",
    method: "PATCH",
    confirmationRequired: true,
    parameters: {
      id: { type: "number", description: "ID of the vaccination record", required: true },
      status: { type: "string", description: "Updated status", required: false, enum: ["scheduled", "completed", "missed"] },
      notes: { type: "string", description: "Updated notes", required: false },
    },
  },

  // ── Attendance ──────────────────────────────────────────
  {
    name: "list_attendance",
    description: "Get worker attendance records",
    category: "read",
    endpoint: "/api/attendance",
    method: "GET",
    parameters: {},
  },
  {
    name: "record_attendance",
    description: "Record worker attendance for today",
    category: "write",
    endpoint: "/api/attendance",
    method: "POST",
    confirmationRequired: true,
    parameters: {
      workerId: { type: "number", description: "ID of the worker", required: true },
      status: { type: "string", description: "Attendance status", required: true, enum: ["present", "absent", "late", "half_day"] },
      notes: { type: "string", description: "Additional notes", required: false },
    },
  },

  // ── Weather ─────────────────────────────────────────────
  {
    name: "get_weather",
    description: "Get current weather and forecast for the farm location",
    category: "read",
    endpoint: "/api/weather",
    method: "GET",
    parameters: {},
  },

  // ── Dashboard ───────────────────────────────────────────
  {
    name: "get_dashboard",
    description: "Get dashboard summary with KPIs, alerts, and recent activity",
    category: "read",
    endpoint: "/api/dashboard",
    method: "GET",
    parameters: {},
  },

  // ── Navigation (frontend actions) ───────────────────────
  {
    name: "navigate_to",
    description: "Navigate the user to a specific page in the app",
    category: "compute",
    endpoint: "__navigate__",
    method: "GET",
    parameters: {
      page: {
        type: "string",
        description: "Page to navigate to",
        required: true,
        enum: [
          "dashboard", "flocks", "production", "finances", "sales",
          "inventory", "workers", "customers", "vaccinations", "attendance",
          "weather", "reports", "settings", "invoices", "export",
        ],
      },
    },
  },
];

// ─── Tool Category Metadata ───────────────────────────────
export const toolCategories = [
  { id: "flocks", label: "🐔 Flocks", tools: ["list_flocks", "create_flock", "delete_flock"] },
  { id: "production", label: "🥚 Production", tools: ["list_production", "record_production"] },
  { id: "finances", label: "💰 Finances", tools: ["list_transactions", "create_transaction", "delete_transaction"] },
  { id: "sales", label: "🛒 Sales", tools: ["list_sales", "create_sale", "delete_sale"] },
  { id: "inventory", label: "📦 Inventory", tools: ["list_inventory", "create_inventory_item", "delete_inventory_item"] },
  { id: "workers", label: "👷 Workers", tools: ["list_workers", "create_worker", "delete_worker"] },
  { id: "customers", label: "👤 Customers", tools: ["list_customers", "create_customer", "delete_customer"] },
  { id: "vaccinations", label: "💉 Vaccinations", tools: ["list_vaccinations", "create_vaccination", "update_vaccination"] },
  { id: "attendance", label: "📋 Attendance", tools: ["list_attendance", "record_attendance"] },
  { id: "weather", label: "🌤️ Weather", tools: ["get_weather"] },
  { id: "dashboard", label: "📊 Dashboard", tools: ["get_dashboard"] },
];

// ─── Convert to OpenAI/Gemini/Anthropic tool format ───────
export function toOpenAITools(tools: MCPTool[]) {
  return tools.map((t) => ({
    type: "function",
    function: {
      name: t.name,
      description: t.description,
      parameters: {
        type: "object",
        properties: Object.fromEntries(
          Object.entries(t.parameters).map(([key, p]) => [
            key,
            {
              type: p.type,
              description: p.description,
              ...(p.enum ? { enum: p.enum } : {}),
            },
          ])
        ),
        required: Object.entries(t.parameters)
          .filter(([, p]) => p.required)
          .map(([key]) => key),
      },
    },
  }));
}
