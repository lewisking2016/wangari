import { Leaf } from "lucide-react";
import Link from "next/link";

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen flex">
      {/* Left — Brand panel */}
      <div className="hidden lg:flex lg:w-1/2 bg-wangari-green-800 relative overflow-hidden flex-col justify-between p-12">
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-20 left-20 h-64 w-64 rounded-full bg-wangari-green-400 blur-3xl" />
          <div className="absolute bottom-20 right-20 h-96 w-96 rounded-full bg-wangari-green-300 blur-3xl" />
        </div>
        <div className="relative z-10">
          <Link href="/" className="flex items-center gap-3 text-white">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
              <Leaf className="h-5 w-5" />
            </div>
            <span className="text-xl font-bold">Wangari</span>
          </Link>
        </div>
        <div className="relative z-10 text-white">
          <h2 className="text-3xl font-bold leading-tight">
            Grow smarter.
            <br />
            Rooted in Africa.
          </h2>
          <p className="mt-4 text-wangari-green-200 text-lg max-w-md">
            The all-in-one farm management platform. Track flocks, manage
            inventory, monitor finances, and grow your farm with confidence.
          </p>
        </div>
        <div className="relative z-10 flex gap-8 text-wangari-green-200 text-sm">
          <div>
            <p className="text-2xl font-bold text-white">50K+</p>
            <p>Farmers</p>
          </div>
          <div>
            <p className="text-2xl font-bold text-white">2M+</p>
            <p>Birds Tracked</p>
          </div>
          <div>
            <p className="text-2xl font-bold text-white">98%</p>
            <p>Satisfaction</p>
          </div>
        </div>
      </div>

      {/* Right — Form panel */}
      <div className="flex-1 flex items-center justify-center p-6 bg-white">
        <div className="w-full max-w-md">{children}</div>
      </div>
    </div>
  );
}
