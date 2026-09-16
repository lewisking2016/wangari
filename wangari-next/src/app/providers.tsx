"use client";

import * as React from "react";
import { LanguageProvider } from "@/components/language-provider";
import { PostHogProvider } from "@/lib/posthog";

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <PostHogProvider>
      <LanguageProvider>{children}</LanguageProvider>
    </PostHogProvider>
  );
}
