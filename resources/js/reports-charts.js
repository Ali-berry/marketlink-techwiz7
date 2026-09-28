// reports page ke saare charts data attribute se [{ label, value }] parhte hain, ek hi function sab banata hai
import Chart from 'chart.js/auto';

function renderChart(canvas, type) {
    const rows = JSON.parse(canvas.dataset.chartRows ?? '[]');
    const color = canvas.dataset.chartColor ?? '#2F6B3F';

    new Chart(canvas, {
        type,
        data: {
            labels: rows.map((row) => row.label),
            datasets: [{
                label: canvas.dataset.chartLabel ?? '',
                data: rows.map((row) => row.value),
                backgroundColor: type === 'bar' ? color : `${color}26`,
                borderColor: color,
                borderRadius: type === 'bar' ? 6 : 0,
                fill: type === 'line',
                tension: 0.3,
                pointRadius: type === 'line' ? 3 : 0,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

document.querySelectorAll('[data-bar-chart]').forEach((canvas) => renderChart(canvas, 'bar'));
document.querySelectorAll('[data-line-chart]').forEach((canvas) => renderChart(canvas, 'line'));
