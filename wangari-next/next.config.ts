import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "**" },
    ],
  },
  // Vercel deployment
  poweredByHeader: false,
  reactStrictMode: true,
};

export default nextConfig;
