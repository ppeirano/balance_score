<?php
// Dashboard - Recopilar datos
$perspectivas = $pdo->query("SELECT * FROM perspectivas ORDER BY orden")->fetchAll();

// Datos por perspectiva
$datosPersp = [];
foreach ($perspectivas as $p) {
    $stmt = $pdo->prepare("SELECT ie.id, ie.codigo, ie.nombre FROM iniciativas_estrategicas ie WHERE ie.perspectiva_id = ?");
    $stmt->execute([$p['id']]);
    $ies = $stmt->fetchAll();

    $totalAvance = 0;
    $totalIes = count($ies);
    foreach ($ies as &$ie) {
        // Calcular avance ponderado de la IE
        $stmt2 = $pdo->prepare("SELECT avance, peso FROM planes_accion WHERE iniciativa_id = ?");
        $stmt2->execute([$ie['id']]);
        $pdas = $stmt2->fetchAll();
        $pesoTotal = array_sum(array_column($pdas, 'peso'));
        $avancePonderado = 0;
        if ($pesoTotal > 0) {
            foreach ($pdas as $pda) {
                $avancePonderado += ($pda['avance'] * $pda['peso']) / $pesoTotal;
            }
        } elseif (count($pdas) > 0) {
            $avancePonderado = array_sum(array_column($pdas, 'avance')) / count($pdas);
        }
        $ie['avance'] = round($avancePonderado);
        $totalAvance += $ie['avance'];
    }
    unset($ie);

    $datosPersp[$p['id']] = [
        'perspectiva' => $p,
        'iniciativas' => $ies,
        'avance_promedio' => $totalIes > 0 ? round($totalAvance / $totalIes) : 0
    ];
}

// KPIs en rojo
$kpisRojos = [];
$allKpis = $pdo->query("SELECT k.*, ie.codigo as ie_codigo FROM kpis k LEFT JOIN iniciativas_estrategicas ie ON k.iniciativa_id = ie.id WHERE k.activo = 1")->fetchAll();
foreach ($allKpis as $k) {
    if ($k['tipo'] === 'cuantitativo' && $k['valor_actual'] !== null) {
        $semaforo = calcularSemaforo($k['valor_actual'], $k['meta'], $k['umbral_verde'], $k['umbral_amarillo'], $k['direccion']);
        if ($semaforo === 'rojo') {
            $k['semaforo'] = $semaforo;
            $kpisRojos[] = $k;
        }
    } elseif ($k['tipo'] === 'cualitativo' && $k['estado_semaforo'] === 'rojo') {
        $k['semaforo'] = 'rojo';
        $kpisRojos[] = $k;
    }
}

// Compromisos pendientes
$compromisosPend = $pdo->query("SELECT c.*, r.titulo as reunion_titulo FROM compromisos c JOIN reuniones r ON c.reunion_id = r.id WHERE c.estado != 'completado' ORDER BY c.fecha_limite LIMIT 10")->fetchAll();

// Riesgos críticos
$riesgosCriticos = $pdo->query("SELECT r.*, ie.codigo as ie_codigo FROM riesgos r LEFT JOIN iniciativas_estrategicas ie ON r.iniciativa_id = ie.id WHERE r.estado = 'abierto' AND r.nivel IN ('critico','alto') ORDER BY FIELD(r.nivel,'critico','alto') LIMIT 5")->fetchAll();

// Contadores generales
$totalPDAs = $pdo->query("SELECT COUNT(*) FROM planes_accion")->fetchColumn();
$pdasCompletados = $pdo->query("SELECT COUNT(*) FROM planes_accion WHERE estado = 'completado'")->fetchColumn();
$totalActividades = $pdo->query("SELECT COUNT(*) FROM actividades")->fetchColumn();
$actividadesCompletadas = $pdo->query("SELECT COUNT(*) FROM actividades WHERE estado = 'completado'")->fetchColumn();

require_once __DIR__ . '/../layout/header.php';
?>

<!-- Tarjetas por Perspectiva -->
<div class="row mb-4">
    <?php foreach ($datosPersp as $dp): ?>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card perspective-card" style="border-left-color: <?= $dp['perspectiva']['color'] ?>;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title" style="color: <?= $dp['perspectiva']['color'] ?>;"><?= sanitize($dp['perspectiva']['nombre']) ?></h6>
                        <small class="text-muted"><?= count($dp['iniciativas']) ?> iniciativa<?= count($dp['iniciativas']) != 1 ? 's' : '' ?></small>
                    </div>
                    <div class="stat-number" style="color: <?= $dp['perspectiva']['color'] ?>; font-size:1.5rem;">
                        <?= $dp['avance_promedio'] ?>%
                    </div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar" style="width:<?= $dp['avance_promedio'] ?>%; background-color:<?= $dp['perspectiva']['color'] ?>;"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Contadores generales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="stat-number"><?= $totalPDAs ?></div>
                <small class="text-muted">Planes de Acción</small>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-success" style="width:<?= $totalPDAs > 0 ? round($pdasCompletados / $totalPDAs * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="stat-number"><?= $totalActividades ?></div>
                <small class="text-muted">Actividades</small>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-info" style="width:<?= $totalActividades > 0 ? round($actividadesCompletadas / $totalActividades * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="stat-number"><?= count($kpisRojos) ?></div>
                <small class="text-muted">KPIs en Rojo</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="stat-number"><?= count($compromisosPend) ?></div>
                <small class="text-muted">Compromisos Pendientes</small>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card dashboard-card">
            <div class="card-header"><h6 class="mb-0">Avance por Perspectiva</h6></div>
            <div class="card-body" style="height:300px;">
                <canvas id="chartPerspectivas"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card dashboard-card">
            <div class="card-header"><h6 class="mb-0">Avance por Iniciativa Estratégica</h6></div>
            <div class="card-body" style="height:300px;">
                <canvas id="chartIEs"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Alertas -->
<div class="row mb-4">
    <!-- KPIs en rojo -->
    <div class="col-md-6">
        <div class="card dashboard-card">
            <div class="card-header bg-danger bg-opacity-10">
                <h6 class="mb-0 text-danger"><i class="bi bi-exclamation-circle me-2"></i>KPIs que Requieren Atención</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($kpisRojos)): ?>
                <table class="table table-sm">
                    <thead><tr><th>IE</th><th>KPI</th><th>Actual</th><th>Meta</th></tr></thead>
                    <tbody>
                        <?php foreach ($kpisRojos as $k): ?>
                        <tr>
                            <td><span class="badge-info"><?= sanitize($k['ie_codigo'] ?? '-') ?></span></td>
                            <td><a href="<?= BASE_URL ?>index.php?page=kpis&action=historial&id=<?= $k['id'] ?>"><?= sanitize($k['nombre']) ?></a></td>
                            <td class="text-danger fw-bold"><?= $k['valor_actual'] ?> <?= sanitize($k['unidad'] ?? '') ?></td>
                            <td><?= $k['meta'] ?> <?= sanitize($k['unidad'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-success mb-0"><i class="bi bi-check-circle me-2"></i>Todos los KPIs dentro de parámetros.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Riesgos críticos -->
    <div class="col-md-6">
        <div class="card dashboard-card">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="mb-0 text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Riesgos Críticos</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($riesgosCriticos)): ?>
                <table class="table table-sm">
                    <thead><tr><th>Nivel</th><th>IE</th><th>Descripción</th></tr></thead>
                    <tbody>
                        <?php foreach ($riesgosCriticos as $r): ?>
                        <tr>
                            <td><?= nivelRiesgoBadge($r['nivel']) ?></td>
                            <td><?= sanitize($r['ie_codigo'] ?? '-') ?></td>
                            <td><?= sanitize(mb_substr($r['descripcion'], 0, 60)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-success mb-0"><i class="bi bi-check-circle me-2"></i>Sin riesgos críticos.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Compromisos pendientes -->
<?php if (!empty($compromisosPend)): ?>
<div class="card dashboard-card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-check2-square me-2"></i>Compromisos Pendientes</h6></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Reunión</th><th>Compromiso</th><th>Responsable</th><th>Fecha Límite</th><th>Estado</th></tr></thead>
            <tbody>
                <?php foreach ($compromisosPend as $c): ?>
                <tr>
                    <td><small><?= sanitize($c['reunion_titulo']) ?></small></td>
                    <td><?= sanitize(mb_substr($c['descripcion'], 0, 60)) ?></td>
                    <td><?= sanitize($c['responsable'] ?? '-') ?></td>
                    <td>
                        <?= formatDate($c['fecha_limite']) ?>
                        <?php if ($c['fecha_limite'] && strtotime($c['fecha_limite']) < time()): ?>
                            <span class="badge-bad">Vencido</span>
                        <?php endif; ?>
                    </td>
                    <td><?= estadoBadge($c['estado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Perspectivas (Radar)
    const ctxPersp = document.getElementById('chartPerspectivas').getContext('2d');
    new Chart(ctxPersp, {
        type: 'radar',
        data: {
            labels: <?= json_encode(array_map(function($dp) { return $dp['perspectiva']['nombre']; }, array_values($datosPersp))) ?>,
            datasets: [{
                label: 'Avance %',
                data: <?= json_encode(array_map(function($dp) { return $dp['avance_promedio']; }, array_values($datosPersp))) ?>,
                backgroundColor: 'rgba(13,110,253,0.2)',
                borderColor: '#0d6efd',
                pointBackgroundColor: <?= json_encode(array_map(function($dp) { return $dp['perspectiva']['color']; }, array_values($datosPersp))) ?>,
                pointRadius: 6,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { r: { beginAtZero: true, max: 100 } },
            plugins: { legend: { display: false } }
        }
    });

    // Gráfico de IEs (Barras horizontales)
    const ctxIEs = document.getElementById('chartIEs').getContext('2d');
    <?php
    $ieLabels = [];
    $ieData = [];
    $ieColors = [];
    foreach ($datosPersp as $dp) {
        foreach ($dp['iniciativas'] as $ie) {
            $ieLabels[] = $ie['codigo'];
            $ieData[] = $ie['avance'];
            $ieColors[] = $dp['perspectiva']['color'];
        }
    }
    ?>
    new Chart(ctxIEs, {
        type: 'bar',
        data: {
            labels: <?= json_encode($ieLabels) ?>,
            datasets: [{
                label: 'Avance %',
                data: <?= json_encode($ieData) ?>,
                backgroundColor: <?= json_encode($ieColors) ?>,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { beginAtZero: true, max: 100 } },
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
