import { NextRequest, NextResponse } from "next/server";

const DEFAULT_LAT = -0.3031;
const DEFAULT_LNG = 36.08;

const WEATHER_CODES: Record<number, { desc: string; condition: string; icon: "sun" | "cloud" | "rain" }> = {
  0: { desc: "Clear sky", condition: "clear", icon: "sun" },
  1: { desc: "Mainly clear", condition: "clear", icon: "sun" },
  2: { desc: "Partly cloudy", condition: "cloudy", icon: "cloud" },
  3: { desc: "Overcast", condition: "cloudy", icon: "cloud" },
  45: { desc: "Fog", condition: "cloudy", icon: "cloud" },
  48: { desc: "Depositing rime fog", condition: "cloudy", icon: "cloud" },
  51: { desc: "Light drizzle", condition: "drizzle", icon: "rain" },
  53: { desc: "Moderate drizzle", condition: "drizzle", icon: "rain" },
  55: { desc: "Dense drizzle", condition: "drizzle", icon: "rain" },
  61: { desc: "Slight rain", condition: "rain", icon: "rain" },
  63: { desc: "Moderate rain", condition: "rain", icon: "rain" },
  65: { desc: "Heavy rain", condition: "rain", icon: "rain" },
  71: { desc: "Slight snow", condition: "snow", icon: "cloud" },
  73: { desc: "Moderate snow", condition: "snow", icon: "cloud" },
  75: { desc: "Heavy snow", condition: "snow", icon: "cloud" },
  80: { desc: "Rain showers", condition: "rain", icon: "rain" },
  81: { desc: "Moderate rain showers", condition: "rain", icon: "rain" },
  82: { desc: "Violent rain showers", condition: "rain", icon: "rain" },
  95: { desc: "Thunderstorm", condition: "thunderstorm", icon: "rain" },
  96: { desc: "Thunderstorm with hail", condition: "thunderstorm", icon: "rain" },
  99: { desc: "Thunderstorm with heavy hail", condition: "thunderstorm", icon: "rain" },
};

function getDayName(dateStr: string, index: number): string {
  if (index === 0) return "Today";
  const d = new Date(dateStr + "T00:00:00");
  return d.toLocaleDateString("en-US", { weekday: "short" });
}

export async function GET(req: NextRequest) {
  try {
    const lat = req.nextUrl.searchParams.get("lat") || DEFAULT_LAT;
    const lon = req.nextUrl.searchParams.get("lon") || DEFAULT_LNG;

    const currentRes = await fetch(
      `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}` +
      `&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,precipitation` +
      `&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code,sunrise,sunset` +
      `&timezone=Africa/Nairobi&forecast_days=7`
    );

    if (!currentRes.ok) {
      return NextResponse.json({ error: "Weather API error" }, { status: 502 });
    }

    const data = await currentRes.json();
    const current = data.current;
    const daily = data.daily;
    const code = current.weather_code;
    const weatherInfo = WEATHER_CODES[code] || { desc: "Unknown", condition: "cloudy", icon: "cloud" as const };

    // Today's data
    const todayWillRain = daily.precipitation_sum[0] > 0.1;
    const today = {
      tempMin: daily.temperature_2m_min[0],
      tempMax: daily.temperature_2m_max[0],
      rainMm: daily.precipitation_sum[0],
      avgHumidity: current.relative_humidity_2m,
      willRain: todayWillRain,
    };

    // Sunrise/sunset
    const sunrise = daily.sunrise?.[0]
      ? new Date(daily.sunrise[0]).toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: false, timeZone: "Africa/Nairobi" })
      : "06:30";
    const sunset = daily.sunset?.[0]
      ? new Date(daily.sunset[0]).toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: false, timeZone: "Africa/Nairobi" })
      : "18:45";

    // Forecast
    const forecast = daily.time.map((date: string, i: number) => {
      const dayCode = daily.weather_code[i];
      const dayInfo = WEATHER_CODES[dayCode] || { desc: "Unknown", condition: "cloudy", icon: "cloud" as const };
      return {
        date,
        day: getDayName(date, i),
        tempMin: daily.temperature_2m_min[i],
        tempMax: daily.temperature_2m_max[i],
        maxTemp: daily.temperature_2m_max[i],
        minTemp: daily.temperature_2m_min[i],
        rain: daily.precipitation_sum[i],
        precipitation: daily.precipitation_sum[i],
        weatherCode: dayCode,
        condition: dayInfo.condition,
        desc: dayInfo.desc,
        icon: dayInfo.icon,
      };
    });

    // Farm tips
    const tips: string[] = [];
    if (current.temperature_2m > 30) tips.push("⚠️ High temperature — ensure adequate water supply and ventilation for your flocks.");
    if (current.temperature_2m < 15) tips.push("❄️ Cool weather — check brooding temperature for chicks and increase feed slightly.");
    if (current.precipitation > 5) tips.push("🌧️ Heavy rain expected — secure feed stores and check drainage around coops.");
    if (current.relative_humidity_2m > 80) tips.push("💧 High humidity — increase ventilation to prevent respiratory issues.");
    if (daily.precipitation_sum.some((p: number) => p > 10)) tips.push("📅 Heavy rain in forecast — plan indoor activities and check roof integrity.");

    return NextResponse.json({
      // Current conditions
      temperature: current.temperature_2m,
      feelsLike: current.apparent_temperature,
      humidity: current.relative_humidity_2m,
      windSpeed: current.wind_speed_10m,
      precipitation: current.precipitation,
      condition: weatherInfo.condition,
      description: weatherInfo.desc,
      icon: weatherInfo.icon,
      code,

      // Today
      today,

      // Sunrise/sunset
      sunrise,
      sunset,

      // Forecast
      forecast,

      // Tips & location
      tips,
      location: `Lat ${lat}, Lon ${lon}`,
    });
  } catch (error) {
    console.error("Weather API error:", error);
    return NextResponse.json({ error: "Failed to fetch weather" }, { status: 500 });
  }
}
