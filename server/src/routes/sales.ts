import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/sales
router.get("/", async (req: Request, res: Response) => {
  try {
    const data = await prisma.sale.findMany({
      where: { farmId: req.user!.farmId! },
      orderBy: { saleDate: "desc" },
      take: 100,
      include: { customer: { select: { name: true } } },
    });
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/sales
router.post("/", async (req: Request, res: Response) => {
  try {
    const result = await prisma.sale.create({
      data: {
        farmId: req.user!.farmId!,
        customerId: req.body.customerId ? Number(req.body.customerId) : null,
        saleDate: new Date(req.body.saleDate || new Date()),
        items: req.body.items || "",
        totalAmount: Number(req.body.totalAmount),
        amountPaid: Number(req.body.amountPaid || 0),
        paymentStatus: req.body.paymentStatus || "pending",
        createdBy: req.user!.userId,
      },
    });
    res.status(201).json(result);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// DELETE /api/sales/:id
router.delete("/:id", async (req: Request, res: Response) => {
  try {
    await prisma.sale.deleteMany({
      where: { id: Number(req.params.id), farmId: req.user!.farmId! },
    });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
