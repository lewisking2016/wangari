"use client";

import { Package } from "lucide-react";
import { FeaturePage } from "@/components/feature-page";

export default function InventoryFeaturePage() {
  return (
    <FeaturePage
      icon={Package}
      badge="Inventory Control"
      title="Never run out of feed or medication"
      subtitle="Track every bag of feed, every vaccine dose, and every supply item. Get low-stock alerts before you run out — so your birds never go hungry."
      description="Wangari's inventory module gives you complete control over your farm supplies. Know exactly what you have, what you've used, and what you need to order. Automated alerts ensure you never face a stockout that could impact your flock's health or production."
      highlights={[
        "Real-time stock levels for all items with automatic deduction",
        "Low-stock alerts sent to your phone before you run out",
        "Batch tracking for feed bags and medication with expiry dates",
        "Purchase history and supplier management",
        "Waste tracking and consumption rate analysis",
        "Automated reorder suggestions based on usage patterns",
        "Multi-location inventory for farms with multiple sites",
        "Barcode scanning for quick item entry",
      ]}
      capabilities={[
        { title: "Stock Dashboard", desc: "See all inventory items at a glance — current stock levels, values, and status. Color-coded indicators show what's in stock, running low, or out of stock." },
        { title: "Automated Alerts", desc: "Set minimum stock levels for each item. Get push notifications and SMS alerts when stock drops below threshold. Never run out of critical supplies again." },
        { title: "Usage Tracking", desc: "Track how much feed each flock consumes daily. Compare actual vs expected usage to detect waste, theft, or measurement errors." },
        { title: "Purchase Management", desc: "Log purchases with supplier details, quantities, and costs. Track price trends over time to negotiate better deals with suppliers." },
        { title: "Expiry Management", desc: "Track medication and vaccine expiry dates. Get advance warnings for items approaching expiry so you can use them in time or adjust orders." },
        { title: "Value Reports", desc: "See the total value of your inventory at any point in time. Generate reports for accounting, insurance, or business planning purposes." },
      ]}
      stats={[
        { value: "Low Stock", label: "Alerts Enabled" },
        { value: "0", label: "Stockouts After Setup" },
        { value: "24/7", label: "Monitoring" },
        { value: "99%", label: "Stock Accuracy" },
      ]}
      testimonial={{
        name: "Peter Ochieng",
        role: "Broiler Farm, Eldoret",
        text: "The inventory alerts help me know exactly when to reorder. No more over-ordering feed out of fear. The system tracks every bag in and out.",
      }}
    />
  );
}
