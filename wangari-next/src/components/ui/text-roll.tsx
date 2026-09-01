"use client";

import { motion } from "framer-motion";
import React from "react";
import { cn } from "@/lib/utils";

const STAGGER = 0.035;

export const TextRoll: React.FC<{
  children: string;
  className?: string;
  center?: boolean;
}> = ({ children, className, center = false }) => {
  const words = children.split(" ");

  return (
    <motion.span
      initial="initial"
      whileHover="hovered"
      className={cn("relative block overflow-hidden cursor-default", className)}
      style={{ lineHeight: 0.85 }}
    >
      {/* Top layer — slides up on hover */}
      <div>
        {words.map((word, wi) => {
          const globalOffset = words.slice(0, wi).reduce((sum, w) => sum + w.length + 1, 0);
          return (
            <React.Fragment key={`top-${wi}`}>
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
                      initial: { y: 0 },
                      hovered: { y: "-100%" },
                    }}
                    transition={{ ease: "easeInOut", delay }}
                    className="inline-block"
                    key={`tc-${wi}-${ci}`}
                  >
                    {char}
                  </motion.span>
                );
              })}
            </React.Fragment>
          );
        })}
      </div>

      {/* Bottom layer — slides up from below on hover */}
      <div className="absolute inset-0">
        {words.map((word, wi) => {
          const globalOffset = words.slice(0, wi).reduce((sum, w) => sum + w.length + 1, 0);
          return (
            <React.Fragment key={`bot-${wi}`}>
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
                      initial: { y: "100%" },
                      hovered: { y: 0 },
                    }}
                    transition={{ ease: "easeInOut", delay }}
                    className="inline-block"
                    key={`bc-${wi}-${ci}`}
                  >
                    {char}
                  </motion.span>
                );
              })}
            </React.Fragment>
          );
        })}
      </div>
    </motion.span>
  );
};
