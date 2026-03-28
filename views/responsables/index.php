<?php
require_once __DIR__ . '/../../models/PlanAccion.php';

// Obtener todos los owners únicos
$stmt = $pdo->query("SELECT DISTINCT owner FROM planes_accion WHERE owner IS NOT NULL AND owner != '' ORDER BY owner");
$owners = $stmt->fetchAll(PDO::FETCH_COLUMN);

$ownerSeleccionado = $_GET['owner'] ?? null;
$datosPorOwner = [];

if ($ownerSeleccionado) {
    // PDAs del owner
    $stmt = $pdo->prepare("SELECT pa.*, ie.codigo as ie_codigo, ie.nombre as ie_nombre
                           FROM planes_accion pa
                           JOIN iniciativas_estrategicas ie ON pa.iniciativa_id = ie.id
                           WHERE pa.owner = ? ORDER BY ie.codigo, pa.codigo");
    $stmt->execute([$ownerSeleccionado]);
    $datosPorOwner['planes'] = $stmt->fetchAll();

    // Actividades asignadas
    $stmt = $pdo->prepare("SELECT a.*, pa.codigo as plan_codigo, pa.nombre as plan_nombre,
                                  ie.codigo as ie_codigo
                           FROM actividades a
                           JOIN planes_accion pa ON a.plan_accion_id = pa.id
                           JOIN iniciativas_estrategicas ie ON pa.iniciativa_id = ie.id
                           WHERE a.responsable = ? ORDER BY ie.codigo, pa.codigo, a.codigo");
    $stmt->execute([$ownerSeleccionado]);
    $datosPorOwner['actividades'] = $stmt->fetchAll();

    // Compromisos pendientes
    $stmt = $pdo->prepare("SELECT c.*, r.titulo as reunion_titulo
                           FROM compromisos c
                           JOIN reuniones r ON c.reunion_id = r.id
                           WHERE c.responsable = ? AND c.estado != 'completado'
                           ORDER BY c.fecha_limite");
    $stmt->execute([$ownerSeleccionado]);
    $datosPorOwner['compromisos'] = $stmt->fetchAll();

    // Riesgos asignados
    $stmt = $pdo->prepare("SELECT r.*, ie.codigo as ie_codigo
                           FROM riesgos r
                           LEFT JOIN iniciativas_estrategicas ie ON r.iniciativa_id = ie.id
                           WHERE r.responsable = ? AND r.estado = 'abierto'
                           ORDER BY FIELD(r.nivel, 'critico','alto','medio','bajo')");
    $stmt->execute([$ownerSeleccionado]);
    $datosPorOwner['riesgos'] = $stmt->fetchAll();

    // Proyectos a cargo
    $stmt = $pdo->prepare("SELECT p.* FROM proyectos p WHERE p.responsable = ? ORDER BY p.prioridad DESC, p.nombre");
    $stmt->execute([$ownerSeleccionado]);
    $datosPorOwner['proyectos'] = $stmt->fetchAll();
}

require_once __DIR__ . '/../layout/header.php';
?>

<h4 class="mb-4"><i class="bi bi-person-badge me-2"></i>Vista por Responsable</h4>

<!-- Selector de responsable -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="responsables">
            <div class="col-md-6">
                <label class="form-label">Seleccionar Responsable</label>
                <select class="form-select" name="owner" onchange="this.form.submit()">
                    <option value="">-- Elegir --</option>
                    <?php foreach ($owners as $o): ?>
                        <option value="<?= sanitize($o) ?>" <?= ($ownerSeleccionado === $o) ? 'selected' : '' ?>><?= sanitize($o) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($ownerSeleccionado): ?>

<!-- Resumen -->
<div class="row row-cols-2 row-cols-md-5 g-3 mb-4">
    <div class="col">
        <div class="card dashboard-card border-start border-primary border-4">
            <div class="card-body text-center">
                <div class="stat-number text-primary"><?= count($datosPorOwner['planes']) ?></div>
                <small class="text-muted">Planes de Acción</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card dashboard-card border-start border-info border-4">
            <div class="card-body text-center">
                <div class="stat-number text-info"><?= count($datosPorOwner['actividades']) ?></div>
                <small class="text-muted">Actividades Asignadas</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card dashboard-card border-start border-warning border-4">
            <div class="card-body text-center">
                <div class="stat-number text-warning"><?= count($datosPorOwner['compromisos']) ?></div>
                <small class="text-muted">Compromisos Pendientes</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card dashboard-card border-start border-danger border-4">
            <div class="card-body text-center">
                <div class="stat-number text-danger"><?= count($datosPorOwner['riesgos']) ?></div>
                <small class="text-muted">Riesgos Abiertos</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card dashboard-card border-start border-success border-4">
            <div class="card-body text-center">
                <div class="stat-number text-success"><?= count($datosPorOwner['proyectos']) ?></div>
                <small class="text-muted">Proyectos</small>
            </div>
        </div>
    </div>
</div>

<!-- PDAs -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Planes de Acción</h6></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>IE</th><th>Código</th><th>Nombre</th><th>Avance</th><th>Estado</th></tr></thead>
            <tbody>
                <?php foreach ($datosPorOwner['planes'] as $p): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= sanitize($p['ie_codigo']) ?></span></td>
                    <td><?= sanitize($p['codigo']) ?></td>
                    <td><a href="<?= BASE_URL ?>index.php?page=planes&action=detalle&id=<?= $p['id'] ?>"><?= sanitize($p['nombre']) ?></a></td>
                    <td>
                        <div class="progress progress-sm" style="width:100px">
                            <div class="progress-bar" style="width:<?= $p['avance'] ?>%"><?= $p['avance'] ?>%</div>
                        </div>
                    </td>
                    <td><?= estadoBadge($p['estado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Actividades -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-activity me-2"></i>Actividades</h6></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>IE</th><th>PDA</th><th>Código</th><th>Descripción</th><th>Estado</th><th>Fecha Límite</th></tr></thead>
            <tbody>
                <?php foreach ($datosPorOwner['actividades'] as $a): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= sanitize($a['ie_codigo']) ?></span></td>
                    <td><small><?= sanitize($a['plan_codigo']) ?></small></td>
                    <td><?= sanitize($a['codigo']) ?></td>
                    <td><?= sanitize(mb_substr($a['descripcion'], 0, 60)) ?></td>
                    <td><?= estadoBadge($a['estado']) ?></td>
                    <td><?= formatDate($a['fecha_limite']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Compromisos pendientes -->
<?php if (!empty($datosPorOwner['compromisos'])): ?>
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-check2-square me-2"></i>Compromisos Pendientes</h6></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Reunión</th><th>Descripción</th><th>Fecha Límite</th><th>Estado</th></tr></thead>
            <tbody>
                <?php foreach ($datosPorOwner['compromisos'] as $c): ?>
                <tr>
                    <td><small><?= sanitize($c['reunion_titulo']) ?></small></td>
                    <td><?= sanitize($c['descripcion']) ?></td>
                    <td><?= formatDate($c['fecha_limite']) ?></td>
                    <td><?= estadoBadge($c['estado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Riesgos abiertos -->
<?php if (!empty($datosPorOwner['riesgos'])): ?>
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Riesgos Abiertos</h6></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Nivel</th><th>IE</th><th>Descripción</th></tr></thead>
            <tbody>
                <?php foreach ($datosPorOwner['riesgos'] as $r): ?>
                <tr>
                    <td><?= nivelRiesgoBadge($r['nivel']) ?></td>
                    <td><?= $r['ie_codigo'] ? sanitize($r['ie_codigo']) : '-' ?></td>
                    <td><?= sanitize(mb_substr($r['descripcion'], 0, 80)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Proyectos -->
<?php if (!empty($datosPorOwner['proyectos'])): ?>
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-kanban me-2"></i>Proyectos</h6></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Nombre</th><th>Avance</th><th>Estado</th><th>Prioridad</th><th>Inicio</th><th>Fin</th></tr></thead>
            <tbody>
                <?php
                $prioridadLabels = [1 => 'Baja', 2 => 'Media', 3 => 'Alta', 4 => 'Muy Alta', 5 => 'Crítica'];
                $prioridadClases = [1 => 'bg-secondary', 2 => 'bg-info text-dark', 3 => 'bg-warning text-dark', 4 => 'bg-danger', 5 => 'bg-dark'];
                foreach ($datosPorOwner['proyectos'] as $proy):
                    $avProy = intval($proy['avance']);
                    $barColor = $avProy >= 75 ? 'bg-success' : ($avProy >= 40 ? 'bg-warning' : 'bg-danger');
                ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>index.php?page=proyectos&action=detalle&id=<?= $proy['id'] ?>"><?= sanitize($proy['nombre']) ?></a></td>
                    <td style="min-width:110px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:8px;">
                                <div class="progress-bar <?= $barColor ?>" style="width:<?= $avProy ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $avProy ?>%</small>
                        </div>
                    </td>
                    <td><?= estadoBadge($proy['estado']) ?></td>
                    <td><span class="badge <?= $prioridadClases[$proy['prioridad']] ?? 'bg-secondary' ?>"><?= $prioridadLabels[$proy['prioridad']] ?? '-' ?></span></td>
                    <td><?= $proy['fecha_inicio'] ? formatDate($proy['fecha_inicio']) : '-' ?></td>
                    <td><?= $proy['fecha_fin'] ? formatDate($proy['fecha_fin']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Gantt simplificado -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Timeline de PDAs</h6></div>
    <div class="card-body">
        <?php
        $hoy = time();
        $minFecha = PHP_INT_MAX;
        $maxFecha = 0;
        foreach ($datosPorOwner['planes'] as $p) {
            if ($p['fecha_inicio']) $minFecha = min($minFecha, strtotime($p['fecha_inicio']));
            if ($p['fecha_fin']) $maxFecha = max($maxFecha, strtotime($p['fecha_fin']));
        }
        if ($minFecha === PHP_INT_MAX) $minFecha = $hoy;
        if ($maxFecha === 0) $maxFecha = strtotime('+6 months');
        $rangoTotal = max($maxFecha - $minFecha, 1);
        ?>
        <?php foreach ($datosPorOwner['planes'] as $p):
            $inicio = $p['fecha_inicio'] ? strtotime($p['fecha_inicio']) : $minFecha;
            $fin = $p['fecha_fin'] ? strtotime($p['fecha_fin']) : $maxFecha;
            $left = (($inicio - $minFecha) / $rangoTotal) * 100;
            $width = max((($fin - $inicio) / $rangoTotal) * 100, 5);
            $color = $p['estado'] === 'completado' ? '#198754' : ($p['estado'] === 'en_progreso' ? '#0d6efd' : '#6c757d');
        ?>
        <div class="mb-2">
            <small class="d-block mb-1"><?= sanitize($p['codigo'] . ': ' . $p['nombre']) ?></small>
            <div style="position:relative; height:24px; background:#e9ecef; border-radius:4px;">
                <div class="gantt-bar" style="position:absolute; left:<?= $left ?>%; width:<?= $width ?>%; background:<?= $color ?>;">
                    <div class="gantt-bar-progress" style="width:<?= $p['avance'] ?>%; background:rgba(255,255,255,0.3);"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($datosPorOwner['planes'])): ?>
            <p class="text-muted">Sin PDAs con fechas definidas.</p>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
