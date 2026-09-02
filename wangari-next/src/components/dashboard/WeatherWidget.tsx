"use client";

import { motion } from "framer-motion";
import { Cloud, Sun, Droplets, Wind, MapPin } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface WeatherData {
  temperature: number;
  humidity: number;
  windSpeed: number;
  condition: string;
  icon: "sun" | "cloud" | "rain";
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
      return <Droplets className={className} />;
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
                {location}
              </div>
            </div>
            <WeatherIcon icon={data.icon} className="h-8 w-8 text-wangari-green-600" />
          </div>
        </CardHeader>
        <CardContent>
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
            </div>
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
}
