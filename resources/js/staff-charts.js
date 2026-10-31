import Chart from 'chart.js/auto';

const config = document.getElementById('staff-chart-data');
if (config) {
    const data = JSON.parse(config.textContent);

document.addEventListener('DOMContentLoaded', function () {
        const navy = '#16233f';
        const orange = '#f6a934';

        new Chart(document.getElementById('resultChart'), {
            type: 'bar',
            data: {
                labels: data.months,
                datasets: [
                    { label: '2019', data: data.series2019, backgroundColor: navy, borderRadius: 4, barPercentage: 0.55 },
                    { label: '2020', data: data.series2020, backgroundColor: orange, borderRadius: 4, barPercentage: 0.55 },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { stepSize: 20 } }
                }
            }
        });

        new Chart(document.getElementById('waveChart'), {
            type: 'line',
            data: {
                labels: data.months,
                datasets: [
                    {
                        label: 'Lorem ipsum',
                        data: data.series2020,
                        borderColor: orange,
                        backgroundColor: 'rgba(246,169,52,0.25)',
                        fill: true,
                        tension: 0.45,
                        pointRadius: 0,
                    },
                    {
                        label: 'Dolor Amet',
                        data: data.series2019,
                        borderColor: navy,
                        backgroundColor: 'rgba(22,35,63,0.08)',
                        fill: true,
                        tension: 0.45,
                        pointRadius: 0,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });

        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [45, 55],
                    backgroundColor: [orange, navy],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
    });
}
