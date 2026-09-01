"use client";

import { Sparkles } from "lucide-react";
import { FeaturePage } from "@/components/feature-page";

export default function AIFeaturePage() {
  return (
    <FeaturePage
      icon={Sparkles}
      badge="AI Assistant — New"
      title="Ask your farm anything"
      subtitle="Get instant, data-driven answers about your farm. Powered by your own production data, market prices, and agricultural best practices."
      description="Wangari's AI Assistant is like having an agricultural expert in your pocket. Ask it anything about your farm — from 'Which flock is most profitable?' to 'How much feed should I order this week?' — and get instant, personalized answers based on your actual data."
      highlights={[
        "Natural language queries — ask in English or Swahili",
        "Answers based on YOUR farm's real data, not generic advice",
        "Production predictions and optimization recommendations",
        "Disease diagnosis support based on symptoms",
        "Feed optimization suggestions to reduce costs",
        "Market price insights and selling timing recommendations",
        "24/7 availability — no waiting for consultations",
        "Continuously learns from your farm's patterns",
      ]}
      capabilities={[
        { title: "Natural Language Chat", desc: "Simply type or speak your question in plain English or Swahili. No special commands or technical knowledge needed. The AI understands context and follows up with clarifying questions." },
        { title: "Data-Powered Answers", desc: "Every response is grounded in your actual farm data. When you ask about profitability, the AI analyzes your real costs and revenue — not industry averages. This means advice that's relevant to YOUR farm." },
        { title: "Production Insights", desc: "Get AI-generated insights about your production trends. The assistant identifies patterns humans might miss — like correlating weather changes with production drops or identifying the most cost-effective feed brands." },
        { title: "Health Advisory", desc: "Describe symptoms you're observing and the AI will suggest possible causes and recommended actions. While it doesn't replace a vet, it helps you make faster decisions in critical situations." },
        { title: "Financial Planning", desc: "Ask the AI to forecast next month's revenue, calculate the ROI of expanding a flock, or compare the profitability of different breeds. Get numbers, not just opinions." },
        { title: "Learning Library", desc: "The AI draws from a vast database of poultry farming knowledge, adapted for Kenyan conditions. Ask about best practices, new techniques, or regulatory changes — and get actionable answers." },
      ]}
      stats={[
        { value: "<2s", label: "Response Time" },
        { value: "10K+", label: "Questions Answered Daily" },
        { value: "EN/SW", label: "Language Support" },
        { value: "24/7", label: "Availability" },
      ]}
      testimonial={{
        name: "Mary Akinyi",
        role: "Mixed Farm, Kisumu",
        text: "The AI assistant is like having an agricultural expert on speed dial. I asked it why my layers were producing fewer eggs and it analyzed three months of data to find the answer — my feed supplier had changed the protein content. I would never have figured that out on my own.",
      }}
    />
  );
}
