import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";
import multer from "multer";
import path from "path";
import fs from "fs";

const router = Router();
router.use(authMiddleware);

// Configure multer storage
const uploadsDir = path.join(process.cwd(), "uploads");
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

const storage = multer.diskStorage({
  destination: (_req, _file, cb) => cb(null, uploadsDir),
  filename: (_req, file, cb) => {
    const uniqueSuffix = Date.now() + "-" + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname) || ".jpg";
    cb(null, `flock-${uniqueSuffix}${ext}`);
  },
});

const upload = multer({
  storage,
  limits: { fileSize: 5 * 1024 * 1024 }, // 5MB
  fileFilter: (_req, file, cb) => {
    const allowed = /jpeg|jpg|png|gif|webp/;
    const ext = allowed.test(path.extname(file.originalname).toLowerCase());
    const mime = allowed.test(file.mimetype);
    cb(null, ext && mime);
  },
});

// POST /api/flocks/:id/photo — upload a photo for a flock
router.post("/:id/photo", upload.single("photo"), async (req: Request, res: Response) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: "No file uploaded" });
    }

    const flockId = Number(req.params.id);
    const photoUrl = `/uploads/${req.file.filename}`;

    // Verify flock belongs to this farm
    const flock = await prisma.flock.findFirst({
      where: { id: flockId, farmId: req.user!.farmId! },
    });

    if (!flock) {
      return res.status(404).json({ error: "Flock not found" });
    }

    // Delete old photo if exists
    if (flock.photoUrl) {
      const oldPath = path.join(process.cwd(), flock.photoUrl);
      if (fs.existsSync(oldPath)) {
        fs.unlinkSync(oldPath);
      }
    }

    await prisma.flock.update({
      where: { id: flockId },
      data: { photoUrl },
    });

    res.json({ success: true, photoUrl });
  } catch (error) {
    console.error("Upload photo error:", error);
    res.status(500).json({ error: "Failed to upload photo" });
  }
});

// DELETE /api/flocks/:id/photo — remove a flock's photo
router.delete("/:id/photo", async (req: Request, res: Response) => {
  try {
    const flockId = Number(req.params.id);
    const flock = await prisma.flock.findFirst({
      where: { id: flockId, farmId: req.user!.farmId! },
    });

    if (!flock) return res.status(404).json({ error: "Flock not found" });

    if (flock.photoUrl) {
      const filePath = path.join(process.cwd(), flock.photoUrl);
      if (fs.existsSync(filePath)) fs.unlinkSync(filePath);
    }

    await prisma.flock.update({
      where: { id: flockId },
      data: { photoUrl: null },
    });

    res.json({ success: true });
  } catch (error) {
    console.error("Delete photo error:", error);
    res.status(500).json({ error: "Failed to delete photo" });
  }
});

// GET /api/flocks/compare — compare multiple flocks
router.get("/compare", async (req: Request, res: Response) => {
  try {
    const ids = (req.query.ids as string)?.split(",").map(Number).filter(Boolean);
    if (!ids || ids.length < 2) {
      return res.status(400).json({ error: "Provide at least 2 flock IDs" });
    }

    const flocks = await prisma.flock.findMany({
      where: { id: { in: ids }, farmId: req.user!.farmId! },
      include: {
        vaccinations: { orderBy: { scheduledDate: "asc" } },
        production: { orderBy: { date: "desc" }, take: 30 },
      },
    });

    // Calculate comparison metrics
    const comparison = flocks.map((f) => {
      const prod = f.production || [];
      const last7 = prod.slice(0, 7);
      const avgProduction = last7.length > 0
        ? last7.reduce((s, p) => s + (p.eggsCollected || 0), 0) / last7.length
        : 0;
      const totalProduction = prod.reduce((s, p) => s + (p.eggsCollected || 0), 0);
      const mortalityRate = f.initialCount > 0 ? ((f.mortality / f.initialCount) * 100) : 0;
      const daysSinceStart = f.hatchDate
        ? Math.floor((Date.now() - new Date(f.hatchDate).getTime()) / 86400000)
        : 0;
      const totalFeedCost = (Number(f.feedCostPerMonth) || 0) * Math.ceil(daysSinceStart / 30);
      const pendingVax = (f.vaccinations || []).filter((v) => v.status === "pending").length;
      const completedVax = (f.vaccinations || []).filter((v) => v.status === "completed").length;

      return {
        id: f.id,
        name: f.name,
        breed: f.breed,
        type: f.type,
        category: f.category,
        initialCount: f.initialCount,
        currentCount: f.currentCount,
        mortality: f.mortality,
        mortalityRate,
        hatchDate: f.hatchDate,
        daysSinceStart,
        avgProduction,
        totalProduction,
        totalFeedCost,
        totalInvestment: Number(f.totalInvestment) || 0,
        feedCostPerMonth: Number(f.feedCostPerMonth) || 0,
        costPerAnimal: Number(f.costPerAnimal) || 0,
        pendingVax,
        completedVax,
        totalVax: (f.vaccinations || []).length,
        status: f.status,
        location: f.location,
        purpose: f.purpose,
        photoUrl: f.photoUrl,
      };
    });

    res.json(comparison);
  } catch (error) {
    console.error("Compare flocks error:", error);
    res.status(500).json({ error: "Failed to compare flocks" });
  }
});

// GET /api/flocks/:id/export — export flock data as CSV
router.get("/:id/export", async (req: Request, res: Response) => {
  try {
    const flockId = Number(req.params.id);
    const flock = await prisma.flock.findFirst({
      where: { id: flockId, farmId: req.user!.farmId! },
      include: {
        vaccinations: { orderBy: { scheduledDate: "asc" } },
        production: { orderBy: { date: "asc" } },
      },
    });

    if (!flock) return res.status(404).json({ error: "Flock not found" });

    const format = (req.query.format as string) || "csv";

    if (format === "json") {
      return res.json(flock);
    }

    // CSV export
    let csv = "Flock Report: " + flock.name + "\n\n";

    // Basic Info
    csv += "BASIC INFORMATION\n";
    csv += "Name," + flock.name + "\n";
    csv += "Breed," + (flock.breed || "") + "\n";
    csv += "Type," + (flock.type || "") + "\n";
    csv += "Category," + (flock.category || "") + "\n";
    csv += "Initial Count," + flock.initialCount + "\n";
    csv += "Current Count," + flock.currentCount + "\n";
    csv += "Mortality," + flock.mortality + "\n";
    csv += "Mortality Rate," + (flock.initialCount > 0 ? ((flock.mortality / flock.initialCount) * 100).toFixed(1) + "%" : "0%") + "\n";
    csv += "Start Date," + (flock.hatchDate ? new Date(flock.hatchDate).toLocaleDateString() : "") + "\n";
    csv += "Status," + flock.status + "\n";
    csv += "Purpose," + (flock.purpose || "") + "\n";
    csv += "Location," + (flock.location || "") + "\n";
    csv += "Cost per Animal," + (flock.costPerAnimal || "") + "\n";
    csv += "Total Investment," + (flock.totalInvestment || "") + "\n";
    csv += "Target Market," + (flock.targetMarket || "") + "\n";
    csv += "\n";

    // Production Data
    csv += "DAILY PRODUCTION\n";
    csv += "Date,Collect,Mortality,Feed Used (kg),Notes\n";
    for (const p of flock.production) {
      csv += [
        new Date(p.date).toLocaleDateString(),
        p.eggsCollected || 0,
        p.mortality || 0,
        p.feedUsed || 0,
        '"' + (p.notes || "").replace(/"/g, '""') + '"',
      ].join(",") + "\n";
    }
    csv += "\n";

    // Vaccination Schedule
    csv += "VACCINATION SCHEDULE\n";
    csv += "Vaccine,Scheduled Date,Completed Date,Status,Notes\n";
    for (const v of flock.vaccinations) {
      csv += [
        '"' + v.vaccineName + '"',
        new Date(v.scheduledDate).toLocaleDateString(),
        v.completedDate ? new Date(v.completedDate).toLocaleDateString() : "",
        v.status,
        '"' + (v.notes || "").replace(/"/g, '""') + '"',
      ].join(",") + "\n";
    }

    res.setHeader("Content-Type", "text/csv");
    res.setHeader("Content-Disposition", `attachment; filename="${flock.name.replace(/[^a-zA-Z0-9]/g, "_")}_report.csv"`);
    res.send(csv);
  } catch (error) {
    console.error("Export flock error:", error);
    res.status(500).json({ error: "Failed to export flock data" });
  }
});

export default router;
