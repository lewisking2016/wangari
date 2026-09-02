import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// GET /api/weather
router.get("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const today = new Date();

    // Check cache
    const cached = await prisma.weatherCache.findFirst({
      where: { farmId, date: today },
    });

    if (cached) {
      return res.json(cached);
    }

    // Mock weather data for now
    const weather = {
      temp: 25 + Math.round(Math.random() * 5),
      humidity: 60 + Math.round(Math.random() * 20),
      description: "Partly cloudy",
      rainfall: Math.round(Math.random() * 10),
    };

    // Cache it
    await prisma.weatherCache.create({
      data: {
        farmId,
        date: today,
        tempMax: weather.temp,
        tempMin: weather.temp - 5,
        rainfallMm: weather.rainfall,
        forecastJson: weather,
      },
    });

    res.json(weather);
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

export default router;
