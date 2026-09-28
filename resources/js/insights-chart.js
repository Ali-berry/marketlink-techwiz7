// farmer insights page ka weekly revenue chart, sirf wahi page load karta hai
import Chart from 'chart.js/auto';

document.querySelectorAll('[data-revenue-chart]').forEach((canvas) => {
    const weeklyRevenue = JSON.parse(canvas.dataset.weeklyRevenue ?? '[]');

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: weeklyRevenue.map((week) => week.label),
            datasets: [{
                label: 'Revenue',
                data: weeklyRevenue.map((week) => week.total),
                borderColor: '#2F6B3F',
                backgroundColor: 'rgba(47, 107, 63, 0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
            },
            scales: {
                y: { beginAtZero: true },
            },
        },
    });
});
