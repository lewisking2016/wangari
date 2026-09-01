"use client";

import * as React from "react";
import Link from "next/link";
import {
  Bird,
  BarChart3,
  Package,
  Users,
  Smartphone,
  Sparkles,
  Egg,
  DollarSign,
  ShoppingCart,
  BookOpen,
  Building2,
  Briefcase,
  ChevronDown,
  Menu,
  MessagesSquare,
  MoveRight,
  ShieldCheck,
  X,
  type LucideIcon,
} from "lucide-react";
import { cn } from "@/lib/utils";

export interface MegaMenuItem {
  title: string;
  description?: string;
  href: string;
  icon?: LucideIcon;
  badge?: string;
}

export interface MegaMenuResourceGroup {
  title: string;
  links: MegaMenuItem[];
}

export interface MegaMenuNavbarProps
  extends Omit<React.HTMLAttributes<HTMLElement>, "children"> {
  brandName?: string;
  brandHref?: string;
  logo?: React.ReactNode;
  features?: MegaMenuItem[];
  resourceGroups?: MegaMenuResourceGroup[];
  pricingHref?: string;
  loginHref?: string;
  ctaHref?: string;
  ctaLabel?: string;
}

type DesktopMenu = "features" | "resources" | null;
type MobileSection = Exclude<DesktopMenu, null>;

const WANGARI_FEATURES: MegaMenuItem[] = [
  {
    title: "Flock Management",
    description: "Track every bird from hatch date to harvest with real-time data.",
    href: "/flocks",
    icon: Bird,
  },
  {
    title: "Production Tracking",
    description: "Log daily egg collection, mortality, and feed usage in 3 taps.",
    href: "/production",
    icon: Egg,
  },
  {
    title: "Smart Analytics",
    description: "See your costs, revenue, and margins at a glance.",
    href: "/dashboard",
    icon: BarChart3,
  },
  {
    title: "Inventory Control",
    description: "Never run out of feed or medication with low-stock alerts.",
    href: "/inventory",
    icon: Package,
  },
  {
    title: "Team Management",
    description: "Manage workers, attendance, and wages from one place.",
    href: "/workers",
    icon: Users,
  },
  {
    title: "AI Assistant",
    description: "Ask your farm anything and get instant answers from your data.",
    href: "/ai",
    icon: Sparkles,
    badge: "New",
  },
];

const WANGARI_RESOURCES: MegaMenuResourceGroup[] = [
  {
    title: "Product",
    links: [
      { title: "Pricing", href: "/pricing", icon: DollarSign },
      { title: "Features", href: "/about", icon: Package },
      { title: "AI Assistant", href: "/ai", icon: Sparkles },
    ],
  },
  {
    title: "Company",
    links: [
      { title: "About Us", href: "/about", icon: Building2 },
      { title: "Contact", href: "mailto:hello@wangari.app", icon: MessagesSquare },
      { title: "Careers", href: "/about", icon: Briefcase },
    ],
  },
];

function NavAction({
  href,
  children,
  variant = "primary",
  className,
  onClick,
}: {
  href: string;
  children: React.ReactNode;
  variant?: "primary" | "ghost" | "outline";
  className?: string;
  onClick?: React.MouseEventHandler<HTMLAnchorElement>;
}) {
  return (
    <a
      href={href}
      onClick={onClick}
      className={cn(
        "inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium transition-colors",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] focus-visible:ring-offset-2",
        variant === "primary" &&
          "bg-[#166534] text-white shadow-sm hover:bg-[#14532D]",
        variant === "ghost" &&
          "text-[#64748B] hover:bg-[#F0FDF4] hover:text-[#166534]",
        variant === "outline" &&
          "border border-[#E5E7EB] bg-white text-[#0F172A] shadow-sm hover:bg-[#F0FDF4]",
        className,
      )}
    >
      {children}
    </a>
  );
}

function Brand({
  brandName,
  brandHref,
  logo,
  onNavigate,
}: {
  brandName: string;
  brandHref: string;
  logo?: React.ReactNode;
  onNavigate?: () => void;
}) {
  return (
    <Link
      href={brandHref}
      onClick={onNavigate}
      className="relative z-10 flex shrink-0 items-center gap-2.5 text-lg font-bold tracking-tight text-[#0F172A]"
    >
      {logo ? (
        <>{logo}</>
      ) : (
        <><img src="/images/wangari-real-logo.png" alt="Wangari" className="h-8 w-8 rounded-full object-cover" /><span>{brandName}</span></>
      )}
    </Link>
  );
}

function MenuTrigger({
  id,
  label,
  isOpen,
  onToggle,
  onOpen,
}: {
  id: string;
  label: string;
  isOpen: boolean;
  onToggle: () => void;
  onOpen: () => void;
}) {
  return (
    <button
      type="button"
      aria-expanded={isOpen}
      aria-controls={id}
      onClick={onToggle}
      onFocus={onOpen}
      className={cn(
        "flex items-center gap-1 rounded-md px-4 py-2 text-sm font-medium transition-colors",
        "text-[#64748B] hover:bg-[#F0FDF4] hover:text-[#166534] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]",
        isOpen && "bg-[#F0FDF4] text-[#166534]",
      )}
    >
      {label}
      <ChevronDown
        className={cn("size-3.5 opacity-60 transition-transform duration-200", isOpen && "rotate-180")}
      />
    </button>
  );
}

function FeatureGrid({ items }: { items: MegaMenuItem[] }) {
  return (
    <div className="grid grid-cols-2 gap-2">
      {items.map((item) => {
        const Icon = item.icon;
        return (
          <Link
            key={item.title}
            href={item.href}
            className="group/item flex items-start gap-3 rounded-lg p-3 transition-colors hover:bg-[#F0FDF4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
          >
            {Icon ? (
              <span className="flex size-10 shrink-0 items-center justify-center rounded-md border border-[#E5E7EB] bg-white shadow-sm transition-colors group-hover/item:border-[#BBF7D0]">
                <Icon className="size-5 text-[#166534]" />
              </span>
            ) : null}
            <span className="min-w-0">
              <span className="flex items-center gap-2">
                <span className="text-sm font-semibold text-[#0F172A]">{item.title}</span>
                {item.badge ? (
                  <span className="rounded-full border border-[#BBF7D0] bg-[#F0FDF4] px-1.5 py-0.5 text-[10px] font-medium text-[#166534]">
                    {item.badge}
                  </span>
                ) : null}
              </span>
              {item.description ? (
                <span className="mt-1 block text-xs leading-relaxed text-[#64748B]">
                  {item.description}
                </span>
              ) : null}
            </span>
          </Link>
        );
      })}
    </div>
  );
}

function DesktopDropdown({
  id,
  open,
  className,
  children,
}: {
  id: string;
  open: boolean;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <div
      id={id}
      aria-hidden={!open}
      className={cn(
        "absolute left-0 top-full z-50 pt-3 transition-all duration-200",
        open ? "visible translate-y-0 opacity-100" : "invisible translate-y-2 opacity-0 pointer-events-none",
        className,
      )}
    >
      {children}
    </div>
  );
}

function MobileAccordion({
  title,
  value,
  openSection,
  onToggle,
  children,
}: {
  title: string;
  value: MobileSection;
  openSection: MobileSection | null;
  onToggle: (value: MobileSection) => void;
  children: React.ReactNode;
}) {
  const isOpen = openSection === value;
  const contentId = `mobile-${value}-content`;

  return (
    <div className="border-b border-[#E5E7EB]">
      <button
        type="button"
        aria-expanded={isOpen}
        aria-controls={contentId}
        onClick={() => onToggle(value)}
        className="flex w-full items-center justify-between py-4 text-sm font-medium text-[#0F172A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
      >
        {title}
        <ChevronDown className={cn("size-4 text-[#64748B] transition-transform duration-200", isOpen && "rotate-180")} />
      </button>
      <div
        id={contentId}
        className={cn(
          "grid transition-[grid-template-rows,opacity] duration-300 ease-out",
          isOpen ? "grid-rows-[1fr] pb-4 opacity-100" : "grid-rows-[0fr] opacity-0",
        )}
      >
        <div className="overflow-hidden">
          <div className="ml-2 flex flex-col gap-1 border-l-2 border-[#F0FDF4] pl-3">
            {children}
          </div>
        </div>
      </div>
    </div>
  );
}

function MobileMenuItem({ item, onNavigate }: { item: MegaMenuItem; onNavigate: () => void }) {
  const Icon = item.icon;
  return (
    <Link
      href={item.href}
      onClick={onNavigate}
      className="flex items-center gap-3 rounded-lg px-2 py-2.5 text-sm text-[#64748B] transition-colors hover:bg-[#F0FDF4] hover:text-[#166534]"
    >
      {Icon ? <Icon className="size-4 text-[#166534]" /> : null}
      <span>{item.title}</span>
    </Link>
  );
}

export function MegaMenuNavbar({
  brandName = "Wangari",
  brandHref = "/",
  logo,
  features = WANGARI_FEATURES,
  resourceGroups = WANGARI_RESOURCES,
  pricingHref = "/pricing",
  loginHref = "/login",
  ctaHref = "/register",
  ctaLabel = "Get Started Free",
  className,
  ...props
}: MegaMenuNavbarProps) {
  const [openMenu, setOpenMenu] = React.useState<DesktopMenu>(null);
  const [mobileOpen, setMobileOpen] = React.useState(false);
  const navRef = React.useRef<HTMLElement>(null);
  const closeButtonRef = React.useRef<HTMLButtonElement>(null);

  React.useEffect(() => {
    const onPointerDown = (event: PointerEvent) => {
      if (!navRef.current?.contains(event.target as Node)) setOpenMenu(null);
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key !== "Escape") return;
      setOpenMenu(null);
      setMobileOpen(false);
    };
    document.addEventListener("pointerdown", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("pointerdown", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, []);

  React.useEffect(() => {
    if (!mobileOpen) return;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeButtonRef.current?.focus();
    return () => { document.body.style.overflow = previousOverflow; };
  }, [mobileOpen]);

  const closeMobile = () => { setMobileOpen(false); };
  const toggleDesktopMenu = (menu: Exclude<DesktopMenu, null>) => {
    setOpenMenu((current) => (current === menu ? null : menu));
  };

  return (
    <header
      {...props}
      ref={navRef}
      className={cn(
        "sticky top-0 z-50 w-full border-b border-[#E5E7EB] bg-white/80 backdrop-blur-lg",
        className,
      )}
      onMouseLeave={() => setOpenMenu(null)}
    >
      <div className="mx-auto max-w-7xl px-4 md:px-6">
        <div className="flex h-16 items-center justify-between">
          <div className="flex items-center gap-8">
            <Brand brandName={brandName} brandHref={brandHref} logo={logo} />

            <nav aria-label="Primary navigation" className="hidden items-center lg:flex">
              <ul className="flex items-center gap-1">
                <li className="relative" onMouseEnter={() => setOpenMenu("features")}>
                  <MenuTrigger
                    id="features-mega-menu"
                    label="Features"
                    isOpen={openMenu === "features"}
                    onToggle={() => toggleDesktopMenu("features")}
                    onOpen={() => setOpenMenu("features")}
                  />
                  <DesktopDropdown id="features-mega-menu" open={openMenu === "features"} className="w-[640px]">
                    <div className="rounded-xl border border-[#E5E7EB] bg-white p-4 shadow-xl">
                      <FeatureGrid items={features} />
                      <div className="mt-4 flex items-center justify-between border-t border-[#F0FDF4] px-2 pt-4">
                        <span className="text-sm text-[#64748B]">See all platform features</span>
                        <Link href="/about" className="inline-flex items-center gap-1 text-sm font-medium text-[#166534] hover:underline">
                          Learn more <MoveRight className="size-4" />
                        </Link>
                      </div>
                    </div>
                  </DesktopDropdown>
                </li>

                <li>
                  <Link
                    href={pricingHref}
                    className="inline-flex rounded-md px-4 py-2 text-sm font-medium text-[#64748B] transition-colors hover:bg-[#F0FDF4] hover:text-[#166534] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
                  >
                    Pricing
                  </Link>
                </li>

                <li className="relative" onMouseEnter={() => setOpenMenu("resources")}>
                  <MenuTrigger
                    id="resources-mega-menu"
                    label="Resources"
                    isOpen={openMenu === "resources"}
                    onToggle={() => toggleDesktopMenu("resources")}
                    onOpen={() => setOpenMenu("resources")}
                  />
                  <DesktopDropdown id="resources-mega-menu" open={openMenu === "resources"} className="w-[520px]">
                    <div className="grid grid-cols-2 gap-6 rounded-xl border border-[#E5E7EB] bg-white p-5 shadow-xl">
                      {resourceGroups.map((group) => (
                        <div key={group.title} className="flex flex-col gap-1">
                          <h4 className="mb-2 text-xs font-semibold uppercase tracking-wider text-[#94A3B8]">{group.title}</h4>
                          {group.links.map((item) => {
                            const Icon = item.icon;
                            return (
                              <Link
                                key={item.title}
                                href={item.href}
                                className="flex items-center gap-2 rounded-md p-2 text-sm text-[#64748B] transition-colors hover:bg-[#F0FDF4] hover:text-[#166534]"
                              >
                                {Icon ? <Icon className="size-4 text-[#166534]" /> : null}
                                {item.title}
                              </Link>
                            );
                          })}
                        </div>
                      ))}
                    </div>
                  </DesktopDropdown>
                </li>

                <li>
                  <Link
                    href="/about"
                    className="inline-flex rounded-md px-4 py-2 text-sm font-medium text-[#64748B] transition-colors hover:bg-[#F0FDF4] hover:text-[#166534] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
                  >
                    About
                  </Link>
                </li>
              </ul>
            </nav>
          </div>

          <div className="flex items-center gap-2">
            <div className="hidden items-center gap-2 lg:flex">
              <NavAction href={loginHref} variant="ghost">Sign In</NavAction>
              <NavAction href={ctaHref}>{ctaLabel}</NavAction>
            </div>
            <button
              type="button"
              aria-label="Open navigation menu"
              aria-expanded={mobileOpen}
              onClick={() => setMobileOpen(true)}
              className="flex size-10 items-center justify-center rounded-md text-[#64748B] transition-colors hover:bg-[#F0FDF4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] lg:hidden"
            >
              <Menu className="size-6" />
            </button>
          </div>
        </div>
      </div>

      {/* Mobile overlay */}
      <div
        aria-hidden={!mobileOpen}
        onClick={closeMobile}
        className={cn(
          "fixed inset-0 z-50 bg-black/60 backdrop-blur-sm transition-opacity duration-300 lg:hidden",
          mobileOpen ? "opacity-100" : "pointer-events-none opacity-0",
        )}
      />

      {/* Mobile drawer */}
      <aside
        role="dialog"
        aria-modal="true"
        aria-hidden={!mobileOpen}
        className={cn(
          "fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col bg-white p-6 shadow-2xl transition-transform duration-300 ease-out lg:hidden",
          mobileOpen ? "translate-x-0" : "translate-x-full",
        )}
      >
        <div className="mb-8 flex items-center justify-between">
          <Brand brandName={brandName} brandHref={brandHref} logo={logo} onNavigate={closeMobile} />
          <button
            ref={closeButtonRef}
            type="button"
            onClick={closeMobile}
            aria-label="Close navigation menu"
            className="flex size-10 items-center justify-center rounded-md text-[#64748B] transition-colors hover:bg-[#F0FDF4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
          >
            <X className="size-5" />
          </button>
        </div>

        <nav aria-label="Mobile navigation" className="flex-1 overflow-y-auto">
          <div className="space-y-1">
            <p className="text-xs font-semibold uppercase tracking-wider text-[#94A3B8] mt-2 mb-2">Features</p>
            {features.map((item) => (
              <MobileMenuItem key={item.title} item={item} onNavigate={closeMobile} />
            ))}

            <Link href={pricingHref} onClick={closeMobile} className="block rounded-lg px-3 py-3 text-sm font-medium text-[#0F172A] hover:bg-[#F0FDF4] hover:text-[#166534] transition-colors">
              Pricing
            </Link>

            <p className="text-xs font-semibold uppercase tracking-wider text-[#94A3B8] mt-4 mb-2">Resources</p>
            {resourceGroups.flatMap((group) =>
              group.links.map((item) => (
                <MobileMenuItem key={`${group.title}-${item.title}`} item={item} onNavigate={closeMobile} />
              )),
            )}

            <Link href="/about" onClick={closeMobile} className="block rounded-lg px-3 py-3 text-sm font-medium text-[#0F172A] hover:bg-[#F0FDF4] hover:text-[#166534] transition-colors">
              About
            </Link>
          </div>
        </nav>

        <div className="mt-auto grid grid-cols-2 gap-3 pt-6">
          <NavAction href={loginHref} variant="outline" className="w-full" onClick={closeMobile}>Sign In</NavAction>
          <NavAction href={ctaHref} className="w-full" onClick={closeMobile}>{ctaLabel}</NavAction>
        </div>
      </aside>
    </header>
  );
}

export default MegaMenuNavbar;
