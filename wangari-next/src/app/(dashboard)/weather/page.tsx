"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Sun, Cloud, CloudRain, Droplets, Wind, Thermometer, AlertTriangle, RefreshCw, MapPin, Calendar, Clock, Leaf, Umbrella } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

const WeatherIcon = ({ condition, className }: { condition: string; className?: string }) => {
  const c = (condition || "").toLowerCase();
  if (c.includes("clear") || c.includes("sun")) return <Sun className={className || "h-6 w-6"} />;
  if (c.includes("rain") || c.includes("drizzle") || c.includes("thunder")) return <CloudRain className={className || "h-6 w-6"} />;
  return <Cloud className={className || "h-6 w-6"} />;
};

export default function WeatherPage() {
  const [weather, setWeather] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState("");

  const fetchWeather = async () => {
    setLoading(true); setError("");
    try { setWeather(await api.get("/api/weather")); }
    catch { setError("Unable to load weather data."); }
    finally { setLoading(false); }
  };
  React.useEffect(() => { fetchWeather(); }, []);

  if (loading) return <div className="flex items-center justify-center min-h-[60vh]"><div className="text-center"><RefreshCw className="h-8 w-8 text-[#166534] animate-spin mx-auto mb-3" /><p className="text-sm text-[#64748B]">Loading weather...</p></div></div>;
  if (error || !weather) return <div className="flex items-center justify-center min-h-[60vh]"><div className="text-center"><AlertTriangle className="h-8 w-8 text-amber-500 mx-auto mb-3" /><p className="text-sm text-[#64748B]">{error || "No data"}</p><button onClick={fetchWeather} className="mt-3 px-4 py-2 bg-[#166534] text-white rounded-xl text-sm font-bold cursor-pointer">Retry</button></div></div>;

  const { today, forecast, location, temperature, feelsLike, humidity, windSpeed, condition, description, sunrise, sunset } = weather;

  // Farm-specific alerts
  const alerts: { type: "danger" | "warning" | "info"; text: string }[] = [];
  if (today?.willRain) alerts.push({ type: "warning", text: "Rain expected today. Avoid spraying pesticides or herbicides." });
  if (today?.rainMm > 10) alerts.push({ type: "danger", text: `Heavy rainfall (${today.rainMm}mm). Check drainage and protect seedlings.` });
  if (today?.tempMax > 35) alerts.push({ type: "danger", text: "Very hot. Ensure extra water for livestock and shade for poultry." });
  if (today?.tempMin < 10) alerts.push({ type: "warning", text: "Cool temperatures. Protect young stock from cold stress." });
  if (humidity > 85) alerts.push({ type: "info", text: "High humidity. Watch for fungal diseases on crops." });
  if (windSpeed > 30) alerts.push({ type: "warning", text: "Strong winds. Secure lightweight structures and check poultry housing." });

  // Spray recommendation
  const willRainSoon = forecast?.slice(0, 2).some((d: any) => d.rain > 0 || (d.condition || "").toLowerCase().includes("rain"));
  const sprayOk = !today?.willRain && !willRainSoon;

  return (
    <motion.div initial="hidden" animate="visible" variants={stagger} className="space-y-6">
      {/* Header */}
      <motion.div variants={fadeUp} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Weather</h1>
          <div className="flex items-center gap-1.5 mt-1 text-sm text-[#64748B]"><MapPin className="h-3.5 w-3.5" />{location}</div>
        </div>
        <button onClick={fetchWeather} className="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-[#166534] border border-[#E5E7EB] rounded-xl hover:bg-[#F0FDF4] cursor-pointer"><RefreshCw className="h-3.5 w-3.5" />Refresh</button>
      </motion.div>

      {/* Current weather hero */}
      <motion.div variants={fadeUp} className="rounded-2xl bg-gradient-to-br from-[#0B1220] via-[#14532D] to-[#166534] p-6 text-white">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-white/50 text-xs font-semibold uppercase tracking-wider">Now</p>
            <div className="flex items-end gap-2 mt-1">
              <span className="text-5xl font-extrabold">{Math.round(temperature)}°</span>
              <span className="text-white/60 text-sm mb-2">{description}</span>
            </div>
            <p className="text-sm text-white/50 mt-1">Feels like {Math.round(feelsLike)}°</p>
          </div>
          <WeatherIcon condition={condition} className="h-16 w-16 text-white/80" />
        </div>
        <div className="grid grid-cols-3 gap-3 mt-5">
          {[
            { icon: Droplets, label: "Humidity", value: `${humidity}%` },
            { icon: Wind, label: "Wind", value: `${windSpeed} km/h` },
            { icon: Thermometer, label: "High/Low", value: `${today?.tempMax || "--"}°/${today?.tempMin || "--"}°` },
          ].map(item => (
            <div key={item.label} className="bg-white/10 rounded-xl p-2.5 text-center">
              <item.icon className="h-4 w-4 text-white/50 mx-auto mb-1" />
              <p className="text-sm font-bold">{item.value}</p>
              <p className="text-[9px] text-white/40">{item.label}</p>
            </div>
          ))}
        </div>
      </motion.div>

      {/* Farm alerts */}
      {alerts.length > 0 && (
        <motion.div variants={fadeUp} className="space-y-2">
          {alerts.map((alert, i) => (
            <Card key={i} className={`border ${alert.type === "danger" ? "border-red-300 bg-red-50" : alert.type === "warning" ? "border-amber-300 bg-amber-50" : "border-blue-300 bg-blue-50"}`}>
              <CardContent className="flex items-start gap-3 p-3">
                <AlertTriangle className={`h-4 w-4 mt-0.5 flex-shrink-0 ${alert.type === "danger" ? "text-red-600" : alert.type === "warning" ? "text-amber-600" : "text-blue-600"}`} />
                <p className={`text-xs font-medium ${alert.type === "danger" ? "text-red-800" : alert.type === "warning" ? "text-amber-800" : "text-blue-800"}`}>{alert.text}</p>
              </CardContent>
            </Card>
          ))}
        </motion.div>
      )}

      {/* Spray recommendation */}
      <motion.div variants={fadeUp}>
        <Card className={`border ${sprayOk ? "border-emerald-200 bg-emerald-50" : "border-amber-200 bg-amber-50"}`}>
          <CardContent className="flex items-center gap-3 p-4">
            <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${sprayOk ? "bg-emerald-100 text-emerald-700" : "bg-amber-100 text-amber-700"}`}>
              {sprayOk ? <Leaf className="h-5 w-5" /> : <Umbrella className="h-5 w-5" />}
            </div>
            <div>
              <p className={`text-sm font-bold ${sprayOk ? "text-emerald-800" : "text-amber-800"}`}>{sprayOk ? "Good day for spraying" : "Not ideal for spraying"}</p>
              <p className={`text-xs ${sprayOk ? "text-emerald-600" : "text-amber-600"}`}>{sprayOk ? "No rain expected. Safe to apply pesticides or herbicides." : "Rain expected soon. Wait for dry conditions."}</p>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Sunrise/Sunset */}
      <motion.div variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="flex items-center justify-around p-4">
            <div className="text-center">
              <Sun className="h-5 w-5 text-amber-500 mx-auto mb-1" />
              <p className="text-xs text-[#94A3B8]">Sunrise</p>
              <p className="text-sm font-bold text-[#0F172A]">{sunrise || "06:30"}</p>
            </div>
            <div className="h-8 w-px bg-[#E5E7EB]" />
            <div className="text-center">
              <Sun className="h-5 w-5 text-orange-500 mx-auto mb-1" />
              <p className="text-xs text-[#94A3B8]">Sunset</p>
              <p className="text-sm font-bold text-[#0F172A]">{sunset || "18:45"}</p>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Forecast — scrollable on mobile */}
      <motion.div variants={fadeUp}>
        <Card className="border border-[#E5E7EB]">
          <CardContent className="p-4">
            <div className="flex items-center gap-2 mb-3">
              <Calendar className="h-4 w-4 text-[#166534]" />
              <p className="text-xs font-bold text-[#0F172A]">Forecast</p>
            </div>
            <div className="flex gap-2 overflow-x-auto pb-2 -mx-1 px-1">
              {forecast?.map((day: any, i: number) => {
                const d = new Date(day.date || day.day);
                const dayName = i === 0 ? "Today" : d.toLocaleDateString("en-US", { weekday: "short" });
                return (
                  <div key={i} className={`flex-shrink-0 w-20 text-center p-3 rounded-xl ${i === 0 ? "bg-[#F0FDF4] border border-[#BBF7D0]" : "bg-[#F8FAFC]"}`}>
                    <p className="text-[10px] font-semibold text-[#64748B]">{dayName}</p>
                    <WeatherIcon condition={day.condition} className="h-5 w-5 text-[#64748B] mx-auto my-2" />
                    <p className="text-sm font-bold text-[#0F172A]">{day.tempMax || day.maxTemp}°</p>
                    <p className="text-[10px] text-[#94A3B8]">{day.tempMin || day.minTemp}°</p>
                    {(day.rain > 0) && <p className="text-[9px] text-blue-500 font-bold mt-1">{day.rain}mm</p>}
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Temperature trend */}
      {forecast && forecast.length > 0 && (
        <motion.div variants={fadeUp}>
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-3">
                <Thermometer className="h-4 w-4 text-[#166534]" />
                <p className="text-xs font-bold text-[#0F172A]">Temperature Range</p>
              </div>
              <div className="flex items-end gap-2 h-32">
                {forecast.map((day: any, i: number) => {
                  const maxT = day.tempMax || day.maxTemp || 30;
                  const minT = day.tempMin || day.minTemp || 15;
                  const range = maxT - minT;
                  const maxH = (maxT / 40) * 100;
                  const minH = (minT / 40) * 100;
                  const d = new Date(day.date || day.day);
                  return (
                    <div key={i} className="flex-1 flex flex-col items-center gap-1">
                      <p className="text-[9px] font-bold text-[#0F172A]">{maxT}°</p>
                      <div className="w-full relative" style={{ height: "60px" }}>
                        <div className="absolute bottom-0 w-full rounded-t-md bg-[#166534]/20" style={{ height: `${maxH}%` }} />
                        <div className="absolute bottom-0 w-full rounded-t-md bg-[#166534]" style={{ height: `${minH}%` }} />
                      </div>
                      <p className="text-[9px] text-[#94A3B8]">{minT}°</p>
                      <p className="text-[8px] text-[#94A3B8]">{i === 0 ? "Now" : d.toLocaleDateString("en-US", { weekday: "short" })}</p>
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        </motion.div>
      )}
    </motion.div>
  );
}
