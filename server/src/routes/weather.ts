import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

const WEATHER_CODES: Record<number, { desc: string; condition: string; icon: "sun" | "cloud" | "rain" }> = {
  0: { desc: "Clear sky", condition: "Clear", icon: "sun" },
  1: { desc: "Mainly clear", condition: "Clear", icon: "sun" },
  2: { desc: "Partly cloudy", condition: "Partly Cloudy", icon: "cloud" },
  3: { desc: "Overcast", condition: "Cloudy", icon: "cloud" },
  45: { desc: "Fog", condition: "Foggy", icon: "cloud" },
  48: { desc: "Depositing rime fog", condition: "Foggy", icon: "cloud" },
  51: { desc: "Light drizzle", condition: "Drizzle", icon: "rain" },
  53: { desc: "Moderate drizzle", condition: "Drizzle", icon: "rain" },
  55: { desc: "Dense drizzle", condition: "Drizzle", icon: "rain" },
  61: { desc: "Slight rain", condition: "Rain", icon: "rain" },
  63: { desc: "Moderate rain", condition: "Rain", icon: "rain" },
  65: { desc: "Heavy rain", condition: "Heavy Rain", icon: "rain" },
  71: { desc: "Slight snow", condition: "Snow", icon: "cloud" },
  73: { desc: "Moderate snow", condition: "Snow", icon: "cloud" },
  75: { desc: "Heavy snow", condition: "Snow", icon: "cloud" },
  80: { desc: "Rain showers", condition: "Rain Showers", icon: "rain" },
  81: { desc: "Moderate rain showers", condition: "Rain Showers", icon: "rain" },
  82: { desc: "Violent rain showers", condition: "Rain Showers", icon: "rain" },
  95: { desc: "Thunderstorm", condition: "Thunderstorm", icon: "rain" },
  96: { desc: "Thunderstorm with hail", condition: "Thunderstorm", icon: "rain" },
  99: { desc: "Thunderstorm with heavy hail", condition: "Thunderstorm", icon: "rain" },
};

function getDayName(dateStr: string, index: number): string {
  if (index === 0) return "Today";
  const d = new Date(dateStr + "T00:00:00");
  return d.toLocaleDateString("en-KE", { weekday: "short" });
}

// GET /api/weather — current weather + 5-day forecast
router.get("/", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;

    // Get coordinates from query params (browser geolocation) or farm location
    let lat = req.query.lat ? Number(req.query.lat) : null;
    let lon = req.query.lon ? Number(req.query.lon) : null;
    let locationName = "Your Farm";

    // If no GPS coords, lookup farm location/county in DB
    if (!lat || !lon) {
      try {
        const farm = await prisma.farm.findUnique({
          where: { id: farmId },
          select: { location: true, county: true },
        });
        const targetLoc = farm?.location || farm?.county;
        if (targetLoc) {
          locationName = targetLoc;
          try {
            const geoRes = await fetch(
              `https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(targetLoc)}&count=1`
            );
            if (geoRes.ok) {
              const geoData: any = await geoRes.json();
              if (geoData.results && geoData.results.length > 0) {
                lat = geoData.results[0].latitude;
                lon = geoData.results[0].longitude;
                locationName = `${geoData.results[0].name}${geoData.results[0].admin1 ? `, ${geoData.results[0].admin1}` : ""}`;
              }
            }
          } catch (e) {
            console.error("Geocoding failed:", e);
          }
        }
      } catch (e) {
        console.error("Farm lookup failed:", e);
      }
    }

    // Default coordinates if still empty: Nakuru, Kenya
    if (!lat || !lon) {
      lat = -0.3031;
      lon = 36.08;
      if (locationName === "Your Farm") {
        locationName = "Nakuru, Kenya";
      }
    }

    // Fetch from Open-Meteo (100% free, no API key needed)
    const currentRes = await fetch(
      `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}` +
      `&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,precipitation` +
      `&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code,sunrise,sunset` +
      `&timezone=Africa/Nairobi&forecast_days=7`
    );

    if (!currentRes.ok) {
      throw new Error(`Open-Meteo status ${currentRes.status}`);
    }

    const data: any = await currentRes.json();
    const current = data.current;
    const daily = data.daily;
    const code = current.weather_code;
    const weatherInfo = WEATHER_CODES[code] || { desc: "Cloudy", condition: "Cloudy", icon: "cloud" as const };

    const todayWillRain = daily.precipitation_sum[0] > 0.1;
    const todayObj = {
      tempMin: Math.round(daily.temperature_2m_min[0]),
      tempMax: Math.round(daily.temperature_2m_max[0]),
      rainMm: Math.round(daily.precipitation_sum[0] * 10) / 10,
      avgHumidity: Math.round(current.relative_humidity_2m),
      willRain: todayWillRain,
    };

    const sunrise = daily.sunrise?.[0]
      ? new Date(daily.sunrise[0]).toLocaleTimeString("en-KE", { hour: "2-digit", minute: "2-digit", hour12: false, timeZone: "Africa/Nairobi" })
      : "06:30";
    const sunset = daily.sunset?.[0]
      ? new Date(daily.sunset[0]).toLocaleTimeString("en-KE", { hour: "2-digit", minute: "2-digit", hour12: false, timeZone: "Africa/Nairobi" })
      : "18:45";

    const forecast = daily.time.map((date: string, i: number) => {
      const dayCode = daily.weather_code[i];
      const dayInfo = WEATHER_CODES[dayCode] || { desc: "Cloudy", condition: "Cloudy", icon: "cloud" as const };
      return {
        date,
        day: getDayName(date, i),
        tempMin: Math.round(daily.temperature_2m_min[i]),
        tempMax: Math.round(daily.temperature_2m_max[i]),
        maxTemp: Math.round(daily.temperature_2m_max[i]),
        minTemp: Math.round(daily.temperature_2m_min[i]),
        rain: Math.round(daily.precipitation_sum[i] * 10) / 10,
        precipitation: Math.round(daily.precipitation_sum[i] * 10) / 10,
        weatherCode: dayCode,
        condition: dayInfo.condition,
        description: dayInfo.desc,
        icon: dayInfo.icon,
      };
    });

    const result = {
      temperature: Math.round(current.temperature_2m),
      feelsLike: Math.round(current.apparent_temperature),
      humidity: Math.round(current.relative_humidity_2m),
      windSpeed: Math.round(current.wind_speed_10m),
      precipitation: current.precipitation,
      condition: weatherInfo.condition,
      description: weatherInfo.desc,
      icon: weatherInfo.icon,
      code,
      location: locationName,
      today: todayObj,
      sunrise,
      sunset,
      forecast,
    };

    return res.json(result);
  } catch (error) {
    console.error("Weather route error:", error);
    return res.status(500).json({ error: "Failed to fetch weather data" });
  }
});

export default router;
