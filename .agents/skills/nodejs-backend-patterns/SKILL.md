---
name: nodejs-backend-patterns
description: >
  Node.js/Express backend patterns for the Wangari server: route structure,
  middleware, error handling, authentication, request validation, and API
  response conventions. Use when writing or reviewing server-side code in
  the `server/` directory.
---
# Node.js Backend Patterns

Full skill source: `../../skills/antigravity/nodejs-backend-patterns/`

## Stack (Wangari Server)
- **Runtime:** Node.js with TypeScript
- **Framework:** Express
- **ORM:** Prisma
- **Auth:** JWT tokens
- **Location:** `server/src/`

## Route Structure
```
server/src/
  routes/
    auth.ts          → /api/auth/*
    flocks.ts        → /api/flocks/*
    production.ts    → /api/production/*
    sales.ts         → /api/sales/*
    finances.ts      → /api/finances/*
    inventory.ts     → /api/inventory/*
    workers.ts       → /api/workers/*
    dashboard.ts     → /api/dashboard
    settings.ts      → /api/settings
    weather.ts       → /api/weather
  middleware/
    auth.ts          → JWT verification
  index.ts           → Express app setup
```

## Standard Route Pattern
```typescript
import { Router, Request, Response } from "express";
import { PrismaClient } from "@prisma/client";
import { authMiddleware } from "../middleware/auth";

const router = Router();
const prisma = new PrismaClient();

// GET list
router.get("/", authMiddleware, async (req: Request, res: Response) => {
  try {
    const farmId = (req as any).user.farmId;
    const items = await prisma.resource.findMany({
      where: { farmId },
      orderBy: { createdAt: "desc" },
    });
    res.json(items);
  } catch (error) {
    res.status(500).json({ error: "Failed to fetch" });
  }
});

// POST create
router.post("/", authMiddleware, async (req: Request, res: Response) => {
  try {
    const farmId = (req as any).user.farmId;
    const item = await prisma.resource.create({
      data: { ...req.body, farmId },
    });
    res.json(item);
  } catch (error) {
    res.status(500).json({ error: "Failed to create" });
  }
});

export default router;
```

## Auth Middleware Usage
```typescript
// All protected routes require authMiddleware
router.get("/protected", authMiddleware, async (req, res) => {
  const { userId, farmId, role } = (req as any).user;
  // ...
});
```

## Error Response Convention
```typescript
// Success
res.json({ data: result });          // or just res.json(result)

// Client error (bad input)
res.status(400).json({ error: "Invalid input: fieldName is required" });

// Unauthorized
res.status(401).json({ error: "Unauthorized" });

// Forbidden (wrong role)
res.status(403).json({ error: "Insufficient permissions" });

// Not found
res.status(404).json({ error: "Resource not found" });

// Server error
res.status(500).json({ error: "Internal server error" });
```

## Validation Pattern
```typescript
// Basic input validation before DB operation
if (!req.body.flockId || !req.body.eggsCollected) {
  return res.status(400).json({ error: "flockId and eggsCollected are required" });
}
const eggsCollected = Number(req.body.eggsCollected);
if (isNaN(eggsCollected) || eggsCollected < 0) {
  return res.status(400).json({ error: "eggsCollected must be a non-negative number" });
}
```

## Key Rules
- ALWAYS filter by `farmId` — never expose cross-farm data
- ALWAYS use try/catch on async route handlers
- Never return passwords or tokens in responses
- Use Prisma's `select` to limit fields returned
- Validate numeric inputs with `Number()` and `isNaN()` check
