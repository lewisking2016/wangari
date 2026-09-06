"use client";

import * as React from "react";
import { motion, AnimatePresence } from "framer-motion";
import { ChevronLeft, ChevronRight, Star, Quote, CheckCircle2, MapPin } from "lucide-react";

interface Testimonial {
  id: number;
  name: string;
  role: string;
  location: string;
  farmDetails: string;
  text: string;
  rating: number;
  avatarBg: string;
  initials: string;
}

const TESTIMONIALS: Testimonial[] = [
  {
    id: 1,
    name: "John Mwangi",
    role: "Poultry Farmer",
    location: "Nakuru, Kenya",
    farmDetails: "2,500 Layers",
    text: "Wangari transformed how we manage feed and daily egg production. I can track laying percentages right from my phone and get alerted when mortality spikes.",
    rating: 5,
    avatarBg: "bg-emerald-700 text-white",
    initials: "JM",
  },
  {
    id: 2,
    name: "Grace Wambui",
    role: "Dairy & Crop Farmer",
    location: "Kiambu, Kenya",
    farmDetails: "Mixed Farm (12 Cows & Crops)",
    text: "The WhatsApp bot is a game changer for my farm workers. They log daily milk yields and feed usage directly on WhatsApp without needing expensive devices.",
    rating: 5,
    avatarBg: "bg-teal-700 text-white",
    initials: "GW",
  },
  {
    id: 3,
    name: "Peter Ochieng",
    role: "Commercial Poultry",
    location: "Eldoret, Kenya",
    farmDetails: "5,000 Broilers",
    text: "I used to lose money on feed wastage. With Wangari's Feed Calculator and FCR tracking, our feed cost per bird dropped by 18% in the first month.",
    rating: 5,
    avatarBg: "bg-green-800 text-white",
    initials: "PO",
  },
  {
    id: 4,
    name: "Mary Njeri",
    role: "Horticulture & Layers",
    location: "Naivasha, Kenya",
    farmDetails: "Greenhouse & 1,000 Birds",
    text: "Managing worker attendance and daily expenses used to be a nightmare of manual receipts. Now everything is consolidated into clean financial reports.",
    rating: 5,
    avatarBg: "bg-emerald-600 text-white",
    initials: "MN",
  },
  {
    id: 5,
    name: "David Kipkurui",
    role: "Livestock Owner",
    location: "Uasin Gishu, Kenya",
    farmDetails: "35 Dairy Cattle",
    text: "The offline-first feature is essential for remote areas with spotty network. Data syncs automatically once back in coverage!",
    rating: 5,
    avatarBg: "bg-teal-800 text-white",
    initials: "DK",
  },
];

export function TestimonialsSlider() {
  const [currentIndex, setCurrentIndex] = React.useState(0);
  const [isPaused, setIsPaused] = React.useState(false);
  const [direction, setDirection] = React.useState(1); // 1 = right, -1 = left

  const nextSlide = React.useCallback(() => {
    setDirection(1);
    setCurrentIndex((prev) => (prev + 1) % TESTIMONIALS.length);
  }, []);

  const prevSlide = React.useCallback(() => {
    setDirection(-1);
    setCurrentIndex((prev) => (prev - 1 + TESTIMONIALS.length) % TESTIMONIALS.length);
  }, []);

  // Auto-play interval
  React.useEffect(() => {
    if (isPaused) return;
    const timer = setInterval(() => {
      nextSlide();
    }, 6000);
    return () => clearInterval(timer);
  }, [isPaused, nextSlide]);

  const current = TESTIMONIALS[currentIndex];

  const variants = {
    enter: (dir: number) => ({
      x: dir > 0 ? 100 : -100,
      opacity: 0,
      scale: 0.95,
    }),
    center: {
      x: 0,
      opacity: 1,
      scale: 1,
      transition: { duration: 0.4, ease: [0.22, 1, 0.36, 1] as const },
    },
    exit: (dir: number) => ({
      x: dir < 0 ? 100 : -100,
      opacity: 0,
      scale: 0.95,
      transition: { duration: 0.3 },
    }),
  };

  return (
    <div
      className="relative max-w-4xl mx-auto px-4"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      {/* Slide Container */}
      <div className="relative min-h-[320px] sm:min-h-[280px] flex items-center justify-center overflow-hidden">
        <AnimatePresence initial={false} custom={direction} mode="wait">
          <motion.div
            key={current.id}
            custom={direction}
            variants={variants}
            initial="enter"
            animate="center"
            exit="exit"
            className="w-full rounded-3xl border border-[#E5E7EB] bg-white p-8 sm:p-10 shadow-xl shadow-[#166534]/5 relative overflow-hidden"
          >
            {/* Top quote icon */}
            <Quote className="h-12 w-12 text-[#166534]/15 absolute top-6 right-6 pointer-events-none" />

            <div className="relative z-10 flex flex-col justify-between h-full">
              {/* Star Rating */}
              <div className="flex items-center gap-1 mb-4">
                {[...Array(current.rating)].map((_, i) => (
                  <Star key={i} className="h-5 w-5 fill-[#166534] text-[#166534]" />
                ))}
              </div>

              {/* Quote Text */}
              <p className="text-base sm:text-xl font-medium text-[#0F172A] leading-relaxed italic mb-6">
                "{current.text}"
              </p>

              {/* Author Footer */}
              <div className="flex items-center justify-between pt-4 border-t border-gray-100 flex-wrap gap-3">
                <div className="flex items-center gap-3">
                  <div className={`h-12 w-12 rounded-2xl flex items-center justify-center font-bold text-sm shadow-sm ${current.avatarBg}`}>
                    {current.initials}
                  </div>
                  <div>
                    <div className="flex items-center gap-1.5">
                      <h4 className="text-base font-extrabold text-[#0F172A]">{current.name}</h4>
                      <CheckCircle2 className="h-4 w-4 text-[#22C55E]" />
                    </div>
                    <p className="text-xs font-semibold text-[#64748B]">
                      {current.role} • <span className="text-[#166534]">{current.farmDetails}</span>
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-1.5 text-xs text-[#64748B] bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100">
                  <MapPin className="h-3.5 w-3.5 text-[#166534]" />
                  <span>{current.location}</span>
                </div>
              </div>
            </div>
          </motion.div>
        </AnimatePresence>
      </div>

      {/* Navigation Controls */}
      <div className="flex items-center justify-between mt-8">
        {/* Dot Indicators */}
        <div className="flex items-center gap-2">
          {TESTIMONIALS.map((t, index) => (
            <button
              key={t.id}
              onClick={() => {
                setDirection(index > currentIndex ? 1 : -1);
                setCurrentIndex(index);
              }}
              className={`h-2.5 rounded-full transition-all cursor-pointer ${
                index === currentIndex ? "w-8 bg-[#166534]" : "w-2.5 bg-gray-200 hover:bg-gray-300"
              }`}
              aria-label={`Go to slide ${index + 1}`}
            />
          ))}
        </div>

        {/* Arrow Buttons */}
        <div className="flex items-center gap-2">
          <button
            onClick={prevSlide}
            className="flex h-11 w-11 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-700 hover:bg-[#F0FDF4] hover:border-[#166534] hover:text-[#166534] transition-all cursor-pointer shadow-sm"
            aria-label="Previous testimonial"
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <button
            onClick={nextSlide}
            className="flex h-11 w-11 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-700 hover:bg-[#F0FDF4] hover:border-[#166534] hover:text-[#166534] transition-all cursor-pointer shadow-sm"
            aria-label="Next testimonial"
          >
            <ChevronRight className="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>
  );
}
