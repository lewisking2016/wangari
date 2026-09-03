import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/crops — list all crops
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.crop.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { createdAt: "desc" },
      include: { harvests: { orderBy: { date: "desc" }, take: 10 } },
    });
    res.json(data);
  } catch (error) {
    console.error("List crops error:", error);
    res.status(500).json({ error: "Failed to fetch crops" });
  }
});

// POST /api/crops — create a crop
router.post("/", async (req: Request, res: Response) => {
  try {
    const { name, cropType, variety, areaAcres, plantingDate, expectedHarvest, location, soilType, irrigation, notes } = req.body;
    const crop = await prisma.crop.create({
      data: {
        farmId: req.user!.farmId!,
        name,
        cropType,
        variety: variety || null,
        areaAcres: areaAcres ? Number(areaAcres) : null,
        plantingDate: plantingDate ? new Date(plantingDate) : null,
        expectedHarvest: expectedHarvest ? new Date(expectedHarvest) : null,
        location: location || null,
        soilType: soilType || null,
        irrigation: irrigation || null,
        notes: notes || null,
        createdBy: req.user!.userId,
      },
    });
    res.status(201).json(crop);
  } catch (error) {
    console.error("Create crop error:", error);
    res.status(500).json({ error: "Failed to create crop" });
  }
});

// PATCH /api/crops/:id — update a crop
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const allowed = ["name", "cropType", "variety", "areaAcres", "plantingDate", "expectedHarvest", "status", "location", "soilType", "irrigation", "notes"];
    const data: Record<string, any> = {};
    for (const key of allowed) {
      if (req.body[key] !== undefined) data[key] = req.body[key] === "" ? null : req.body[key];
    }
    if (data.areaAcres) data.areaAcres = Number(data.areaAcres);
    if (data.plantingDate) data.plantingDate = new Date(data.plantingDate);
    if (data.expectedHarvest) data.expectedHarvest = new Date(data.expectedHarvest);

    const result = await prisma.crop.updateMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
      data,
    });
    res.json({ success: true, updated: result.count });
  } catch (error) {
    console.error("Update crop error:", error);
    res.status(500).json({ error: "Failed to update crop" });
  }
});

// DELETE /api/crops/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.cropHarvest.deleteMany({ where: { cropId: Number(req.params.id) } });
    await prisma.crop.deleteMany({ where: { id: Number(req.params.id), farmId: req.user!.farmId! } });
    res.json({ success: true });
  } catch (error) {
    console.error("Delete crop error:", error);
    res.status(500).json({ error: "Failed to delete crop" });
  }
});

// POST /api/crops/:id/harvest — record a harvest
router.post("/:id/harvest", async (req: Request, res: Response) => {
  try {
    const cropId = Number(req.params.id);
    const crop = await prisma.crop.findFirst({ where: { id: cropId, farmId: req.user!.farmId! } });
    if (!crop) return res.status(404).json({ error: "Crop not found" });

    const { date, quantityKg, quality, soldQuantity, salePrice, notes } = req.body;
    const harvest = await prisma.cropHarvest.create({
      data: {
        cropId,
        farmId: req.user!.farmId!,
        date: date ? new Date(date) : new Date(),
        quantityKg: Number(quantityKg),
        quality: quality || null,
        soldQuantity: soldQuantity ? Number(soldQuantity) : null,
        salePrice: salePrice ? Number(salePrice) : null,
        notes: notes || null,
      },
    });
    res.status(201).json(harvest);
  } catch (error) {
    console.error("Record harvest error:", error);
    res.status(500).json({ error: "Failed to record harvest" });
  }
});

// GET /api/crops/:id/harvests — list harvests for a crop
router.get("/:id/harvests", async (req: Request, res: Response) => {
  try {
    const harvests = await prisma.cropHarvest.findMany({
      where: { cropId: Number(req.params.id), farmId: req.user!.farmId! },
      orderBy: { date: "desc" },
    });
    res.json(harvests);
  } catch (error) {
    console.error("List harvests error:", error);
    res.status(500).json({ error: "Failed to fetch harvests" });
  }
});

// POST /api/crops/:id/health — record a health issue
router.post("/:id/health", async (req: Request, res: Response) => {
  try {
    const cropId = Number(req.params.id);
    const crop = await prisma.crop.findFirst({ where: { id: cropId, farmId: req.user!.farmId! } });
    if (!crop) return res.status(404).json({ error: "Crop not found" });

    const { date, issueType, description, severity, treatment, notes } = req.body;
    const health = await prisma.cropHealth.create({
      data: {
        cropId,
        farmId: req.user!.farmId!,
        date: date ? new Date(date) : new Date(),
        issueType,
        description,
        severity: severity || "low",
        treatment: treatment || null,
        notes: notes || null,
      },
    });
    res.status(201).json(health);
  } catch (error) {
    console.error("Record health error:", error);
    res.status(500).json({ error: "Failed to record health issue" });
  }
});

// GET /api/crops/:id/health — list health issues for a crop
router.get("/:id/health", async (req: Request, res: Response) => {
  try {
    const health = await prisma.cropHealth.findMany({
      where: { cropId: Number(req.params.id), farmId: req.user!.farmId! },
      orderBy: { date: "desc" },
    });
    res.json(health);
  } catch (error) {
    console.error("List health error:", error);
    res.status(500).json({ error: "Failed to fetch health records" });
  }
});

// PATCH /api/crops/health/:id — update health issue (e.g. mark resolved)
router.patch("/health/:id", async (req: Request, res: Response) => {
  try {
    const { outcome, treatment, treatedDate } = req.body;
    const data: Record<string, any> = {};
    if (outcome) data.outcome = outcome;
    if (treatment) data.treatment = treatment;
    if (treatedDate) data.treatedDate = new Date(treatedDate);
    await prisma.cropHealth.updateMany({ where: { id: Number(req.params.id), farmId: req.user!.farmId! }, data });
    res.json({ success: true });
  } catch (error) {
    console.error("Update health error:", error);
    res.status(500).json({ error: "Failed to update" });
  }
});

// POST /api/crops/:id/apply — record fertilizer/pesticide/irrigation application
router.post("/:id/apply", async (req: Request, res: Response) => {
  try {
    const cropId = Number(req.params.id);
    const crop = await prisma.crop.findFirst({ where: { id: cropId, farmId: req.user!.farmId! } });
    if (!crop) return res.status(404).json({ error: "Crop not found" });

    const { date, type, productName, quantity, unit, cost, notes } = req.body;
    const application = await prisma.cropApplication.create({
      data: {
        cropId,
        farmId: req.user!.farmId!,
        date: date ? new Date(date) : new Date(),
        type,
        productName,
        quantity: Number(quantity),
        unit: unit || "kg",
        cost: cost ? Number(cost) : null,
        notes: notes || null,
      },
    });
    res.status(201).json(application);
  } catch (error) {
    console.error("Record application error:", error);
    res.status(500).json({ error: "Failed to record application" });
  }
});

// GET /api/crops/:id/applications — list applications for a crop
router.get("/:id/applications", async (req: Request, res: Response) => {
  try {
    const apps = await prisma.cropApplication.findMany({
      where: { cropId: Number(req.params.id), farmId: req.user!.farmId! },
      orderBy: { date: "desc" },
    });
    res.json(apps);
  } catch (error) {
    console.error("List applications error:", error);
    res.status(500).json({ error: "Failed to fetch applications" });
  }
});

export default router;
