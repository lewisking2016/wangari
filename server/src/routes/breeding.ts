import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/breeding — list all breeding records for the farm
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.breeding.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { matingDate: "desc" },
      include: { flock: { select: { name: true, type: true, breed: true } } },
    });
    res.json(data);
  } catch (error) {
    console.error("List breeding error:", error);
    res.status(500).json({ error: "Failed to fetch breeding records" });
  }
});

// GET /api/breeding/flock/:flockId — breeding records for a specific flock
router.get("/flock/:flockId", async (req: Request, res: Response) => {
  try {
    const data = await prisma.breeding.findMany({
      where: {
        flockId: Number(req.params.flockId),
        farmId: req.user!.farmId!,
      },
      orderBy: { matingDate: "desc" },
    });
    res.json(data);
  } catch (error) {
    console.error("List flock breeding error:", error);
    res.status(500).json({ error: "Failed to fetch breeding records" });
  }
});

// POST /api/breeding — create a breeding record
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.breeding.create({
      data: {
        flockId: Number(req.body.flockId),
        farmId: req.user!.farmId!,
        sireName: req.body.sireName || null,
        sireBreed: req.body.sireBreed || null,
        damName: req.body.damName || null,
        damBreed: req.body.damBreed || null,
        matingDate: new Date(req.body.matingDate),
        expectedBirth: req.body.expectedBirth ? new Date(req.body.expectedBirth) : null,
        method: req.body.method || null,
        notes: req.body.notes || null,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    console.error("Create breeding error:", error);
    res.status(500).json({ error: "Failed to create breeding record" });
  }
});

// PATCH /api/breeding/:id — update (mark as born, add offspring count, etc.)
router.patch("/:id", async (req: Request, res: Response) => {
  try {
    const data: Record<string, any> = {};
    if (req.body.status !== undefined) data.status = req.body.status;
    if (req.body.actualBirth !== undefined) data.actualBirth = req.body.actualBirth ? new Date(req.body.actualBirth) : null;
    if (req.body.offspringCount !== undefined) data.offspringCount = Number(req.body.offspringCount);
    if (req.body.offspringAlive !== undefined) data.offspringAlive = Number(req.body.offspringAlive);
    if (req.body.notes !== undefined) data.notes = req.body.notes;
    if (req.body.sireName !== undefined) data.sireName = req.body.sireName;
    if (req.body.damName !== undefined) data.damName = req.body.damName;

    const result = await prisma.breeding.update({
      where: { id: Number(req.params.id) },
      data,
    });
    res.json(result);
  } catch (error) {
    console.error("Update breeding error:", error);
    res.status(500).json({ error: "Failed to update breeding record" });
  }
});

// DELETE /api/breeding/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.breeding.delete({ where: { id: Number(req.params.id) } });
    res.json({ success: true });
  } catch (error) {
    console.error("Delete breeding error:", error);
    res.status(500).json({ error: "Failed to delete breeding record" });
  }
});

export default router;
