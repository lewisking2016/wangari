import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

const OPENWEATHER_API_KEY = process.env.OPENWEATHER_API_KEY;

// GET /api/weather — current weather + 5-day forecast
router.get("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Check cache first
    try {
      const cached = await prisma.weatherCache.findFirst({
        where: { farmId, date: today },
      });
      if (cached && cached.forecastJson) {
        return res.json(cached.forecastJson);
      }
    } catch {
      // Cache table might not exist, continue
    }

    // Get coordinates from query params (browser geolocation) or farm location
    let lat = req.query.lat ? Number(req.query.lat) : null;
    let lon = req.query.lon ? Number(req.query.lon) : null;
    let location = "Your Farm";

    // If no GPS coords provided, try farm location from DB
    if (!lat || !lon) {
      try {
        const farm = await prisma.farm.findUnique({
          where: { id: farmId },
          select: { location: true, county: true },
        });
        location = farm?.location || farm?.county || "";
      } catch {}
    }

    // Try to fetch real weather if API key exists
    if (OPENWEATHER_API_KEY && OPENWEATHER_API_KEY !== "your-openweather-api-key") {
      // Only geocode if we don't have GPS coordinates from browser
      if (!lat || !lon) {
        try {
          const geoRes = await fetch(
            `https://api.openweathermap.org/geo/1.0/direct?q=${encodeURIComponent(location)},KE&limit=1&appid=${OPENWEATHER_API_KEY}`
          );
          const geoData = (await geoRes.json()) as any[];
          if (geoData && geoData.length > 0) {
            lat = geoData[0].lat;
            lon = geoData[0].lon;
            location = geoData[0].name || location;
          }
        } catch {}
      }
      // If still no coordinates, can't fetch weather
      if (!lat || !lon) {
        return res.json({ error: "No location available", location: location || "Set your farm location in Settings" });
      }

      try {
        // Fetch current weather + 5-day forecast
        const weatherRes = await fetch(
          `https://api.openweathermap.org/data/2.5/forecast?lat=${lat}&lon=${lon}&units=metric&cnt=40&appid=${OPENWEATHER_API_KEY}`
        );
        const forecastData: any = await weatherRes.json();

        if (forecastData && forecastData.list && forecastData.list.length > 0) {
          const current = forecastData.list[0];
          const todayForecast = forecastData.list.filter((item: any) => {
            const d = new Date(item.dt * 1000);
            return d.toDateString() === today.toDateString();
          });

          const dailyMin = todayForecast.length > 0
            ? Math.round(Math.min(...todayForecast.map((i: any) => i.main.temp_min)))
            : Math.round(current.main.temp_min);
          const dailyMax = todayForecast.length > 0
            ? Math.round(Math.max(...todayForecast.map((i: any) => i.main.temp_max)))
            : Math.round(current.main.temp_max);
          const totalRain = todayForecast.reduce((sum: number, i: any) => sum + (i.rain?.["3h"] || 0), 0);
          const avgHumidity = todayForecast.length > 0
            ? Math.round(todayForecast.reduce((sum: number, i: any) => sum + i.main.humidity, 0) / todayForecast.length)
            : current.main.humidity;

          // 5-day forecast
          const dailyForecast: any[] = [];
          const seen = new Set<string>();
          for (const item of forecastData.list) {
            const d = new Date(item.dt * 1000);
            const dayKey = d.toDateString();
            if (!seen.has(dayKey) && dailyForecast.length < 5) {
              seen.add(dayKey);
              dailyForecast.push({
                date: d.toISOString(),
                day: d.toLocaleDateString("en-KE", { weekday: "short" }),
                tempMin: Math.round(item.main.temp_min),
                tempMax: Math.round(item.main.temp_max),
                condition: item.weather[0]?.main || "Clear",
                description: item.weather[0]?.description || "",
                icon: getWeatherIcon(item.weather[0]?.main || "Clear"),
                rain: item.rain?.["3h"] || 0,
              });
            }
          }

          const sunrise = forecastData.city?.sunrise
            ? new Date(forecastData.city.sunrise * 1000).toLocaleTimeString("en-KE", { hour: "2-digit", minute: "2-digit" })
            : "06:30";
          const sunset = forecastData.city?.sunset
            ? new Date(forecastData.city.sunset * 1000).toLocaleTimeString("en-KE", { hour: "2-digit", minute: "2-digit" })
            : "18:45";

          const weather = {
            temperature: Math.round(current.main.temp),
            feelsLike: Math.round(current.main.feels_like),
            humidity: current.main.humidity,
            windSpeed: Math.round(current.wind?.speed * 3.6 || 0),
            condition: current.weather[0]?.main || "Clear",
            description: current.weather[0]?.description || "",
            icon: getWeatherIcon(current.weather[0]?.main || "Clear"),
            location: forecastData.city?.name || location,
            today: {
              tempMin: dailyMin,
              tempMax: dailyMax,
              rainMm: Math.round(totalRain * 10) / 10,
              avgHumidity,
              willRain: totalRain > 0,
            },
            sunrise,
            sunset,
            forecast: dailyForecast,
          };

          // Cache it (don't fail if cache write fails)
          try {
            await prisma.weatherCache.upsert({
              where: { location_date: { location, date: today } },
              create: {
                farmId,
                location,
                date: today,
                tempMax: dailyMax,
                tempMin: dailyMin,
                rainfallMm: totalRain,
                weatherCode: current.weather[0]?.main,
                forecastJson: weather,
              },
              update: {
                tempMax: dailyMax,
                tempMin: dailyMin,
                rainfallMm: totalRain,
                weatherCode: current.weather[0]?.main,
                forecastJson: weather,
              },
            });
          } catch {
            // Cache write failed, not critical
          }

          return res.json(weather);
        }
      } catch (err) {
        console.error("Weather API error:", err);
      }
    }

    // Fallback: no API key or no coordinates — return location prompt
    const weather = {
      temperature: 0,
      feelsLike: 0,
      humidity: 0,
      windSpeed: 0,
      condition: "Unknown",
      description: "Weather data unavailable — set your farm location in Settings or enable GPS",
      icon: "cloud" as const,
      location: location || "No location set",
      today: { tempMin: 0, tempMax: 0, rainMm: 0, avgHumidity: 0, willRain: false },
      sunrise: "--:--",
      sunset: "--:--",
      forecast: [],
      noData: true,
    };

    // Try to cache mock data
    try {
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
        update: { forecastJson: weather },
      });
    } catch {
      // Cache write failed, not critical
    }

    res.json(weather);
  } catch (error) {
    console.error("Weather error:", error);
    res.json({
      temperature: 0,
      feelsLike: 0,
      humidity: 0,
      windSpeed: 0,
      condition: "Unknown",
      description: "Unable to load weather. Check your internet connection and farm location.",
      icon: "cloud",
      location: "",
      today: { tempMin: 0, tempMax: 0, rainMm: 0, avgHumidity: 0, willRain: false },
      sunrise: "--:--",
      sunset: "--:--",
      forecast: [],
      noData: true,
    });
  }
});

function getWeatherIcon(condition: string): "sun" | "cloud" | "rain" {
  const c = condition.toLowerCase();
  if (c.includes("clear") || c.includes("sun")) return "sun";
  if (c.includes("rain") || c.includes("drizzle") || c.includes("thunder")) return "rain";
  return "cloud";
}

export default router;
