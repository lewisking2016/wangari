"use client";

import * as React from "react";
import { motion } from "framer-motion";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { Eye, EyeOff, Loader2, ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { login, googleLogin } from "@/lib/auth-client";

const fadeUp = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08 } },
};

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const callbackUrl = searchParams.get("callbackUrl") || "/dashboard";

  const [email, setEmail] = React.useState("");
  const [password, setPassword] = React.useState("");
  const [showPassword, setShowPassword] = React.useState(false);
  const [error, setError] = React.useState("");
  const [loading, setLoading] = React.useState(false);

  const googleButtonRef = React.useRef<HTMLDivElement>(null);
  const [googleLoaded, setGoogleLoaded] = React.useState(false);

  // Load Google Identity Services
  React.useEffect(() => {
    const clientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID;
    if (!clientId) return;

    const script = document.createElement("script");
    script.src = "https://accounts.google.com/gsi/client";
    script.async = true;
    script.onload = () => {
      if (window.google) {
        window.google.accounts.id.initialize({
          client_id: clientId,
          callback: async (response: any) => {
            try {
              setError("");
              setLoading(true);
              await googleLogin(response.credential);
              router.push(callbackUrl);
            } catch (err) {
              setError(err instanceof Error ? err.message : "Google sign-in failed");
            } finally {
              setLoading(false);
            }
          },
        });
        if (googleButtonRef.current) {
          window.google.accounts.id.renderButton(googleButtonRef.current, {
            theme: "outline",
            size: "large",
            width: "100%",
            text: "signin_with",
          });
        }
        setGoogleLoaded(true);
      }
    };
    document.head.appendChild(script);
  }, [router, callbackUrl]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      await login(email, password);
      router.push(callbackUrl);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Something went wrong. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <motion.div
      initial="hidden"
      animate="visible"
      variants={stagger}
      className="space-y-8"
    >
      {/* Heading */}
      <motion.div variants={fadeUp}>
        <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">
          Welcome back
        </h1>
        <p className="mt-2 text-sm text-[#64748B]">
          Sign in to your farm management dashboard
        </p>
      </motion.div>

      {/* Error */}
      {error && (
        <motion.div
          initial={{ opacity: 0, y: -8 }}
          animate={{ opacity: 1, y: 0 }}
          className="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 font-medium"
        >
          {error}
        </motion.div>
      )}

      {/* Form */}
      <motion.form
        variants={fadeUp}
        onSubmit={handleSubmit}
        className="space-y-5"
      >
        <motion.div variants={fadeUp} className="space-y-2">
          <Label htmlFor="email" className="text-sm font-semibold text-[#334155]">
            Email
          </Label>
          <Input
            id="email"
            type="email"
            placeholder="you@example.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="h-12 rounded-xl border-[#E5E7EB] focus:border-[#166534] focus:ring-[#166534]/20 transition-all"
          />
        </motion.div>

        <motion.div variants={fadeUp} className="space-y-2">
          <Label htmlFor="password" className="text-sm font-semibold text-[#334155]">
            Password
          </Label>
          <div className="relative">
            <Input
              id="password"
              type={showPassword ? "text" : "password"}
              placeholder="Enter your password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="h-12 rounded-xl border-[#E5E7EB] focus:border-[#166534] focus:ring-[#166534]/20 transition-all pr-12"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8] hover:text-[#64748B] transition-colors"
            >
              {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
            </button>
          </div>
        </motion.div>

        <motion.div variants={fadeUp} className="flex items-center justify-between">
          <label className="flex items-center gap-2 text-sm text-[#64748B] cursor-pointer">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-[#E5E7EB] text-[#166534] focus:ring-[#166534]/20"
            />
            Remember me
          </label>
          <Link
            href="/forgot-password"
            className="text-sm font-semibold text-[#166534] hover:underline"
          >
            Forgot password?
          </Link>
        </motion.div>

        <motion.div variants={fadeUp}>
          <Button
            type="submit"
            disabled={loading}
            className="w-full h-12 rounded-xl bg-[#166534] hover:bg-[#14532D] text-white font-bold text-sm transition-all duration-200 hover:shadow-lg hover:shadow-[#166534]/25 hover:-translate-y-0.5 cursor-pointer"
          >
            {loading ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin mr-2" />
                Signing in...
              </>
            ) : (
              <>
                Sign In
                <ArrowRight className="h-4 w-4 ml-2" />
              </>
            )}
          </Button>
        </motion.div>
      </motion.form>

      {/* Divider */}
      <motion.div variants={fadeUp} className="flex items-center gap-4">
        <div className="flex-1 h-px bg-[#E5E7EB]" />
        <span className="text-xs text-[#94A3B8] font-medium">or</span>
        <div className="flex-1 h-px bg-[#E5E7EB]" />
      </motion.div>

      {/* Google Sign-In */}
      <motion.div variants={fadeUp} className="space-y-3">
        <div ref={googleButtonRef} className="w-full" />
        {!googleLoaded && (
          <div className="w-full h-12 rounded-xl border border-[#E5E7EB] flex items-center justify-center gap-3 hover:bg-gray-50 transition-colors cursor-pointer">
            <svg className="h-5 w-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            <span className="text-sm font-medium text-[#64748B]">Sign in with Google</span>
          </div>
        )}
        <p className="text-[10px] text-[#94A3B8] text-center">Only Gmail and Outlook emails accepted for manual sign-in</p>
      </motion.div>

      {/* Register link */}
      <motion.p variants={fadeUp} className="text-center text-sm text-[#64748B]">
        Don&apos;t have an account?{" "}
        <Link
          href="/register"
          className="font-bold text-[#166534] hover:underline"
        >
          Create one free
        </Link>
      </motion.p>
    </motion.div>
  );
}

export default function LoginPage() {
  return (
    <React.Suspense fallback={<div className="flex items-center justify-center min-h-screen"><Loader2 className="h-8 w-8 animate-spin text-[#166534]" /></div>}>
      <LoginForm />
    </React.Suspense>
  );
}
