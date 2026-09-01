import Link from "next/link";
import { Logo } from "@/components/ui/logo";
import { Footer } from "@/components/layout/footer";
import { Link001 } from "@/components/ui/animated-link";

export default function MarketingLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-white">
      <header className="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-[#E5E7EB]">
        <div className="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
          <Link href="/"><Logo size="md" showText={false} /></Link>
          <nav className="hidden md:flex items-center gap-8 text-sm font-semibold text-[#64748B]">
            <Link001 href="/pricing" className="text-[#64748B] hover:text-[#166534] transition-colors">Pricing</Link001>
            <Link001 href="/about" className="text-[#64748B] hover:text-[#166534] transition-colors">About</Link001>
            <Link001 href="/login" className="text-[#64748B] hover:text-[#166534] transition-colors">Sign In</Link001>
          </nav>
          <Link href="/register" className="rounded-full bg-[#166534] text-white px-5 py-2.5 text-sm font-semibold hover:bg-[#14532D] transition-colors">Get Started Free</Link>
        </div>
      </header>
      <main className="pt-16">{children}</main>
      <Footer />
    </div>
  );
}
