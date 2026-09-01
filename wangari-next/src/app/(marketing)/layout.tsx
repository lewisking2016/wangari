import Link from "next/link";
import { Leaf } from "lucide-react";

export default function MarketingLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-white">
      <header className="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-wangari-border">
        <div className="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
          <Link href="/" className="flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-wangari-green-800">
              <Leaf className="h-4 w-4 text-white" />
            </div>
            <span className="text-lg font-bold text-wangari-heading">Wangari</span>
          </Link>
          <nav className="hidden md:flex items-center gap-8 text-sm font-medium text-wangari-muted">
            <Link href="/pricing" className="hover:text-wangari-green-800 transition-colors">Pricing</Link>
            <Link href="/about" className="hover:text-wangari-green-800 transition-colors">About</Link>
            <Link href="/login" className="hover:text-wangari-green-800 transition-colors">Sign In</Link>
          </nav>
          <Link href="/register" className="rounded-full bg-wangari-green-800 text-white px-5 py-2.5 text-sm font-semibold hover:bg-wangari-green-900 transition-colors">
            Get Started Free
          </Link>
        </div>
      </header>
      <main className="pt-16">{children}</main>
    </div>
  );
}
