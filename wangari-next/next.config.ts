import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "**" },
    ],
  },
  poweredByHeader: false,
  reactStrictMode: true,
  // API calls go directly to VPS via NEXT_PUBLIC_BACKEND_URL
  // No rewrites needed — the api-client handles this
};

export default nextConfig;
