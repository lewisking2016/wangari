"use client";

import { useState, useEffect, useCallback } from "react";
import type { Farm } from "@/types";
import api from "@/lib/api-client";

export function useFarm() {
  const [farm, setFarm] = useState<Farm | null>(null);
  const [farms, setFarms] = useState<Farm[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchFarms = useCallback(async () => {
    try {
      const data = await api.get<Farm[]>('/api/farms');
      setFarms(data);
      if (data.length > 0 && !farm) {
        setFarm(data[0]);
      }
    } catch {
      console.error("Failed to fetch farms");
    } finally {
      setLoading(false);
    }
  }, [farm]);

  useEffect(() => {
    fetchFarms();
  }, [fetchFarms]);

  const selectFarm = (farmId: number) => {
    const selected = farms.find((f) => f.id === farmId);
    if (selected) setFarm(selected);
  };

  return { farm, farms, loading, selectFarm };
}
