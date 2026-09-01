import Link from "next/link";
import { Logo } from "@/components/ui/logo";

export default function MarketingLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-white">
      <header className="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-[#E5E7EB]">
        <div className="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
          <Link href="/"><Logo size="md" /></Link>
          <nav className="hidden md:flex items-center gap-8 text-sm font-medium text-[#64748B]">
            <Link href="/pricing" className="hover:text-[#166534] transition-colors">Pricing</Link>
            <Link href="/about" className="hover:text-[#166534] transition-colors">About</Link>
            <Link href="/login" className="hover:text-[#166534] transition-colors">Sign In</Link>
          </nav>
          <Link href="/register" className="rounded-full bg-[#166534] text-white px-5 py-2.5 text-sm font-semibold hover:bg-[#14532D] transition-colors">Get Started Free</Link>
        </div>
      </header>
      <main className="pt-16">{children}</main>
    </div>
  );
}
