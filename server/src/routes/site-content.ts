import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { requireAdmin, auditAdminAction } from "../lib/admin-auth.js";

/**
 * Editable website content (super-admin Website module).
 * - GET  /api/site-content/:page        — public, no auth (marketing pages fetch it)
 * - GET  /api/admin/site-content        — admin: list pages + data
 * - PUT  /api/admin/site-content/:page  — admin: upsert content, audited
 *
 * Content is a JSON blob per page. The frontend defines the shape (with safe
 * hardcoded fallbacks) and renders whatever the DB provides — a malformed or
 * missing blob can never take the marketing site down.
 */

const ALLOWED_PAGES = ["pricing", "contact"];

const router = Router();

// Public: marketing pages fetch their live content. CORS is open on /api.
router.get("/site-content/:page", async (req: Request, res: Response) => {
  try {
    const page = String(req.params.page || "");
    if (!ALLOWED_PAGES.includes(page)) {
      return res.status(404).json({ error: "Unknown page" });
    }
    const row = await prisma.siteContent.findUnique({ where: { page } });
    res.json({ page, data: row?.data ?? null, updatedAt: row?.updatedAt ?? null });
  } catch (error) {
    console.error("Site content read error:", error);
    res.status(500).json({ error: "Failed to load content" });
  }
});

// Admin: list all editable pages.
router.get("/admin/site-content", requireAdmin(["support", "support_read"]), async (_req: Request, res: Response) => {
  try {
    const rows = await prisma.siteContent.findMany();
    const byPage: Record<string, unknown> = {};
    for (const p of ALLOWED_PAGES) {
      const row = rows.find((r) => r.page === p);
      byPage[p] = { data: row?.data ?? null, updatedAt: row?.updatedAt ?? null };
    }
    res.json({ pages: byPage });
  } catch (error) {
    console.error("Admin site-content list error:", error);
    res.status(500).json({ error: "Failed to load content" });
  }
});

// Admin: upsert a page's content (full JSON replace — the editor sends the
// complete blob). Audited so every content change is traceable.
router.put("/admin/site-content/:page", requireAdmin(["support"]), async (req: Request, res: Response) => {
  try {
    const admin = (req as any).admin;
    const page = String(req.params.page || "");
    if (!ALLOWED_PAGES.includes(page)) {
      return res.status(400).json({ error: "Unknown page" });
    }
    const data = req.body?.data;
    if (!data || typeof data !== "object" || Array.isArray(data)) {
      return res.status(400).json({ error: "data object required" });
    }
    const saved = await prisma.siteContent.upsert({
      where: { page },
      create: { page, data, updatedBy: admin.adminId },
      update: { data, updatedBy: admin.adminId },
    });
    auditAdminAction(admin, `admin.site_content.update`, "site_content", page, { page });
    res.json({ page, data: saved.data, updatedAt: saved.updatedAt });
  } catch (error) {
    console.error("Admin site-content update error:", error);
    res.status(500).json({ error: "Failed to save content" });
  }
});

export default router;
