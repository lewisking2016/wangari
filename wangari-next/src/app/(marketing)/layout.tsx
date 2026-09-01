import { MegaMenuNavbar } from "@/components/ui/mega-menu-navbar";
import { Footer } from "@/components/layout/footer";

export default function MarketingLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-white">
      <MegaMenuNavbar
        logo={
          <div className="flex items-center gap-2.5">
            <img src="/images/wangari-real-logo.png" alt="Wangari" className="h-8 w-8 rounded-full object-cover" />
            <span className="text-base font-bold text-[#0F172A] tracking-tight">Wangari</span>
          </div>
        }
      />
      <main>{children}</main>
      <Footer />
    </div>
  );
}
