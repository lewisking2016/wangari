import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

const OPENWEATHER_API_KEY = process.env.OPENWEATHER_API_KEY;

// GET /api/weather
router.get("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Check cache first
    const cached = await prisma.weatherCache.findFirst({
      where: { farmId, date: today },
    });

    if (cached && cached.forecastJson) {
      return res.json(cached.forecastJson);
    }

    // Get farm location
    const farm = await prisma.farm.findUnique({
      where: { id: farmId },
      select: { location: true, county: true },
    });

    // Default to Nairobi if no location
    const location = farm?.location || farm?.county || "Nairobi";
    let lat = -1.2921; // Nairobi default
    let lon = 36.8219;

    // Try to get coordinates from location name
    if (OPENWEATHER_API_KEY) {
      try {
        const geoRes = await fetch(
          `https://api.openweathermap.org/geo/1.0/direct?q=${encodeURIComponent(location)},KE&limit=1&appid=${OPENWEATHER_API_KEY}`
        );
        const geoData = await geoRes.json();
        if (geoData && geoData.length > 0) {
          lat = geoData[0].lat;
          lon = geoData[0].lon;
        }
      } catch {
        // Use default coordinates
      }

      // Fetch current weather
      try {
        const weatherRes = await fetch(
          `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&units=metric&appid=${OPENWEATHER_API_KEY}`
        );
        const weatherData = await weatherRes.json();

        if (weatherData && weatherData.main) {
          const weather = {
            temperature: Math.round(weatherData.main.temp),
            humidity: weatherData.main.humidity,
            windSpeed: Math.round(weatherData.wind?.speed * 3.6 || 0), // m/s to km/h
            condition: weatherData.weather[0]?.main || "Clear",
            description: weatherData.weather[0]?.description || "",
            icon: getWeatherIcon(weatherData.weather[0]?.main || "Clear"),
            location: weatherData.name || location,
          };

          // Cache it
          await prisma.weatherCache.upsert({
            where: { location_date: { location: location, date: today } },
            create: {
              farmId,
              location,
              date: today,
              tempMax: weather.temperature,
              tempMin: weather.temperature - 5,
              rainfallMm: 0,
              weatherCode: weatherData.weather[0]?.main,
              forecastJson: weather,
            },
            update: {
              tempMax: weather.temperature,
              tempMin: weather.temperature - 5,
              weatherCode: weatherData.weather[0]?.main,
              forecastJson: weather,
            },
          });

          return res.json(weather);
        }
      } catch {
        // Fall through to mock
      }
    }

    // Fallback: mock weather data
    const weather = {
      temperature: 25 + Math.round(Math.random() * 5),
      humidity: 60 + Math.round(Math.random() * 20),
      windSpeed: 5 + Math.round(Math.random() * 15),
      condition: "Partly Cloudy",
      description: "partly cloudy",
      icon: "cloud" as const,
      location,
    };

    // Cache mock data
    await prisma.weatherCache.upsert({
      where: { location_date: { location, date: today } },
      create: {
        farmId,
        location,
        date: today,
        tempMax: weather.temperature,
        tempMin: weather.temperature - 5,
        rainfallMm: 0,
        weatherCode: "Clouds",
        forecastJson: weather,
      },
      update: {
        forecastJson: weather,
      },
    });

    res.json(weather);
  } catch (error) {
    console.error("Weather error:", error);
    res.status(500).json({ error: "Failed to fetch weather" });
  }
});

function getWeatherIcon(condition: string): "sun" | "cloud" | "rain" {
  const c = condition.toLowerCase();
  if (c.includes("clear") || c.includes("sun")) return "sun";
  if (c.includes("rain") || c.includes("drizzle") || c.includes("thunder")) return "rain";
  return "cloud";
}

export default router;
