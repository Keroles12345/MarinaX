import Chart from 'chart.js/auto';

const config = document.getElementById('admin-chart-data');
if (config) {
    const data = JSON.parse(config.textContent);

document.addEventListener('DOMContentLoaded', function () {
    const indigo = '#3b49df';
    const emerald = '#34d399';
    const sky = '#38bdf8';

    // Sparklines
    document.querySelectorAll('canvas.spark').forEach(function (c) {
        const data = c.dataset.values.split(',').map(Number).sort(() => Math.random() - 0.5);
        new Chart(c, {
            type: 'line',
            data: { labels: data.map((_, i) => i), datasets: [{ data, borderColor: indigo, borderWidth: 2, pointRadius: 0, tension: 0.4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } } }
        });
    });

    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: data.months,
            datasets: [
                { label: 'Revenue', data: data.revenue, borderColor: indigo, backgroundColor: 'rgba(59,73,223,0.12)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: indigo, borderWidth: 2 },
                { label: 'Sales', data: data.sales, borderColor: emerald, backgroundColor: 'rgba(52,211,153,0.12)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: emerald, borderWidth: 2 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#f1f5f9' }, ticks: { stepSize: 50 } }
            }
        }
    });

    new Chart(document.getElementById('radarChart'), {
        type: 'radar',
        data: {
            labels: data.radarLabels,
            datasets: [
                { label: 'Sales', data: data.radarSales, borderColor: sky, backgroundColor: 'rgba(56,189,248,0.35)', borderWidth: 2, pointRadius: 0 },
                { label: 'Orders', data: data.radarOrders, borderColor: indigo, backgroundColor: 'rgba(59,73,223,0.25)', borderWidth: 2, pointRadius: 0 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { r: { grid: { color: '#e5e7eb' }, angleLines: { color: '#e5e7eb' }, ticks: { display: false }, pointLabels: { font: { size: 11 }, color: '#9ca3af' } } }
        }
    });
});
}
