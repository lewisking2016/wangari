import express from "express";
import cors from "cors";
import helmet from "helmet";
import compression from "compression";
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
import breedingRoutes from "./routes/breeding.js";
import cropsRoutes from "./routes/crops.js";
import invoicesRoutes from "./routes/invoices.js";
import farmsRoutes from "./routes/farms.js";
import auditRoutes from "./routes/audit.js";
import exportRoutes from "./routes/export.js";
import importRoutes from "./routes/import.js";
import settingsRoutes from "./routes/settings.js";
import zktecoRoutes from "./routes/zkteco.js";
import workerApiRoutes from "./routes/worker.js";
import flocksUploadRoutes from "./routes/flocks-upload.js";
import uploadRoutes from "./routes/upload.js";
import paystackRoutes from "./routes/paystack.js";
import trialRoutes from "./routes/trial.js";

// ─── Process-Level Crash Safety ────────────────────────────
// One bad async call must not kill the PM2 process silently.
process.on("unhandledRejection", (reason) => {
  console.error("[unhandledRejection]", reason);
  // Log and keep serving — rejections are recoverable.
});
process.on("uncaughtException", (err) => {
  console.error("[uncaughtException]", err);
  // Unknown state — exit and let PM2 restart us cleanly.
  process.exit(1);
});

const app = express();

// Behind nginx on the VPS — required for express-rate-limit to identify
// clients correctly from X-Forwarded-For (silences ERR_ERL_UNEXPECTED_X_FORWARDED_FOR).
app.set("trust proxy", 1);
const PORT = process.env.PORT || 3001;

// ─── Performance & Compression ──────────────────────────────
app.use(compression());

// ─── Security ─────────────────────────────────────────────
app.use(helmet({ crossOriginOpenerPolicy: false }));
const allowedOrigins = [
  process.env.FRONTEND_URL || "https://wangari.imeantech.com",
  // Local development (any port) — matches only pages actually served from
  // localhost/127.0.0.1, so it cannot be abused by third-party sites.
  /^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/,
];
app.use(
  cors({
    origin(origin, cb) {
      // No Origin header = non-browser client (curl, device, server-to-server).
      if (!origin || allowedOrigins.some((o) => (typeof o === "string" ? o === origin : o.test(origin)))) {
        return cb(null, true);
      }
      cb(new Error("Not allowed by CORS"));
    },
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

// ─── Stricter Brute-Force Limiter for Auth ─────────────────
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 20,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: "Too many login attempts, please try again later" },
});
app.use("/api/auth/login", authLimiter);
app.use("/api/auth/register", authLimiter);
app.use("/api/auth/forgot-password", authLimiter);
app.use("/api/worker/login", authLimiter);

// ─── Body Parsing ─────────────────────────────────────────
// Paystack webhook needs the RAW body to verify the HMAC signature — mount before express.json.
app.use("/api/paystack/webhook", express.raw({ type: "application/json" }));
app.use(express.json({ limit: "10mb" }));
app.use("/uploads", express.static("uploads"));

// ─── Health Check ─────────────────────────────────────────
app.get("/health", (_req, res) => {
  res.json({ status: "ok", timestamp: new Date().toISOString() });
});

// ─── API Routes ───────────────────────────────────────────
app.use("/api/auth", authRoutes);
app.use("/api/dashboard", dashboardRoutes);
app.use("/api/flocks", flocksRoutes);
app.use("/api/flocks", flocksUploadRoutes);
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
app.use("/api/breeding", breedingRoutes);
app.use("/api/crops", cropsRoutes);
app.use("/api/invoices", invoicesRoutes);
app.use("/api/farms", farmsRoutes);
app.use("/api/audit", auditRoutes);
app.use("/api/export", exportRoutes);
app.use("/api/import", importRoutes);
app.use("/api/settings", settingsRoutes);
app.use("/api/upload", uploadRoutes);
app.use("/api/zkteco", zktecoRoutes);
app.use("/api/worker", workerApiRoutes);
app.use("/api/paystack", paystackRoutes);
app.use("/api/trial", trialRoutes);

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
