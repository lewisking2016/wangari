import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import { Providers } from "./providers";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
  variable: "--font-inter",
});

export const metadata: Metadata = {
  title: {
    default: "Wangari — Farm Management Software with WhatsApp Bot | Kenya",
    template: "%s | Wangari — Smart Farm Management",
  },
  description:
    "Know your real farm profit in 30 seconds. Track poultry, crops, inventory, sales & finances from your phone or via WhatsApp. Free for 30 days. Built for Kenyan farmers.",
  keywords: [
    "farm management software Kenya",
    "poultry farm tracker",
    "WhatsApp farm bot",
    "farm profit calculator",
    "Kenya farming app",
    "livestock management",
    "farm record keeping",
    "poultry farm management",
    "farm inventory tracker",
    "African farm management",
  ],
  authors: [{ name: "iMeanTech" }],
  openGraph: {
    title: "Wangari — Know Your Real Farm Profit in 30 Seconds",
    description:
      "Track every egg, every bag of feed, every shilling. WhatsApp bot for easy data entry. AI-powered farm insights. Free for 30 days.",
    url: "https://wangari.imeantech.com",
    siteName: "Wangari",
    locale: "en_KE",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Wangari — Farm Management with WhatsApp Bot",
    description:
      "Know your real profit. Track poultry, crops, inventory via WhatsApp. Free for 30 days.",
  },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-video-preview": -1,
      "max-image-preview": "large",
      "max-snippet": -1,
    },
  },
  alternates: {
    canonical: "https://wangari.imeantech.com",
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={inter.variable} suppressHydrationWarning>
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap"
          rel="stylesheet"
        />
      </head>
      <body className="font-sans antialiased text-[#334155] bg-[#FAFBFC]" suppressHydrationWarning>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify({
              "@context": "https://schema.org",
              "@type": "SoftwareApplication",
              name: "Wangari",
              applicationCategory: "BusinessApplication",
              operatingSystem: "Web, Android, iOS",
              description: "Farm management software with WhatsApp bot for Kenyan farmers. Track poultry, crops, inventory, sales, and finances.",
              url: "https://wangari.imeantech.com",
              offers: [
                {
                  "@type": "Offer",
                  price: "0",
                  priceCurrency: "KES",
                  name: "Starter Free Trial",
                  description: "30-day free trial, no credit card",
                },
                {
                  "@type": "Offer",
                  price: "500",
                  priceCurrency: "KES",
                  name: "Starter",
                  description: "1 hub + WhatsApp bot",
                },
                {
                  "@type": "Offer",
                  price: "1500",
                  priceCurrency: "KES",
                  name: "Pro",
                  description: "3 hubs + AI + alerts",
                },
                {
                  "@type": "Offer",
                  price: "3000",
                  priceCurrency: "KES",
                  name: "Enterprise",
                  description: "All 7 hubs + priority support",
                },
              ],
              aggregateRating: {
                "@type": "AggregateRating",
                ratingValue: "4.8",
                ratingCount: "150",
              },
              author: {
                "@type": "Organization",
                name: "iMeanTech",
                url: "https://imeantech.com",
              },
            }),
          }}
        />
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
