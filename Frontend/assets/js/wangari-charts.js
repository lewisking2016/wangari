/**
 * Wangari Chicken — Charts Library
 * Reusable animated Chart.js helpers + count-up stat cards.
 * All charts share the same color palette and animation timing.
 */
(function (global) {
    'use strict';

    // Brand colors
    const C = {
        primary:   '#1B5E20',
        primaryLt: '#2E7D32',
        accent:    '#FFC107',
        amber:     '#FF8F00',
        green:     '#16a34a',
        greenLt:   '#86efac',
        red:       '#dc2626',
        redLt:     '#fca5a5',
        blue:      '#2563eb',
        blueLt:    '#93c5fd',
        purple:    '#7c3aed',
        purpleLt:  '#c4b5fd',
        cyan:      '#0891b2',
        cyanLt:    '#67e8f9',
        pink:      '#db2777',
        orange:    '#ea580c',
        text:      '#475569',
        textLt:    '#94a3b8',
        grid:      'rgba(148,163,184,0.12)',
    };

    // Gradient helper
    function grad(ctx, color, height = 320) {
        const g = ctx.createLinearGradient(0, 0, 0, height);
        g.addColorStop(0, color + '40');
        g.addColorStop(1, color + '00');
        return g;
    }

    // Default font
    Chart.defaults.font.family = "'Inter', 'Outfit', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = C.text;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.padding = 14;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.95)';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { weight: 'bold' };
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxPadding = 6;
    Chart.defaults.animations.duration = 900;
    Chart.defaults.animations.easing = 'easeOutQuart';

    function baseScales(opts) {
        opts = opts || {};
        return {
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: C.text, font: { size: 11 } },
            },
            y: {
                grid: { color: C.grid, drawBorder: false },
                ticks: { color: C.text, font: { size: 11 } },
                beginAtZero: true,
            },
        };
    }

    function basePlugins(title, fmt) {
        return {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: fmt || {},
                },
            },
        };
    }

    // ────────────────────────────────────────────────────────────
    // Line chart with smooth area
    // ────────────────────────────────────────────────────────────
    function lineChart(canvas, labels, datasets, opts) {
        opts = opts || {};
        const ctx = canvas.getContext('2d');
        const ds = datasets.map((d, i) => {
            const color = d.color || [C.primary, C.amber, C.blue, C.purple][i % 4];
            return Object.assign({
                borderColor: color,
                backgroundColor: d.fill !== false ? grad(ctx, color, canvas.height) : color,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: color,
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                tension: 0.35,
                fill: d.fill !== false,
            }, d);
        });
        return new Chart(canvas, {
            type: 'line',
            data: { labels, datasets: ds },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
            }, baseScales(), basePlugins()),
        });
    }

    // ────────────────────────────────────────────────────────────
    // Bar chart (vertical)
    // ────────────────────────────────────────────────────────────
    function barChart(canvas, labels, values, opts) {
        opts = opts || {};
        const ctx = canvas.getContext('2d');
        const color = opts.color || C.primary;
        const data = {
            labels,
            datasets: [{
                data: values,
                backgroundColor: opts.gradient !== false ? grad(ctx, color, canvas.height) : color,
                borderColor: color,
                borderWidth: 0,
                borderRadius: opts.radius !== undefined ? opts.radius : 6,
                borderSkipped: false,
                maxBarThickness: opts.maxBarThickness || 48,
            }],
        };
        return new Chart(canvas, {
            type: 'bar',
            data,
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: opts.tooltip || {},
                },
                scales: baseScales(),
            }),
        });
    }

    // ────────────────────────────────────────────────────────────
    // Horizontal bar chart (for top products, low stock, etc.)
    // ────────────────────────────────────────────────────────────
    function hBarChart(canvas, labels, values, opts) {
        opts = opts || {};
        const color = opts.color || C.primary;
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: color,
                    borderRadius: 4,
                    borderSkipped: false,
                    barThickness: 18,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: C.grid }, ticks: { color: C.text, font: { size: 11 } }, beginAtZero: true },
                    y: { grid: { display: false }, ticks: { color: C.text, font: { size: 11 } } },
                },
            },
        });
    }

    // ────────────────────────────────────────────────────────────
    // Stacked bar (e.g. mortality vs eggs)
    // ────────────────────────────────────────────────────────────
    function stackedBar(canvas, labels, datasets) {
        const ctx = canvas.getContext('2d');
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: datasets.map((d, i) => Object.assign({
                    backgroundColor: d.color || [C.green, C.red, C.amber, C.blue][i % 4],
                    borderRadius: 4,
                    borderSkipped: false,
                    maxBarThickness: 30,
                }, d)),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: C.text } },
                    y: { stacked: true, grid: { color: C.grid }, ticks: { color: C.text }, beginAtZero: true },
                },
            },
        });
    }

    // ────────────────────────────────────────────────────────────
    // Donut chart with center text
    // ────────────────────────────────────────────────────────────
    function donutChart(canvas, labels, values, opts) {
        opts = opts || {};
        const colors = opts.colors || [C.primary, C.amber, C.blue, C.purple, C.pink, C.cyan, C.green, C.red, C.orange];
        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((_, i) => colors[i % colors.length]),
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 12,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: opts.cutout || '68%',
                plugins: {
                    legend: {
                        position: opts.legendPos || 'right',
                        labels: { boxWidth: 10, padding: 12, font: { size: 11 } },
                    },
                    tooltip: {
                        callbacks: {
                            label: (c) => ` ${c.label}: ${c.parsed.toLocaleString()}`,
                        },
                    },
                },
            },
        });
    }

    // ────────────────────────────────────────────────────────────
    // Area chart (similar to line but filled emphasis)
    // ────────────────────────────────────────────────────────────
    function areaChart(canvas, labels, values, opts) {
        opts = opts || {};
        const color = opts.color || C.primary;
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: values,
                    borderColor: color,
                    backgroundColor: (ctx) => {
                        const c = ctx.chart.ctx.createLinearGradient(0, 0, 0, 280);
                        c.addColorStop(0, color + '55');
                        c.addColorStop(1, color + '05');
                        return c;
                    },
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: color,
                    tension: 0.4,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: baseScales(),
            },
        });
    }

    // ────────────────────────────────────────────────────────────
    // Combo: bars + line on same axis
    // ────────────────────────────────────────────────────────────
    function comboChart(canvas, labels, barData, lineData, opts) {
        opts = opts || {};
        const ctx = canvas.getContext('2d');
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    Object.assign({
                        type: 'bar',
                        label: barData.label || 'Value',
                        data: barData.values,
                        backgroundColor: grad(ctx, C.primary, canvas.height),
                        borderRadius: 6,
                        borderSkipped: false,
                        yAxisID: 'y',
                        maxBarThickness: 36,
                    }, barData),
                    Object.assign({
                        type: 'line',
                        label: lineData.label || 'Trend',
                        data: lineData.values,
                        borderColor: C.amber,
                        backgroundColor: C.amber,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: C.amber,
                        pointBorderWidth: 2,
                        tension: 0.35,
                        yAxisID: 'y1',
                    }, lineData),
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { grid: { display: false }, ticks: { color: C.text } },
                    y: { type: 'linear', position: 'left', grid: { color: C.grid }, ticks: { color: C.text }, beginAtZero: true, title: { display: true, text: barData.label || '', color: C.text } },
                    y1: { type: 'linear', position: 'right', grid: { display: false }, ticks: { color: C.amber }, title: { display: true, text: lineData.label || '', color: C.amber } },
                },
            },
        });
    }

    // ────────────────────────────────────────────────────────────
    // Count-up animation for stat numbers
    // ────────────────────────────────────────────────────────────
    function countUp(el, target, opts) {
        opts = opts || {};
        const duration = opts.duration || 1500;
        const decimals = opts.decimals !== undefined ? opts.decimals : 0;
        const prefix = opts.prefix || '';
        const suffix = opts.suffix || '';
        const isCurrency = opts.currency === true;
        const start = opts.from !== undefined ? opts.from : 0;
        const startTime = performance.now();

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // ease-out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = start + (target - start) * eased;
            el.textContent = prefix + (isCurrency ? 'KES ' + value.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) : value.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals })) + suffix;
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function countUpAll() {
        document.querySelectorAll('[data-countup]').forEach(el => {
            const target = parseFloat(el.dataset.countup);
            const opts = {
                prefix: el.dataset.prefix || '',
                suffix: el.dataset.suffix || '',
                decimals: el.dataset.decimals ? parseInt(el.dataset.decimals) : 0,
                currency: el.dataset.currency === 'true',
            };
            countUp(el, target, opts);
        });
    }

    // ────────────────────────────────────────────────────────────
    // Sparkline (mini line in a small space)
    // ────────────────────────────────────────────────────────────
    function sparkline(canvas, values, color) {
        color = color || C.primary;
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: values.map((_, i) => i),
                datasets: [{
                    data: values,
                    borderColor: color,
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.4,
                    fill: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } },
                animations: { duration: 600 },
            },
        });
    }

    // ────────────────────────────────────────────────────────────
    // Animate stat cards entrance
    // ────────────────────────────────────────────────────────────
    function animateCards(selector) {
        const cards = document.querySelectorAll(selector || '.anim-card');
        cards.forEach((card, i) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, i * 60);
        });
    }

    // ────────────────────────────────────────────────────────────
    // Format helpers
    // ────────────────────────────────────────────────────────────
    function k(n) {
        if (n >= 1e9) return (n / 1e9).toFixed(1) + 'B';
        if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return n.toString();
    }
    function kes(n) {
        if (n >= 1e6) return 'KES ' + (n / 1e6).toFixed(2) + 'M';
        if (n >= 1e3) return 'KES ' + (n / 1e3).toFixed(1) + 'K';
        return 'KES ' + Math.round(n).toLocaleString();
    }
    function dayLabel(d) {
        if (!d) return '';
        const dt = new Date(d);
        if (isNaN(dt)) return d;
        return dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    }
    function monthLabel(ym) {
        if (!ym) return '';
        const [y, m] = ym.split('-');
        return new Date(parseInt(y), parseInt(m) - 1, 1).toLocaleDateString('en-GB', { month: 'short', year: '2-digit' });
    }

    // Export
    global.WangariCharts = {
        C,
        lineChart, barChart, hBarChart, stackedBar, donutChart, areaChart, comboChart,
        sparkline, countUp, countUpAll, animateCards,
        k, kes, dayLabel, monthLabel,
    };
})(window);
