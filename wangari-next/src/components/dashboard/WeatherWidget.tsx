"use client";

import { motion } from "framer-motion";
import { Cloud, Sun, Droplets, Wind, Thermometer } from "lucide-react";
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
      <Card className="border-0 shadow-lg">
        <CardContent className="flex items-center justify-center py-8">
          <p className="text-gray-500">Weather data unavailable</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay: 0.2 }}
    >
      <Card className="relative overflow-hidden border-0 shadow-lg">
        {/* Gradient background */}
        <div className="absolute inset-0 bg-gradient-to-br from-blue-500 to-blue-600" />
        <div className="absolute inset-0 opacity-10">
          <div className="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white" />
          <div className="absolute -bottom-4 -left-4 h-24 w-24 rounded-full bg-white" />
        </div>

        <div className="relative">
          <CardHeader className="pb-0">
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="text-lg font-semibold text-white">
                  Weather
                </CardTitle>
                <p className="text-sm text-blue-100">{location}</p>
              </div>
              <WeatherIcon icon={data.icon} className="h-10 w-10 text-white" />
            </div>
          </CardHeader>
          <CardContent className="pt-4">
            <div className="flex items-center gap-6">
              <div>
                <p className="text-5xl font-bold text-white">{data.temperature}°</p>
                <p className="text-sm text-blue-100">{data.condition}</p>
              </div>
              <div className="flex-1 space-y-2">
                <div className="flex items-center gap-2 text-white/80">
                  <Droplets className="h-4 w-4" />
                  <span className="text-sm">Humidity: {data.humidity}%</span>
                </div>
                <div className="flex items-center gap-2 text-white/80">
                  <Wind className="h-4 w-4" />
                  <span className="text-sm">Wind: {data.windSpeed} km/h</span>
                </div>
              </div>
            </div>
          </CardContent>
        </div>
      </Card>
    </motion.div>
  );
}
