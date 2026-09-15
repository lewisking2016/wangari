// Unified invoice + receipt template system.
//
// One template choice drives BOTH invoices and sale receipts ("all at once"):
//   - farm_invoice_template  → applied to every invoice print
//   - farm_receipt_template  → "same" (default) or a specific template id
// Inspired by Manta's single accent-color + template setting and the LaTeX
// template collection's formal single/multi-page invoice structures.

export interface InvoiceTemplate {
  id: string;
  name: string;
  description: string;
  preview: string; // short description for preview card
  color: string;   // default accent color
}

export const INVOICE_TEMPLATES: InvoiceTemplate[] = [
  {
    id: "professional",
    name: "Professional",
    description: "Clean corporate look with green branding — ideal for established farms",
    preview: "Green header, structured layout, farm logo prominent",
    color: "#166534",
  },
  {
    id: "simple",
    name: "Simple & Clean",
    description: "Minimal design — easy to read, quick to print",
    preview: "White background, thin borders, no frills",
    color: "#334155",
  },
  {
    id: "detailed",
    name: "Detailed Farm Invoice",
    description: "Full farm details, payment terms, bank info — for formal transactions",
    preview: "Gradient header, From/To grid, bank details, T&C",
    color: "#1E3A5F",
  },
  {
    id: "minimal",
    name: "Minimal",
    description: "Manta-inspired typography-first layout with an accent-color band",
    preview: "Accent band, bold name, airy spacing",
    color: "#166534",
  },
  {
    id: "business",
    name: "Business",
    description: "Formal multi-block layout for registered businesses — like the classic LaTeX invoice",
    preview: "From/To columns, ruled tables, formal footer",
    color: "#1E3A5F",
  },
  {
    id: "thermal",
    name: "Thermal Slip",
    description: "Compact 80mm receipt style — perfect for counter printers and quick sales",
    preview: "Narrow monospace slip, dashed rules",
    color: "#0F172A",
  },
];

export interface FarmProfile {
  businessName: string;
  logoUrl: string;
  phone: string;
  email: string;
  address: string;
  tinNumber: string;
  slogan: string;
  bankName: string;
  bankAccount: string;
  bankBranch: string;
  invoiceNotes: string;
  invoiceTerms: string;
  /** Accent color override (Manta-style). Empty = template default. */
  accentColor?: string;
}

export function getDefaultFarmProfile(): FarmProfile {
  return {
    businessName: "",
    logoUrl: "",
    phone: "",
    email: "",
    address: "",
    tinNumber: "",
    slogan: "",
    bankName: "",
    bankAccount: "",
    bankBranch: "",
    invoiceNotes: "",
    invoiceTerms: "",
    accentColor: "",
  };
}

/** Resolve the effective accent color: profile override > template default. */
export function resolveAccent(templateId: string, profile: FarmProfile): string {
  if (profile.accentColor && /^#[0-9a-fA-F]{6}$/.test(profile.accentColor)) {
    return profile.accentColor;
  }
  return INVOICE_TEMPLATES.find((t) => t.id === templateId)?.color || "#166534";
}

/** Which template to use for a receipt: "same" follows the invoice template. */
export function resolveReceiptTemplate(
  receiptSetting: string | undefined | null,
  invoiceTemplateId: string
): string {
  if (!receiptSetting || receiptSetting === "same") return invoiceTemplateId;
  return INVOICE_TEMPLATES.some((t) => t.id === receiptSetting) ? receiptSetting : invoiceTemplateId;
}

function formatKES(amount: number): string {
  return `KES ${amount.toLocaleString()}`;
}

function escapeHtml(str: string): string {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function safeDate(value: any): string {
  const d = new Date(value);
  if (isNaN(d.getTime())) return "—";
  return d.toLocaleDateString("en-KE", { year: "numeric", month: "long", day: "numeric" });
}

function statusBadgeHtml(status: string, accent: string): string {
  const s = String(status || "pending");
  const style =
    s === "paid"
      ? "background:#F0FDF4;color:#166534;border:1px solid #BBF7D0;"
      : s === "partial"
      ? "background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;"
      : "background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;";
  return `<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;${style}">${escapeHtml(s)}</span>`;
}

function logoOrInitial(profile: FarmProfile, farmName: string, accent: string): string {
  if (profile.logoUrl) {
    return `<img src="${escapeHtml(profile.logoUrl)}" alt="Logo" style="height:60px;object-fit:contain;" onerror="this.outerHTML='<div style=&quot;width:60px;height:60px;border-radius:12px;background:${accent};display:flex;align-items:center;justify-content:center;color:white;font-size:24px;font-weight:bold;&quot;>${escapeHtml(farmName.charAt(0).toUpperCase())}</div>'" />`;
  }
  return `<div style="width:60px;height:60px;border-radius:12px;background:${accent};display:flex;align-items:center;justify-content:center;color:white;font-size:24px;font-weight:bold;">${escapeHtml(farmName.charAt(0).toUpperCase())}</div>`;
}

function contactLinesOf(profile: FarmProfile): string[] {
  const lines: string[] = [];
  if (profile.phone) lines.push(`📞 ${escapeHtml(profile.phone)}`);
  if (profile.email) lines.push(`✉️ ${escapeHtml(profile.email)}`);
  if (profile.address) lines.push(`📍 ${escapeHtml(profile.address)}`);
  if (profile.tinNumber) lines.push(`TIN/PIN: ${escapeHtml(profile.tinNumber)}`);
  return lines;
}

function baseDoc(title: string, body: string, pageWidth = "800px"): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>${escapeHtml(title)}</title>
  <style>
    @media print { body { margin: 0; padding: 0; } .no-print { display: none !important; } }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #334155; background: white; }
    .page { max-width: ${pageWidth}; margin: 0 auto; padding: 40px; }
  </style>
</head>
<body>
  <div class="page">${body}
  </div>
</body>
</html>`;
}

function itemsTableHtml(items: any[], accent: string, cols: Array<{ key: string; label: string; align: string }>, zebra = false): string {
  const head = cols
    .map((c) => `<th style="padding:12px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:${c.align};background:${c.key === "name" ? accent : "transparent"};">${c.label}</th>`)
    .join("");
  // Header row uses a single accent background; per-column background above is ignored.
  const headRow = cols
    .map((c) => `<th style="padding:12px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:${c.align};">${c.label}</th>`)
    .join("");
  const rows = (Array.isArray(items) ? items : [])
    .map((item: any, i: number) => {
      const bg = zebra && i % 2 === 1 ? "background:#F8FAFC;" : "";
      return `<tr style="border-bottom:1px solid #E5E7EB;${bg}">
      ${cols
        .map((c) => {
          const align = `text-align:${c.align};`;
          if (c.key === "i") return `<td style="padding:12px 16px;font-size:13px;color:#94A3B8;${align}${bg}">${i + 1}</td>`;
          if (c.key === "name") return `<td style="padding:12px 16px;font-size:13px;color:#0F172A;font-weight:600;${align}${bg}">${escapeHtml(item.name || "Item")}</td>`;
          if (c.key === "qty") return `<td style="padding:12px 16px;font-size:13px;color:#64748B;${align}${bg}">${item.quantity || 1}</td>`;
          if (c.key === "price") return `<td style="padding:12px 16px;font-size:13px;color:#64748B;${align}${bg}">${formatKES(Number(item.price || 0))}</td>`;
          return `<td style="padding:12px 16px;font-size:13px;color:#0F172A;font-weight:600;${align}${bg}">${formatKES(Number(item.quantity || 1) * Number(item.price || 0))}</td>`;
        })
        .join("")}
    </tr>`;
    })
    .join("");
  return `<table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
    <thead><tr style="background:${accent};">${headRow}</tr></thead>
    <tbody>${rows || `<tr><td colspan="${cols.length}" style="padding:24px;text-align:center;color:#94A3B8;font-size:13px;">No items</td></tr>`}</tbody>
  </table>`;
}

function totalsHtml(total: number, paid: number, compact = false): string {
  const balance = total - paid;
  const row = (label: string, value: string, extra = "") =>
    `<div style="display:flex;justify-content:space-between;padding:${compact ? 8 : 10}px 0;border-bottom:1px solid #E5E7EB;${extra}">
      <span style="font-size:13px;color:#64748B;">${label}</span>
      <span style="font-size:13px;font-weight:600;color:#0F172A;">${value}</span>
    </div>`;
  const balanceBlock =
    balance > 0
      ? `<div style="display:flex;justify-content:space-between;padding:12px 16px;background:#FEF2F2;border-radius:8px;margin-top:8px;">
          <span style="font-size:14px;font-weight:700;color:#DC2626;">Balance Due</span>
          <span style="font-size:18px;font-weight:800;color:#DC2626;">${formatKES(balance)}</span>
        </div>`
      : `<div style="display:flex;justify-content:space-between;padding:12px 16px;background:#F0FDF4;border-radius:8px;margin-top:8px;">
          <span style="font-size:14px;font-weight:700;color:#166534;">✓ Fully Paid</span>
        </div>`;
  return `<div style="display:flex;justify-content:flex-end;margin-bottom:32px;">
    <div style="min-width:260px;">
      ${row("Subtotal", formatKES(total))}
      ${row("Amount Paid", `<span style="color:#166534;">${formatKES(paid)}</span>`)}
      ${balanceBlock}
    </div>
  </div>`;
}

function bankDetailsHtml(profile: FarmProfile, accent: string): string {
  if (!profile.bankName) return "";
  return `<div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:12px;padding:20px;margin-bottom:24px;">
    <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:12px;">Payment Details</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
      <div><span style="color:#64748B;">Bank:</span> <span style="font-weight:600;color:#0F172A;">${escapeHtml(profile.bankName)}</span></div>
      ${profile.bankAccount ? `<div><span style="color:#64748B;">Account:</span> <span style="font-weight:600;color:#0F172A;">${escapeHtml(profile.bankAccount)}</span></div>` : ""}
      ${profile.bankBranch ? `<div><span style="color:#64748B;">Branch:</span> <span style="font-weight:600;color:#0F172A;">${escapeHtml(profile.bankBranch)}</span></div>` : ""}
    </div>
  </div>`;
}

function notesTermsHtml(profile: FarmProfile): string {
  return `${profile.invoiceNotes ? `<div style="margin-bottom:24px;">
    <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:6px;">Notes</p>
    <p style="font-size:12px;color:#64748B;line-height:1.6;">${escapeHtml(profile.invoiceNotes)}</p>
  </div>` : ""}
  ${profile.invoiceTerms ? `<div style="margin-bottom:32px;">
    <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:6px;">Terms &amp; Conditions</p>
    <p style="font-size:11px;color:#94A3B8;line-height:1.6;">${escapeHtml(profile.invoiceTerms)}</p>
  </div>` : ""}`;
}

/* ────────────────────────────────────────────────────────────
   Invoice generator — one function, all templates
   ──────────────────────────────────────────────────────────── */
export function generateInvoiceHtml(invoice: any, templateId: string, profile: FarmProfile): string {
  const items = Array.isArray(invoice.items) ? invoice.items : [];
  const total = Number(invoice.totalAmount);
  const paid = Number(invoice.amountPaid);
  const farmName = profile.businessName || "My Farm";
  const accent = resolveAccent(templateId, profile);
  const contactLines = contactLinesOf(profile);
  const customerName = escapeHtml(invoice.customer?.name || "Walk-in Customer");
  const customerPhone = invoice.customer?.phone ? escapeHtml(invoice.customer.phone) : "";
  const invoiceDate = safeDate(invoice.createdAt);
  const dueDate = invoice.dueDate ? safeDate(invoice.dueDate) : null;
  const tId = INVOICE_TEMPLATES.some((t) => t.id === templateId) ? templateId : "professional";

  /* ── PROFESSIONAL ── */
  if (tId === "professional") {
    const body = `
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:24px;border-bottom:3px solid ${accent};">
      <div style="display:flex;align-items:center;gap:16px;">
        ${logoOrInitial(profile, farmName, accent)}
        <div>
          <h1 style="font-size:22px;font-weight:800;color:${accent};letter-spacing:-0.5px;">${escapeHtml(farmName)}</h1>
          ${profile.slogan ? `<p style="font-size:12px;color:#64748B;margin-top:2px;font-style:italic;">${escapeHtml(profile.slogan)}</p>` : ""}
          <div style="margin-top:8px;font-size:11px;color:#94A3B8;line-height:1.6;">${contactLines.map((l) => `<span style="display:block;">${l}</span>`).join("")}</div>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="background:${accent};color:white;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:700;letter-spacing:1px;">INVOICE</div>
        <p style="margin-top:12px;font-size:14px;font-weight:700;color:#0F172A;">${escapeHtml(invoice.invoiceNumber)}</p>
        <p style="font-size:12px;color:#94A3B8;margin-top:4px;">Date: ${invoiceDate}</p>
        ${dueDate ? `<p style="font-size:12px;color:#94A3B8;">Due: ${dueDate}</p>` : ""}
        <div style="margin-top:8px;">${statusBadgeHtml(invoice.paymentStatus, accent)}</div>
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:32px;">
      <div style="background:#F8FAFC;border-radius:12px;padding:20px;min-width:280px;">
        <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:8px;">Bill To</p>
        <p style="font-size:16px;font-weight:700;color:#0F172A;">${customerName}</p>
        ${customerPhone ? `<p style="font-size:13px;color:#64748B;margin-top:4px;">${customerPhone}</p>` : ""}
      </div>
      <div style="text-align:right;">
        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:16px 24px;">
          <p style="font-size:11px;color:#64748B;text-transform:uppercase;letter-spacing:1px;">Total Amount</p>
          <p style="font-size:28px;font-weight:800;color:${accent};margin-top:4px;">${formatKES(total)}</p>
        </div>
      </div>
    </div>
    ${itemsTableHtml(items, accent, [
      { key: "i", label: "#", align: "left" },
      { key: "name", label: "Item", align: "left" },
      { key: "qty", label: "Qty", align: "center" },
      { key: "price", label: "Price", align: "right" },
      { key: "amount", label: "Total", align: "right" },
    ])}
    ${totalsHtml(total, paid)}
    ${bankDetailsHtml(profile, accent)}
    ${notesTermsHtml(profile)}
    <div style="text-align:center;padding-top:24px;border-top:2px solid ${accent};">
      <p style="font-size:12px;color:${accent};font-weight:600;">Thank you for your business!</p>
      <p style="font-size:10px;color:#94A3B8;margin-top:4px;">${escapeHtml(farmName)} · Generated by Wangari Farm OS</p>
    </div>`;
    return baseDoc(invoice.invoiceNumber, body);
  }

  /* ── SIMPLE ── */
  if (tId === "simple") {
    const body = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:40px;">
      <div style="display:flex;align-items:center;gap:12px;">
        ${logoOrInitial(profile, farmName, accent)}
        <div>
          <h1 style="font-size:20px;font-weight:700;color:#0F172A;">${escapeHtml(farmName)}</h1>
          ${contactLines.length > 0 ? `<p style="font-size:11px;color:#94A3B8;margin-top:2px;">${contactLines.join(" · ")}</p>` : ""}
        </div>
      </div>
      <div style="text-align:right;">
        <p style="font-size:24px;font-weight:800;color:#0F172A;">INVOICE</p>
        <p style="font-size:13px;color:#64748B;margin-top:4px;">${escapeHtml(invoice.invoiceNumber)}</p>
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:32px;padding:16px 0;border-top:1px solid #E5E7EB;border-bottom:1px solid #E5E7EB;">
      <div>
        <p style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:1px;">Bill To</p>
        <p style="font-size:15px;font-weight:700;color:#0F172A;margin-top:4px;">${customerName}</p>
        ${customerPhone ? `<p style="font-size:12px;color:#64748B;margin-top:2px;">${customerPhone}</p>` : ""}
      </div>
      <div style="text-align:right;">
        <p style="font-size:11px;color:#94A3B8;">Date: <span style="color:#0F172A;font-weight:600;">${invoiceDate}</span></p>
        ${dueDate ? `<p style="font-size:11px;color:#94A3B8;margin-top:2px;">Due: <span style="color:#0F172A;font-weight:600;">${dueDate}</span></p>` : ""}
        <p style="margin-top:6px;">${statusBadgeHtml(invoice.paymentStatus, accent)}</p>
      </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
      <thead>
        <tr style="border-bottom:2px solid #0F172A;">
          <th style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748B;text-align:left;">Item</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748B;text-align:center;">Qty</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748B;text-align:right;">Price</th>
          <th style="padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748B;text-align:right;">Total</th>
        </tr>
      </thead>
      <tbody>
        ${items.map((item: any) => `
        <tr style="border-bottom:1px solid #F1F5F9;">
          <td style="padding:12px 16px;font-size:13px;color:#0F172A;font-weight:500;">${escapeHtml(item.name || "Item")}</td>
          <td style="padding:12px 16px;font-size:13px;color:#64748B;text-align:center;">${item.quantity || 1}</td>
          <td style="padding:12px 16px;font-size:13px;color:#64748B;text-align:right;">${formatKES(Number(item.price || 0))}</td>
          <td style="padding:12px 16px;font-size:13px;color:#0F172A;font-weight:600;text-align:right;">${formatKES(Number(item.quantity || 1) * Number(item.price || 0))}</td>
        </tr>`).join("") || `<tr><td colspan="4" style="padding:24px;text-align:center;color:#94A3B8;">No items</td></tr>`}
      </tbody>
    </table>
    ${totalsHtml(total, paid)}
    ${profile.bankName ? `<div style="margin-top:24px;padding:16px;background:#F8FAFC;border-radius:8px;font-size:12px;color:#64748B;">
      <strong>Payment:</strong> ${escapeHtml(profile.bankName)}${profile.bankAccount ? ` · A/C ${escapeHtml(profile.bankAccount)}` : ""}${profile.bankBranch ? ` · ${escapeHtml(profile.bankBranch)}` : ""}
    </div>` : ""}
    ${profile.invoiceNotes ? `<p style="margin-top:16px;font-size:11px;color:#94A3B8;text-align:center;">${escapeHtml(profile.invoiceNotes)}</p>` : ""}
    <p style="margin-top:24px;text-align:center;font-size:10px;color:#CBD5E1;">${escapeHtml(farmName)} · Wangari Farm OS</p>`;
    return baseDoc(invoice.invoiceNumber, body);
  }

  /* ── DETAILED ── */
  if (tId === "detailed") {
    const body = `
    <div style="background:linear-gradient(135deg,${accent} 0%,#166534 100%);color:white;padding:32px;border-radius:16px;margin-bottom:32px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div style="display:flex;align-items:center;gap:16px;">
          ${profile.logoUrl
            ? `<img src="${escapeHtml(profile.logoUrl)}" alt="Logo" style="height:64px;object-fit:contain;border-radius:8px;background:white;padding:4px;" />`
            : `<div style="width:64px;height:64px;border-radius:12px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:bold;">${escapeHtml(farmName.charAt(0).toUpperCase())}</div>`}
          <div>
            <h1 style="font-size:24px;font-weight:800;letter-spacing:-0.5px;">${escapeHtml(farmName)}</h1>
            ${profile.slogan ? `<p style="font-size:13px;opacity:0.8;margin-top:4px;font-style:italic;">${escapeHtml(profile.slogan)}</p>` : ""}
          </div>
        </div>
        <div style="text-align:right;">
          <div style="background:rgba(255,255,255,0.15);padding:8px 20px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:2px;">TAX INVOICE</div>
          <p style="margin-top:12px;font-size:16px;font-weight:700;">${escapeHtml(invoice.invoiceNumber)}</p>
          <p style="font-size:12px;opacity:0.7;margin-top:4px;">${invoiceDate}</p>
          ${dueDate ? `<p style="font-size:12px;opacity:0.7;">Due: ${dueDate}</p>` : ""}
        </div>
      </div>
      <div style="margin-top:20px;display:flex;gap:24px;font-size:12px;opacity:0.8;flex-wrap:wrap;">
        ${contactLines.map((l) => `<span>${l}</span>`).join("")}
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">
      <div>
        <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;margin-bottom:8px;">From</p>
        <p style="font-size:15px;font-weight:700;color:#0F172A;">${escapeHtml(farmName)}</p>
        ${profile.address ? `<p style="font-size:12px;color:#64748B;margin-top:4px;">${escapeHtml(profile.address)}</p>` : ""}
        ${profile.tinNumber ? `<p style="font-size:12px;color:#64748B;margin-top:2px;">TIN: ${escapeHtml(profile.tinNumber)}</p>` : ""}
      </div>
      <div>
        <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;margin-bottom:8px;">Bill To</p>
        <p style="font-size:15px;font-weight:700;color:#0F172A;">${customerName}</p>
        ${customerPhone ? `<p style="font-size:12px;color:#64748B;margin-top:4px;">${customerPhone}</p>` : ""}
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;background:#F8FAFC;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
      <div style="display:flex;gap:24px;font-size:13px;flex-wrap:wrap;">
        <div><span style="color:#94A3B8;">Invoice:</span> <strong>${escapeHtml(invoice.invoiceNumber)}</strong></div>
        <div><span style="color:#94A3B8;">Date:</span> <strong>${invoiceDate}</strong></div>
        ${dueDate ? `<div><span style="color:#94A3B8;">Due:</span> <strong>${dueDate}</strong></div>` : ""}
      </div>
      ${statusBadgeHtml(invoice.paymentStatus, accent)}
    </div>
    ${itemsTableHtml(items, accent, [
      { key: "i", label: "#", align: "left" },
      { key: "name", label: "Description", align: "left" },
      { key: "qty", label: "Qty", align: "center" },
      { key: "price", label: "Unit Price", align: "right" },
      { key: "amount", label: "Amount", align: "right" },
    ], true)}
    ${totalsHtml(total, paid)}
    ${bankDetailsHtml(profile, accent)}
    ${notesTermsHtml(profile)}
    <div style="text-align:center;padding-top:24px;border-top:2px solid ${accent};">
      <p style="font-size:13px;color:${accent};font-weight:600;">${escapeHtml(farmName)}</p>
      ${profile.phone || profile.email ? `<p style="font-size:11px;color:#94A3B8;margin-top:4px;">${[profile.phone, profile.email].filter(Boolean).join(" · ")}</p>` : ""}
      <p style="font-size:10px;color:#CBD5E1;margin-top:8px;">Generated by Wangari Farm OS</p>
    </div>`;
    return baseDoc(invoice.invoiceNumber, body);
  }

  /* ── MINIMAL (Manta-inspired) ── */
  if (tId === "minimal") {
    const body = `
    <div style="background:${accent};height:6px;border-radius:3px;margin-bottom:36px;"></div>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px;">
      <h1 style="font-size:32px;font-weight:300;color:#0F172A;letter-spacing:-0.5px;">${escapeHtml(farmName)}</h1>
      <div style="text-align:right;">
        <p style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:2px;">Invoice</p>
        <p style="font-size:15px;font-weight:700;color:#0F172A;">${escapeHtml(invoice.invoiceNumber)}</p>
      </div>
    </div>
    ${profile.slogan ? `<p style="font-size:12px;color:#94A3B8;font-style:italic;margin-bottom:28px;">${escapeHtml(profile.slogan)}</p>` : `<div style="margin-bottom:28px;"></div>`}
    <div style="display:flex;justify-content:space-between;margin-bottom:36px;font-size:13px;">
      <div>
        <p style="font-size:10px;color:#94A3B8;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">Billed To</p>
        <p style="font-weight:700;color:#0F172A;">${customerName}</p>
        ${customerPhone ? `<p style="color:#64748B;margin-top:2px;">${customerPhone}</p>` : ""}
        <p style="color:#94A3B8;margin-top:10px;">Issued ${invoiceDate}${dueDate ? ` · Due ${dueDate}` : ""}</p>
      </div>
      <div style="text-align:right;">
        <p style="font-size:10px;color:#94A3B8;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">Total</p>
        <p style="font-size:30px;font-weight:800;color:${accent};">${formatKES(total)}</p>
        <p style="margin-top:6px;">${statusBadgeHtml(invoice.paymentStatus, accent)}</p>
      </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:28px;">
      <thead>
        <tr>
          <th style="padding:10px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;text-align:left;border-bottom:1px solid #E5E7EB;">Description</th>
          <th style="padding:10px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;text-align:center;border-bottom:1px solid #E5E7EB;">Qty</th>
          <th style="padding:10px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;text-align:right;border-bottom:1px solid #E5E7EB;">Amount</th>
        </tr>
      </thead>
      <tbody>
        ${items.map((item: any) => `
        <tr>
          <td style="padding:12px 8px;font-size:13px;color:#0F172A;border-bottom:1px solid #F1F5F9;">${escapeHtml(item.name || "Item")}</td>
          <td style="padding:12px 8px;font-size:13px;color:#64748B;text-align:center;border-bottom:1px solid #F1F5F9;">${item.quantity || 1}</td>
          <td style="padding:12px 8px;font-size:13px;color:#0F172A;font-weight:600;text-align:right;border-bottom:1px solid #F1F5F9;">${formatKES(Number(item.quantity || 1) * Number(item.price || 0))}</td>
        </tr>`).join("") || `<tr><td colspan="3" style="padding:24px;text-align:center;color:#94A3B8;">No items</td></tr>`}
      </tbody>
    </table>
    ${totalsHtml(total, paid)}
    ${profile.invoiceNotes ? `<p style="font-size:11px;color:#94A3B8;margin-bottom:20px;">${escapeHtml(profile.invoiceNotes)}</p>` : ""}
    <p style="text-align:center;font-size:10px;color:#CBD5E1;border-top:1px solid #F1F5F9;padding-top:20px;">${escapeHtml(farmName)} · Wangari Farm OS</p>`;
    return baseDoc(invoice.invoiceNumber, body);
  }

  /* ── BUSINESS (LaTeX-collection-inspired formal layout) ── */
  if (tId === "business") {
    const body = `
    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0F172A;padding-bottom:20px;margin-bottom:24px;">
      <div style="display:flex;align-items:center;gap:14px;">
        ${logoOrInitial(profile, farmName, accent)}
        <div>
          <p style="font-size:18px;font-weight:800;color:#0F172A;">${escapeHtml(farmName)}</p>
          ${profile.address ? `<p style="font-size:11px;color:#64748B;margin-top:2px;">${escapeHtml(profile.address)}</p>` : ""}
          ${profile.tinNumber ? `<p style="font-size:11px;color:#64748B;">TIN/PIN: ${escapeHtml(profile.tinNumber)}</p>` : ""}
        </div>
      </div>
      <div style="text-align:right;font-size:12px;color:#334155;">
        <p style="font-size:20px;font-weight:800;letter-spacing:3px;color:#0F172A;">INVOICE</p>
        <p style="margin-top:6px;">No. <strong>${escapeHtml(invoice.invoiceNumber)}</strong></p>
        <p>Date: <strong>${invoiceDate}</strong></p>
        ${dueDate ? `<p>Due: <strong>${dueDate}</strong></p>` : ""}
        <p style="margin-top:4px;">${statusBadgeHtml(invoice.paymentStatus, accent)}</p>
      </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:28px;font-size:12px;">
      <tr>
        <td style="width:50%;vertical-align:top;padding-right:16px;">
          <div style="background:#F8FAFC;border-left:3px solid ${accent};padding:12px 16px;">
            <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;margin-bottom:4px;">From</p>
            <p style="font-weight:700;color:#0F172A;">${escapeHtml(farmName)}</p>
            ${profile.phone ? `<p style="color:#64748B;margin-top:2px;">${escapeHtml(profile.phone)}</p>` : ""}
            ${profile.email ? `<p style="color:#64748B;">${escapeHtml(profile.email)}</p>` : ""}
          </div>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:16px;">
          <div style="background:#F8FAFC;border-left:3px solid #94A3B8;padding:12px 16px;">
            <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;margin-bottom:4px;">To</p>
            <p style="font-weight:700;color:#0F172A;">${customerName}</p>
            ${customerPhone ? `<p style="color:#64748B;margin-top:2px;">${customerPhone}</p>` : ""}
          </div>
        </td>
      </tr>
    </table>
    ${itemsTableHtml(items, accent, [
      { key: "i", label: "#", align: "left" },
      { key: "name", label: "Description", align: "left" },
      { key: "qty", label: "Qty", align: "center" },
      { key: "price", label: "Unit Price", align: "right" },
      { key: "amount", label: "Amount", align: "right" },
    ])}
    ${totalsHtml(total, paid)}
    ${bankDetailsHtml(profile, accent)}
    ${notesTermsHtml(profile)}
    <div style="display:flex;justify-content:space-between;align-items:center;border-top:2px solid #0F172A;padding-top:16px;margin-top:8px;">
      <p style="font-size:11px;color:#64748B;">${escapeHtml(farmName)} — Wangari Farm OS</p>
      <p style="font-size:11px;color:#64748B;font-style:italic;">Thank you for your business.</p>
    </div>`;
    return baseDoc(invoice.invoiceNumber, body);
  }

  /* ── THERMAL SLIP (80mm receipt style) ── */
  const slip = `
    <div style="font-family:'Courier New',monospace;font-size:12px;color:#0F172A;max-width:320px;margin:0 auto;">
      <div style="text-align:center;border-bottom:1px dashed #0F172A;padding-bottom:10px;margin-bottom:10px;">
        <p style="font-size:15px;font-weight:800;">${escapeHtml(farmName.toUpperCase())}</p>
        ${profile.phone ? `<p style="font-size:11px;">Tel: ${escapeHtml(profile.phone)}</p>` : ""}
        ${profile.tinNumber ? `<p style="font-size:11px;">PIN: ${escapeHtml(profile.tinNumber)}</p>` : ""}
      </div>
      <div style="display:flex;justify-content:space-between;font-size:11px;">
        <span>${escapeHtml(invoice.invoiceNumber)}</span>
        <span>${new Date(invoice.createdAt).toLocaleDateString("en-KE")}</span>
      </div>
      <div style="font-size:11px;margin-bottom:8px;">Customer: ${customerName}</div>
      <div style="border-top:1px dashed #0F172A;border-bottom:1px dashed #0F172A;padding:8px 0;margin-bottom:8px;">
        ${(Array.isArray(items) ? items : []).map((item: any) => `
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span>${escapeHtml(item.name || "Item")} x${item.quantity || 1}</span>
          <span>${formatKES(Number(item.quantity || 1) * Number(item.price || 0))}</span>
        </div>`).join("") || `<div>No items</div>`}
      </div>
      <div style="display:flex;justify-content:space-between;font-weight:800;font-size:14px;">
        <span>TOTAL</span><span>${formatKES(total)}</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:11px;">
        <span>PAID</span><span>${formatKES(paid)}</span>
      </div>
      ${total - paid > 0 ? `<div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;">
        <span>BALANCE</span><span>${formatKES(total - paid)}</span>
      </div>` : `<div style="text-align:center;font-weight:700;margin-top:4px;">** PAID IN FULL **</div>`}
      <div style="text-align:center;border-top:1px dashed #0F172A;margin-top:10px;padding-top:10px;font-size:10px;">
        <p>Thank you for your business!</p>
        ${profile.invoiceNotes ? `<p style="margin-top:2px;">${escapeHtml(profile.invoiceNotes)}</p>` : ""}
        <p style="margin-top:4px;">Wangari Farm OS</p>
      </div>
    </div>`;
  return baseDoc(invoice.invoiceNumber, slip, "420px");
}

/* ────────────────────────────────────────────────────────────
   Sale receipt generator — same templates, receipt-shaped data
   ──────────────────────────────────────────────────────────── */
export function generateReceiptHtml(
  sale: any,
  templateId: string,
  profile: FarmProfile,
  receiptNumber?: string
): string {
  // Reuse the invoice pipeline: a sale receipt is just an invoice with a
  // receipt number, fully-paid status, and receipt wording.
  const items = Array.isArray(sale.items)
    ? sale.items
    : [{ name: sale.items || sale.productType || "Sale", quantity: 1, price: Number(sale.totalAmount || 0) }];
  const invoiceShaped = {
    invoiceNumber: receiptNumber || `RCP-${String(sale.id).padStart(5, "0")}`,
    items,
    totalAmount: sale.totalAmount,
    amountPaid: sale.amountPaid ?? sale.totalAmount,
    paymentStatus: "paid",
    createdAt: sale.saleDate || sale.createdAt,
    dueDate: null,
    customer: sale.customer,
  };
  return generateInvoiceHtml(invoiceShaped, templateId, profile);
}
