/**
 * Wangari Export functionality
 * Export tables to Excel/CSV format
 */

class WangariExport {
    constructor() {
        this.init();
    }

    init() {
        // Add export buttons to all admin tables
        document.querySelectorAll('.admin-table').forEach(table => {
            this.addExportButton(table);
        });
    }

    addExportButton(table) {
        const card = table.closest('.admin-card') || table.parentElement;
        if (!card || card.querySelector('.export-btn')) return;

        const header = card.querySelector('h3, h4');
        if (!header) return;

        // Use card-action-bar instead

        const csvBtn = document.createElement('button');
        csvBtn.className = 'btn btn-outline btn-sm export-btn';
        csvBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> CSV';
        csvBtn.onclick = () => this.exportToCSV(table);

        const excelBtn = document.createElement('button');
        excelBtn.className = 'btn btn-outline btn-sm export-btn';
        excelBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Excel';
        excelBtn.onclick = () => this.exportToExcel(table);

        // Look for existing card-action-bar or create one
        let bar = header.nextElementSibling;
        if (!bar || !bar.classList.contains('card-action-bar')) {
            bar = document.createElement('div');
            bar.className = 'card-action-bar';
            header.parentElement.insertBefore(bar, header.nextSibling);
        }
        bar.appendChild(csvBtn);
        bar.appendChild(excelBtn);
    }

    exportToCSV(table) {
        const rows = [];
        const headers = [];

        // Get headers
        table.querySelectorAll('thead th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        rows.push(headers);

        // Get data rows
        table.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            tr.querySelectorAll('td').forEach(td => {
                // Clean up the cell content
                let text = td.textContent.trim();
                // Remove extra whitespace
                text = text.replace(/\s+/g, ' ');
                // Escape quotes for CSV
                text = text.replace(/"/g, '""');
                row.push(text);
            });
            if (row.length > 0) {
                rows.push(row);
            }
        });

        // Create CSV content
        const csvContent = rows.map(row => 
            row.map(cell => `"${cell}"`).join(',')
        ).join('\n');

        // Download
        this.downloadFile(csvContent, 'export.csv', 'text/csv;charset=utf-8;');
    }

    exportToExcel(table) {
        // Create HTML table for Excel
        let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        html += '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Export</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
        html += '<body>';
        html += '<table border="1">';

        // Headers
        html += '<tr>';
        table.querySelectorAll('thead th').forEach(th => {
            html += `<th style="background:#22C55E;color:white;font-weight:bold;">${th.textContent.trim()}</th>`;
        });
        html += '</tr>';

        // Data rows
        table.querySelectorAll('tbody tr').forEach(tr => {
            html += '<tr>';
            tr.querySelectorAll('td').forEach(td => {
                html += `<td>${td.textContent.trim()}</td>`;
            });
            html += '</tr>';
        });

        html += '</table></body></html>';

        // Download
        this.downloadFile(html, 'export.xls', 'application/vnd.ms-excel');
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // Export specific table by ID
    exportTableById(tableId, format = 'csv') {
        const table = document.getElementById(tableId);
        if (!table) {
            console.error(`Table ${tableId} not found`);
            return;
        }

        if (format === 'csv') {
            this.exportToCSV(table);
        } else {
            this.exportToExcel(table);
        }
    }

    // Export all tables on page
    exportAllTables(format = 'csv') {
        document.querySelectorAll('.admin-table').forEach(table => {
            const card = table.closest('.admin-card');
            const title = card?.querySelector('h3, h4')?.textContent?.trim() || 'export';
            const filename = title.toLowerCase().replace(/[^a-z0-9]/g, '_');
            
            if (format === 'csv') {
                this.exportToCSV(table, filename);
            } else {
                this.exportToExcel(table, filename);
            }
        });
    }
}

// Initialize globally
const wangariExport = new WangariExport();

// Add print functionality
function printPage() {
    window.print();
}

function printSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print - Wangari</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #22C55E; color: white; }
                h1, h2, h3 { color: #0B1220; }
                .no-print { display: none; }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>Wangari Farm Management</h1>
            ${section.innerHTML}
            <script>window.onload = function() { window.print(); window.close(); }<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
