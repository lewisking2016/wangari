import { MegaMenuNavbar } from "@/components/ui/mega-menu-navbar";
import { Footer } from "@/components/layout/footer";

export default function MarketingLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-white">
      <MegaMenuNavbar />
      <main className="pt-16">{children}</main>
      <Footer />
    </div>
  );
}
