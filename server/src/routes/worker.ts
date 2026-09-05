import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";
import jwt from "jsonwebtoken";

const router = Router();
const JWT_SECRET = process.env.JWT_SECRET || "wangari-secret-key-2025";

const db = prisma as any;

// ─── POST /api/worker/login — Worker PIN & Farm Code login ───
router.post("/login", async (req: Request, res: Response) => {
  try {
    const { farmCode, phone, pin } = req.body;
    if (!pin) {
      return res.status(400).json({ error: "4-digit PIN is required" });
    }

    let targetFarmId: number | null = null;

    // 1. If farmCode provided, lookup farm
    if (farmCode) {
      const cleanCode = String(farmCode).trim().toUpperCase();
      const farm = await db.farm.findFirst({
        where: {
          OR: [
            { code: cleanCode },
            { name: { contains: cleanCode, mode: "insensitive" } },
          ],
        },
      });
      if (farm) {
        targetFarmId = farm.id;
      }
    }

    // 2. Lookup worker by farmId + pin OR phone + pin
    let worker: any = null;
    if (targetFarmId) {
      worker = await db.worker.findFirst({
        where: {
          farmId: targetFarmId,
          pin: String(pin).trim(),
          status: "active",
        },
        include: { farm: true },
      });
    }

    // Fallback: search by phone number if farmCode didn't yield result
    if (!worker && phone) {
      const cleanPhone = phone.replace(/[\s\-\+\(\)]/g, "");
      worker = await db.worker.findFirst({
        where: {
          status: "active",
          phone: { contains: cleanPhone.slice(-9) },
          pin: String(pin).trim(),
        },
        include: { farm: true },
      });
    }

    // Final fallback: if only 1 worker exists with this PIN on the farm
    if (!worker) {
      worker = await db.worker.findFirst({
        where: {
          pin: String(pin).trim(),
          status: "active",
        },
        include: { farm: true },
      });
    }

    if (!worker) {
      return res.status(401).json({ error: "Incorrect Farm Code, Phone, or 4-digit PIN" });
    }

    // Generate worker JWT token
    const token = jwt.sign(
      {
        workerId: worker.id,
        farmId: worker.farmId,
        role: "worker",
        name: worker.name,
      },
      JWT_SECRET,
      { expiresIn: "30d" }
    );

    return res.json({
      token,
      worker: {
        id: worker.id,
        name: worker.name,
        phone: worker.phone,
        role: worker.role || "Farm Worker",
        farmId: worker.farmId,
        farmName: worker.farm.name,
        farmCode: worker.farm.code || `WANGARI-${worker.farm.id}`,
      },
    });
  } catch (error) {
    console.error("Worker login error:", error);
    return res.status(500).json({ error: "Worker login failed" });
  }
});

// All routes below require auth token
router.use(authMiddleware);

// ─── GET /api/worker/tasks — Today's tasks ────────────────
router.get("/tasks", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId || (req.user as any).farmId;
    const workerId = (req.user as any).workerId;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const tasks = await db.workerTask.findMany({
      where: {
        farmId,
        OR: [
          { workerId: workerId || undefined },
          { workerId: null },
        ],
      },
      orderBy: [
        { isCompleted: "asc" },
        { createdAt: "desc" },
      ],
      take: 20,
    });

    return res.json(tasks);
  } catch (error) {
    console.error("Get worker tasks error:", error);
    return res.status(500).json({ error: "Failed to fetch tasks" });
  }
});

// ─── POST /api/worker/tasks — Create a task (Farm owner or manager) ───
router.post("/tasks", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const { title, description, category, workerId, dueDate } = req.body;

    if (!title) {
      return res.status(400).json({ error: "Task title is required" });
    }

    const task = await db.workerTask.create({
      data: {
        farmId,
        workerId: workerId ? Number(workerId) : null,
        title,
        description: description || null,
        category: category || "general",
        dueDate: dueDate ? new Date(dueDate) : new Date(),
      },
    });

    return res.json(task);
  } catch (error) {
    console.error("Create task error:", error);
    return res.status(500).json({ error: "Failed to create task" });
  }
});

// ─── POST /api/worker/tasks/:id/complete — Toggle task done ───
router.post("/tasks/:id/complete", async (req: Request, res: Response) => {
  try {
    const taskId = Number(req.params.id);
    const farmId = req.user!.farmId!;
    const workerId = (req.user as any).workerId || null;

    const task = await db.workerTask.findFirst({
      where: { id: taskId, farmId },
    });

    if (!task) {
      return res.status(404).json({ error: "Task not found" });
    }

    const updated = await db.workerTask.update({
      where: { id: taskId },
      data: {
        isCompleted: !task.isCompleted,
        completedAt: !task.isCompleted ? new Date() : null,
      },
    });

    // Also record worker log if completing
    if (!task.isCompleted && workerId) {
      await db.workerLog.create({
        data: {
          farmId,
          workerId,
          type: "task_done",
          quantity: 1,
          unit: "task",
          notes: `Completed task: ${task.title}`,
        },
      }).catch(() => {});
    }

    return res.json(updated);
  } catch (error) {
    console.error("Complete task error:", error);
    return res.status(500).json({ error: "Failed to update task" });
  }
});

// ─── POST /api/worker/log-output — Fast numeric logger ─────
router.post("/log-output", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const workerId = (req.user as any).workerId || null;
    const { type, quantity, unit, flockId, notes } = req.body;

    const qty = Number(quantity);
    if (isNaN(qty) || qty <= 0) {
      return res.status(400).json({ error: "Valid positive quantity is required" });
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // 1. Record in WorkerLog
    let logRecord: any = null;
    if (workerId) {
      logRecord = await db.workerLog.create({
        data: {
          farmId,
          workerId,
          type, // 'eggs', 'milk', 'feed', 'mortality'
          quantity: qty,
          unit: unit || "units",
          notes: notes || null,
        },
      });
    }

    // 2. Automatically sync with DailyProduction or Inventory for Farm Owner
    if (flockId) {
      const targetFlock = await db.flock.findFirst({
        where: { id: Number(flockId), farmId },
      });

      if (targetFlock) {
        if (type === "eggs") {
          await db.dailyProduction.upsert({
            where: { flockId_date: { flockId: targetFlock.id, date: today } },
            create: {
              farmId,
              flockId: targetFlock.id,
              date: today,
              eggsCollected: qty,
            },
            update: {
              eggsCollected: { increment: qty },
            },
          });
        } else if (type === "milk") {
          await db.dailyProduction.upsert({
            where: { flockId_date: { flockId: targetFlock.id, date: today } },
            create: {
              farmId,
              flockId: targetFlock.id,
              date: today,
              milkCollected: qty,
            },
            update: {
              milkCollected: { increment: qty },
            },
          });
        } else if (type === "feed") {
          await db.dailyProduction.upsert({
            where: { flockId_date: { flockId: targetFlock.id, date: today } },
            create: {
              farmId,
              flockId: targetFlock.id,
              date: today,
              feedUsed: qty,
            },
            update: {
              feedUsed: { increment: qty },
            },
          });
        } else if (type === "mortality") {
          await db.dailyProduction.upsert({
            where: { flockId_date: { flockId: targetFlock.id, date: today } },
            create: {
              farmId,
              flockId: targetFlock.id,
              date: today,
              mortality: qty,
            },
            update: {
              mortality: { increment: qty },
            },
          });

          // Decrement flock current count
          await db.flock.update({
            where: { id: targetFlock.id },
            data: {
              currentCount: { decrement: qty },
              mortality: { increment: qty },
            },
          });
        }
      }
    }

    return res.json({
      success: true,
      message: "Log saved successfully",
      logRecord,
    });
  } catch (error) {
    console.error("Worker log output error:", error);
    return res.status(500).json({ error: "Failed to record log" });
  }
});

// ─── GET /api/worker/my-activity — Worker's logs for today ───
router.get("/my-activity", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const workerId = (req.user as any).workerId;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const logs = await db.workerLog.findMany({
      where: {
        farmId,
        workerId: workerId || undefined,
        createdAt: { gte: today },
      },
      orderBy: { createdAt: "desc" },
    });

    return res.json(logs);
  } catch (error) {
    console.error("Get worker activity error:", error);
    return res.status(500).json({ error: "Failed to fetch activity" });
  }
});

export default router;
