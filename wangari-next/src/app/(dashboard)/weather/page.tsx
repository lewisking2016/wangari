"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  Sun,
  Cloud,
  CloudRain,
  Droplets,
  Wind,
  Thermometer,
  Eye,
  Umbrella,
  AlertTriangle,
  RefreshCw,
  MapPin,
  Calendar,
} from "lucide-react";
import api from "@/lib/api-client";

interface WeatherData {
  current: {
    temperature: number;
    feelsLike: number;
    humidity: number;
    windSpeed: number;
    precipitation: number;
    description: string;
    icon: string;
    code: number;
  };
  forecast: Array<{
    date: string;
    maxTemp: number;
    minTemp: number;
    precipitation: number;
    weatherCode: number;
    desc: string;
    icon: string;
  }>;
  tips: string[];
  location: string;
}

const fadeUp = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5 } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08 } },
};

export default function WeatherPage() {
  const [weather, setWeather] = React.useState<WeatherData | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState("");

  const fetchWeather = async () => {
    setLoading(true);
    setError("");
    try {
      const data = await api.get("/api/weather");
      setWeather(data as WeatherData);
    } catch {
      setError("Unable to load weather data. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  React.useEffect(() => {
    fetchWeather();
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="text-center">
          <RefreshCw className="h-8 w-8 text-wangari-green-800 animate-spin mx-auto mb-3" />
          <p className="text-sm text-wangari-muted">Loading weather data...</p>
        </div>
      </div>
    );
  }

  if (error || !weather) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="text-center">
          <AlertTriangle className="h-8 w-8 text-amber-500 mx-auto mb-3" />
          <p className="text-sm text-wangari-muted">{error || "No data"}</p>
          <button onClick={fetchWeather} className="mt-3 px-4 py-2 bg-wangari-green-800 text-white rounded-lg text-sm font-medium hover:bg-wangari-green-900 transition-colors">
            Retry
          </button>
        </div>
      </div>
    );
  }

  const { current, forecast, tips, location } = weather;

  return (
    <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-6">
      {/* Header */}
      <motion.div variants={fadeUp} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-wangari-heading">Weather Dashboard</h1>
          <div className="flex items-center gap-2 mt-1 text-sm text-wangari-muted">
            <MapPin className="h-4 w-4" />
            {location}
          </div>
        </div>
        <button onClick={fetchWeather} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-wangari-green-800 border border-wangari-border rounded-xl hover:bg-wangari-green-50 transition-colors">
          <RefreshCw className="h-4 w-4" />
          Refresh
        </button>
      </motion.div>

      {/* Current Weather */}
      <motion.div variants={fadeUp} className="rounded-2xl bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] p-8 text-white">
        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="text-center md:text-left">
            <p className="text-white/60 text-sm font-medium mb-1">Current Weather</p>
            <div className="flex items-end gap-3">
              <span className="text-6xl font-extrabold">{Math.round(current.temperature)}°C</span>
              <span className="text-5xl mb-2">{current.icon}</span>
            </div>
            <p className="text-lg text-white/80 mt-1">{current.description}</p>
            <p className="text-sm text-white/50 mt-1">Feels like {Math.round(current.feelsLike)}°C</p>
          </div>

          <div className="grid grid-cols-2 gap-4">
            {[
              { icon: Droplets, label: "Humidity", value: `${current.humidity}%` },
              { icon: Wind, label: "Wind", value: `${current.windSpeed} km/h` },
              { icon: CloudRain, label: "Precipitation", value: `${current.precipitation} mm` },
              { icon: Eye, label: "Conditions", value: current.description },
            ].map((item) => (
              <div key={item.label} className="bg-white/10 backdrop-blur-sm rounded-xl p-3 text-center">
                <item.icon className="h-5 w-5 text-white/60 mx-auto mb-1" />
                <p className="text-lg font-bold">{item.value}</p>
                <p className="text-[11px] text-white/50">{item.label}</p>
              </div>
            ))}
          </div>
        </div>
      </motion.div>

      {/* 7-Day Forecast */}
      <motion.div variants={fadeUp} className="rounded-2xl border border-wangari-border bg-white p-6">
        <div className="flex items-center gap-2 mb-4">
          <Calendar className="h-5 w-5 text-wangari-green-800" />
          <h2 className="text-lg font-bold text-wangari-heading">7-Day Forecast</h2>
        </div>
        <div className="grid grid-cols-7 gap-2">
          {forecast.map((day, i) => {
            const date = new Date(day.date);
            const dayName = i === 0 ? "Today" : date.toLocaleDateString("en-US", { weekday: "short" });
            return (
              <motion.div
                key={day.date}
                variants={fadeUp}
                whileHover={{ y: -4 }}
                className={`text-center p-3 rounded-xl transition-colors ${i === 0 ? "bg-wangari-green-50 border border-wangari-green-200" : "hover:bg-gray-50"}`}
              >
                <p className="text-xs font-semibold text-wangari-muted">{dayName}</p>
                <p className="text-2xl my-2">{day.icon}</p>
                <p className="text-sm font-bold text-wangari-heading">{Math.round(day.maxTemp)}°</p>
                <p className="text-xs text-wangari-muted">{Math.round(day.minTemp)}°</p>
                {day.precipitation > 0 && (
                  <div className="flex items-center justify-center gap-0.5 mt-1">
                    <Droplets className="h-3 w-3 text-blue-500" />
                    <span className="text-[10px] text-blue-500">{day.precipitation}mm</span>
                  </div>
                )}
              </motion.div>
            );
          })}
        </div>
      </motion.div>

      {/* Farm Tips */}
      {tips.length > 0 && (
        <motion.div variants={fadeUp} className="rounded-2xl border border-amber-200 bg-amber-50 p-6">
          <div className="flex items-center gap-2 mb-4">
            <AlertTriangle className="h-5 w-5 text-amber-600" />
            <h2 className="text-lg font-bold text-amber-800">Farm Weather Tips</h2>
          </div>
          <div className="space-y-3">
            {tips.map((tip, i) => (
              <motion.div key={i} variants={fadeUp} className="flex items-start gap-3 text-sm text-amber-800">
                <span className="shrink-0 mt-0.5">•</span>
                <span>{tip}</span>
              </motion.div>
            ))}
          </div>
        </motion.div>
      )}

      {/* Temperature Trend */}
      <motion.div variants={fadeUp} className="rounded-2xl border border-wangari-border bg-white p-6">
        <h2 className="text-lg font-bold text-wangari-heading mb-4">Temperature Trend</h2>
        <div className="flex items-end gap-1 h-40">
          {forecast.map((day, i) => {
            const maxH = (day.maxTemp / 40) * 100;
            const minH = (day.minTemp / 40) * 100;
            const date = new Date(day.date);
            return (
              <div key={day.date} className="flex-1 flex flex-col items-center gap-1">
                <div className="w-full relative" style={{ height: "120px" }}>
                  <div
                    className="absolute bottom-0 w-full rounded-t-md bg-gradient-to-t from-wangari-green-800 to-wangari-green-600 opacity-80"
                    style={{ height: `${maxH}%` }}
                  />
                  <div
                    className="absolute bottom-0 w-full rounded-t-md bg-wangari-green-300"
                    style={{ height: `${minH}%` }}
                  />
                </div>
                <span className="text-[10px] font-medium text-wangari-muted">
                  {i === 0 ? "Now" : date.toLocaleDateString("en-US", { weekday: "short" })}
                </span>
              </div>
            );
          })}
        </div>
      </motion.div>
    </motion.div>
  );
}
