interface GoogleAccounts {
  id: {
    initialize(config: {
      client_id: string;
      callback: (response: { credential: string }) => void;
      auto_select?: boolean;
      cancel_on_tap_outside?: boolean;
    }): void;
    renderButton(
      element: HTMLElement,
      config: {
        theme?: "outline" | "filled_blue" | "filled_black";
        size?: "large" | "medium" | "small";
        width?: number | string;
        text?: "signin_with" | "signup_with" | "continue_with" | "signin";
        shape?: "rectangular" | "pill" | "circle" | "square";
        logo_alignment?: "left" | "center";
      }
    ): void;
    prompt(): void;
  };
}

interface Window {
  google?: {
    accounts: GoogleAccounts;
  };
}
