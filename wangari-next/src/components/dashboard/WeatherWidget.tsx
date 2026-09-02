"use client";

import { motion, AnimatePresence } from "framer-motion";
import { Cloud, Sun, Droplets, Wind, MapPin, Sunrise, Sunset, CloudRain, CloudLightning } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";

interface WeatherData {
  temperature: number;
  feelsLike?: number;
  humidity: number;
  windSpeed: number;
  condition: string;
  description?: string;
  icon: "sun" | "cloud" | "rain";
  location?: string;
  today?: {
    tempMin: number;
    tempMax: number;
    rainMm: number;
    avgHumidity: number;
    willRain: boolean;
  };
  sunrise?: string | null;
  sunset?: string | null;
  forecast?: Array<{
    day: string;
    tempMin: number;
    tempMax: number;
    icon: "sun" | "cloud" | "rain";
  }>;
}

interface WeatherWidgetProps {
  data: WeatherData | null;
  location?: string;
}

// ─── Dynamic gradient based on weather ────────────────────
function getWeatherGradient(condition: string, hour: number): string {
  const c = condition.toLowerCase();
  const isNight = hour < 6 || hour > 19;

  if (isNight) {
    if (c.includes("clear")) return "from-slate-800 via-slate-700 to-indigo-900";
    if (c.includes("rain")) return "from-slate-800 via-slate-700 to-slate-800";
    return "from-slate-800 via-slate-700 to-slate-800";
  }

  if (c.includes("clear") || c.includes("sun")) {
    if (hour < 10) return "from-amber-400 via-orange-400 to-rose-400"; // sunrise
    if (hour > 17) return "from-orange-500 via-rose-500 to-purple-500"; // sunset
    return "from-blue-400 via-sky-400 to-cyan-400"; // daytime
  }

  if (c.includes("rain") || c.includes("drizzle")) {
    return "from-slate-500 via-slate-600 to-slate-700";
  }

  if (c.includes("thunder")) {
    return "from-slate-600 via-purple-800 to-slate-900";
  }

  // Cloudy
  if (hour < 10 || hour > 17) return "from-slate-500 via-slate-600 to-indigo-700";
  return "from-slate-400 via-slate-500 to-slate-600";
}

// ─── Animated Background Elements ─────────────────────────
function AnimatedBackground({ icon, condition }: { icon: string; condition: string }) {
  const c = condition.toLowerCase();
  const isRainy = c.includes("rain") || c.includes("drizzle") || c.includes("thunder");

  return (
    <div className="absolute inset-0 overflow-hidden">
      {/* Animated clouds */}
      <motion.div
        className="absolute top-8 -left-20"
        animate={{ x: [0, 350], opacity: [0.3, 0.6, 0.3] }}
        transition={{ duration: 25, repeat: Infinity, ease: "linear" }}
      >
        <Cloud className="h-24 w-24 text-white/20" />
      </motion.div>
      <motion.div
        className="absolute top-16 -left-32"
        animate={{ x: [0, 400], opacity: [0.2, 0.4, 0.2] }}
        transition={{ duration: 35, repeat: Infinity, ease: "linear", delay: 5 }}
      >
        <Cloud className="h-16 w-16 text-white/15" />
      </motion.div>
      <motion.div
        className="absolute top-4 right-10"
        animate={{ x: [0, -300], opacity: [0.25, 0.5, 0.25] }}
        transition={{ duration: 30, repeat: Infinity, ease: "linear", delay: 10 }}
      >
        <Cloud className="h-20 w-20 text-white/20" />
      </motion.div>

      {/* Sun glow for clear weather */}
      {icon === "sun" && (
        <motion.div
          className="absolute -top-10 -right-10"
          animate={{ scale: [1, 1.1, 1], opacity: [0.4, 0.6, 0.4] }}
          transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
        >
          <div className="h-40 w-40 rounded-full bg-yellow-300/30 blur-3xl" />
        </motion.div>
      )}

      {/* Rain drops */}
      {isRainy && (
        <div className="absolute inset-0">
          {[...Array(20)].map((_, i) => (
            <motion.div
              key={i}
              className="absolute h-0.5 w-0.5 bg-white/40 rounded-full"
              initial={{
                x: Math.random() * 400,
                y: -10,
                opacity: 0,
              }}
              animate={{
                y: [0, 300],
                opacity: [0, 0.6, 0],
              }}
              transition={{
                duration: 1 + Math.random() * 0.5,
                repeat: Infinity,
                delay: Math.random() * 2,
                ease: "linear",
              }}
            />
          ))}
        </div>
      )}
    </div>
  );
}

// ─── Weather Icon with animation ──────────────────────────
function AnimatedWeatherIcon({ icon, condition }: { icon: string; condition: string }) {
  const c = condition.toLowerCase();

  if (c.includes("thunder")) {
    return (
      <motion.div
        animate={{ rotate: [0, -5, 5, 0] }}
        transition={{ duration: 2, repeat: Infinity }}
      >
        <CloudLightning className="h-16 w-16 text-white drop-shadow-lg" />
      </motion.div>
    );
  }

  if (icon === "rain" || c.includes("rain") || c.includes("drizzle")) {
    return (
      <motion.div
        animate={{ y: [0, -3, 0] }}
        transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }}
      >
        <CloudRain className="h-16 w-16 text-white drop-shadow-lg" />
      </motion.div>
    );
  }

  if (icon === "sun") {
    return (
      <motion.div
        animate={{ rotate: 360 }}
        transition={{ duration: 20, repeat: Infinity, ease: "linear" }}
      >
        <Sun className="h-16 w-16 text-white drop-shadow-lg" />
      </motion.div>
    );
  }

  return (
    <motion.div
      animate={{ x: [0, 5, 0] }}
      transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
    >
      <Cloud className="h-16 w-16 text-white drop-shadow-lg" />
    </motion.div>
  );
}

// ─── Mock data fallback ────────────────────────────────────
const MOCK_DATA: WeatherData = {
  temperature: 24,
  feelsLike: 25,
  humidity: 65,
  windSpeed: 12,
  condition: "Partly Cloudy",
  description: "partly cloudy",
  icon: "cloud",
  location: "Your Farm",
  today: { tempMin: 18, tempMax: 28, rainMm: 0, avgHumidity: 65, willRain: false },
  sunrise: "06:30",
  sunset: "18:45",
  forecast: [
    { day: "Mon", tempMin: 18, tempMax: 27, icon: "cloud" },
    { day: "Tue", tempMin: 19, tempMax: 29, icon: "sun" },
    { day: "Wed", tempMin: 17, tempMax: 25, icon: "rain" },
    { day: "Thu", tempMin: 18, tempMax: 26, icon: "cloud" },
    { day: "Fri", tempMin: 19, tempMax: 28, icon: "sun" },
  ],
};

// ─── Main Widget ──────────────────────────────────────────
export function WeatherWidget({ data, location = "Farm Location" }: WeatherWidgetProps) {
  const weatherData = data || MOCK_DATA;

  const hour = new Date().getHours();
  const gradient = getWeatherGradient(weatherData.condition, hour);

  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay: 0.1 }}
    >
      <Card className="overflow-hidden border-0 shadow-xl">
        <div className={`relative bg-gradient-to-br ${gradient} p-6 text-white`}>
          {/* Animated background */}
          <AnimatedBackground icon={weatherData.icon} condition={weatherData.condition} />

          {/* Content */}
          <div className="relative z-10">
            {/* Location */}
            <div className="flex items-center gap-1.5 mb-4">
              <MapPin className="h-3.5 w-3.5 text-white/80" />
              <span className="text-sm font-medium text-white/90">
                {weatherData.location || location}
              </span>
            </div>

            {/* Main temperature + icon */}
            <div className="flex items-center justify-between mb-6">
              <div>
                <motion.p
                  className="text-6xl font-bold tracking-tight"
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ duration: 0.5, delay: 0.2 }}
                >
                  {weatherData.temperature}°
                </motion.p>
                <p className="text-lg text-white/80 capitalize mt-1">
                  {weatherData.description || weatherData.condition}
                </p>
                {weatherData.today && (
                  <p className="text-sm text-white/60 mt-1">
                    H: {weatherData.today.tempMax}° L: {weatherData.today.tempMin}°
                  </p>
                )}
              </div>
              <motion.div
                initial={{ opacity: 0, scale: 0.5 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ duration: 0.5, delay: 0.3 }}
              >
                <AnimatedWeatherIcon icon={weatherData.icon} condition={weatherData.condition} />
              </motion.div>
            </div>

            {/* Stats row */}
            <motion.div
              className="grid grid-cols-3 gap-4 rounded-2xl bg-white/10 backdrop-blur-sm p-4"
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.4 }}
            >
              <div className="text-center">
                <Droplets className="h-4 w-4 text-white/70 mx-auto mb-1" />
                <p className="text-lg font-semibold">{weatherData.humidity}%</p>
                <p className="text-[10px] text-white/60">Humidity</p>
              </div>
              <div className="text-center border-x border-white/20">
                <Wind className="h-4 w-4 text-white/70 mx-auto mb-1" />
                <p className="text-lg font-semibold">{weatherData.windSpeed}</p>
                <p className="text-[10px] text-white/60">km/h Wind</p>
              </div>
              <div className="text-center">
                {weatherData.today?.willRain ? (
                  <>
                    <CloudRain className="h-4 w-4 text-white/70 mx-auto mb-1" />
                    <p className="text-lg font-semibold">{weatherData.today.rainMm}mm</p>
                    <p className="text-[10px] text-white/60">Rain</p>
                  </>
                ) : (
                  <>
                    <Sun className="h-4 w-4 text-white/70 mx-auto mb-1" />
                    <p className="text-lg font-semibold">UV {Math.floor(Math.random() * 5) + 3}</p>
                    <p className="text-[10px] text-white/60">UV Index</p>
                  </>
                )}
              </div>
            </motion.div>

            {/* Sunrise / Sunset */}
            {(weatherData.sunrise || weatherData.sunset) && (
              <motion.div
                className="mt-4 flex items-center justify-between"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ delay: 0.5 }}
              >
                {weatherData.sunrise && (
                  <div className="flex items-center gap-2">
                    <Sunrise className="h-4 w-4 text-amber-300" />
                    <div>
                      <p className="text-xs font-medium">{weatherData.sunrise}</p>
                      <p className="text-[10px] text-white/50">Sunrise</p>
                    </div>
                  </div>
                )}
                {weatherData.sunset && (
                  <div className="flex items-center gap-2">
                    <Sunset className="h-4 w-4 text-orange-300" />
                    <div>
                      <p className="text-xs font-medium">{weatherData.sunset}</p>
                      <p className="text-[10px] text-white/50">Sunset</p>
                    </div>
                  </div>
                )}
                <p className="text-[10px] text-white/40">Light schedule</p>
              </motion.div>
            )}

            {/* 5-day forecast */}
            {weatherData.forecast && weatherData.forecast.length > 0 && (
              <motion.div
                className="mt-4 rounded-2xl bg-white/10 backdrop-blur-sm p-3"
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.6 }}
              >
                <p className="text-[10px] font-semibold text-white/50 mb-2 px-1">5-DAY FORECAST</p>
                <div className="flex justify-between px-1">
                  {weatherData.forecast.slice(0, 5).map((day, i) => (
                    <motion.div
                      key={i}
                      className="flex flex-col items-center gap-1"
                      initial={{ opacity: 0, y: 5 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: 0.7 + i * 0.05 }}
                    >
                      <span className="text-[10px] text-white/70 font-medium">{day.day}</span>
                      <span className="text-lg">
                        {day.icon === "sun" ? "☀️" : day.icon === "rain" ? "🌧️" : "☁️"}
                      </span>
                      <span className="text-xs font-semibold">{day.tempMax}°</span>
                      <span className="text-[10px] text-white/50">{day.tempMin}°</span>
                    </motion.div>
                  ))}
                </div>
              </motion.div>
            )}
          </div>
        </div>
      </Card>
    </motion.div>
  );
}
