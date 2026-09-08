import { Router, Request, Response } from "express";
import { getActivePlans } from "../lib/plans.js";

const router = Router();

// GET /api/plans — public pricing list (no auth; pricing is public information).
// Frontend subscription page and upgrade popups render from this — no price literals in the UI.
router.get("/", async (_req: Request, res: Response) => {
  try {
    const plans = await getActivePlans();
    res.json(
      plans.map((p) => ({
        id: p.id,
        name: p.name,
        description: p.description,
        amount: p.amount, // pesewas (minor unit)
        amountKes: p.amount / 100,
        days: p.days,
      }))
    );
  } catch (error) {
    console.error("Plans list error:", error);
    res.status(500).json({ error: "Failed to load plans" });
  }
});

export default router;
