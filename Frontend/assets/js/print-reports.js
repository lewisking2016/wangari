/**
 * Wangari Print Reports
 * Generate print-friendly reports
 */

class WangariPrintReports {
    constructor() {
        this.init();
    }

    init() {
        // Add print buttons to reports
        document.querySelectorAll('.admin-card').forEach(card => {
            if (card.querySelector('table, .chart-box, canvas')) {
                this.addPrintButton(card);
            }
        });
    }

    addPrintButton(card) {
        const header = card.querySelector('h3, h4');
        if (!header || card.querySelector('.print-btn')) return;

        const printBtn = document.createElement('button');
        printBtn.className = 'btn btn-outline btn-sm print-btn';
        printBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> Print';
        printBtn.onclick = () => this.printSection(card);

        // Look for existing card-action-bar or create one
        let bar = header.nextElementSibling;
        if (!bar || !bar.classList.contains('card-action-bar')) {
            bar = document.createElement('div');
            bar.className = 'card-action-bar';
            header.parentElement.insertBefore(bar, header.nextSibling);
        }
        bar.appendChild(printBtn);
    }

    printSection(section) {
        const printWindow = window.open('', '_blank');
        const title = section.querySelector('h3, h4')?.textContent?.trim() || 'Report';
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${title} - Wangari</title>
                <style>
                    * { box-sizing: border-box; }
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        padding: 30px; 
                        color: #333;
                        line-height: 1.6;
                    }
                    .report-header {
                        text-align: center;
                        margin-bottom: 30px;
                        padding-bottom: 20px;
                        border-bottom: 2px solid #22C55E;
                    }
                    .report-header h1 {
                        color: #0B1220;
                        margin: 0 0 10px 0;
                        font-size: 24px;
                    }
                    .report-header p {
                        color: #64748B;
                        margin: 0;
                        font-size: 14px;
                    }
                    .report-date {
                        text-align: right;
                        margin-bottom: 20px;
                        color: #64748B;
                        font-size: 12px;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 20px;
                        font-size: 13px;
                    }
                    th, td { 
                        border: 1px solid #E5E7EB; 
                        padding: 10px 12px; 
                        text-align: left; 
                    }
                    th { 
                        background: #22C55E; 
                        color: white; 
                        font-weight: 600;
                        text-transform: uppercase;
                        font-size: 11px;
                        letter-spacing: 0.5px;
                    }
                    tr:nth-child(even) { background: #F9FAFB; }
                    tr:hover { background: #F3F4F6; }
                    .stat-box {
                        display: inline-block;
                        padding: 15px 20px;
                        margin: 5px;
                        background: #F0FDF4;
                        border-radius: 8px;
                        border-left: 4px solid #22C55E;
                    }
                    .stat-box .label {
                        font-size: 12px;
                        color: #64748B;
                        text-transform: uppercase;
                    }
                    .stat-box .value {
                        font-size: 24px;
                        font-weight: bold;
                        color: #0B1220;
                    }
                    .footer {
                        margin-top: 40px;
                        padding-top: 20px;
                        border-top: 1px solid #E5E7EB;
                        text-align: center;
                        color: #94A3B8;
                        font-size: 11px;
                    }
                    @media print {
                        body { padding: 20px; }
                        .no-print { display: none !important; }
                        .stat-box { break-inside: avoid; }
                        table { break-inside: auto; }
                        tr { break-inside: avoid; }
                    }
                </style>
            </head>
            <body>
                <div class="report-header">
                    <h1>Wangari Farm Management</h1>
                    <p>${title}</p>
                </div>
                <div class="report-date">
                    Generated: ${new Date().toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}
                </div>
                <div class="report-content">
                    ${this.cleanContent(section)}
                </div>
                <div class="footer">
                    <p>Wangari Farm Management System | ${new Date().getFullYear()}</p>
                    <p>Generated by iMeanTech</p>
                </div>
                <script>window.onload = function() { window.print(); }<\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    cleanContent(section) {
        // Clone the section and clean it up for printing
        const clone = section.cloneNode(true);
        
        // Remove buttons and interactive elements
        clone.querySelectorAll('button, .btn, .export-btn, .print-btn, form').forEach(el => {
            if (!el.classList.contains('no-print')) {
                el.remove();
            }
        });

        // Remove links styling
        clone.querySelectorAll('a').forEach(a => {
            a.style.color = '#22C55E';
            a.style.textDecoration = 'none';
        });

        // Clean up charts (just show placeholder)
        clone.querySelectorAll('canvas, .chart-box').forEach(el => {
            el.innerHTML = '<div style="text-align:center;padding:20px;color:#94A3B8;">[Chart/Graph]</div>';
        });

        return clone.innerHTML;
    }

    // Generate specific reports
    generateDailyReport() {
        this.printSection(document.querySelector('.dashboard-hero-card') || document.body);
    }

    generateFinancialReport() {
        const financeCard = document.querySelector('[href*="hub_finance"]');
        if (financeCard) {
            this.printSection(financeCard.closest('.admin-card') || document.body);
        }
    }

    generateInventoryReport() {
        const inventoryCard = document.querySelector('[href*="hub_inventory"]');
        if (inventoryCard) {
            this.printSection(inventoryCard.closest('.admin-card') || document.body);
        }
    }

    generateWorkerReport() {
        const workerCard = document.querySelector('[href*="hub_labour"]');
        if (workerCard) {
            this.printSection(workerCard.closest('.admin-card') || document.body);
        }
    }
}

// Initialize globally
const wangariPrintReports = new WangariPrintReports();

// Add global print functions
window.printPage = function() {
    window.print();
};

window.printSection = function(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        wangariPrintReports.printSection(section);
    }
};
