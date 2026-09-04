import { Router, Request, Response } from "express";
import { authMiddleware } from "../middleware/auth.js";
import multer from "multer";
import path from "path";
import fs from "fs";

const router = Router();
router.use(authMiddleware);

// Configure multer storage
const uploadsDir = path.join(process.cwd(), "uploads");
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

const storage = multer.diskStorage({
  destination: (_req, _file, cb) => cb(null, uploadsDir),
  filename: (_req, file, cb) => {
    const uniqueSuffix = Date.now() + "-" + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname) || ".jpg";
    const prefix = file.fieldname || "image";
    cb(null, `${prefix}-${uniqueSuffix}${ext}`);
  },
});

const upload = multer({
  storage,
  limits: { fileSize: 10 * 1024 * 1024 }, // 10MB
  fileFilter: (_req, file, cb) => {
    const allowed = /jpeg|jpg|png|gif|webp/;
    const ext = allowed.test(path.extname(file.originalname).toLowerCase());
    const mime = allowed.test(file.mimetype);
    if (ext && mime) {
      cb(null, true);
    } else {
      cb(new Error("Only image files (jpg, png, gif, webp) are allowed"));
    }
  },
});

// POST /api/upload — generic image upload
// Accepts any field name, returns the URL
router.post("/", upload.single("file"), async (req: Request, res: Response) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: "No file uploaded" });
    }

    const url = `/uploads/${req.file.filename}`;
    res.json({ success: true, url, filename: req.file.filename });
  } catch (error) {
    console.error("Upload error:", error);
    res.status(500).json({ error: "Failed to upload file" });
  }
});

// POST /api/upload/multiple — upload multiple images
router.post("/multiple", upload.array("files", 10), async (req: Request, res: Response) => {
  try {
    const files = req.files as Express.Multer.File[];
    if (!files || files.length === 0) {
      return res.status(400).json({ error: "No files uploaded" });
    }

    const urls = files.map((f) => ({
      url: `/uploads/${f.filename}`,
      filename: f.filename,
    }));

    res.json({ success: true, files: urls });
  } catch (error) {
    console.error("Multiple upload error:", error);
    res.status(500).json({ error: "Failed to upload files" });
  }
});

// DELETE /api/upload/:filename — delete an uploaded file
router.delete("/:filename", async (req: Request, res: Response) => {
  try {
    const filename = String(req.params.filename || "");
    const filePath = path.join(uploadsDir, filename);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
      res.json({ success: true });
    } else {
      res.status(404).json({ error: "File not found" });
    }
  } catch (error) {
    console.error("Delete file error:", error);
    res.status(500).json({ error: "Failed to delete file" });
  }
});

export default router;
