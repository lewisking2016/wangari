import Link from "next/link";
import { WifiOff } from "lucide-react";

export const metadata = { title: "Offline — Wangari Farm OS" };

export default function OfflinePage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-wangari-cream px-4 text-center">
      <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-wangari-green-50">
        <WifiOff className="h-8 w-8 text-wangari-green-700" />
      </div>
      <h1 className="text-xl font-bold text-wangari-heading">You&apos;re offline</h1>
      <p className="mt-2 max-w-sm text-sm text-wangari-muted">
        No internet connection right now. Pages you visited recently and your last farm data are still
        available — everything will sync again when you&apos;re back online.
      </p>
      <Link
        href="/dashboard"
        className="mt-6 rounded-xl bg-wangari-green-800 px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-wangari-green-900"
      >
        Try the dashboard anyway
      </Link>
    </div>
  );
}
