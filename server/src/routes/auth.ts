import { TRIAL_DAYS, trialEndDate } from "../lib/config.js";
import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import bcrypt from "bcryptjs";
import crypto from "crypto";
import { createUniqueFarmCode } from "../lib/farm-code.js";
import jwt from "jsonwebtoken";
import { generateToken, JWT_SECRET } from "../middleware/auth.js";

// Allowed email domains for manual registration/login
const ALLOWED_DOMAINS = ["gmail.com", "outlook.com", "hotmail.com", "live.com", "yahoo.com"];

function isAllowedEmail(email: string): boolean {
  const domain = email.split("@")[1]?.toLowerCase();
  return ALLOWED_DOMAINS.includes(domain);
}

const router = Router();

// POST /api/auth/register
router.post("/register", async (req: Request, res: Response) => {
  try {
    const { name, email, password, phone } = req.body;

    if (!name || !email || !password) {
      return res.status(400).json({ error: "Name, email, and password are required" });
    }

    const normalizedEmail = email.toLowerCase().trim();

    // Basic format sanity only — no domain allowlist. It blocked legitimate
    // business/organization emails while rate limiting handles spam.
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail)) {
      return res.status(400).json({ error: "Please provide a valid email address" });
    }

    const existing = await prisma.user.findFirst({
      where: {
        email: { equals: normalizedEmail, mode: "insensitive" },
      },
    });
    if (existing) {
      return res.status(409).json({ error: "An account with this email address already has a registered farm. Please sign in instead." });
    }

    const hashedPassword = await bcrypt.hash(password, 12);
    const now = new Date();
    const trialEndsAt = trialEndDate(now);

    const user = await prisma.user.create({
      data: {
        name,
        email: normalizedEmail,
        password: hashedPassword,
        phone: phone || null,
        trialStartsAt: now,
        trialEndsAt,
      },
    });

    // Create a farm for the user
    const farm = await prisma.farm.create({
      data: {
        name: `${name}'s Farm`,
        ownerId: user.id,
        code: await createUniqueFarmCode(),
      },
    });

    // Add user as farm member
    await prisma.farmMember.create({
      data: {
        userId: user.id,
        farmId: farm.id,
        role: "farm_owner",
      },
    });

    const token = await generateToken(user.id, farm.id);

    res.status(201).json({
      token,
      user: { id: user.id, name: user.name, email: user.email },
      farm: { id: farm.id, name: farm.name },
    });
  } catch (error) {
    console.error("Register error:", error);
    res.status(500).json({ error: "Registration failed" });
  }
});

// POST /api/auth/login
router.post("/login", async (req: Request, res: Response) => {
  try {
    const { email, password } = req.body ?? {};

    if (typeof email !== "string" || typeof password !== "string") {
      return res.status(400).json({ error: "Email and password are required" });
    }

    const normalizedEmail = email.toLowerCase().trim();

    // NOTE: no domain allowlist here — the allowlist is a registration-time
    // spam control. Existing users (e.g. staff on imeantech.com, or users who
    // registered before/with other domains) must always be able to log in.

    const user = await prisma.user.findFirst({
      where: {
        email: { equals: normalizedEmail, mode: "insensitive" },
      },
    });
    if (!user) {
      return res.status(401).json({ error: "Invalid credentials" });
    }

    // Google-only users don't have a password
    if (!user.password) {
      return res.status(400).json({ error: "This account uses Google sign-in. Please use the Google button to sign in." });
    }

    const valid = await bcrypt.compare(password, user.password);
    if (!valid) {
      return res.status(401).json({ error: "Invalid credentials" });
    }

    const member = await prisma.farmMember.findFirst({ where: { userId: user.id } });
    const farmId = member?.farmId || null;

    const token = await generateToken(user.id, farmId);

    res.json({
      token,
      user: { id: user.id, name: user.name, email: user.email, role: user.role },
      farmId,
    });
  } catch (error) {
    console.error("Login error:", error);
    res.status(500).json({ error: "Login failed" });
  }
});

// POST /api/auth/switch-farm — switch active farm, re-issue token
router.post("/switch-farm", async (req: Request, res: Response) => {
  try {
    const { farmId } = req.body;
    if (!farmId) return res.status(400).json({ error: "farmId required" });

    // Verify user is a member of this farm
    const token = req.headers.authorization?.replace("Bearer ", "");
    if (!token) return res.status(401).json({ error: "Unauthorized" });
    const decoded = jwt.verify(token, JWT_SECRET) as { userId: number };

    const member = await prisma.farmMember.findFirst({ where: { userId: decoded.userId, farmId: Number(farmId) } });
    if (!member) return res.status(403).json({ error: "Not a member of this farm" });

    const newToken = await generateToken(decoded.userId, Number(farmId));
    res.json({ token: newToken, farmId: Number(farmId) });
  } catch (error) {
    res.status(500).json({ error: "Failed" });
  }
});

// POST /api/auth/forgot-password
router.post("/forgot-password", async (req: Request, res: Response) => {
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ error: "Email is required" });
    }

    // Always return success to prevent email enumeration
    const user = await prisma.user.findUnique({ where: { email } });
    if (!user) {
      return res.json({ message: "If an account with that email exists, a reset link has been sent." });
    }

    // Generate a secure random token
    const resetToken = crypto.randomBytes(32).toString("hex");
    const resetTokenExpiry = new Date(Date.now() + 60 * 60 * 1000); // 1 hour

    await prisma.user.update({
      where: { id: user.id },
      data: { resetToken, resetTokenExpiry },
    });

    // In production, send an email here with the reset link.
    // For now, log the token so it can be used during development.
    const resetUrl = `${process.env.FRONTEND_URL || "https://wangari.imeantech.com"}/reset-password?token=${resetToken}`;
    console.log(`[DEV] Password reset link for ${email}: ${resetUrl}`);

    res.json({ message: "If an account with that email exists, a reset link has been sent." });
  } catch (error) {
    console.error("Forgot password error:", error);
    res.status(500).json({ error: "Failed to process password reset request" });
  }
});

// POST /api/auth/reset-password
router.post("/reset-password", async (req: Request, res: Response) => {
  try {
    const { token, password } = req.body;

    if (!token || !password) {
      return res.status(400).json({ error: "Token and password are required" });
    }

    if (password.length < 6) {
      return res.status(400).json({ error: "Password must be at least 6 characters" });
    }

    const user = await prisma.user.findFirst({
      where: {
        resetToken: token,
        resetTokenExpiry: { gt: new Date() },
      },
    });

    if (!user) {
      return res.status(400).json({ error: "Invalid or expired reset token" });
    }

    const hashedPassword = await bcrypt.hash(password, 12);

    await prisma.user.update({
      where: { id: user.id },
      data: {
        password: hashedPassword,
        resetToken: null,
        resetTokenExpiry: null,
      },
    });

    res.json({ message: "Password has been reset successfully" });
  } catch (error) {
    console.error("Reset password error:", error);
    res.status(500).json({ error: "Failed to reset password" });
  }
});

// POST /api/auth/google — sign in/up with Google
router.post("/google", async (req: Request, res: Response) => {
  try {
    const { credential, clientId } = req.body || {};
    if (!credential) {
      return res.status(400).json({ error: "Google credential is required" });
    }

    // Verify the Google ID token by calling Google's tokeninfo endpoint
    let googleUser: { sub: string; email: string; name: string; picture: string; email_verified: string | boolean; aud?: string };
    try {
      const googleRes = await fetch(`https://oauth2.googleapis.com/tokeninfo?id_token=${credential}`);
      if (!googleRes.ok) {
        return res.status(401).json({ error: "Invalid Google token" });
      }
      googleUser = await googleRes.json() as any;
    } catch {
      return res.status(401).json({ error: "Failed to verify Google token" });
    }

    if (!googleUser.email || !googleUser.sub) {
      return res.status(400).json({ error: "Invalid Google token data" } as any);
    }

    // Token must be issued for OUR client ID and the email must be verified by Google.
    const expectedAud = process.env.GOOGLE_CLIENT_ID;
    if (expectedAud && googleUser.aud !== expectedAud) {
      return res.status(401).json({ error: "Google token was not issued for this app" } as any);
    }
    if (googleUser.email_verified === "false" || googleUser.email_verified === false) {
      return res.status(401).json({ error: "Google account email is not verified" } as any);
    }

    const normalizedGoogleEmail = googleUser.email.toLowerCase().trim();

    // Check if user exists by googleId or email (case-insensitive)
    let user = await prisma.user.findFirst({
      where: {
        OR: [
          { googleId: googleUser.sub },
          { email: { equals: normalizedGoogleEmail, mode: "insensitive" } },
        ],
      },
    });

    if (user) {
      // Existing user — update Google data if not linked yet
      const updateData: any = {};
      if (!user.googleId) updateData.googleId = googleUser.sub;
      if (googleUser.picture && user.avatar !== googleUser.picture) updateData.avatar = googleUser.picture;
      if (Object.keys(updateData).length > 0) {
        user = await prisma.user.update({ where: { id: user.id }, data: updateData });
      }
    } else {
      // New user — create account
      const now = new Date();
      const trialEndsAt = trialEndDate(now);

      user = await prisma.user.create({
        data: {
          name: googleUser.name || normalizedGoogleEmail.split("@")[0],
          email: normalizedGoogleEmail,
          googleId: googleUser.sub,
          avatar: googleUser.picture || null,
          trialStartsAt: now,
          trialEndsAt,
          // No password for Google users
        },
      });

      // Create a farm for the new user
      const farm = await prisma.farm.create({
        data: {
          name: `${googleUser.name || "My"} Farm`,
          ownerId: user.id,
          code: await createUniqueFarmCode(),
        },
      });

      await prisma.farmMember.create({
        data: {
          userId: user.id,
          farmId: farm.id,
          role: "farm_owner",
        },
      });
    }

    // Get farm membership
    const member = await prisma.farmMember.findFirst({ where: { userId: user.id } });
    const farmId = member?.farmId || null;

    const token = await generateToken(user.id, farmId);

    res.json({
      token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        avatar: user.avatar,
        role: user.role,
        profileComplete: user.profileComplete,
        googleId: user.googleId,
      },
      farmId,
    });
  } catch (error) {
    console.error("Google auth error:", error);
    res.status(500).json({ error: "Google authentication failed" });
  }
});

// POST /api/auth/link-google — link Google account to existing user
router.post("/link-google", async (req: Request, res: Response) => {
  try {
    const { credential } = req.body || {};
    if (!credential) {
      return res.status(400).json({ error: "Google credential is required" });
    }

    // Verify token
    let googleUser: { sub: string; email: string; name: string; picture: string };
    try {
      const googleRes = await fetch(`https://oauth2.googleapis.com/tokeninfo?id_token=${credential}`);
      if (!googleRes.ok) return res.status(401).json({ error: "Invalid Google token" });
      googleUser = await googleRes.json() as any;
    } catch {
      return res.status(401).json({ error: "Failed to verify Google token" });
    }

    // Check if this Google account is already linked to another user
    const existingGoogleUser = await prisma.user.findUnique({ where: { googleId: googleUser.sub } });
    if (existingGoogleUser && existingGoogleUser.id !== req.user!.userId) {
      return res.status(409).json({ error: "This Google account is already linked to another user" });
    }

    // Link to current user
    const user = await prisma.user.update({
      where: { id: req.user!.userId },
      data: {
        googleId: googleUser.sub,
        avatar: googleUser.picture || undefined,
      },
    });

    res.json({ success: true, user: { id: user.id, name: user.name, avatar: user.avatar } });
  } catch (error) {
    console.error("Link Google error:", error);
    res.status(500).json({ error: "Failed to link Google account" });
  }
});

// PUT /api/auth/profile — update user profile
router.put("/profile", async (req: Request, res: Response) => {
  try {
    const userId = req.user!.userId;
    const { name, phone, location, county, farmName } = req.body;

    const updateData: any = {};
    if (name) updateData.name = name;
    if (phone) updateData.phone = phone;

    // Mark profile as complete if key fields are filled
    if (name && phone) {
      updateData.profileComplete = true;
    }

    const user = await prisma.user.update({ where: { id: userId }, data: updateData });

    // Update farm if provided
    if (farmName || location || county) {
      const member = await prisma.farmMember.findFirst({ where: { userId } });
      if (member) {
        await prisma.farm.update({ where: { id: member.farmId }, data: {
          name: farmName || undefined,
          location: location || undefined,
          county: county || undefined,
        }});
      }
    }

    res.json({
      success: true,
      user: { id: user.id, name: user.name, email: user.email, avatar: user.avatar, profileComplete: user.profileComplete },
    });
  } catch (error) {
    console.error("Profile update error:", error);
    res.status(500).json({ error: "Failed to update profile" });
  }
});

// POST /api/auth/send-verification
// Generates a 6-digit code, stores it, emails it via the backend's SMTP.
router.post("/send-verification", async (req: Request, res: Response) => {
  try {
    const { email } = req.body || {};
    if (!email || typeof email !== "string") {
      return res.status(400).json({ error: "Email is required" });
    }

    const user = await prisma.user.findUnique({ where: { email: email.toLowerCase() } });
    if (!user) {
      // Don't reveal whether the email exists
      return res.json({ message: "If that email is registered, a code has been sent." });
    }
    if (user.emailVerified) {
      return res.json({ message: "Email is already verified." });
    }

    const code = crypto.randomInt(100000, 999999).toString();
    const expiresAt = new Date(Date.now() + 15 * 60 * 1000);

    await prisma.verificationCode.deleteMany({
      where: { userId: user.id, purpose: "email_verification" },
    });
    await prisma.verificationCode.create({
      data: { userId: user.id, code, purpose: "email_verification", expiresAt },
    });

    const { sendEmail } = await import("../lib/email.js");
    const html = `<!doctype html><html><body style="margin:0;padding:24px;background:#f6f8f6;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;"><div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:16px;border:1px solid #e5e7eb;padding:28px;"><h2 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Your verification code</h2><p style="margin:0 0 24px;font-size:15px;color:#64748b;">Use the code below to complete your email verification. It expires in <strong>15 minutes</strong>.</p><div style="background:#f0fdf4;border-radius:8px;padding:20px;text-align:center;margin-bottom:24px;"><span style="font-size:32px;font-weight:700;letter-spacing:6px;color:#166534;font-family:monospace;">${code}</span></div><p style="margin:0;font-size:13px;color:#64748b;">If you didn't request this, you can safely ignore this email.</p></div></body></html>`;
    await sendEmail({
      to: user.email,
      subject: "Verify your email — Wangari",
      html,
      template: "email_verification",
      userId: user.id,
    });

    res.json({ message: "Verification code sent to your email." });
  } catch (error) {
    console.error("Send verification error:", error);
    res.status(500).json({ error: "Something went wrong. Please try again." });
  }
});

// POST /api/auth/verify-email
router.post("/verify-email", async (req: Request, res: Response) => {
  try {
    const { email, code } = req.body || {};
    if (!email || !code) {
      return res.status(400).json({ error: "Email and code are required" });
    }

    const user = await prisma.user.findUnique({ where: { email: email.toLowerCase() } });
    if (!user) return res.status(400).json({ error: "Invalid code" });
    if (user.emailVerified) return res.json({ message: "Email is already verified." });

    const verification = await prisma.verificationCode.findFirst({
      where: { userId: user.id, purpose: "email_verification", usedAt: null },
      orderBy: { createdAt: "desc" },
    });
    if (!verification) {
      return res.status(400).json({ error: "No verification code found. Please request a new one." });
    }
    if (new Date() > verification.expiresAt) {
      return res.status(400).json({ error: "Code has expired. Please request a new one." });
    }
    if (verification.code !== String(code)) {
      return res.status(400).json({ error: "Invalid code. Please try again." });
    }

    await prisma.$transaction([
      prisma.verificationCode.update({ where: { id: verification.id }, data: { usedAt: new Date() } }),
      prisma.user.update({ where: { id: user.id }, data: { emailVerified: new Date() } }),
    ]);

    res.json({ message: "Email verified successfully!" });
  } catch (error) {
    console.error("Verify email error:", error);
    res.status(500).json({ error: "Something went wrong. Please try again." });
  }
});

export default router;
