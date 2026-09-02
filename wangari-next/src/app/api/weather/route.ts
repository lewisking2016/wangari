import { NextResponse } from "next/server";

const NAKURU_LAT = -0.3031;
const NAKURU_LNG = 36.0800;

export async function GET() {
  try {
    // Current weather
    const currentRes = await fetch(
      `https://api.open-meteo.com/v1/forecast?latitude=${NAKURU_LAT}&longitude=${NAKURU_LNG}&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,precipitation&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code&timezone=Africa/Nairobi&forecast_days=7`
    );
    const data = await currentRes.json();

    const weatherCodes: Record<number, { desc: string; icon: string }> = {
      0: { desc: "Clear sky", icon: "☀️" },
      1: { desc: "Mainly clear", icon: "🌤️" },
      2: { desc: "Partly cloudy", icon: "⛅" },
      3: { desc: "Overcast", icon: "☁️" },
      45: { desc: "Fog", icon: "🌫️" },
      51: { desc: "Light drizzle", icon: "🌦️" },
      53: { desc: "Moderate drizzle", icon: "🌦️" },
      61: { desc: "Slight rain", icon: "🌧️" },
      63: { desc: "Moderate rain", icon: "🌧️" },
      65: { desc: "Heavy rain", icon: "⛈️" },
      80: { desc: "Rain showers", icon: "🌦️" },
      95: { desc: "Thunderstorm", icon: "⛈️" },
    };

    const current = data.current;
    const code = current.weather_code;
    const weatherInfo = weatherCodes[code] || { desc: "Unknown", icon: "🌡️" };

    const forecast = data.daily.time.map((date: string, i: number) => ({
      date,
      maxTemp: data.daily.temperature_2m_max[i],
      minTemp: data.daily.temperature_2m_min[i],
      precipitation: data.daily.precipitation_sum[i],
      weatherCode: data.daily.weather_code[i],
      desc: weatherCodes[data.daily.weather_code[i]]?.desc || "Unknown",
      icon: weatherCodes[data.daily.weather_code[i]]?.icon || "🌡️",
    }));

    // Farm tips based on weather
    const tips: string[] = [];
    if (current.temperature_2m > 30) tips.push("⚠️ High temperature — ensure adequate water supply and ventilation for your flocks.");
    if (current.temperature_2m < 15) tips.push("❄️ Cool weather — check brooding temperature for chicks and increase feed slightly.");
    if (current.precipitation > 5) tips.push("🌧️ Heavy rain expected — secure feed stores and check drainage around coops.");
    if (current.relative_humidity_2m > 80) tips.push("💧 High humidity — increase ventilation to prevent respiratory issues.");
    if (data.daily.precipitation_sum.some((p: number) => p > 10)) tips.push("📅 Heavy rain in forecast — plan indoor activities and check roof integrity.");

    return NextResponse.json({
      current: {
        temperature: current.temperature_2m,
        feelsLike: current.apparent_temperature,
        humidity: current.relative_humidity_2m,
        windSpeed: current.wind_speed_10m,
        precipitation: current.precipitation,
        description: weatherInfo.desc,
        icon: weatherInfo.icon,
        code,
      },
      forecast,
      tips,
      location: "Nakuru, Kenya",
    });
  } catch (error) {
    console.error("Weather API error:", error);
    return NextResponse.json({ error: "Failed to fetch weather" }, { status: 500 });
  }
}
