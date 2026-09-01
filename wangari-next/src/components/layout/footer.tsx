"use client";

import Link from "next/link";
import { Heart, Bird, Mail, Phone, MapPin } from "lucide-react";
import { motion } from "framer-motion";

const fadeUp = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] } },
};
const stagger = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.08 } },
};

interface FooterProps {
  variant?: "marketing" | "dashboard";
}

export function Footer({ variant = "marketing" }: FooterProps) {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="border-t border-[#E5E7EB] bg-white">
      <div className="mx-auto max-w-7xl px-6 py-16">
        <motion.div
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true }}
          variants={stagger}
          className="grid md:grid-cols-2 lg:grid-cols-5 gap-10"
        >
          {/* Brand */}
          <motion.div variants={fadeUp} className="lg:col-span-2">
            <div className="flex items-center gap-2.5 mb-4">
              <img src="/images/wangari-mark.svg" alt="Wangari" className="h-9 w-9" />
              <span className="text-xl font-extrabold text-[#0F172A]">Wangari</span>
            </div>
            <p className="text-sm text-[#64748B] leading-relaxed max-w-sm">
              The all-in-one farm management platform built for African farmers.
              Track flocks, manage inventory, monitor finances, and grow your farm
              with confidence.
            </p>
            <div className="mt-5 space-y-2.5">
              <div className="flex items-center gap-2.5 text-sm text-[#64748B]">
                <Mail className="h-4 w-4 text-[#22C55E]" />
                <a href="mailto:hello@wangari.app" className="hover:text-[#166534] transition-colors">hello@wangari.app</a>
              </div>
              <div className="flex items-center gap-2.5 text-sm text-[#64748B]">
                <Phone className="h-4 w-4 text-[#22C55E]" />
                <span>+254 700 123 456</span>
              </div>
              <div className="flex items-center gap-2.5 text-sm text-[#64748B]">
                <MapPin className="h-4 w-4 text-[#22C55E]" />
                <span>Nairobi, Kenya</span>
              </div>
            </div>
          </motion.div>

          {/* Product */}
          <motion.div variants={fadeUp}>
            <h3 className="text-xs font-bold text-[#0F172A] uppercase tracking-widest mb-4">Product</h3>
            <ul className="space-y-3 text-sm text-[#64748B]">
              <li><Link href="/pricing" className="hover:text-[#166534] transition-colors">Pricing</Link></li>
              <li><Link href="/about" className="hover:text-[#166534] transition-colors">Features</Link></li>
              <li><Link href="/ai" className="hover:text-[#166534] transition-colors">AI Assistant</Link></li>
              <li><Link href="/dashboard" className="hover:text-[#166534] transition-colors">Dashboard</Link></li>
              <li><a href="https://docs.wangari.app" className="hover:text-[#166534] transition-colors">Documentation</a></li>
            </ul>
          </motion.div>

          {/* Company */}
          <motion.div variants={fadeUp}>
            <h3 className="text-xs font-bold text-[#0F172A] uppercase tracking-widest mb-4">Company</h3>
            <ul className="space-y-3 text-sm text-[#64748B]">
              <li><Link href="/about" className="hover:text-[#166534] transition-colors">About Us</Link></li>
              <li><a href="mailto:hello@wangari.app" className="hover:text-[#166534] transition-colors">Contact</a></li>
              <li><Link href="/about" className="hover:text-[#166534] transition-colors">Careers</Link></li>
              <li><Link href="/about" className="hover:text-[#166534] transition-colors">Blog</Link></li>
            </ul>
          </motion.div>

          {/* Legal */}
          <motion.div variants={fadeUp}>
            <h3 className="text-xs font-bold text-[#0F172A] uppercase tracking-widest mb-4">Legal</h3>
            <ul className="space-y-3 text-sm text-[#64748B]">
              <li><Link href="/about" className="hover:text-[#166534] transition-colors">Privacy Policy</Link></li>
              <li><Link href="/about" className="hover:text-[#166534] transition-colors">Terms of Service</Link></li>
              <li><Link href="/about" className="hover:text-[#166534] transition-colors">Cookie Policy</Link></li>
            </ul>
            <div className="mt-6 flex gap-3">
              <a href="https://twitter.com/wangari_app" target="_blank" rel="noopener noreferrer" className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#F0FDF4] text-[#166534] hover:bg-[#166534] hover:text-white transition-all duration-200">
                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" /></svg>
              </a>
              <a href="https://github.com/wangari" target="_blank" rel="noopener noreferrer" className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#F0FDF4] text-[#166534] hover:bg-[#166534] hover:text-white transition-all duration-200">
                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" /></svg>
              </a>
            </div>
          </motion.div>
        </motion.div>
      </div>

      {/* Bottom bar */}
      <div className="border-t border-[#E5E7EB] bg-[#FAFBFC]">
        <div className="mx-auto max-w-7xl px-6 py-5 flex flex-col md:flex-row items-center justify-between gap-4">
          <p className="text-xs text-[#94A3B8]">
            &copy; {currentYear} Wangari Technologies Ltd. All rights reserved.
          </p>
          <div className="flex items-center gap-1.5 text-xs text-[#94A3B8]">
            <span>Made with</span>
            <Heart className="h-3 w-3 text-[#EF4444] fill-[#EF4444]" />
            <span>for African farmers</span>
          </div>
          <div className="flex items-center gap-1.5 text-xs text-[#94A3B8]">
            <Bird className="h-3.5 w-3.5 text-[#22C55E]" />
            <span>Wangari v1.0</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
