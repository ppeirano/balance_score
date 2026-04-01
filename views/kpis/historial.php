<?php
require_once __DIR__ . '/../../models/Kpi.php';

$kpi = Kpi::getById($pdo, $id);
if (!$kpi) {
    flash('error', 'KPI no encontrado.');
    redirect('index.php?page=kpis');
}

$historial = Kpi::getHistorial($pdo, $id);

// Determine semaforo
if ($kpi['tipo'] === 'cuantitativo') {
    $semaforo = calcularSemaforo(
        $kpi['valor_actual'],
        $kpi['meta'],
        $kpi['umbral_verde'],
        $kpi['umbral_amarillo'],
        $kpi['direccion']
    );
} else {
    $semaforo = $kpi['estado_semaforo'] ?? 'gris';
}

// Opciones for cualitativo dropdown
$opciones = [];
if ($kpi['tipo'] === 'cualitativo' && !empty($kpi['opciones_cualitativas'])) {
    $opciones = array_map('trim', explode(',', $kpi['opciones_cualitativas']));
}

$coloresSemaforo = [
    'verde' => '#198754',
    'amarillo' => '#ffc107',
    'rojo' => '#dc3545',
    'gris' => '#6c757d'
];

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clock-history me-2"></i>Historial de KPI</h2>
    <a href="<?= BASE_URL ?>index.php?page=kpis" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a KPIs
    </a>
</div>

<!-- KPI Detail Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-1 text-center">
                <span style="display:inline-block; width:48px; height:48px; border-radius:50%; background-color:<?= $coloresSemaforo[$semaforo] ?? '#6c757d' ?>;" title="<?= ucfirst($semaforo) ?>"></span>
            </div>
            <div class="col-md-5">
                <h4 class="mb-1"><?= sanitize($kpi['nombre']) ?></h4>
                <p class="text-muted mb-1">
                    <?php if ($kpi['iniciativa_nombre']): ?>
                        <i class="bi bi-bullseye me-1"></i><?= sanitize($kpi['iniciativa_codigo'] . ' - ' . $kpi['iniciativa_nombre']) ?>
                    <?php else: ?>
                        <span class="text-muted">Sin IE asignada</span>
                    <?php endif; ?>
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                        <span class="badge-info">Cuantitativo</span>
                    <?php else: ?>
                        <span class="badge text-white" style="background-color: #6f42c1;">Cualitativo</span>
                    <?php endif; ?>
                    <span class="badge-neutral"><?= sanitize(ucfirst($kpi['frecuencia'] ?? '-')) ?></span>
                    <?php if (!empty($kpi['responsable'])): ?>
                        <span class="badge-neutral"><i class="bi bi-person me-1"></i><?= sanitize($kpi['responsable']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                    <div class="fs-2 fw-bold"><?= $kpi['valor_actual'] !== null ? formatKpiValor($kpi['valor_actual'], $kpi['es_entero'] ?? 0) : '-' ?></div>
                    <small class="text-muted">de <?= formatKpiValor($kpi['meta'], $kpi['es_entero'] ?? 0) ?> <?= sanitize($kpi['unidad'] ?? '') ?></small>
                <?php else: ?>
                    <div class="fs-4 fw-bold"><?= $kpi['valor_cualitativo'] ? sanitize($kpi['valor_cualitativo']) : '-' ?></div>
                    <small class="text-muted">Escala: <?= sanitize(str_replace('_', ' ', ucfirst($kpi['escala_cualitativa'] ?? '-'))) ?></small>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <table class="table table-sm table-borderless mb-0">
                    <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                        <tr><td class="text-muted">Unidad:</td><td><?= sanitize($kpi['unidad'] ?? '-') ?></td></tr>
                        <tr><td class="text-muted">Direcci&oacute;n:</td><td><?= $kpi['direccion'] === 'mayor_mejor' ? 'Mayor es mejor' : 'Menor es mejor' ?></td></tr>
                        <tr><td class="text-muted">Umbral verde:</td><td>&ge; <?= sanitize($kpi['umbral_verde'] ?? '90') ?>%</td></tr>
                        <tr><td class="text-muted">Umbral amarillo:</td><td>&ge; <?= sanitize($kpi['umbral_amarillo'] ?? '70') ?>%</td></tr>
                    <?php else: ?>
                        <tr><td class="text-muted">Escala:</td><td><?= sanitize(str_replace('_', ' ', ucfirst($kpi['escala_cualitativa'] ?? '-'))) ?></td></tr>
                        <tr><td class="text-muted">Opciones:</td><td><?= sanitize($kpi['opciones_cualitativas'] ?? '-') ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- Registrar nuevo valor -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Registrar nuevo valor</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=kpis&action=registrar_valor">
            <input type="hidden" name="kpi_id" value="<?= (int)$kpi['id'] ?>">

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Per&iacute;odo *</label>
                    <input type="date" class="form-control" name="periodo" value="<?= date('Y-m-d') ?>" required>
                </div>

                <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Valor *</label>
                        <input type="number" class="form-control" name="valor" step="<?= ($kpi['es_entero'] ?? 0) ? '1' : 'any' ?>" required>
                    </div>
                <?php else: ?>
                    <div class="col-md-3">
                        <label class="form-label">Valor Cualitativo *</label>
                        <select class="form-select" name="valor_cualitativo" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($opciones as $opcion): ?>
                                <option value="<?= sanitize($opcion) ?>"><?= sanitize($opcion) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sem&aacute;foro</label>
                        <select class="form-select" name="semaforo">
                            <option value="verde">Verde</option>
                            <option value="amarillo">Amarillo</option>
                            <option value="rojo">Rojo</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label">Observaciones</label>
                    <input type="text" class="form-control" name="observaciones" placeholder="Comentario opcional">
                </div>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i>Registrar Valor
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Historial table -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Historial de mediciones</h5>
    </div>
    <?php if (!empty($historial)): ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Per&iacute;odo</th>
                    <th>Valor</th>
                    <th>Sem&aacute;foro</th>
                    <th>Observaciones</th>
                    <th>Fecha Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($historial) as $reg): ?>
                    <tr>
                        <td><?= formatDate($reg['periodo']) ?></td>
                        <td>
                            <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                                <?= formatKpiValor($reg['valor'], $kpi['es_entero'] ?? 0) ?> <?= sanitize($kpi['unidad'] ?? '') ?>
                            <?php else: ?>
                                <?= sanitize($reg['valor_cualitativo'] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= semaforoBadge($reg['semaforo'] ?? 'gris') ?></td>
                        <td><?= sanitize($reg['observaciones'] ?? '-') ?></td>
                        <td><?= formatDate($reg['created_at'] ?? $reg['periodo']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="card-body">
        <p class="text-muted mb-0">No hay mediciones registradas a&uacute;n.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Chart for cuantitativo KPIs -->
<?php if ($kpi['tipo'] === 'cuantitativo' && !empty($historial)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Gr&aacute;fico de tendencia</h5>
    </div>
    <div class="card-body">
        <canvas id="kpiChart" height="100"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const historialData = <?= json_encode(array_values($historial)) ?>;
    const meta = <?= json_encode($kpi['meta']) ?>;

    const labels = historialData.map(function(r) {
        const d = new Date(r.periodo);
        return d.toLocaleDateString('es-ES', { year: 'numeric', month: 'short' });
    });
    const valores = historialData.map(function(r) {
        return parseFloat(r.valor);
    });
    const metaLine = historialData.map(function() {
        return parseFloat(meta);
    });

    const ctx = document.getElementById('kpiChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Valor',
                    data: valores,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointBackgroundColor: '#0d6efd'
                },
                {
                    label: 'Meta (<?= sanitize($kpi['meta'] ?? '') ?>)',
                    data: metaLine,
                    borderColor: '#dc3545',
                    borderDash: [8, 4],
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: '<?= sanitize($kpi['unidad'] ?? 'Valor') ?>'
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
