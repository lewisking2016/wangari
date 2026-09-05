"use client";

import * as React from "react";
import { motion } from "framer-motion";
import { Sparkles, Clock, ArrowLeft, Lightbulb, Bot, ShieldCheck } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";

const fadeUp = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4 } } };

export default function AIAssistantPage() {
  return (
    <div className="max-w-3xl mx-auto py-8 px-4 space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex items-center gap-3">
        <Link href="/dashboard" className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
          <ArrowLeft className="h-5 w-5" />
        </Link>
        <div>
          <h1 className="text-2xl font-extrabold text-wangari-heading tracking-tight flex items-center gap-2">
            Wangari AI Assistant <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">Coming Soon</span>
          </h1>
          <p className="text-xs text-wangari-muted mt-0.5">Automated AI farm intelligence and decision support</p>
        </div>
      </motion.div>

      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <Card className="border border-wangari-border bg-gradient-to-br from-wangari-green-900 via-wangari-green-800 to-wangari-green-900 text-white rounded-3xl overflow-hidden shadow-lg">
          <CardContent className="p-8 text-center space-y-6">
            <div className="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 mx-auto shadow-inner">
              <Sparkles className="h-10 w-10 animate-pulse" />
            </div>

            <div>
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-400/20 text-emerald-300 border border-emerald-400/30 uppercase tracking-widest">
                <Clock className="h-3.5 w-3.5" /> Under Active Development
              </span>
              <h2 className="text-2xl sm:text-3xl font-black mt-3">
                Smart Farm Intelligence is On Its Way
              </h2>
              <p className="text-sm text-emerald-100/90 max-w-lg mx-auto mt-2 leading-relaxed">
                We are building an intelligent AI assistant tailored for poultry and livestock farmers in Kenya. It will analyze feed efficiency, diagnose flock health, and predict farm profitability in plain English and Swahili.
              </p>
            </div>

            <div className="grid sm:grid-cols-3 gap-3 pt-2 text-left">
              {[
                { icon: <Bot className="h-5 w-5 text-emerald-300" />, title: "Natural Language Chat", desc: "Ask questions in Swahili or English about your farm data." },
                { icon: <Lightbulb className="h-5 w-5 text-amber-300" />, title: "Feed & FCR Optimization", desc: "Get recommendations to reduce feed waste and boost yield." },
                { icon: <ShieldCheck className="h-5 w-5 text-blue-300" />, title: "Health Advisory", desc: "Early symptom checking for flock vaccinations and treatment." },
              ].map((item, i) => (
                <div key={i} className="p-4 rounded-2xl bg-white/10 border border-white/15 backdrop-blur-xs">
                  <div className="mb-2">{item.icon}</div>
                  <h4 className="text-xs font-extrabold text-white">{item.title}</h4>
                  <p className="text-[11px] text-emerald-100/80 mt-1">{item.desc}</p>
                </div>
              ))}
            </div>

            <div className="pt-4 border-t border-white/10 flex items-center justify-between">
              <p className="text-xs text-emerald-200/80 font-medium">Expected release in upcoming platform update.</p>
              <Link href="/dashboard">
                <Button className="bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl">
                  Back to Dashboard
                </Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  );
}
