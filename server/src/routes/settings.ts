import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware, generateToken } from "../middleware/auth.js";
import bcrypt from "bcryptjs";

const router = Router();
router.use(authMiddleware);

// GET /api/settings — get all farm settings
router.get("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const settings = await prisma.farmSetting.findMany({ where: { farmId } });
    const map: Record<string, string> = {};
    settings.forEach((s: any) => { map[s.settingKey] = s.settingValue || ""; });

    // Also get farm info and user info
    const farm = await prisma.farm.findUnique({ where: { id: farmId } });
    const user = await prisma.user.findUnique({ where: { id: req.user!.userId }, select: { name: true, email: true, phone: true } });

    res.json({ settings: map, farm, user });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PUT /api/settings — update settings (bulk)
router.put("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const { settings } = req.body;

    if (typeof settings === "object" && settings !== null) {
      for (const [key, value] of Object.entries(settings)) {
        await prisma.farmSetting.upsert({
          where: { farmId_settingKey: { farmId, settingKey: key } },
          create: { farmId, settingKey: key, settingValue: String(value) },
          update: { settingValue: String(value) },
        });
      }
    }
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PUT /api/settings/profile — update farm + user profile
router.put("/profile", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const userId = req.user!.userId;

    // Update farm
    if (req.body.farmName || req.body.location || req.body.county || req.body.farmType) {
      await prisma.farm.update({ where: { id: farmId }, data: {
        name: req.body.farmName || undefined,
        location: req.body.location || undefined,
        county: req.body.county || undefined,
        farmType: req.body.farmType || undefined,
      }});
    }

    // Update user
    if (req.body.name || req.body.email || req.body.phone) {
      await prisma.user.update({ where: { id: userId }, data: {
        name: req.body.name || undefined,
        email: req.body.email || undefined,
        phone: req.body.phone || undefined,
      }});
    }

    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// PUT /api/settings/password — change password
router.put("/password", async (req: Request, res: Response) => {
  try {
    const { currentPassword, newPassword } = req.body;
    if (!currentPassword || !newPassword) return res.status(400).json({ error: "Both passwords required" });
    if (newPassword.length < 6) return res.status(400).json({ error: "Password must be at least 6 characters" });

    const user = await prisma.user.findUnique({ where: { id: req.user!.userId } });
    if (!user) return res.status(404).json({ error: "User not found" });

    if (!user.password) {
      return res.status(400).json({ error: "This account uses Google sign-in. Password cannot be changed here." });
    }

    const valid = await bcrypt.compare(currentPassword, user.password);
    if (!valid) return res.status(401).json({ error: "Current password is incorrect" });

    const hashed = await bcrypt.hash(newPassword, 12);
    await prisma.user.update({ where: { id: user.id }, data: { password: hashed } });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
