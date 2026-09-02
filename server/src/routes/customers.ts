import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/customers
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.customer.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { name: "asc" },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/customers
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.customer.create({
      data: {
        farmId: req.user!.farmId!,
        name: req.body.name,
        phone: req.body.phone || null,
        email: req.body.email || null,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/customers/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.customer.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
