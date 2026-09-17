"use client";

import * as React from "react";
import { LanguageProvider } from "@/components/language-provider";
import { PostHogProvider } from "@/lib/posthog";
import { OfflineProvider } from "@/components/offline-provider";

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <PostHogProvider>
      <OfflineProvider>
        <LanguageProvider>{children}</LanguageProvider>
      </OfflineProvider>
    </PostHogProvider>
  );
}
