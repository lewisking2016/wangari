import { prisma } from "../db.js";

interface AuditEntry {
  userId?: number | null;
  farmId?: number | null;
  action: string; // e.g. "transaction.create", "sale.delete"
  entityType?: string;
  entityId?: number | null;
  details?: Record<string, unknown> | null;
}

/**
 * Fire-and-forget audit logging for money-path mutations.
 * Never throws — audit failure must not fail the business operation.
 */
export function auditMoneyMutation(entry: AuditEntry): void {
  prisma.auditLog
    .create({
      data: {
        userId: entry.userId ?? null,
        farmId: entry.farmId ?? null,
        action: entry.action,
        entityType: entry.entityType || null,
        entityId: entry.entityId ?? null,
        details: (entry.details || null) as any,
      },
    })
    .catch((err) => console.error("[audit] failed to record:", err?.message || err));
}
