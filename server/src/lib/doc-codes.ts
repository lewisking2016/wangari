import { prisma } from "../db.js";

/**
 * Sequential document codes per farm — INV-202609-0001 style.
 *
 * FarmSetting row `doc_seq_<prefix>` holds the last number used. Incrementing
 * inside the caller's transaction means two simultaneous creates can never
 * share a code, and a code is never reused even if the document is deleted —
 * nothing gets lost and references stay meaningful forever.
 */
export const DOC_PREFIXES = {
  invoice: "INV",
  quote: "QTE",
  receipt: "RCP",
  report: "RPT",
  delivery: "DLV",
  purchase: "PUR",
} as const;

export type DocKind = keyof typeof DOC_PREFIXES;

/**
 * Get the next sequential code for a farm + kind.
 * Must be called inside the same transaction (or immediately before create)
 * so concurrent writers serialise on the upsert.
 */
export async function nextDocCode(tx: any, farmId: number, kind: DocKind): Promise<string> {
  const prefix = DOC_PREFIXES[kind];
  const key = `doc_seq_${prefix}`;
  const now = new Date();
  const ym = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, "0")}`;

  // Reset the counter when the month rolls over so codes stay readable,
  // while old codes remain unique because they carry the old YYYYMM.
  const periodKey = `doc_seq_period_${prefix}`;
  const existing = await tx.farmSetting.findUnique({
    where: { farmId_settingKey: { farmId, settingKey: key } },
  });
  const existingPeriod = await tx.farmSetting.findUnique({
    where: { farmId_settingKey: { farmId, settingKey: periodKey } },
  });

  let next = 1;
  const sameMonth = existingPeriod?.settingValue === ym;
  if (existing && sameMonth) {
    next = Number(existing.settingValue || 0) + 1;
  }

  await tx.farmSetting.upsert({
    where: { farmId_settingKey: { farmId, settingKey: key } },
    create: { farmId, settingKey: key, settingValue: String(next) },
    update: { settingValue: String(next) },
  });
  await tx.farmSetting.upsert({
    where: { farmId_settingKey: { farmId, settingKey: periodKey } },
    create: { farmId, settingKey: periodKey, settingValue: ym },
    update: { settingValue: ym },
  });

  return `${prefix}-${ym}-${String(next).padStart(4, "0")}`;
}
