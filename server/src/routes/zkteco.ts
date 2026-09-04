import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();

// ─── PUBLIC: Device Push Endpoint ─────────────────────────
// ZKTeco devices POST attendance data here
// No auth required — authenticated by device serial + password
router.post("/push", async (req: Request, res: Response) => {
  try {
    // ZKTeco ADMS push format: JSON with device info + attendance records
    const { sn, record } = req.body;

    if (!sn) {
      return res.status(400).json({ error: "Missing device serial number" });
    }

    // Find the device
    const device = await prisma.zKTecoDevice.findFirst({
      where: { serialNumber: sn, status: "active" },
    });

    if (!device) {
      return res.status(404).json({ error: "Device not registered" });
    }

    // Update last sync time
    await prisma.zKTecoDevice.update({
      where: { id: device.id },
      data: { lastSyncAt: new Date() },
    });

    // Process attendance records
    const records = Array.isArray(record) ? record : [record];
    let imported = 0;

    for (const rec of records) {
      if (!rec || !rec.user_id) continue;

      const attendanceId = `${sn}-${rec.user_id}-${rec.timestamp || rec.time}`;

      // Skip duplicates
      const existing = await prisma.zKTecoLog.findUnique({
        where: { deviceId_attendanceId: { deviceId: device.id, attendanceId } },
      }).catch(() => null);

      if (existing) continue;

      // Try to map to a Wangari worker
      const mappedWorker = await prisma.worker.findFirst({
        where: {
          farmId: device.farmId,
          OR: [
            { name: { contains: String(rec.user_id), mode: "insensitive" } },
            { phone: String(rec.user_id) },
          ],
        },
      });

      const timestamp = rec.timestamp
        ? new Date(rec.timestamp * 1000) // Unix timestamp
        : rec.time
        ? new Date(rec.time)
        : new Date();

      await prisma.zKTecoLog.create({
        data: {
          deviceId: device.id,
          farmId: device.farmId,
          deviceUserId: String(rec.user_id),
          workerId: mappedWorker?.id || null,
          timestamp,
          verifyType: rec.verify_type || "fingerprint",
          attendanceId,
          raw: rec,
        },
      });

      // Also create/update attendance record for today
      const today = new Date(timestamp);
      today.setHours(0, 0, 0, 0);

      if (mappedWorker) {
        const existingAttendance = await prisma.attendance.findFirst({
          where: {
            workerId: mappedWorker.id,
            farmId: device.farmId,
            date: today,
          },
        });

        const timeStr = timestamp.toTimeString().slice(0, 5);

        if (existingAttendance) {
          // Update checkout if already clocked in
          if (existingAttendance.checkIn && !existingAttendance.checkOut) {
            await prisma.attendance.update({
              where: { id: existingAttendance.id },
              data: { checkOut: timeStr, status: "present" },
            });
          }
        } else {
          // New clock in
          await prisma.attendance.create({
            data: {
              workerId: mappedWorker.id,
              farmId: device.farmId,
              date: today,
              checkIn: timeStr,
              status: "present",
              notes: `Biometric (${rec.verify_type || "fingerprint"})`,
            },
          });
        }
      }

      imported++;
    }

    res.json({ success: true, imported, device: device.name || device.serialNumber });
  } catch (error) {
    console.error("ZKTeco push error:", error);
    res.status(500).json({ error: "Failed to process push data" });
  }
});

// ─── AUTHENTICATED: Device Management ─────────────────────
router.use(authMiddleware);

// GET /api/zkteco/devices — list registered devices
router.get("/devices", async (req: Request, res: Response) => {
  try {
    const devices = await prisma.zKTecoDevice.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { createdAt: "desc" },
      include: {
        _count: { select: { logs: true } },
      },
    });
    res.json(devices);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/zkteco/devices — register a new device
router.post("/devices", async (req: Request, res: Response) => {
  try {
    const { serialNumber, name, model } = req.body;
    if (!serialNumber) return res.status(400).json({ error: "Serial number required" });

    const device = await prisma.zKTecoDevice.create({
      data: {
        farmId: req.user!.farmId!,
        serialNumber,
        name: name || null,
        model: model || null,
      },
    });
    res.status(201).json(device);
  } catch (error: any) {
    if (error?.code === "P2002") {
      return res.status(409).json({ error: "Device already registered" });
    }
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/zkteco/devices/:id
router.delete("/devices/:id", async (req: Request, res: Response) => {
  try {
    await prisma.zKTecoLog.deleteMany({ where: { deviceId: Number(req.params.id) } });
    await prisma.zKTecoDevice.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// GET /api/zkteco/logs — list biometric logs
router.get("/logs", async (req: Request, res: Response) => {
  try {
    const logs = await prisma.zKTecoLog.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { timestamp: "desc" },
      take: 100,
      include: {
        device: { select: { name: true, serialNumber: true } },
        worker: { select: { name: true, role: true } },
      },
    });
    res.json(logs);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PATCH /api/zkteco/logs/:id/map — map a log to a worker
router.patch("/logs/:id/map", async (req: Request, res: Response) => {
  try {
    const { workerId } = req.body;
    await prisma.zKTecoLog.update({
      where: { id: Number(req.params.id) },
      data: { workerId: workerId ? Number(workerId) : null },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// GET /api/zkteco/unmapped — get unmapped device user IDs
router.get("/unmapped", async (req: Request, res: Response) => {
  try {
    const logs = await prisma.zKTecoLog.findMany({
      where: { farmId: req.user!.farmId!, workerId: null },
      distinct: ["deviceUserId"],
      select: { deviceUserId: true, device: { select: { name: true } } },
      orderBy: { timestamp: "desc" },
    });
    res.json(logs);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
