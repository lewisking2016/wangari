"use client";

import { useState, useEffect, useCallback } from "react";
import api from "@/lib/api-client";

interface FarmData {
  id: number;
  name: string;
  location?: string;
  county?: string;
  farmType?: string;
}

export function useFarm() {
  const [farm, setFarm] = useState<FarmData | null>(null);
  const [farms, setFarms] = useState<FarmData[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchFarms = useCallback(async () => {
    try {
      const data = await api.get<FarmData[]>("/api/farms");
      const list = Array.isArray(data) ? data : [];
      setFarms(list);
      // Restore selected farm from localStorage
      if (list.length > 0) {
        const savedId = typeof window !== "undefined" ? localStorage.getItem("selectedFarmId") : null;
        const saved = savedId ? list.find(f => f.id === Number(savedId)) : null;
        setFarm(saved || list[0]);
      }
    } catch {
      // silently fail
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchFarms(); }, [fetchFarms]);

  const selectFarm = async (farmId: number) => {
    const selected = farms.find(f => f.id === farmId);
    if (!selected) return;
    try {
      const res = await api.post<{ token: string }>("/api/auth/switch-farm", { farmId });
      // Update token in localStorage
      if (res?.token && typeof window !== "undefined") {
        localStorage.setItem("token", res.token);
        localStorage.setItem("selectedFarmId", String(farmId));
      }
      setFarm(selected);
      // Reload page to refresh all data for the new farm
      window.location.reload();
    } catch {
      // fallback — just switch locally without reload
      setFarm(selected);
      if (typeof window !== "undefined") localStorage.setItem("selectedFarmId", String(farmId));
    }
  };

  const createFarm = async (name: string, location?: string, farmType?: string) => {
    const newFarm = await api.post<FarmData>("/api/farms", { name, location, farmType });
    await fetchFarms();
    return newFarm;
  };

  return { farm, farms, loading, selectFarm, createFarm, refresh: fetchFarms };
}
