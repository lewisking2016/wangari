import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const farmId = 1; // Default farm

    // Save each preference as a FarmSetting
    const preferences = [
      { key: "active_hubs", value: JSON.stringify(body.activeHubs || []) },
      { key: "farm_name", value: body.farmName || "" },
      { key: "farm_location", value: body.farmLocation || "" },
      { key: "farm_phone", value: body.farmPhone || "" },
      { key: "farm_type", value: body.farmType || "" },
      { key: "onboarded_at", value: body.onboardedAt || new Date().toISOString() },
      { key: "onboarding_complete", value: "true" },
    ];

    for (const pref of preferences) {
      await prisma.farmSetting.upsert({
        where: { farmId_settingKey: { farmId, settingKey: pref.key } },
        update: { settingValue: pref.value },
        create: { farmId, settingKey: pref.key, settingValue: pref.value },
      });
    }

    // Also update farm name if provided
    if (body.farmName) {
      await prisma.farm.update({
        where: { id: farmId },
        data: {
          name: body.farmName,
          location: body.farmLocation || undefined,
        },
      });
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Preferences save error:", error);
    return NextResponse.json({ error: "Failed to save preferences" }, { status: 500 });
  }
}

export async function GET() {
  try {
    const farmId = 1;
    const settings = await prisma.farmSetting.findMany({
      where: { farmId },
    });

    const prefs: Record<string, string> = {};
    settings.forEach((s) => {
      prefs[s.settingKey] = s.settingValue || "";
    });

    return NextResponse.json(prefs);
  } catch (error) {
    console.error("Preferences fetch error:", error);
    return NextResponse.json({});
  }
}
