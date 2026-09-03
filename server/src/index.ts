import express from "express";
import cors from "cors";
import helmet from "helmet";
import rateLimit from "express-rate-limit";

// Routes
import authRoutes from "./routes/auth.js";
import dashboardRoutes from "./routes/dashboard.js";
import flocksRoutes from "./routes/flocks.js";
import customersRoutes from "./routes/customers.js";
import transactionsRoutes from "./routes/transactions.js";
import salesRoutes from "./routes/sales.js";
import workersRoutes from "./routes/workers.js";
import inventoryRoutes from "./routes/inventory.js";
import productionRoutes from "./routes/production.js";
import vaccinationsRoutes from "./routes/vaccinations.js";
import attendanceRoutes from "./routes/attendance.js";
import weatherRoutes from "./routes/weather.js";
import aiRoutes from "./routes/ai.js";

const app = express();
const PORT = process.env.PORT || 3001;

// ─── Security ─────────────────────────────────────────────
app.use(helmet());
app.use(
  cors({
    origin: process.env.FRONTEND_URL || "https://wangari.imeantech.com",
    credentials: true,
    methods: ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    allowedHeaders: ["Content-Type", "Authorization"],
  })
);

// ─── Rate Limiting ────────────────────────────────────────
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: "Too many requests, please try again later" },
});
app.use("/api/", limiter);

// ─── Body Parsing ─────────────────────────────────────────
app.use(express.json({ limit: "10mb" }));

// ─── Health Check ─────────────────────────────────────────
app.get("/health", (_req, res) => {
  res.json({ status: "ok", timestamp: new Date().toISOString() });
});

// ─── API Routes ───────────────────────────────────────────
app.use("/api/auth", authRoutes);
app.use("/api/dashboard", dashboardRoutes);
app.use("/api/flocks", flocksRoutes);
app.use("/api/customers", customersRoutes);
app.use("/api/transactions", transactionsRoutes);
app.use("/api/sales", salesRoutes);
app.use("/api/workers", workersRoutes);
app.use("/api/inventory", inventoryRoutes);
app.use("/api/production", productionRoutes);
app.use("/api/vaccinations", vaccinationsRoutes);
app.use("/api/attendance", attendanceRoutes);
app.use("/api/weather", weatherRoutes);
app.use("/api/ai", aiRoutes);

// ─── 404 Handler ──────────────────────────────────────────
app.use((_req, res) => {
  res.status(404).json({ error: "Not found" });
});

// ─── Error Handler ────────────────────────────────────────
app.use((err: Error, _req: express.Request, res: express.Response, _next: express.NextFunction) => {
  console.error("Unhandled error:", err);
  res.status(500).json({ error: "Internal server error" });
});

// ─── Start Server ─────────────────────────────────────────
app.listen(PORT, () => {
  console.log(`🌱 Wangari API server running on port ${PORT}`);
  console.log(`   Environment: ${process.env.NODE_ENV || "development"}`);
  console.log(`   Frontend URL: ${process.env.FRONTEND_URL || "https://wangari.imeantech.com"}`);
});

export default app;
