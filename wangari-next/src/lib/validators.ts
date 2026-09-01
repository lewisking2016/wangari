import { z } from "zod";

export const loginSchema = z.object({
  email: z.string().email("Please enter a valid email"),
  password: z.string().min(6, "Password must be at least 6 characters"),
});

export const registerSchema = z.object({
  name: z.string().min(2, "Name must be at least 2 characters"),
  email: z.string().email("Please enter a valid email"),
  password: z.string().min(6, "Password must be at least 6 characters"),
  farmName: z.string().min(2, "Farm name is required"),
  phone: z.string().optional(),
});

export const flockSchema = z.object({
  name: z.string().min(1, "Flock name is required"),
  breed: z.string().optional(),
  type: z.enum(["layers", "broilers", "chicks"]).default("layers"),
  initialCount: z.number().min(1, "Must have at least 1 bird"),
  hatchDate: z.string().optional(),
});

export const productionSchema = z.object({
  flockId: z.number().min(1, "Select a flock"),
  date: z.string().min(1, "Date is required"),
  eggsCollected: z.number().min(0).default(0),
  mortality: z.number().min(0).default(0),
  feedUsed: z.number().min(0).default(0),
  notes: z.string().optional(),
});

export const transactionSchema = z.object({
  type: z.enum(["income", "expense"]),
  category: z.string().optional(),
  amount: z.number().min(0.01, "Amount must be greater than 0"),
  description: z.string().optional(),
  date: z.string().min(1, "Date is required"),
  paymentMethod: z.enum(["cash", "mpesa", "bank", "credit"]).default("cash"),
});

export const inventorySchema = z.object({
  itemName: z.string().min(1, "Item name is required"),
  category: z.string().optional(),
  quantity: z.number().min(0),
  unit: z.string().default("bags"),
  unitCost: z.number().min(0),
  reorderLevel: z.number().min(0).default(0),
  supplier: z.string().optional(),
});

export const customerSchema = z.object({
  name: z.string().min(1, "Customer name is required"),
  phone: z.string().optional(),
  email: z.string().email().optional().or(z.literal("")),
  address: z.string().optional(),
});

export const saleSchema = z.object({
  customerId: z.number().optional(),
  items: z.array(
    z.object({
      item: z.string(),
      qty: z.number().min(1),
      price: z.number().min(0),
    })
  ).min(1, "Add at least one item"),
  totalAmount: z.number().min(0.01),
  paymentStatus: z.enum(["paid", "partial", "credit"]).default("paid"),
  amountPaid: z.number().min(0),
});

export const workerSchema = z.object({
  name: z.string().min(1, "Worker name is required"),
  phone: z.string().optional(),
  role: z.string().optional(),
  dailyWage: z.number().min(0).optional(),
  hiredDate: z.string().optional(),
});

export type LoginInput = z.infer<typeof loginSchema>;
export type RegisterInput = z.infer<typeof registerSchema>;
export type FlockInput = z.infer<typeof flockSchema>;
export type ProductionInput = z.infer<typeof productionSchema>;
export type TransactionInput = z.infer<typeof transactionSchema>;
export type InventoryInput = z.infer<typeof inventorySchema>;
export type CustomerInput = z.infer<typeof customerSchema>;
export type SaleInput = z.infer<typeof saleSchema>;
export type WorkerInput = z.infer<typeof workerSchema>;
