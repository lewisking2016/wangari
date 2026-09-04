"use client";

import * as React from "react";
import { AlertTriangle, RefreshCw } from "lucide-react";

interface Props {
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends React.Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error) {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error("ErrorBoundary caught:", error, errorInfo);
    // Send to monitoring service here
  }

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) return this.props.fallback;
      return (
        <div className="flex flex-col items-center justify-center min-h-[300px] p-8 text-center">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-500 mb-4">
            <AlertTriangle className="h-6 w-6" />
          </div>
          <h3 className="text-sm font-bold text-[#0F172A] mb-1">Something went wrong</h3>
          <p className="text-xs text-[#64748B] mb-4 max-w-sm">
            An unexpected error occurred. Please try refreshing the page.
          </p>
          <button
            onClick={() => { this.setState({ hasError: false, error: null }); window.location.reload(); }}
            className="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-[#14532D] cursor-pointer"
          >
            <RefreshCw className="h-3.5 w-3.5" /> Refresh
          </button>
        </div>
      );
    }
    return this.props.children;
  }
}
