import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import bcrypt from "bcryptjs";
import crypto from "crypto";
import jwt from "jsonwebtoken";
import { generateToken } from "../middleware/auth.js";

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

    if (!isAllowedEmail(email)) {
      return res.status(400).json({ error: "Only Gmail and Outlook email addresses are accepted for registration" });
    }

    const existing = await prisma.user.findUnique({ where: { email } });
    if (existing) {
      return res.status(409).json({ error: "Email already registered" });
    }

    const hashedPassword = await bcrypt.hash(password, 12);
    const now = new Date();
    const trialEndsAt = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000);

    const user = await prisma.user.create({
      data: {
        name,
        email,
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

    const token = generateToken(user.id, farm.id);

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
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(400).json({ error: "Email and password are required" });
    }

    if (!isAllowedEmail(email)) {
      return res.status(400).json({ error: "Only Gmail and Outlook email addresses are accepted" });
    }

    const user = await prisma.user.findUnique({ where: { email } });
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

    const token = generateToken(user.id, farmId);

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
    const decoded = jwt.verify(token, process.env.JWT_SECRET || "wangari-dev-secret-change-in-production") as { userId: number };

    const member = await prisma.farmMember.findFirst({ where: { userId: decoded.userId, farmId: Number(farmId) } });
    if (!member) return res.status(403).json({ error: "Not a member of this farm" });

    const newToken = generateToken(decoded.userId, Number(farmId));
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
    let googleUser: { sub: string; email: string; name: string; picture: string; email_verified: string };
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
      return res.status(400).json({ error: "Invalid Google token data" });
    }

    // Check if user exists by googleId or email
    let user = await prisma.user.findFirst({
      where: {
        OR: [
          { googleId: googleUser.sub },
          { email: googleUser.email },
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
      const trialEndsAt = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000);

      user = await prisma.user.create({
        data: {
          name: googleUser.name || googleUser.email.split("@")[0],
          email: googleUser.email,
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

    const token = generateToken(user.id, farmId);

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

export default router;
