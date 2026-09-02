import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/inventory
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.inventory.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { itemName: "asc" },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/inventory
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.inventory.create({
      data: {
        farmId: req.user!.farmId!,
        itemName: req.body.itemName,
        category: req.body.category || null,
        quantity: Number(req.body.quantity),
        unit: req.body.unit || "bags",
        unitCost: Number(req.body.unitCost),
        reorderLevel: Number(req.body.reorderLevel || 0),
        supplier: req.body.supplier || null,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/inventory/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.inventory.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
