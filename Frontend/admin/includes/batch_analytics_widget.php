<?php
/**
 * Batch Analytics Dashboard Widget
 * Shows FCR, HDP, and Batch Profitability
 * Include this in hub_operations.php or hub_poultry.php
 */

$analyticsApiUrl = '/Backend/api/batch_analytics.php';
?>

<!-- Batch Analytics Widget -->
<div class="admin-card" style="margin-bottom:20px; ">
    <div class="card-header-row" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" style="vertical-align:middle;margin-right:6px;">
                <path d="M18 20V10M12 20V4M6 20v-6"/>
            </svg>
            Batch Performance Analytics
        </h3>
        <div style="display:flex;gap:8px;">
            <button onclick="refreshAnalytics()" style="background:#10B981;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:0.8rem;">
                Refresh
            </button>
            <button onclick="exportAnalyticsCSV()" style="background:#6366f1;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:0.8rem;">
                Export CSV
            </button>
        </div>
    </div>
    
    <!-- Summary Cards Row -->
    <div id="analytics-summary" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px;">
        <div style="background:#f0fdf4;border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:0.75rem;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;">Avg FCR</div>
            <div id="metric-fcr" style="font-size:1.6rem;font-weight:700;color:#10B981;margin:4px 0;">--</div>
            <div id="metric-fcr-label" style="font-size:0.7rem;color:#94A3B8;">Feed Conversion Ratio</div>
        </div>
        <div style="background:#eff6ff;border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:0.75rem;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;">Avg HDP</div>
            <div id="metric-hdp" style="font-size:1.6rem;font-weight:700;color:#3B82F6;margin:4px 0;">--</div>
            <div id="metric-hdp-label" style="font-size:0.7rem;color:#94A3B8;">Hen-Day Production %</div>
        </div>
        <div style="background:#fef3c7;border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:0.75rem;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;">Total Profit</div>
            <div id="metric-profit" style="font-size:1.6rem;font-weight:700;color:#F59E0B;margin:4px 0;">--</div>
            <div id="metric-profit-label" style="font-size:0.7rem;color:#94A3B8;">Profit Margin</div>
        </div>
        <div style="background:#fce7f3;border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:0.75rem;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;">Active Batches</div>
            <div id="metric-batches" style="font-size:1.6rem;font-weight:700;color:#EC4899;margin:4px 0;">--</div>
            <div id="metric-batches-label" style="font-size:0.7rem;color:#94A3B8;">Layers + Broilers</div>
        </div>
    </div>
    
    <!-- Batch Details Table -->
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:10px 12px;text-align:left;font-weight:600;color:#475569;">Batch</th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600;color:#475569;">Type</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569;">Birds</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569;">FCR</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569;">HDP</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569;">Costs</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569;">Revenue</th>
                    <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569;">Profit</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:600;color:#475569;">Status</th>
                </tr>
            </thead>
            <tbody id="analytics-table-body">
                <tr>
                    <td colspan="9" style="padding:20px;text-align:center;color:#94A3B8;">Loading analytics...</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Profit Margin Bar -->
    <div id="profit-bar-container" style="margin-top:16px;display:none;">
        <div style="font-size:0.8rem;color:#64748B;margin-bottom:6px;">Overall Profit Margin</div>
        <div style="background:#e2e8f0;border-radius:20px;height:12px;overflow:hidden;">
            <div id="profit-bar" style="height:100%;border-radius:20px;transition:width 0.8s ease;width:0%;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span style="font-size:0.7rem;color:#94A3B8;">0%</span>
            <span id="profit-bar-value" style="font-size:0.75rem;font-weight:600;">0%</span>
            <span style="font-size:0.7rem;color:#94A3B8;">100%</span>
        </div>
    </div>
</div>

<!-- FCR/HDP Benchmark Legend -->
<div class="admin-card" style="margin-bottom:20px;">
    <h3 style="margin:0 0 12px;font-family:'Outfit',sans-serif;font-size:1rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" style="vertical-align:middle;margin-right:4px;">
            <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
        </svg>
        Industry Benchmarks
    </h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <div style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:8px;">FCR (Feed Conversion Ratio)</div>
            <div style="font-size:0.75rem;color:#64748B;">
                <span style="color:#10B981;font-weight:600;">Excellent:</span> Below 1.6<br>
                <span style="color:#3B82F6;font-weight:600;">Good:</span> 1.6 - 1.8<br>
                <span style="color:#F59E0B;font-weight:600;">Average:</span> 1.8 - 2.0<br>
                <span style="color:#EF4444;font-weight:600;">Poor:</span> Above 2.0
            </div>
        </div>
        <div>
            <div style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:8px;">HDP (Hen-Day Production)</div>
            <div style="font-size:0.75rem;color:#64748B;">
                <span style="color:#10B981;font-weight:600;">Excellent:</span> 90%+<br>
                <span style="color:#3B82F6;font-weight:600;">Good:</span> 80% - 90%<br>
                <span style="color:#F59E0B;font-weight:600;">Average:</span> 70% - 80%<br>
                <span style="color:#EF4444;font-weight:600;">Poor:</span> Below 70%
            </div>
        </div>
    </div>
</div>

<!-- SACCO Report Button -->
<div style="margin-bottom:20px;">
    <button onclick="generateSACCOReport()" style="background:#6366f1;color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:0.85rem;font-weight:500;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        Generate SACCO / Lender Report (PDF)
    </button>
</div>

<script>
let analyticsData = null;

async function loadBatchAnalytics() {
    try {
        const resp = await fetch('<?= $analyticsApiUrl ?>?action=dashboard');
        const data = await resp.json();
        analyticsData = data;
        
        if (data.summary) {
            const s = data.summary;
            
            // Update summary cards
            document.getElementById('metric-fcr').textContent = s.avg_fcr > 0 ? s.avg_fcr : '--';
            document.getElementById('metric-hdp').textContent = s.avg_hdp > 0 ? s.avg_hdp + '%' : '--';
            document.getElementById('metric-profit').textContent = s.total_profit >= 0 
                ? 'KES ' + Number(s.total_profit).toLocaleString()
                : '-KES ' + Math.abs(s.total_profit).toLocaleString();
            document.getElementById('metric-profit').style.color = s.total_profit >= 0 ? '#10B981' : '#EF4444';
            document.getElementById('metric-profit-label').textContent = 'Margin: ' + s.profit_margin + '%';
            document.getElementById('metric-batches').textContent = s.active_batches;
            document.getElementById('metric-batches-label').textContent = s.layer_batches + ' layers, ' + s.broiler_batches + ' broilers';
            
            // Update table
            const tbody = document.getElementById('analytics-table-body');
            if (data.batches && data.batches.length > 0) {
                let html = '';
                data.batches.forEach(b => {
                    const fcrColor = getFCRColor(b.fcr_status);
                    const hdpColor = getHDPColor(b.hdp_status);
                    const profitColor = b.profit >= 0 ? '#10B981' : '#EF4444';
                    
                    html += `<tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 12px;font-weight:500;">${escapeHtml(b.batch_name)}</td>
                        <td style="padding:10px 12px;"><span style="background:${b.batch_type==='layer'?'#eff6ff':'#fef3c7'};padding:2px 8px;border-radius:10px;font-size:0.75rem;">${b.batch_type}</span></td>
                        <td style="padding:10px 12px;text-align:right;">${b.current_birds}/${b.initial_birds}</td>
                        <td style="padding:10px 12px;text-align:right;">
                            ${b.fcr > 0 ? `<span style="color:${fcrColor};font-weight:600;">${b.fcr}</span> <span style="font-size:0.7rem;color:#94A3B8;">${b.fcr_status}</span>` : '<span style="color:#94A3B8;">N/A</span>'}
                        </td>
                        <td style="padding:10px 12px;text-align:right;">
                            ${b.hdp > 0 ? `<span style="color:${hdpColor};font-weight:600;">${b.hdp}%</span> <span style="font-size:0.7rem;color:#94A3B8;">${b.hdp_status}</span>` : '<span style="color:#94A3B8;">N/A</span>'}
                        </td>
                        <td style="padding:10px 12px;text-align:right;">KES ${Number(b.total_costs).toLocaleString()}</td>
                        <td style="padding:10px 12px;text-align:right;">KES ${Number(b.revenue).toLocaleString()}</td>
                        <td style="padding:10px 12px;text-align:right;color:${profitColor};font-weight:600;">
                            ${b.profit >= 0 ? '+' : ''}KES ${Number(b.profit).toLocaleString()}
                        </td>
                        <td style="padding:10px 12px;text-align:center;">
                            <span style="background:${b.mortality_rate > 5 ? '#fef2f2' : '#f0fdf4'};color:${b.mortality_rate > 5 ? '#EF4444' : '#10B981'};padding:2px 8px;border-radius:10px;font-size:0.7rem;">
                                ${b.mortality_rate}% mortality
                            </span>
                        </td>
                    </tr>`;
                });
                tbody.innerHTML = html;
                
                // Update profit bar
                const profitBar = document.getElementById('profit-bar-container');
                const profitBarFill = document.getElementById('profit-bar');
                const profitBarValue = document.getElementById('profit-bar-value');
                profitBar.style.display = 'block';
                const margin = Math.min(100, Math.max(0, s.profit_margin));
                profitBarFill.style.width = margin + '%';
                profitBarFill.style.background = margin > 20 ? '#10B981' : margin > 0 ? '#F59E0B' : '#EF4444';
                profitBarValue.textContent = s.profit_margin + '%';
            } else {
                tbody.innerHTML = '<tr><td colspan="9" style="padding:20px;text-align:center;color:#94A3B8;">No active batches. Add a batch in Poultry Tools to see analytics.</td></tr>';
            }
        }
    } catch (err) {
        console.error('Analytics load error:', err);
    }
}

function getFCRColor(status) {
    switch(status) {
        case 'Excellent': return '#10B981';
        case 'Good': return '#3B82F6';
        case 'Average': return '#F59E0B';
        case 'Poor': return '#EF4444';
        default: return '#94A3B8';
    }
}

function getHDPColor(status) {
    switch(status) {
        case 'Excellent': return '#10B981';
        case 'Good': return '#3B82F6';
        case 'Average': return '#F59E0B';
        case 'Poor': return '#EF4444';
        default: return '#94A3B8';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function refreshAnalytics() {
    loadBatchAnalytics();
}

function exportAnalyticsCSV() {
    if (!analyticsData || !analyticsData.batches) {
        alert('No data to export');
        return;
    }
    
    let csv = 'Batch,Type,Birds,FCR,FCR Status,HDP,HDP Status,Costs,Revenue,Profit,Profit Margin,Mortality\n';
    analyticsData.batches.forEach(b => {
        csv += `"${b.batch_name}",${b.batch_type},${b.current_birds},${b.fcr},${b.fcr_status},${b.hdp},${b.hdp_status},${b.total_costs},${b.revenue},${b.profit},${b.profit_margin}%,${b.mortality_rate}%\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'batch-analytics-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}

async function generateSACCOReport() {
    try {
        const resp = await fetch('/Backend/api/batch_analytics.php?action=sacco_report');
        const data = await resp.json();
        
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        // Open print-friendly report
        const reportWindow = window.open('', '_blank');
        reportWindow.document.write(generateSACCOHTML(data));
        reportWindow.document.close();
        reportWindow.print();
    } catch (err) {
        alert('Error generating report: ' + err.message);
    }
}

function generateSACCOHTML(report) {
    let batchRows = '';
    report.batches.forEach((b, i) => {
        batchRows += `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(b.batch_name)}</td>
            <td>${b.batch_type}</td>
            <td>${b.initial_birds} -> ${b.current_birds}</td>
            <td>${b.fcr > 0 ? b.fcr : 'N/A'}</td>
            <td>${b.hdp > 0 ? b.hdp + '%' : 'N/A'}</td>
            <td>KES ${Number(b.total_costs).toLocaleString()}</td>
            <td>KES ${Number(b.revenue).toLocaleString()}</td>
            <td style="color:${b.profit >= 0 ? '#10B981' : '#EF4444'}">KES ${Number(b.profit).toLocaleString()}</td>
            <td>${b.profit_margin}%</td>
        </tr>`;
    });
    
    return `<!DOCTYPE html>
<html><head><title>SACCO/Lender Report - ${escapeHtml(report.owner_name)}</title>
<style>
body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
h1 { color: #1a1a2e; border-bottom: 3px solid #10B981; padding-bottom: 10px; }
h2 { color: #475569; margin-top: 30px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th { background: #1a1a2e; color: white; padding: 10px; text-align: left; }
td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
.summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
.summary-card { background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 4px solid #10B981; }
.summary-card .label { font-size: 12px; color: #64748B; text-transform: uppercase; }
.summary-card .value { font-size: 24px; font-weight: bold; color: #1a1a2e; }
.footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94A3B8; }
@media print { body { padding: 20px; } }
</style></head><body>
<h1>Wangari Farm Management System</h1>
<h2>SACCO / Lender Production Report</h2>
<p><strong>Owner:</strong> ${escapeHtml(report.owner_name)}</p>
<p><strong>Report Date:</strong> ${report.generated_date}</p>

<div class="summary-grid">
    <div class="summary-card">
        <div class="label">Total Investment</div>
        <div class="value">KES ${Number(report.totals.total_investment).toLocaleString()}</div>
    </div>
    <div class="summary-card">
        <div class="label">Total Revenue</div>
        <div class="value">KES ${Number(report.totals.total_revenue).toLocaleString()}</div>
    </div>
    <div class="summary-card">
        <div class="label">Net Profit</div>
        <div class="value" style="color:${report.totals.net_profit >= 0 ? '#10B981' : '#EF4444'}">KES ${Number(report.totals.net_profit).toLocaleString()}</div>
    </div>
    <div class="summary-card">
        <div class="label">Total Birds</div>
        <div class="value">${report.totals.total_birds}</div>
    </div>
    <div class="summary-card">
        <div class="label">Average FCR</div>
        <div class="value">${report.totals.avg_fcr > 0 ? report.totals.avg_fcr : 'N/A'}</div>
    </div>
    <div class="summary-card">
        <div class="label">Average HDP</div>
        <div class="value">${report.totals.avg_hdp > 0 ? report.totals.avg_hdp + '%' : 'N/A'}</div>
    </div>
</div>

<h2>Batch Details</h2>
<table>
    <thead>
        <tr><th>#</th><th>Batch</th><th>Type</th><th>Birds</th><th>FCR</th><th>HDP</th><th>Costs</th><th>Revenue</th><th>Profit</th><th>Margin</th></tr>
    </thead>
    <tbody>${batchRows}</tbody>
</table>

<div class="footer">
    <p>This report was generated by Wangari Farm Management System on ${report.generated_date}.</p>
    <p>For verification, contact support@imeantech.com or +254 114 971 070</p>
</div>
</body></html>`;
}

// Load on page ready
document.addEventListener('DOMContentLoaded', loadBatchAnalytics);
</script>
