import gsap from 'gsap';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

async function loadAnalytics() {
  const res = await fetch('/Backend/api/admin_analytics.php');
  const json = await res.json();
  if (!json.success) {
    console.error('Analytics load failed', json);
    return;
  }

  const sales = json.data.sales.map((s) => ({ day: s.day, total: parseFloat(s.total) }));
  const orders = json.data.orders.map((o) => ({ day: o.day, cnt: parseInt(o.cnt, 10) }));
  const labels = sales.map((s) => s.day);
  const salesData = sales.map((s) => s.total);
  const ordersData = orders.map((o) => o.cnt);

  const salesChart = new Chart(document.getElementById('chart-sales'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Revenue',
          data: salesData,
          borderColor: '#1B5E20',
          backgroundColor: 'rgba(27,94,32,0.16)',
          fill: true,
          tension: 0.35,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: '#1B5E20',
        },
      ],
    },
    options: {
      responsive: true,
      animation: {
        duration: 1200,
        easing: 'easeOutQuart',
      },
      plugins: {
        legend: { display: false },
        tooltip: { intersect: false, mode: 'index' },
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#475569' } },
        y: { grid: { color: 'rgba(148,163,184,0.2)' }, ticks: { color: '#475569' } },
      },
    },
  });

  new Chart(document.getElementById('chart-orders'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Orders',
          data: ordersData,
          backgroundColor: '#047857',
          borderRadius: 10,
          maxBarThickness: 22,
        },
      ],
    },
    options: {
      responsive: true,
      animation: { duration: 1100, easing: 'easeOutQuart' },
      plugins: { legend: { display: false }, tooltip: { intersect: false, mode: 'index' } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#475569' } },
        y: { grid: { color: 'rgba(148,163,184,0.2)' }, ticks: { color: '#475569', precision: 0 } },
      },
    },
  });

  const top = json.data.top_products;
  const topLabels = top.map((t) => t.name);
  const topData = top.map((t) => parseInt(t.qty, 10));

  new Chart(document.getElementById('chart-top-products'), {
    type: 'bar',
    data: {
      labels: topLabels,
      datasets: [
        {
          label: 'Units',
          data: topData,
          backgroundColor: '#2563EB',
          borderRadius: 10,
          maxBarThickness: 18,
        },
      ],
    },
    options: {
      responsive: true,
      animation: { duration: 1000, easing: 'easeOutElastic' },
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y} units` } } },
      indexAxis: 'y',
      scales: {
        x: { grid: { display: false }, ticks: { color: '#475569' } },
        y: { grid: { display: false }, ticks: { color: '#475569' } },
      },
    },
  });

  const low = json.data.inventory;
  const ul = document.getElementById('low-stock');
  ul.innerHTML = low
    .map((it) => `<li>${it.name}: ${it.stock_quantity}</li>`)
    .join('');

  const totalSales = salesData.reduce((acc, value) => acc + value, 0);
  const totalOrders = ordersData.reduce((acc, value) => acc + value, 0);
  const avg = totalOrders ? Math.round(totalSales / totalOrders) : 0;

  gsap.fromTo(
    '#kpi-sales',
    { opacity: 0, y: 20 },
    { opacity: 1, y: 0, duration: 0.9, delay: 0.2 }
  );
  gsap.fromTo(
    '#kpi-orders',
    { opacity: 0, y: 20 },
    { opacity: 1, y: 0, duration: 0.9, delay: 0.35 }
  );
  gsap.fromTo(
    '#kpi-avg',
    { opacity: 0, y: 20 },
    { opacity: 1, y: 0, duration: 0.9, delay: 0.5 }
  );

  const kpiSalesEl = document.getElementById('kpi-sales');
  const kpiOrdersEl = document.getElementById('kpi-orders');
  const kpiAvgEl = document.getElementById('kpi-avg');

  gsap.to({ value: 0 }, {
    duration: 1.4,
    value: totalSales,
    ease: 'power2.out',
    onUpdate(self) {
      kpiSalesEl.textContent = `KES ${Math.round(self.value).toLocaleString()}`;
    },
  });

  gsap.to({ value: 0 }, {
    duration: 1.2,
    value: totalOrders,
    ease: 'power2.out',
    onUpdate(self) {
      kpiOrdersEl.textContent = Math.round(self.value).toLocaleString();
    },
  });

  gsap.to({ value: 0 }, {
    duration: 1.2,
    value: avg,
    ease: 'power2.out',
    onUpdate(self) {
      kpiAvgEl.textContent = `KES ${Math.round(self.value).toLocaleString()}`;
    },
  });
}

document.addEventListener('DOMContentLoaded', loadAnalytics);
