<?php
/**
 * Admin — Analytics & Charts
 * The central page for all visual data — 10+ animated charts.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Analytics & Charts - Admin';
include __DIR__ . '/includes/admin_header.php';
?>
<style>
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulseGlow {
    0%, 100% { box-shadow: 0 0 0 0 rgba(27, 94, 32, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(27, 94, 32, 0); }
}
.anim-card { animation: slideUp 0.5s ease both; }
.chart-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    border: 1px solid rgba(203, 213, 225, 0.5);
    transition: all 0.25s ease;
}
.chart-card:hover {
    box-shadow: 0 6px 24px rgba(15, 23, 42, 0.08);
    transform: translateY(-2px);
}
/* Grid items must be allowed to shrink below their content width, or the
   2fr/1fr chart rows and 4-col KPI rows overflow the page and push cards
   off-screen (min-content of a canvas is its fixed pixel width). */
.chart-card,
.stat-card { min-width: 0; }
.chart-box,
.chart-box-sm,
.chart-box-lg { min-width: 0; }
.chart-box canvas,
.chart-box-sm canvas,
.chart-box-lg canvas { max-width: 100%; }
/* On narrower windows, collapse the 4-across and 2-across grids so every
   card stays fully visible (inline styles are overridden deliberately). */
@media (max-width: 1150px) {
    div[style*="repeat(4,1fr)"] { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 640px) {
    div[style*="repeat(4,1fr)"] { grid-template-columns: 1fr !important; }
    div[style*="2fr 1fr"] { grid-template-columns: 1fr !important; }
}
.chart-card-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.chart-card-title h3 {
    margin: 0;
    font-family: 'Outfit', sans-serif;
    font-size: 1.05rem;
    color: var(--admin-text-heading);
    font-weight: 700;
}
.chart-card-sub {
    color: #64748b;
    font-size: 0.82rem;
    margin: 2px 0 0;
}
.chart-box {
    position: relative;
    height: 280px;
    width: 100%;
}
.chart-box-sm { height: 200px; }
.chart-box-lg { height: 340px; }
.stat-card { animation: slideUp 0.5s ease both; transition: all 0.25s ease; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); }
.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}
.stat-pill.down { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
.refresh-btn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1B5E20, #2E7D32);
    color: #fff;
    border: none;
    box-shadow: 0 8px 24px rgba(27, 94, 32, 0.35);
    cursor: pointer;
    z-index: 999;
    transition: transform 0.2s;
}
.refresh-btn:hover { transform: scale(1.08) rotate(45deg); }
.refresh-btn.loading { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
    font-size: 0.9rem;
}
</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Analytics & Charts</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">All your farm data, visualised. Sales, costs, mortality, FCR, production, cash flow — at a glance.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <select id="period" class="admin-form-control" style="max-width:160px;" onchange="loadAll()">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>
</div>

<!-- Top KPI cards with count-up animation -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Money In (period)</small>
            <strong data-countup="0" data-prefix="KES " data-decimals="0" style="font-size:1.4rem;color:#16a34a;">KES 0</strong>
            <span class="stat-pill" id="kpi-money-in-trend">+0%</span>
        </div>
        <div class="stat-card-icon" style="background:rgba(22,163,74,0.1);color:#16a34a;"><i data-lucide="trending-up" style="width:22px;height:22px;"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Money Out (period)</small>
            <strong data-countup="0" data-prefix="KES " data-decimals="0" style="font-size:1.4rem;color:#dc2626;">KES 0</strong>
            <span class="stat-pill down" id="kpi-money-out-trend">+0%</span>
        </div>
        <div class="stat-card-icon" style="background:rgba(220,38,38,0.1);color:#dc2626;"><i data-lucide="trending-down" style="width:22px;height:22px;"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Profit (period)</small>
            <strong data-countup="0" data-prefix="KES " data-decimals="0" style="font-size:1.4rem;">KES 0</strong>
            <span class="stat-pill" id="kpi-profit-trend">+0%</span>
        </div>
        <div class="stat-card-icon accent"><i data-lucide="wallet" style="width:22px;height:22px;"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Eggs Collected (period)</small>
            <strong data-countup="0" data-suffix=" eggs" style="font-size:1.4rem;">0</strong>
            <span class="stat-pill" id="kpi-eggs-trend">+0%</span>
        </div>
        <div class="stat-card-icon info"><i data-lucide="egg" style="width:22px;height:22px;"></i></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Total Birds</small>
            <strong data-countup="0" data-suffix="" style="font-size:1.4rem;">0</strong>
        </div>
        <div class="stat-card-icon"><i data-lucide="bird" style="width:22px;height:22px;"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Mortality (period)</small>
            <strong data-countup="0" data-suffix=" birds" style="font-size:1.4rem;color:#dc2626;">0</strong>
        </div>
        <div class="stat-card-icon" style="background:rgba(220,38,38,0.1);color:#dc2626;"><i data-lucide="skull" style="width:22px;height:22px;"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Credit Owed</small>
            <strong data-countup="0" data-prefix="KES " style="font-size:1.4rem;color:#d97706;">KES 0</strong>
        </div>
        <div class="stat-card-icon" style="background:rgba(217,119,6,0.1);color:#d97706;"><i data-lucide="hand-coins" style="width:22px;height:22px;"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Low Stock Items</small>
            <strong data-countup="0" style="font-size:1.4rem;color:#dc2626;">0</strong>
        </div>
        <div class="stat-card-icon" style="background:rgba(220,38,38,0.1);color:#dc2626;"><i data-lucide="package-x" style="width:22px;height:22px;"></i></div>
    </div>
</div>

<!-- Row 1: Sales + Cash flow -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-bottom:18px;">
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Sales vs Costs (Last 6 months)</h3>
                <p class="chart-card-sub">Where your money comes in and where it goes</p>
            </div>
            <span class="stat-pill" id="sales-trend-pill">+0%</span>
        </div>
        <div class="chart-box"><canvas id="chart-profit"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Cash Flow</h3>
                <p class="chart-card-sub">Money in vs out, daily</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-cashflow"></canvas></div>
    </div>
</div>

<!-- Row 2: Production + Top products -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-bottom:18px;">
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Daily Egg Production</h3>
                <p class="chart-card-sub">Eggs collected each day — last 30 days</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-production"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Top Products</h3>
                <p class="chart-card-sub">Best sellers by quantity</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-top-products"></canvas></div>
    </div>
</div>

<!-- Row 3: Mortality + FCR + Growth curve -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px;">
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Mortality by Batch</h3>
                <p class="chart-card-sub">% of birds lost per batch</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-mortality"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>FCR per Batch</h3>
                <p class="chart-card-sub">Feed Conversion Ratio — lower is better</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-fcr"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Broiler Growth Curve</h3>
                <p class="chart-card-sub" id="growth-batch-name">Weight gain over time</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-growth"></canvas></div>
    </div>
</div>

<!-- Row 4: Credit aging + Low stock + Product mix + Customer types -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:18px;margin-bottom:18px;">
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Credit Aging</h3>
                <p class="chart-card-sub">Who owes you &amp; for how long</p>
            </div>
        </div>
        <div class="chart-box-sm"><canvas id="chart-credit-aging"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Low Stock (Raw Materials)</h3>
                <p class="chart-card-sub">Order these now</p>
            </div>
        </div>
        <div class="chart-box-sm"><canvas id="chart-low-stock"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Product Mix</h3>
                <p class="chart-card-sub">What you sell most</p>
            </div>
        </div>
        <div class="chart-box-sm"><canvas id="chart-product-mix"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Customer Types</h3>
                <p class="chart-card-sub">Who buys from you</p>
            </div>
        </div>
        <div class="chart-box-sm"><canvas id="chart-customer-types"></canvas></div>
    </div>
</div>

<!-- Row 5: Bird count trend + Top debtors + Revenue last 7 days -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px;">
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Bird Count Trend</h3>
                <p class="chart-card-sub">Total live birds over time</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-bird-trend"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Top Debtors</h3>
                <p class="chart-card-sub">Customers who owe the most</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-top-debtors"></canvas></div>
    </div>
    <div class="chart-card anim-card">
        <div class="chart-card-title">
            <div>
                <h3>Revenue Last 7 Days</h3>
                <p class="chart-card-sub">Daily revenue from online orders</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="chart-revenue-7d"></canvas></div>
    </div>
</div>

<button class="refresh-btn" onclick="loadAll()" title="Refresh charts">
    <i data-lucide="refresh-cw" style="width:22px;height:22px;"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/Frontend/assets/js/wangari-charts.js"></script>
<script>
const C = WangariCharts.C;
let charts = {};

function destroyAll() {
    Object.values(charts).forEach(c => c && c.destroy());
    charts = {};
}

async function loadAll() {
    const btn = document.querySelector('.refresh-btn');
    btn.classList.add('loading');
    try {
        const res = await fetch('/Backend/api/admin_analytics.php');
        const r = await res.json();
        if (!r.success) { console.error(r.message); return; }
        const d = r.data;
        destroyAll();
        renderKpis(d);
        renderProfitChart(d.profit);
        renderCashFlowChart(d.cashbook_flow);
        renderProductionChart(d.production);
        renderTopProductsChart(d.top_products);
        renderMortalityChart(d.mortality_by_batch);
        renderFcrChart(d.fcr_batches);
        renderGrowthChart(d.growth_curve, d.growth_batch_name);
        renderCreditAging(d.credit_aging);
        renderLowStockChart(d.low_stock);
        renderProductMix(d.product_mix);
        renderCustomerTypes(d.customer_types);
        renderBirdTrend(d.bird_trend);
        renderTopDebtors(d.top_debtors);
        renderRevenue7d(d.sales);
        WangariCharts.countUpAll();
        WangariCharts.animateCards('.chart-card');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) { console.error(e); }
    finally { btn.classList.remove('loading'); }
}

function renderKpis(d) {
    // Last 30 days cashbook
    const today = new Date();
    const monthAgo = new Date(today.getTime() - 30*86400000);
    const prev = new Date(today.getTime() - 60*86400000);
    let moneyIn = 0, moneyOut = 0, prevIn = 0, prevOut = 0;
    (d.cashbook_flow || []).forEach(c => {
        const dt = new Date(c.day);
        if (dt >= monthAgo) { moneyIn += +c.money_in || 0; moneyOut += +c.money_out || 0; }
        else if (dt >= prev) { prevIn += +c.money_in || 0; prevOut += +c.money_out || 0; }
    });
    let totalEggs = 0;
    (d.production || []).forEach(p => totalEggs += +p.eggs || 0);
    let totalMort = 0;
    (d.production || []).forEach(p => totalMort += +p.mortality || 0);
    const totalBirds = (d.fcr_batches || []).reduce((s,b) => s + (+b.current_birds || 0), 0);
    const totalOwed = d.credit_aging ? +d.credit_aging.total_owed || 0 : 0;
    const lowStock = (d.low_stock || []).length;

    // Update countup targets
    document.querySelector('[data-prefix="KES "]').dataset.countup = Math.round(moneyIn);
    document.querySelectorAll('[data-prefix="KES "]')[1].dataset.countup = Math.round(moneyOut);
    document.querySelectorAll('[data-prefix="KES "]')[2].dataset.countup = Math.round(moneyIn - moneyOut);
    document.querySelector('[data-suffix=" eggs"]').dataset.countup = Math.round(totalEggs);
    document.querySelectorAll('[data-suffix=" birds"]')[0].dataset.countup = Math.round(totalMort);
    document.querySelectorAll('[data-prefix="KES "]')[3].dataset.countup = Math.round(totalOwed);
    document.querySelectorAll('.stat-card strong').forEach((el, i) => {
        // first 8 stat cards already updated above via dataset
    });

    // Trends
    const inPct = prevIn > 0 ? Math.round(((moneyIn - prevIn) / prevIn) * 100) : 0;
    const outPct = prevOut > 0 ? Math.round(((moneyOut - prevOut) / prevOut) * 100) : 0;
    const profitPct = (prevIn - prevOut) > 0 ? Math.round((((moneyIn - moneyOut) - (prevIn - prevOut)) / (prevIn - prevOut)) * 100) : 0;
    document.getElementById('kpi-money-in-trend').textContent = (inPct >= 0 ? '+' : '') + inPct + '% vs prev period';
    document.getElementById('kpi-money-out-trend').textContent = (outPct >= 0 ? '+' : '') + outPct + '% vs prev period';
    document.getElementById('kpi-profit-trend').textContent = (profitPct >= 0 ? '+' : '') + profitPct + '% vs prev period';
    document.getElementById('kpi-profit-trend').className = 'stat-pill ' + (profitPct < 0 ? 'down' : '');
    document.getElementById('kpi-money-in-trend').className = 'stat-pill ' + (inPct < 0 ? 'down' : '');
    document.getElementById('kpi-money-out-trend').className = 'stat-pill down';

    // Total birds
    const birdsCard = document.querySelectorAll('.stat-card strong');
    // Update bird count & low stock cards (positions vary)
    const cardStrong = document.querySelectorAll('.stat-card strong');
    cardStrong.forEach((el) => {
        if (el.textContent.trim() === '0' && !el.dataset.countup) {
            // skip
        }
    });
    // More targeted
    const allStrong = document.querySelectorAll('.stat-card strong');
    allStrong[4].dataset.countup = totalBirds;
    allStrong[6].dataset.countup = lowStock;

    // sales trend pill
    const salesPct = prevIn > 0 ? Math.round((inPct || 0)) : 0;
    document.getElementById('sales-trend-pill').textContent = (salesPct >= 0 ? '+' : '') + salesPct + '% vs prev period';
}

function renderProfitChart(profit) {
    const labels = (profit || []).map(p => WangariCharts.monthLabel(p.month));
    const rev = (profit || []).map(p => +p.revenue || 0);
    const cost = (profit || []).map(p => +p.cost || 0);
    const ctx = document.getElementById('chart-profit');
    charts.profit = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Revenue', data: rev, backgroundColor: (c) => { const g = c.chart.ctx.createLinearGradient(0,0,0,300); g.addColorStop(0,'rgba(27,94,32,0.85)'); g.addColorStop(1,'rgba(27,94,32,0.35)'); return g; }, borderRadius: 6, maxBarThickness: 30 },
                { label: 'Costs',   data: cost, backgroundColor: (c) => { const g = c.chart.ctx.createLinearGradient(0,0,0,300); g.addColorStop(0,'rgba(220,38,38,0.85)'); g.addColorStop(1,'rgba(220,38,38,0.35)'); return g; }, borderRadius: 6, maxBarThickness: 30 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', align: 'end' },
                tooltip: { callbacks: { label: (c) => `${c.dataset.label}: KES ${c.parsed.y.toLocaleString()}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: C.text } },
                y: { grid: { color: C.grid }, ticks: { color: C.text, callback: v => WangariCharts.kes(v) }, beginAtZero: true },
            },
        },
    });
}

function renderCashFlowChart(cashbook) {
    const labels = (cashbook || []).map(c => WangariCharts.dayLabel(c.day));
    const min = (cashbook || []).map(c => +c.money_in || 0);
    const mout = (cashbook || []).map(c => -+c.money_out || 0);
    const ctx = document.getElementById('chart-cashflow');
    charts.cashflow = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [
            { label: 'Money In',  data: min, backgroundColor: 'rgba(22,163,74,0.8)', borderRadius: 4, stack: 's' },
            { label: 'Money Out', data: mout, backgroundColor: 'rgba(220,38,38,0.8)', borderRadius: 4, stack: 's' },
        ]},
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', align: 'end' },
                tooltip: { callbacks: { label: (c) => `${c.dataset.label}: KES ${Math.abs(c.parsed.y).toLocaleString()}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: C.text } },
                y: { grid: { color: C.grid }, ticks: { color: C.text, callback: v => WangariCharts.kes(v) } },
            },
        },
    });
}

function renderProductionChart(prod) {
    const labels = (prod || []).map(p => WangariCharts.dayLabel(p.day));
    const eggs = (prod || []).map(p => +p.eggs || 0);
    const ctx = document.getElementById('chart-production');
    charts.production = WangariCharts.areaChart(ctx, labels, eggs, { color: C.amber });
}

function renderTopProductsChart(top) {
    const labels = (top || []).map(p => p.name || '');
    const values = (top || []).map(p => +p.qty || 0);
    const ctx = document.getElementById('chart-top-products');
    charts.top = WangariCharts.hBarChart(ctx, labels, values, { color: C.primary });
}

function renderMortalityChart(m) {
    const labels = (m || []).map(x => x.batch_name || '');
    const values = (m || []).map(x => +x.mortality_pct || 0);
    const colors = values.map(v => v > 10 ? C.red : (v > 5 ? C.amber : C.green));
    const ctx = document.getElementById('chart-mortality');
    charts.mortality = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 4, maxBarThickness: 24 }] },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => `${c.parsed.x.toFixed(1)}% mortality` } },
            },
            scales: {
                x: { grid: { color: C.grid }, ticks: { color: C.text, callback: v => v + '%' }, beginAtZero: true },
                y: { grid: { display: false }, ticks: { color: C.text, font: { size: 10 } } },
            },
        },
    });
}

function renderFcrChart(batches) {
    const labels = (batches || []).map(b => b.batch_name || '');
    const values = (batches || []).map(b => +b.fcr || 0);
    const colors = values.map(v => v > 2.2 ? C.red : (v > 1.8 ? C.amber : C.green));
    const ctx = document.getElementById('chart-fcr');
    charts.fcr = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 4, maxBarThickness: 24 }] },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => `FCR: ${c.parsed.x.toFixed(2)}` } },
            },
            scales: {
                x: { grid: { color: C.grid }, ticks: { color: C.text }, beginAtZero: true },
                y: { grid: { display: false }, ticks: { color: C.text, font: { size: 10 } } },
            },
        },
    });
}

function renderGrowthChart(growth, batchName) {
    if (batchName) document.getElementById('growth-batch-name').textContent = 'Batch: ' + batchName;
    const labels = (growth || []).map(g => 'Day ' + g.day_number);
    const values = (growth || []).map(g => +g.avg_weight_kg || 0);
    const ctx = document.getElementById('chart-growth');
    charts.growth = WangariCharts.lineChart(ctx, labels, [{ data: values, label: 'Avg Weight (kg)', color: C.primary }], {});
}

function renderCreditAging(ca) {
    ca = ca || {};
    const data = {
        labels: ['Current', '1-30 days overdue', '30+ days overdue'],
        values: [+ca.current_due || 0, +ca.overdue_1_30 || 0, +ca.overdue_30 || 0],
    };
    const colors = [C.green, C.amber, C.red];
    const ctx = document.getElementById('chart-credit-aging');
    charts.credit = WangariCharts.donutChart(ctx, data.labels, data.values, { colors, legendPos: 'bottom' });
}

function renderLowStockChart(items) {
    const labels = (items || []).map(i => i.material_name || '');
    const values = (items || []).map(i => +i.current_stock || 0);
    const ctx = document.getElementById('chart-low-stock');
    charts.lowstock = WangariCharts.hBarChart(ctx, labels, values, { color: C.red });
}

function renderProductMix(items) {
    const labels = (items || []).map(i => i.product_type || 'Other');
    const values = (items || []).map(i => +i.qty || 0);
    const ctx = document.getElementById('chart-product-mix');
    charts.productmix = WangariCharts.donutChart(ctx, labels, values, { legendPos: 'bottom' });
}

function renderCustomerTypes(items) {
    const labels = (items || []).map(i => i.customer_type || 'Other');
    const values = (items || []).map(i => +i.cnt || 0);
    const ctx = document.getElementById('chart-customer-types');
    charts.customertypes = WangariCharts.donutChart(ctx, labels, values, { legendPos: 'bottom' });
}

function renderBirdTrend(bt) {
    const labels = (bt || []).map(b => WangariCharts.dayLabel(b.day));
    const values = (bt || []).map(b => +b.birds || 0);
    const ctx = document.getElementById('chart-bird-trend');
    charts.birdtrend = WangariCharts.lineChart(ctx, labels, [{ data: values, label: 'Live Birds', color: C.green }], {});
}

function renderTopDebtors(td) {
    const labels = (td || []).map(d => d.customer_name || '');
    const values = (td || []).map(d => +d.total_owed || 0);
    const ctx = document.getElementById('chart-top-debtors');
    charts.topdebtors = WangariCharts.hBarChart(ctx, labels, values, { color: C.amber });
}

function renderRevenue7d(sales) {
    const labels = (sales || []).map(s => WangariCharts.dayLabel(s.day));
    const values = (sales || []).map(s => +s.total || 0);
    const ctx = document.getElementById('chart-revenue-7d');
    charts.revenue7d = WangariCharts.barChart(ctx, labels, values, { color: C.green, radius: 6 });
}

document.addEventListener('DOMContentLoaded', () => {
    loadAll();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
