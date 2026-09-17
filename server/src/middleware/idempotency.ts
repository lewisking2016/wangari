import { Request, Response, NextFunction } from "express";
import { prisma } from "../db.js";

/**
 * Idempotency for offline-synced writes.
 *
 * The mobile/PWA client queues writes offline and replays them with a
 * unique `clientId`. Because network hiccups can deliver the same queued
 * write more than once, this middleware guarantees at-most-once creation:
 * the first request's response is stored for 48h and replayed verbatim on
 * duplicate clientId. Also collapses concurrent double-taps with the same
 * clientId into a single write.
 */

export function idempotencyGuard(req: Request, res: Response, next: NextFunction) {
  const clientId = req.body?.clientId;
  if (!clientId || typeof clientId !== "string" || clientId.length > 100) return next();

  (async () => {
    try {
      const existing = await prisma.syncedWrite.findUnique({ where: { clientId } });
      if (existing) {
        // Same write already processed — replay the stored response.
        return res.status(existing.statusCode).json(JSON.parse(existing.responseBody));
      }

      // Reserve the clientId BEFORE the handler runs so concurrent
      // duplicates (double-tap, retries) wait instead of double-writing.
      try {
        await prisma.syncedWrite.create({
          data: {
            clientId,
            responseBody: "{}",
            statusCode: 500,
            userId: req.user?.userId ?? null,
          },
        });
      } catch (e: any) {
        if (e?.code === "P2002") {
          // Another request with this clientId is in flight — poll briefly.
          for (let i = 0; i < 20; i++) {
            await new Promise((r) => setTimeout(r, 250));
            const done = await prisma.syncedWrite.findUnique({ where: { clientId } });
            if (done && done.statusCode !== 500) {
              return res.status(done.statusCode).json(JSON.parse(done.responseBody));
            }
          }
          return res.status(409).json({ error: "Duplicate request in progress" });
        }
        throw e;
      }

      // Intercept the handler's response and store it.
      const origJson = res.json.bind(res);
      res.json = (body: any) => {
        const statusCode = res.statusCode;
        prisma.syncedWrite
          .update({
            where: { clientId },
            data: { responseBody: JSON.stringify(body ?? {}), statusCode, userId: req.user?.userId ?? null },
          })
          .catch(() => {});
        return origJson(body);
      };
      next();
    } catch (error) {
      console.error("Idempotency guard error:", error);
      next(); // never break the request because of sync bookkeeping
    }
  })();
}
