import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { sendEmail } from "@/lib/email";
import { dailyDigestEmail, type DigestAlert } from "@/lib/email-templates";

const CRON_SECRET = process.env.CRON_SECRET || "";

/**
 * GET /api/cron/farm-digest
 *
 * Daily farmer email digest — the "out-of-app notifications" layer.
 * For every farm owner with an email it gathers:
 *  - PHI expiry alerts (safe-to-harvest countdown after pesticide sprays)
 *  - Harvest season reminders / expected-harvest dates
 *  - Acidic soil warnings from the latest soil test per crop
 *  - Low-stock items
 *  - Vaccinations due in the next 3 days
 *
 * Schedule daily ~06:30 EAT (03:30 UTC) via Vercel Cron:
 *   "0 3 * * *"
 * Protect with CRON_SECRET as a Bearer token.
 */

const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function parseSeason(season: string): { startMonth: number; endMonth: number } | null {
  const m = season.match(/([A-Za-z]{3,})\s*[-–to]+\s*([A-Za-z]{3,})/);
  if (!m) return null;
  const start = MONTHS.findIndex((mo) => mo.toLowerCase() === m[1].slice(0, 3).toLowerCase());
  const end = MONTHS.findIndex((mo) => mo.toLowerCase() === m[2].slice(0, 3).toLowerCase());
  if (start < 0 || end < 0) return null;
  return { startMonth: start, endMonth: end };
}

export async function GET(req: NextRequest) {
  const authHeader = req.headers.get("authorization");
  if (CRON_SECRET && authHeader !== `Bearer ${CRON_SECRET}`) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const now = new Date();
  const dashboardUrl = `${process.env.NEXTAUTH_URL || "https://wangari.imeantech.com"}/dashboard`;
  const threeDaysAhead = new Date(now.getTime() + 3 * 86400000);

  try {
    // One query per farm keeps memory flat; farms count is modest for now.
    const farms = await prisma.farm.findMany({
      select: {
        id: true,
        name: true,
        ownerId: true,
        owner: { select: { name: true, email: true } },
      },
    });

    let sent = 0;
    let skipped = 0;
    const errors: string[] = [];

    for (const farm of farms) {
      try {
        if (!farm.owner?.email) { skipped++; continue; }

        const [crops, inventory, vaccinations] = await Promise.all([
          prisma.crop.findMany({
            where: { farmId: farm.id },
            include: {
              applications: { orderBy: { date: "desc" } },
              soilTests: { orderBy: { date: "desc" }, take: 1 },
            },
          }),
          prisma.inventory.findMany({
            where: { farmId: farm.id },
            select: { itemName: true, quantity: true, unit: true, reorderLevel: true },
          }),
          prisma.vaccination.findMany({
            where: {
              status: "pending",
              scheduledDate: { gte: now, lte: threeDaysAhead },
              flock: { farmId: farm.id },
            },
            include: { flock: { select: { name: true } } },
          }),
        ]);

        const alerts: DigestAlert[] = [];

        // ── PHI (pre-harvest interval) alerts ──
        for (const crop of crops) {
          for (const app of crop.applications) {
            if (app.type !== "Pesticide" || !app.phiDays || Number(app.phiDays) <= 0) continue;
            const safeDate = new Date(app.date);
            safeDate.setDate(safeDate.getDate() + Number(app.phiDays));
            const daysLeft = Math.ceil((safeDate.getTime() - now.getTime()) / 86400000);
            if (daysLeft > 60) continue;
            if (daysLeft <= 0) {
              alerts.push({
                icon: "✅",
                title: `Safe to harvest: ${crop.name}`,
                detail: `${app.productName} PHI has elapsed — the crop is safe to harvest from today.`,
                tone: "success",
              });
            } else if (daysLeft <= 3) {
              alerts.push({
                icon: "⏳",
                title: `PHI ending soon: ${crop.name}`,
                detail: `${app.productName} — safe to harvest in ${daysLeft} day${daysLeft === 1 ? "" : "s"} (${safeDate.toLocaleDateString("en-KE")}).`,
                tone: "warning",
              });
            }
          }
        }

        // ── Harvest reminders ──
        for (const crop of crops) {
          if (crop.expectedHarvest && crop.status === "active") {
            const daysLeft = Math.ceil((new Date(crop.expectedHarvest).getTime() - now.getTime()) / 86400000);
            if (daysLeft >= 0 && daysLeft <= 3) {
              alerts.push({
                icon: "🌾",
                title: `Harvest time: ${crop.name}`,
                detail: daysLeft === 0 ? "Expected harvest is today." : `Expected harvest in ${daysLeft} day${daysLeft === 1 ? "" : "s"}.`,
                tone: "info",
              });
            }
          }
          if (crop.harvestSeason) {
            const s = parseSeason(crop.harvestSeason);
            if (s) {
              const month = now.getMonth();
              const inSeason = s.startMonth <= s.endMonth ? month >= s.startMonth && month <= s.endMonth : month >= s.startMonth || month <= s.endMonth;
              if (inSeason) {
                alerts.push({
                  icon: "🗓️",
                  title: `Season open: ${crop.name}`,
                  detail: `Harvest season (${crop.harvestSeason}) is on — schedule picking labour and buyers.`,
                  tone: "info",
                });
              }
            }
          }
          // ── Acidic soil warning (latest test per crop) ──
          const latestTest = crop.soilTests?.[0];
          if (latestTest?.ph && Number(latestTest.ph) < 5.2) {
            alerts.push({
              icon: "🧪",
              title: `Acidic soil: ${crop.name}`,
              detail: `Latest soil test shows pH ${Number(latestTest.ph)} (below 5.2) — lime application recommended before the next planting.`,
              tone: "warning",
            });
          }
        }

        // ── Low stock ──
        for (const item of inventory) {
          if (item.reorderLevel > 0 && Number(item.quantity) <= item.reorderLevel) {
            alerts.push({
              icon: "📦",
              title: `Low stock: ${item.itemName}`,
              detail: `${Number(item.quantity)} ${item.unit} remaining (reorder at ${item.reorderLevel}).`,
              tone: "danger",
            });
          }
        }

        // ── Vaccinations due ──
        for (const v of vaccinations) {
          alerts.push({
            icon: "💉",
            title: `Vaccination due: ${v.flock?.name || "Flock"}`,
            detail: `${v.vaccineName || "Vaccination"} scheduled for ${new Date(v.scheduledDate).toLocaleDateString("en-KE")}.`,
            tone: "warning",
          });
        }

        // Skip digest when there's nothing to say (avoid training farmers to ignore it)
        if (alerts.length === 0) { skipped++; continue; }

        const html = dailyDigestEmail(farm.owner.name || "Farmer", farm.name, alerts, dashboardUrl);
        const result = await sendEmail({
          to: farm.owner.email,
          subject: `🌿 Wangari daily digest — ${alerts.length} item${alerts.length === 1 ? "" : "s"} need${alerts.length === 1 ? "s" : ""} your attention`,
          html,
        });

        if (result.success) sent++;
        else { skipped++; errors.push(`${farm.name}: ${result.error}`); }
      } catch (farmErr: any) {
        errors.push(`${farm.name}: ${farmErr?.message || "unknown"}`);
      }
    }

    return NextResponse.json({ ok: true, farms: farms.length, sent, skipped, errors: errors.slice(0, 10) });
  } catch (error: any) {
    console.error("Farm digest cron error:", error);
    return NextResponse.json({ error: "Digest failed", detail: error?.message }, { status: 500 });
  }
}
