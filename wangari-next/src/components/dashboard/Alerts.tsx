"use client";

import { motion } from "framer-motion";
import { AlertTriangle, Package, Syringe, TrendingDown } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

// ─── Mortality Alert ──────────────────────────────────────
interface MortalityAlertProps {
  flockName: string;
  mortalityRate: number;
  totalMortality: number;
}

export function MortalityAlert({ flockName, mortalityRate, totalMortality }: MortalityAlertProps) {
  const isHigh = mortalityRate > 5;

  return (
    <motion.div
      initial={{ opacity: 0, x: -20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ duration: 0.3 }}
    >
      <div
        className={`flex items-center gap-4 rounded-xl border p-4 ${
          isHigh
            ? "border-red-200 bg-red-50"
            : "border-amber-200 bg-amber-50"
        }`}
      >
        <div
          className={`rounded-lg p-2 ${
            isHigh ? "bg-red-100" : "bg-amber-100"
          }`}
        >
          <TrendingDown
            className={`h-5 w-5 ${isHigh ? "text-red-600" : "text-amber-600"}`}
          />
        </div>
        <div className="flex-1">
          <p className="font-medium text-gray-900">{flockName}</p>
          <p className="text-sm text-gray-600">
            {totalMortality} birds lost ({mortalityRate}% mortality)
          </p>
        </div>
        <Badge variant={isHigh ? "danger" : "warning"}>
          {isHigh ? "Critical" : "Warning"}
        </Badge>
      </div>
    </motion.div>
  );
}

// ─── Low Stock Alert ──────────────────────────────────────
interface LowStockAlertProps {
  itemName: string;
  currentStock: number;
  unit: string;
  reorderLevel: number;
}

export function LowStockAlert({ itemName, currentStock, unit, reorderLevel }: LowStockAlertProps) {
  const percentage = (currentStock / reorderLevel) * 100;

  return (
    <motion.div
      initial={{ opacity: 0, x: -20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ duration: 0.3 }}
    >
      <div className="flex items-center gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <div className="rounded-lg bg-amber-100 p-2">
          <Package className="h-5 w-5 text-amber-600" />
        </div>
        <div className="flex-1">
          <p className="font-medium text-gray-900">{itemName}</p>
          <p className="text-sm text-gray-600">
            {currentStock} {unit} remaining (reorder at {reorderLevel})
          </p>
          <div className="mt-2 h-2 overflow-hidden rounded-full bg-amber-200">
            <div
              className="h-full bg-amber-500"
              style={{ width: `${Math.min(percentage, 100)}%` }}
            />
          </div>
        </div>
        <Badge variant="warning">Low Stock</Badge>
      </div>
    </motion.div>
  );
}

// ─── Upcoming Vaccination ─────────────────────────────────
interface VaccinationAlertProps {
  flockName: string;
  vaccineName: string;
  dueDate: string;
}

export function VaccinationAlert({ flockName, vaccineName, dueDate }: VaccinationAlertProps) {
  const daysUntil = Math.ceil(
    (new Date(dueDate).getTime() - Date.now()) / (1000 * 60 * 60 * 24)
  );
  const isUrgent = daysUntil <= 3;

  return (
    <motion.div
      initial={{ opacity: 0, x: -20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ duration: 0.3 }}
    >
      <div
        className={`flex items-center gap-4 rounded-xl border p-4 ${
          isUrgent
            ? "border-red-200 bg-red-50"
            : "border-blue-200 bg-blue-50"
        }`}
      >
        <div
          className={`rounded-lg p-2 ${
            isUrgent ? "bg-red-100" : "bg-blue-100"
          }`}
        >
          <Syringe
            className={`h-5 w-5 ${isUrgent ? "text-red-600" : "text-blue-600"}`}
          />
        </div>
        <div className="flex-1">
          <p className="font-medium text-gray-900">{flockName}</p>
          <p className="text-sm text-gray-600">
            {vaccineName} due in {daysUntil} day{daysUntil !== 1 ? "s" : ""}
          </p>
        </div>
        <Badge variant={isUrgent ? "danger" : "info"}>
          {daysUntil <= 0 ? "Overdue" : `${daysUntil}d`}
        </Badge>
      </div>
    </motion.div>
  );
}

// ─── Alerts Card ──────────────────────────────────────────
interface AlertsCardProps {
  mortalityAlerts: MortalityAlertProps[];
  stockAlerts: LowStockAlertProps[];
  vaccinationAlerts: VaccinationAlertProps[];
}

export function AlertsCard({ mortalityAlerts, stockAlerts, vaccinationAlerts }: AlertsCardProps) {
  const totalAlerts = mortalityAlerts.length + stockAlerts.length + vaccinationAlerts.length;

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay: 0.6 }}
    >
      <Card className="border-0 shadow-lg">
        <CardHeader className="pb-2">
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="text-lg font-semibold">Alerts & Notifications</CardTitle>
              <p className="text-sm text-gray-500">
                {totalAlerts === 0 ? "No alerts" : `${totalAlerts} alert${totalAlerts !== 1 ? "s" : ""} requiring attention`}
              </p>
            </div>
            {totalAlerts > 0 && (
              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                <span className="text-sm font-bold text-red-600">{totalAlerts}</span>
              </div>
            )}
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {mortalityAlerts.map((alert, i) => (
              <MortalityAlert key={`mortality-${i}`} {...alert} />
            ))}
            {stockAlerts.map((alert, i) => (
              <LowStockAlert key={`stock-${i}`} {...alert} />
            ))}
            {vaccinationAlerts.map((alert, i) => (
              <VaccinationAlert key={`vaccination-${i}`} {...alert} />
            ))}
            {totalAlerts === 0 && (
              <div className="py-8 text-center">
                <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                  <AlertTriangle className="h-6 w-6 text-emerald-600" />
                </div>
                <p className="text-sm text-gray-500">All systems running smoothly</p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
}
