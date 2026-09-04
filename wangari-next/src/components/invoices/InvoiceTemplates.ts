// Farm-specific invoice templates
export interface InvoiceTemplate {
  id: string;
  name: string;
  description: string;
  preview: string; // short description for preview card
  color: string;   // accent color
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
    preview: "Includes farm profile, T&C, bank details, QR-ready",
    color: "#1E3A5F",
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
  };
}

function formatKES(amount: number): string {
  return `KES ${amount.toLocaleString()}`;
}

function escapeHtml(str: string): string {
  return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

export function generateInvoiceHtml(
  invoice: any,
  templateId: string,
  profile: FarmProfile
): string {
  const items = Array.isArray(invoice.items) ? invoice.items : [];
  const total = Number(invoice.totalAmount);
  const paid = Number(invoice.amountPaid);
  const balance = total - paid;
  const farmName = profile.businessName || "My Farm";
  const logoHtml = profile.logoUrl
    ? `<img src="${escapeHtml(profile.logoUrl)}" alt="Logo" style="height:60px;object-fit:contain;" />`
    : `<div style="width:60px;height:60px;border-radius:12px;background:#166534;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;font-weight:bold;">${farmName.charAt(0)}</div>`;

  const contactLines: string[] = [];
  if (profile.phone) contactLines.push(`📞 ${escapeHtml(profile.phone)}`);
  if (profile.email) contactLines.push(`✉️ ${escapeHtml(profile.email)}`);
  if (profile.address) contactLines.push(`📍 ${escapeHtml(profile.address)}`);
  if (profile.tinNumber) contactLines.push(`TIN/PIN: ${escapeHtml(profile.tinNumber)}`);

  const itemRows = items
    .map(
      (item: any, i: number) => `
    <tr style="border-bottom:1px solid #E5E7EB;">
      <td style="padding:12px 16px;font-size:13px;color:#334155;">${i + 1}</td>
      <td style="padding:12px 16px;font-size:13px;color:#0F172A;font-weight:600;">${escapeHtml(item.name || "Item")}</td>
      <td style="padding:12px 16px;font-size:13px;color:#64748B;text-align:center;">${item.quantity || 1}</td>
      <td style="padding:12px 16px;font-size:13px;color:#64748B;text-align:right;">${formatKES(Number(item.price || 0))}</td>
      <td style="padding:12px 16px;font-size:13px;color:#0F172A;font-weight:600;text-align:right;">${formatKES(Number(item.quantity || 1) * Number(item.price || 0))}</td>
    </tr>`
    )
    .join("");

  const customerName = escapeHtml(invoice.customer?.name || "Walk-in Customer");
  const customerPhone = invoice.customer?.phone ? escapeHtml(invoice.customer.phone) : "";
  const invoiceDate = new Date(invoice.createdAt).toLocaleDateString("en-KE", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const dueDate = invoice.dueDate
    ? new Date(invoice.dueDate).toLocaleDateString("en-KE", {
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    : null;

  if (templateId === "professional") {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>${escapeHtml(invoice.invoiceNumber)}</title>
  <style>
    @media print { body { margin: 0; padding: 0; } .no-print { display: none !important; } }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #334155; background: white; }
    .page { max-width: 800px; margin: 0 auto; padding: 40px; }
  </style>
</head>
<body>
  <div class="page">
    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:24px;border-bottom:3px solid #166534;">
      <div style="display:flex;align-items:center;gap:16px;">
        ${logoHtml}
        <div>
          <h1 style="font-size:22px;font-weight:800;color:#166534;letter-spacing:-0.5px;">${escapeHtml(farmName)}</h1>
          ${profile.slogan ? `<p style="font-size:12px;color:#64748B;margin-top:2px;font-style:italic;">${escapeHtml(profile.slogan)}</p>` : ""}
          <div style="margin-top:8px;font-size:11px;color:#94A3B8;line-height:1.6;">${contactLines.map((l) => `<span style="display:block;">${l}</span>`).join("")}</div>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="background:#166534;color:white;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:700;letter-spacing:1px;">INVOICE</div>
        <p style="margin-top:12px;font-size:14px;font-weight:700;color:#0F172A;">${escapeHtml(invoice.invoiceNumber)}</p>
        <p style="font-size:12px;color:#94A3B8;margin-top:4px;">Date: ${invoiceDate}</p>
        ${dueDate ? `<p style="font-size:12px;color:#94A3B8;">Due: ${dueDate}</p>` : ""}
        <div style="margin-top:8px;">
          <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;${invoice.paymentStatus === "paid" ? "background:#F0FDF4;color:#166534;border:1px solid #BBF7D0;" : invoice.paymentStatus === "partial" ? "background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;" : "background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;"}">${escapeHtml(invoice.paymentStatus)}</span>
        </div>
      </div>
    </div>

    <!-- Bill To + Summary -->
    <div style="display:flex;justify-content:space-between;margin-bottom:32px;">
      <div style="background:#F8FAFC;border-radius:12px;padding:20px;min-width:280px;">
        <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:8px;">Bill To</p>
        <p style="font-size:16px;font-weight:700;color:#0F172A;">${customerName}</p>
        ${customerPhone ? `<p style="font-size:13px;color:#64748B;margin-top:4px;">${customerPhone}</p>` : ""}
      </div>
      <div style="text-align:right;">
        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:16px 24px;">
          <p style="font-size:11px;color:#64748B;text-transform:uppercase;letter-spacing:1px;">Total Amount</p>
          <p style="font-size:28px;font-weight:800;color:#166534;margin-top:4px;">${formatKES(total)}</p>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
      <thead>
        <tr style="background:#166534;">
          <th style="padding:12px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:left;">#</th>
          <th style="padding:12px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:left;">Item</th>
          <th style="padding:12px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:center;">Qty</th>
          <th style="padding:12px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:right;">Price</th>
          <th style="padding:12px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:right;">Total</th>
        </tr>
      </thead>
      <tbody>${itemRows || `<tr><td colspan="5" style="padding:24px;text-align:center;color:#94A3B8;font-size:13px;">No items</td></tr>`}</tbody>
    </table>

    <!-- Totals -->
    <div style="display:flex;justify-content:flex-end;margin-bottom:32px;">
      <div style="min-width:260px;">
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #E5E7EB;">
          <span style="font-size:13px;color:#64748B;">Subtotal</span>
          <span style="font-size:13px;font-weight:600;color:#0F172A;">${formatKES(total)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #E5E7EB;">
          <span style="font-size:13px;color:#64748B;">Amount Paid</span>
          <span style="font-size:13px;font-weight:600;color:#166534;">${formatKES(paid)}</span>
        </div>
        ${balance > 0 ? `
        <div style="display:flex;justify-content:space-between;padding:12px 0;background:#FEF2F2;border-radius:8px;margin-top:8px;padding:12px 16px;">
          <span style="font-size:14px;font-weight:700;color:#DC2626;">Balance Due</span>
          <span style="font-size:18px;font-weight:800;color:#DC2626;">${formatKES(balance)}</span>
        </div>` : `
        <div style="display:flex;justify-content:space-between;padding:12px 0;background:#F0FDF4;border-radius:8px;margin-top:8px;padding:12px 16px;">
          <span style="font-size:14px;font-weight:700;color:#166534;">✓ Fully Paid</span>
        </div>`}
      </div>
    </div>

    <!-- Bank Details -->
    ${profile.bankName ? `
    <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:12px;padding:20px;margin-bottom:24px;">
      <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:12px;">Payment Details</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
        <div><span style="color:#64748B;">Bank:</span> <span style="font-weight:600;color:#0F172A;">${escapeHtml(profile.bankName)}</span></div>
        ${profile.bankAccount ? `<div><span style="color:#64748B;">Account:</span> <span style="font-weight:600;color:#0F172A;">${escapeHtml(profile.bankAccount)}</span></div>` : ""}
        ${profile.bankBranch ? `<div><span style="color:#64748B;">Branch:</span> <span style="font-weight:600;color:#0F172A;">${escapeHtml(profile.bankBranch)}</span></div>` : ""}
      </div>
    </div>` : ""}

    <!-- Notes -->
    ${profile.invoiceNotes ? `
    <div style="margin-bottom:24px;">
      <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:6px;">Notes</p>
      <p style="font-size:12px;color:#64748B;line-height:1.6;">${escapeHtml(profile.invoiceNotes)}</p>
    </div>` : ""}

    <!-- Terms -->
    ${profile.invoiceTerms ? `
    <div style="margin-bottom:32px;">
      <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:6px;">Terms & Conditions</p>
      <p style="font-size:11px;color:#94A3B8;line-height:1.6;">${escapeHtml(profile.invoiceTerms)}</p>
    </div>` : ""}

    <!-- Footer -->
    <div style="text-align:center;padding-top:24px;border-top:2px solid #166534;">
      <p style="font-size:12px;color:#166534;font-weight:600;">Thank you for your business!</p>
      <p style="font-size:10px;color:#94A3B8;margin-top:4px;">${escapeHtml(farmName)} · Generated by Wangari Farm OS</p>
    </div>
  </div>
</body>
</html>`;
  }

  if (templateId === "simple") {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>${escapeHtml(invoice.invoiceNumber)}</title>
  <style>
    @media print { body { margin: 0; padding: 0; } }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; background: white; }
    .page { max-width: 800px; margin: 0 auto; padding: 40px; }
  </style>
</head>
<body>
  <div class="page">
    <!-- Simple Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:40px;">
      <div style="display:flex;align-items:center;gap:12px;">
        ${logoHtml}
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

    <!-- Info Row -->
    <div style="display:flex;justify-content:space-between;margin-bottom:32px;padding:16px 0;border-top:1px solid #E5E7EB;border-bottom:1px solid #E5E7EB;">
      <div>
        <p style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:1px;">Bill To</p>
        <p style="font-size:15px;font-weight:700;color:#0F172A;margin-top:4px;">${customerName}</p>
        ${customerPhone ? `<p style="font-size:12px;color:#64748B;margin-top:2px;">${customerPhone}</p>` : ""}
      </div>
      <div style="text-align:right;">
        <p style="font-size:11px;color:#94A3B8;">Date: <span style="color:#0F172A;font-weight:600;">${invoiceDate}</span></p>
        ${dueDate ? `<p style="font-size:11px;color:#94A3B8;margin-top:2px;">Due: <span style="color:#0F172A;font-weight:600;">${dueDate}</span></p>` : ""}
        <p style="font-size:11px;margin-top:4px;"><span style="padding:3px 10px;border-radius:12px;font-weight:700;text-transform:uppercase;font-size:10px;${invoice.paymentStatus === "paid" ? "background:#F0FDF4;color:#166534;" : invoice.paymentStatus === "partial" ? "background:#FFFBEB;color:#D97706;" : "background:#FEF2F2;color:#DC2626;"}">${escapeHtml(invoice.paymentStatus)}</span></p>
      </div>
    </div>

    <!-- Items -->
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

    <!-- Totals -->
    <div style="display:flex;justify-content:flex-end;">
      <div style="min-width:220px;">
        <div style="display:flex;justify-content:space-between;padding:8px 0;">
          <span style="font-size:13px;color:#64748B;">Subtotal</span>
          <span style="font-size:13px;font-weight:600;">${formatKES(total)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #E5E7EB;">
          <span style="font-size:13px;color:#64748B;">Paid</span>
          <span style="font-size:13px;font-weight:600;color:#166534;">${formatKES(paid)}</span>
        </div>
        ${balance > 0 ? `
        <div style="display:flex;justify-content:space-between;padding:12px 0;">
          <span style="font-size:15px;font-weight:700;color:#DC2626;">Balance Due</span>
          <span style="font-size:18px;font-weight:800;color:#DC2626;">${formatKES(balance)}</span>
        </div>` : `
        <div style="text-align:right;padding:12px 0;">
          <span style="font-size:14px;font-weight:700;color:#166534;">✓ Paid in Full</span>
        </div>`}
      </div>
    </div>

    ${profile.bankName ? `
    <div style="margin-top:24px;padding:16px;background:#F8FAFC;border-radius:8px;font-size:12px;color:#64748B;">
      <strong>Payment:</strong> ${escapeHtml(profile.bankName)}${profile.bankAccount ? ` · A/C ${escapeHtml(profile.bankAccount)}` : ""}${profile.bankBranch ? ` · ${escapeHtml(profile.bankBranch)}` : ""}
    </div>` : ""}

    ${profile.invoiceNotes ? `<p style="margin-top:16px;font-size:11px;color:#94A3B8;text-align:center;">${escapeHtml(profile.invoiceNotes)}</p>` : ""}

    <p style="margin-top:24px;text-align:center;font-size:10px;color:#CBD5E1;">${escapeHtml(farmName)} · Wangari Farm OS</p>
  </div>
</body>
</html>`;
  }

  // templateId === "detailed"
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>${escapeHtml(invoice.invoiceNumber)}</title>
  <style>
    @media print { body { margin: 0; padding: 0; } .no-print { display: none !important; } }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; background: white; }
    .page { max-width: 800px; margin: 0 auto; padding: 40px; }
  </style>
</head>
<body>
  <div class="page">
    <!-- Detailed Header with stripe -->
    <div style="background:linear-gradient(135deg,#1E3A5F 0%,#166534 100%);color:white;padding:32px;border-radius:16px;margin-bottom:32px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div style="display:flex;align-items:center;gap:16px;">
          ${profile.logoUrl ? `<img src="${escapeHtml(profile.logoUrl)}" alt="Logo" style="height:64px;object-fit:contain;border-radius:8px;background:white;padding:4px;" />` : `<div style="width:64px;height:64px;border-radius:12px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:bold;">${farmName.charAt(0)}</div>`}
          <div>
            <h1 style="font-size:24px;font-weight:800;letter-spacing:-0.5px;">${escapeHtml(farmName)}</h1>
            ${profile.slogan ? `<p style="font-size:13px;opacity:0.8;margin-top:4px;font-style:italic;">${escapeHtml(profile.slogan)}</p>` : ""}
          </div>
        </div>
        <div style="text-align:right;">
          <div style="background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);padding:8px 20px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:2px;">TAX INVOICE</div>
          <p style="margin-top:12px;font-size:16px;font-weight:700;">${escapeHtml(invoice.invoiceNumber)}</p>
          <p style="font-size:12px;opacity:0.7;margin-top:4px;">${invoiceDate}</p>
          ${dueDate ? `<p style="font-size:12px;opacity:0.7;">Due: ${dueDate}</p>` : ""}
        </div>
      </div>
      <div style="margin-top:20px;display:flex;gap:24px;font-size:12px;opacity:0.8;">
        ${contactLines.map((l) => `<span>${l}</span>`).join("")}
      </div>
    </div>

    <!-- From / To -->
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

    <!-- Status Bar -->
    <div style="display:flex;justify-content:space-between;align-items:center;background:#F8FAFC;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
      <div style="display:flex;gap:24px;font-size:13px;">
        <div><span style="color:#94A3B8;">Invoice:</span> <strong>${escapeHtml(invoice.invoiceNumber)}</strong></div>
        <div><span style="color:#94A3B8;">Date:</span> <strong>${invoiceDate}</strong></div>
        ${dueDate ? `<div><span style="color:#94A3B8;">Due:</span> <strong>${dueDate}</strong></div>` : ""}
      </div>
      <span style="padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;${invoice.paymentStatus === "paid" ? "background:#F0FDF4;color:#166534;border:1px solid #BBF7D0;" : invoice.paymentStatus === "partial" ? "background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;" : "background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;"}">${escapeHtml(invoice.paymentStatus)}</span>
    </div>

    <!-- Items Table -->
    <table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
      <thead>
        <tr style="background:#1E3A5F;">
          <th style="padding:14px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:left;">#</th>
          <th style="padding:14px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:left;">Description</th>
          <th style="padding:14px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:center;">Qty</th>
          <th style="padding:14px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:right;">Unit Price</th>
          <th style="padding:14px 16px;color:white;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        ${items.map((item: any, i: number) => `
        <tr style="border-bottom:1px solid #E5E7EB;${i % 2 === 0 ? "" : "background:#F8FAFC;"}">
          <td style="padding:14px 16px;font-size:13px;color:#94A3B8;">${i + 1}</td>
          <td style="padding:14px 16px;font-size:13px;color:#0F172A;font-weight:600;">${escapeHtml(item.name || "Item")}</td>
          <td style="padding:14px 16px;font-size:13px;color:#64748B;text-align:center;">${item.quantity || 1}</td>
          <td style="padding:14px 16px;font-size:13px;color:#64748B;text-align:right;">${formatKES(Number(item.price || 0))}</td>
          <td style="padding:14px 16px;font-size:13px;color:#0F172A;font-weight:600;text-align:right;">${formatKES(Number(item.quantity || 1) * Number(item.price || 0))}</td>
        </tr>`).join("") || `<tr><td colspan="5" style="padding:32px;text-align:center;color:#94A3B8;">No items on this invoice</td></tr>`}
      </tbody>
    </table>

    <!-- Totals -->
    <div style="display:flex;justify-content:flex-end;margin-bottom:32px;">
      <div style="min-width:280px;">
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #E5E7EB;">
          <span style="font-size:13px;color:#64748B;">Subtotal</span>
          <span style="font-size:13px;font-weight:600;color:#0F172A;">${formatKES(total)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #E5E7EB;">
          <span style="font-size:13px;color:#64748B;">Amount Paid</span>
          <span style="font-size:13px;font-weight:600;color:#166534;">${formatKES(paid)}</span>
        </div>
        ${balance > 0 ? `
        <div style="display:flex;justify-content:space-between;padding:14px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;margin-top:8px;">
          <span style="font-size:15px;font-weight:700;color:#DC2626;">BALANCE DUE</span>
          <span style="font-size:22px;font-weight:800;color:#DC2626;">${formatKES(balance)}</span>
        </div>` : `
        <div style="text-align:center;padding:14px 16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;margin-top:8px;">
          <span style="font-size:15px;font-weight:700;color:#166534;">✓ PAYMENT RECEIVED — THANK YOU</span>
        </div>`}
      </div>
    </div>

    <!-- Bank Details -->
    ${profile.bankName ? `
    <div style="background:#F0F9FF;border:1px solid #BAE6FD;border-radius:12px;padding:20px;margin-bottom:24px;">
      <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#0369A1;margin-bottom:12px;">🏦 Payment Information</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;color:#334155;">
        <div><span style="color:#64748B;">Bank Name:</span> <strong>${escapeHtml(profile.bankName)}</strong></div>
        ${profile.bankAccount ? `<div><span style="color:#64748B;">Account No:</span> <strong>${escapeHtml(profile.bankAccount)}</strong></div>` : ""}
        ${profile.bankBranch ? `<div><span style="color:#64748B;">Branch:</span> <strong>${escapeHtml(profile.bankBranch)}</strong></div>` : ""}
        ${profile.tinNumber ? `<div><span style="color:#64748B;">TIN/PIN:</span> <strong>${escapeHtml(profile.tinNumber)}</strong></div>` : ""}
      </div>
    </div>` : ""}

    <!-- Notes -->
    ${profile.invoiceNotes ? `
    <div style="margin-bottom:20px;">
      <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;margin-bottom:6px;">Notes</p>
      <p style="font-size:12px;color:#64748B;line-height:1.6;background:#F8FAFC;padding:12px 16px;border-radius:8px;">${escapeHtml(profile.invoiceNotes)}</p>
    </div>` : ""}

    <!-- Terms -->
    ${profile.invoiceTerms ? `
    <div style="margin-bottom:32px;">
      <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#94A3B8;margin-bottom:6px;">Terms & Conditions</p>
      <div style="font-size:11px;color:#94A3B8;line-height:1.7;padding:12px 16px;border-left:3px solid #E5E7EB;">
        ${escapeHtml(profile.invoiceTerms).replace(/\n/g, "<br>")}
      </div>
    </div>` : ""}

    <!-- Footer -->
    <div style="text-align:center;padding-top:24px;border-top:2px solid #1E3A5F;">
      <p style="font-size:13px;color:#1E3A5F;font-weight:600;">${escapeHtml(farmName)}</p>
      ${profile.phone || profile.email ? `<p style="font-size:11px;color:#94A3B8;margin-top:4px;">${[profile.phone, profile.email].filter(Boolean).join(" · ")}</p>` : ""}
      <p style="font-size:10px;color:#CBD5E1;margin-top:8px;">Generated by Wangari Farm OS</p>
    </div>
  </div>
</body>
</html>`;
}
