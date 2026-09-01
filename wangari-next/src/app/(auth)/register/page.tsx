"use client";

import * as React from "react";
import { motion } from "framer-motion";
import { signIn } from "next-auth/react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { Loader2, ArrowRight, User, Mail, Lock, Sprout } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const fadeUp = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08 } },
};

export default function RegisterPage() {
  const router = useRouter();
  const [form, setForm] = React.useState({
    name: "",
    email: "",
    password: "",
    farmName: "",
  });
  const [error, setError] = React.useState("");
  const [loading, setLoading] = React.useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/auth/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
      });

      if (!res.ok) {
        const data = await res.json();
        setError(data.error || "Registration failed");
        return;
      }

      const result = await signIn("credentials", {
        email: form.email,
        password: form.password,
        redirect: false,
      });

      if (result?.error) {
        router.push("/login");
      } else {
        router.push("/dashboard");
        router.refresh();
      }
    } catch {
      setError("Something went wrong. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const fields = [
    { id: "name", label: "Full Name", type: "text", placeholder: "John Kamau", icon: User, key: "name" as const },
    { id: "email", label: "Email", type: "email", placeholder: "you@example.com", icon: Mail, key: "email" as const },
    { id: "farmName", label: "Farm Name", type: "text", placeholder: "Kamau Poultry Farm", icon: Sprout, key: "farmName" as const },
    { id: "password", label: "Password", type: "password", placeholder: "At least 6 characters", icon: Lock, key: "password" as const },
  ];

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
          Create your farm account
        </h1>
        <p className="mt-2 text-sm text-[#64748B]">
          Start managing your farm in minutes — it&apos;s free
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
        className="space-y-4"
      >
        {fields.map((field) => (
          <motion.div key={field.id} variants={fadeUp} className="space-y-2">
            <Label htmlFor={field.id} className="text-sm font-semibold text-[#334155]">
              {field.label}
            </Label>
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                <field.icon className="h-4 w-4" />
              </div>
              <Input
                id={field.id}
                type={field.type}
                placeholder={field.placeholder}
                value={form[field.key]}
                onChange={(e) => setForm({ ...form, [field.key]: e.target.value })}
                required
                minLength={field.key === "password" ? 6 : undefined}
                className="h-12 rounded-xl border-[#E5E7EB] focus:border-[#166534] focus:ring-[#166534]/20 transition-all pl-10"
              />
            </div>
          </motion.div>
        ))}

        <motion.div variants={fadeUp} className="pt-2">
          <Button
            type="submit"
            disabled={loading}
            className="w-full h-12 rounded-xl bg-[#166534] hover:bg-[#14532D] text-white font-bold text-sm transition-all duration-200 hover:shadow-lg hover:shadow-[#166534]/25 hover:-translate-y-0.5 cursor-pointer"
          >
            {loading ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin mr-2" />
                Creating account...
              </>
            ) : (
              <>
                Create Account
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

      {/* Login link */}
      <motion.p variants={fadeUp} className="text-center text-sm text-[#64748B]">
        Already have an account?{" "}
        <Link
          href="/login"
          className="font-bold text-[#166534] hover:underline"
        >
          Sign in
        </Link>
      </motion.p>
    </motion.div>
  );
}
