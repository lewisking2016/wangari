"use client";

import { motion } from "framer-motion";
import React from "react";
import { cn } from "@/lib/utils";

const STAGGER = 0.04;

export const TextRoll: React.FC<{
  children: string;
  className?: string;
  center?: boolean;
}> = ({ children, className, center = false }) => {
  const words = children.split(" ");

  return (
    <motion.span
      initial="initial"
      animate="visible"
      className={cn("relative block", className)}
      style={{ lineHeight: 1.1, textShadow: "0 2px 20px rgba(0,0,0,0.5)" }}
    >
      {words.map((word, wi) => {
        const globalOffset = words.slice(0, wi).reduce((sum, w) => sum + w.length + 1, 0);
        return (
          <React.Fragment key={`word-${wi}`}>
            {wi > 0 && <span className="inline-block">&nbsp;</span>}
            {word.split("").map((char, ci) => {
              const charIndex = globalOffset + ci;
              const totalChars = children.replace(/ /g, "").length;
              const delay = center
                ? STAGGER * Math.abs(charIndex - totalChars / 2)
                : STAGGER * charIndex;

              return (
                <motion.span
                  variants={{
                    initial: { opacity: 0, y: 20 },
                    visible: { opacity: 1, y: 0 },
                  }}
                  transition={{
                    duration: 0.4,
                    delay,
                    ease: [0.22, 1, 0.36, 1],
                  }}
                  className="inline-block"
                  key={`char-${wi}-${ci}`}
                >
                  {char}
                </motion.span>
              );
            })}
          </React.Fragment>
        );
      })}
    </motion.span>
  );
};
