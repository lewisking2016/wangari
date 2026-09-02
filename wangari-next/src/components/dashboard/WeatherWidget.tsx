"use client";

import { motion } from "framer-motion";
import { Cloud, Sun, Droplets, Wind, MapPin, Sunrise, Sunset, CloudRain } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

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

const WeatherIcon = ({ icon, className }: { icon: string; className?: string }) => {
  switch (icon) {
    case "sun":
      return <Sun className={className} />;
    case "rain":
      return <CloudRain className={className} />;
    default:
      return <Cloud className={className} />;
  }
};

export function WeatherWidget({ data, location = "Farm Location" }: WeatherWidgetProps) {
  if (!data) {
    return (
      <Card>
        <CardContent className="flex items-center justify-center py-10">
          <p className="text-sm text-wangari-muted">Weather data unavailable</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4, delay: 0.1 }}
    >
      <Card>
        <CardHeader className="pb-2">
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="text-base font-bold text-wangari-heading">
                Weather
              </CardTitle>
              <div className="flex items-center gap-1 text-xs text-wangari-muted">
                <MapPin className="h-3 w-3" />
                {data.location || location}
              </div>
            </div>
            <WeatherIcon icon={data.icon} className="h-8 w-8 text-wangari-green-600" />
          </div>
        </CardHeader>
        <CardContent>
          {/* Current weather */}
          <div className="flex items-center gap-6">
            <div>
              <p className="text-4xl font-bold text-wangari-heading font-serif">
                {data.temperature}°
              </p>
              <p className="text-xs text-wangari-muted">{data.condition}</p>
            </div>
            <div className="flex-1 space-y-1.5">
              <div className="flex items-center gap-2 text-wangari-text">
                <Droplets className="h-3.5 w-3.5 text-wangari-green-600" />
                <span className="text-xs">Humidity {data.humidity}%</span>
              </div>
              <div className="flex items-center gap-2 text-wangari-text">
                <Wind className="h-3.5 w-3.5 text-wangari-green-600" />
                <span className="text-xs">Wind {data.windSpeed} km/h</span>
              </div>
              {data.today?.willRain && (
                <div className="flex items-center gap-2 text-wangari-text">
                  <CloudRain className="h-3.5 w-3.5 text-blue-500" />
                  <span className="text-xs">Rain {data.today.rainMm}mm</span>
                </div>
              )}
            </div>
          </div>

          {/* Sunrise/sunset */}
          {(data.sunrise || data.sunset) && (
            <div className="mt-4 flex items-center gap-4 border-t border-wangari-border pt-3">
              {data.sunrise && (
                <div className="flex items-center gap-1.5">
                  <Sunrise className="h-4 w-4 text-amber-500" />
                  <span className="text-xs text-wangari-muted">{data.sunrise}</span>
                </div>
              )}
              {data.sunset && (
                <div className="flex items-center gap-1.5">
                  <Sunset className="h-4 w-4 text-orange-500" />
                  <span className="text-xs text-wangari-muted">{data.sunset}</span>
                </div>
              )}
              <span className="text-[11px] text-wangari-subtle">Light schedule</span>
            </div>
          )}

          {/* 5-day forecast */}
          {data.forecast && data.forecast.length > 0 && (
            <div className="mt-4 border-t border-wangari-border pt-3">
              <p className="text-[11px] font-semibold text-wangari-muted mb-2">5-Day Forecast</p>
              <div className="flex justify-between">
                {data.forecast.slice(0, 5).map((day, i) => (
                  <div key={i} className="flex flex-col items-center gap-1">
                    <span className="text-[10px] text-wangari-muted">{day.day}</span>
                    <WeatherIcon icon={day.icon} className="h-4 w-4 text-wangari-green-600" />
                    <span className="text-[11px] font-medium text-wangari-heading">
                      {day.tempMax}°
                    </span>
                    <span className="text-[10px] text-wangari-subtle">{day.tempMin}°</span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </motion.div>
  );
}
