// BSC Temis Lostalo - JavaScript principal

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});

// Helper para crear gráficos de tendencia de KPIs
function crearGraficoTendencia(canvasId, labels, valores, meta, unidad) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    const datasets = [{
        label: 'Valor',
        data: valores,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,0.1)',
        fill: true,
        tension: 0.3,
        pointRadius: 4,
        pointBackgroundColor: '#0d6efd'
    }];

    if (meta !== null) {
        datasets.push({
            label: 'Meta',
            data: Array(labels.length).fill(meta),
            borderColor: '#dc3545',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: false
        });
    }

    new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    title: { display: !!unidad, text: unidad }
                }
            },
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}
