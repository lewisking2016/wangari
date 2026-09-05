import type { NextConfig } from "next";

const allowedOrigins = [
  "https://wangari.imeantech.com",
  "https://api.wangari.imeantech.com",
  "http://localhost:3000",
  "http://localhost:3099",
];

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "**" },
    ],
  },
  poweredByHeader: false,
  reactStrictMode: true,
  headers: async () => [
    {
      source: "/(.*)",
      headers: [
        { key: "X-Frame-Options", value: "SAMEORIGIN" },
        { key: "X-Content-Type-Options", value: "nosniff" },
        { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
        { key: "X-XSS-Protection", value: "1; mode=block" },
        { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
        { key: "Cross-Origin-Opener-Policy", value: "unsafe-none" },
      ],
    },
    {
      source: "/api/(.*)",
      headers: [
        { key: "Access-Control-Allow-Origin", value: "*" },
        { key: "Access-Control-Allow-Methods", value: "GET, POST, PUT, PATCH, DELETE, OPTIONS" },
        { key: "Access-Control-Allow-Headers", value: "Content-Type, Authorization" },
        { key: "Access-Control-Max-Age", value: "86400" },
      ],
    },
  ],
};

export default nextConfig;
