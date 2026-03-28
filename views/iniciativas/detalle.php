<?php
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/PlanAccion.php';
require_once __DIR__ . '/../../models/Kpi.php';
require_once __DIR__ . '/../../models/Riesgo.php';
require_once __DIR__ . '/../../models/Proyecto.php';

if (!$id) {
    flash('error', 'Iniciativa no especificada.');
    redirect('index.php?page=iniciativas');
}

// Obtener la iniciativa con su perspectiva
$stmtIe = $pdo->prepare("
    SELECT ie.*, p.nombre AS perspectiva_nombre, p.color AS perspectiva_color, p.icono AS perspectiva_icono
    FROM iniciativas_estrategicas ie
    JOIN perspectivas p ON ie.perspectiva_id = p.id
    WHERE ie.id = ?
");
$stmtIe->execute([$id]);
$ie = $stmtIe->fetch();

if (!$ie) {
    flash('error', 'Iniciativa no encontrada.');
    redirect('index.php?page=iniciativas');
}

// Obtener planes de accion de esta iniciativa
$stmtPdas = $pdo->prepare("
    SELECT pa.*
    FROM planes_accion pa
    WHERE pa.iniciativa_id = ?
    ORDER BY pa.codigo ASC
");
$stmtPdas->execute([$id]);
$pdas = $stmtPdas->fetchAll();

// Obtener KPIs asociados a esta iniciativa
$stmtKpis = $pdo->prepare("
    SELECT k.*
    FROM kpis k
    WHERE k.iniciativa_id = ?
    ORDER BY k.nombre ASC
");
$stmtKpis->execute([$id]);
$kpis = $stmtKpis->fetchAll();

// Obtener riesgos asociados a esta iniciativa
$stmtRiesgos = $pdo->prepare("
    SELECT r.*
    FROM riesgos r
    WHERE r.iniciativa_id = ?
    ORDER BY r.created_at DESC
");
$stmtRiesgos->execute([$id]);
$riesgos = $stmtRiesgos->fetchAll();

// Obtener proyectos vinculados a esta iniciativa
$stmtProy = $pdo->prepare("
    SELECT p.*
    FROM proyectos p
    INNER JOIN proyecto_vinculos pv ON p.id = pv.proyecto_id
    WHERE pv.entidad_tipo = 'iniciativa' AND pv.entidad_id = ?
    ORDER BY p.nombre
");
$stmtProy->execute([$id]);
$proyectos = $stmtProy->fetchAll();

// Calcular avance general de la IE
$totalPeso = 0;
$avancePonderado = 0;
foreach ($pdas as $p) {
    if ($p['peso'] > 0) {
        $totalPeso += $p['peso'];
        $avancePonderado += $p['avance'] * $p['peso'];
    }
}
$avanceIe = ($totalPeso > 0) ? round($avancePonderado / $totalPeso) : 0;

require_once __DIR__ . '/../layout/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php?page=iniciativas">Iniciativas</a></li>
        <li class="breadcrumb-item active"><?= sanitize($ie['codigo']) ?> - <?= sanitize($ie['nombre']) ?></li>
    </ol>
</nav>

<!-- Header de la IE -->
<div class="card mb-4" style="border-left: 5px solid <?= sanitize($ie['perspectiva_color']) ?>;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div></div>
            <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=editar&id=<?= (int)$ie['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Editar IE
            </a>
        </div>
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-1">
                    <span class="badge me-2" style="background-color: <?= sanitize($ie['perspectiva_color']) ?>;">
                        <?= sanitize($ie['codigo']) ?>
                    </span>
                    <?= sanitize($ie['nombre']) ?>
                </h2>
                <p class="text-muted mb-2">
                    <span class="badge" style="background-color: <?= sanitize($ie['perspectiva_color']) ?>;">
                        <i class="bi <?= sanitize($ie['perspectiva_icono'] ?? 'bi-circle') ?> me-1"></i>
                        <?= sanitize($ie['perspectiva_nombre']) ?>
                    </span>
                </p>
                <?php if (!empty($ie['descripcion'])): ?>
                    <p class="mb-0"><?= sanitize($ie['descripcion']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="mb-2">
                    <small class="text-muted d-block">Avance General</small>
                    <span class="fs-2 fw-bold"><?= $avanceIe ?>%</span>
                </div>
                <div class="progress" style="height: 12px;">
                    <?php
                    $barClass = 'bg-danger';
                    if ($avanceIe >= 70) $barClass = 'bg-success';
                    elseif ($avanceIe >= 40) $barClass = 'bg-warning';
                    elseif ($avanceIe >= 20) $barClass = 'bg-info';
                    ?>
                    <div class="progress-bar <?= $barClass ?>" role="progressbar"
                         style="width: <?= $avanceIe ?>%;"
                         aria-valuenow="<?= $avanceIe ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Planes de Accion -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Planes de Acci&oacute;n (<?= count($pdas) ?>)</h5>
        <a href="<?= BASE_URL ?>index.php?page=planes&action=crear&iniciativa_id=<?= (int)$ie['id'] ?>"
           class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Agregar Plan de Acci&oacute;n
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($pdas)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">C&oacute;digo</th>
                            <th>Nombre</th>
                            <th>Owner</th>
                            <th style="width: 180px;">Avance</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Prioridad</th>
                            <th class="text-center">Peso %</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pdas as $pda): ?>
                            <?php
                            $avancePda = (int)$pda['avance'];
                            $barClassPda = 'bg-danger';
                            if ($avancePda >= 70) $barClassPda = 'bg-success';
                            elseif ($avancePda >= 40) $barClassPda = 'bg-warning';
                            elseif ($avancePda >= 20) $barClassPda = 'bg-info';

                            $prioridadMap = [
                                1 => ['texto' => 'Muy Alta', 'badge' => 'bg-danger'],
                                2 => ['texto' => 'Alta', 'badge' => 'bg-danger'],
                                3 => ['texto' => 'Media', 'badge' => 'bg-warning text-dark'],
                                4 => ['texto' => 'Baja', 'badge' => 'bg-info text-dark'],
                                5 => ['texto' => 'Muy Baja', 'badge' => 'bg-secondary'],
                            ];
                            $prio = $prioridadMap[(int)$pda['prioridad']] ?? $prioridadMap[3];
                            $prioridadTexto = $prio['texto'];
                            $prioridadBadge = $prio['badge'];
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= sanitize($pda['codigo']) ?></span></td>
                                <td>
                                    <a href="<?= BASE_URL ?>index.php?page=planes&action=detalle&id=<?= (int)$pda['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= sanitize($pda['nombre']) ?>
                                    </a>
                                </td>
                                <td><?= $pda['owner'] ? sanitize($pda['owner']) : '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar <?= $barClassPda ?>" role="progressbar"
                                                 style="width: <?= $avancePda ?>%;"
                                                 aria-valuenow="<?= $avancePda ?>" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="fw-bold"><?= $avancePda ?>%</small>
                                    </div>
                                </td>
                                <td class="text-center"><?= estadoBadge($pda['estado']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $prioridadBadge ?>"><?= $prioridadTexto ?></span>
                                </td>
                                <td class="text-center"><?= number_format($pda['peso'], 1) ?>%</td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>index.php?page=planes&action=detalle&id=<?= (int)$pda['id'] ?>"
                                       class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No hay planes de acci&oacute;n para esta iniciativa.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- KPIs -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>KPIs Asociados (<?= count($kpis) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($kpis)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Meta</th>
                            <th class="text-center">Valor Actual</th>
                            <th class="text-center">Sem&aacute;foro</th>
                            <th class="text-center">Frecuencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kpis as $kpi): ?>
                            <?php
                            $semaforo = 'gris';
                            if ($kpi['tipo'] === 'cuantitativo' && $kpi['valor_actual'] !== null && $kpi['meta'] !== null) {
                                $semaforo = calcularSemaforo(
                                    $kpi['valor_actual'],
                                    $kpi['meta'],
                                    $kpi['umbral_verde'],
                                    $kpi['umbral_amarillo'],
                                    $kpi['direccion']
                                );
                            } elseif ($kpi['tipo'] === 'cualitativo' && $kpi['estado_semaforo']) {
                                $semaforo = $kpi['estado_semaforo'];
                            }
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($kpi['nombre']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $kpi['tipo'] === 'cuantitativo' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                        <?= ucfirst(sanitize($kpi['tipo'])) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($kpi['tipo'] === 'cuantitativo' && $kpi['meta'] !== null): ?>
                                        <?= number_format($kpi['meta'], 2) ?> <?= sanitize($kpi['unidad'] ?? '') ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($kpi['tipo'] === 'cuantitativo' && $kpi['valor_actual'] !== null): ?>
                                        <?= number_format($kpi['valor_actual'], 2) ?> <?= sanitize($kpi['unidad'] ?? '') ?>
                                    <?php elseif ($kpi['tipo'] === 'cualitativo' && $kpi['valor_cualitativo']): ?>
                                        <?= sanitize($kpi['valor_cualitativo']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin registro</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= semaforoBadge($semaforo) ?></td>
                                <td class="text-center"><?= ucfirst(sanitize($kpi['frecuencia'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-graph-up fs-1 d-block mb-2"></i>
                No hay KPIs asociados a esta iniciativa.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Riesgos -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Riesgos (<?= count($riesgos) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($riesgos)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descripci&oacute;n</th>
                            <th class="text-center">Probabilidad</th>
                            <th class="text-center">Impacto</th>
                            <th class="text-center">Nivel</th>
                            <th class="text-center">Estado</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riesgos as $riesgo): ?>
                            <tr>
                                <td>
                                    <?= sanitize(mb_substr($riesgo['descripcion'], 0, 150)) ?>
                                    <?= mb_strlen($riesgo['descripcion']) > 150 ? '...' : '' ?>
                                </td>
                                <td class="text-center"><?= ucfirst(sanitize($riesgo['probabilidad'])) ?></td>
                                <td class="text-center"><?= ucfirst(sanitize($riesgo['impacto'])) ?></td>
                                <td class="text-center"><?= nivelRiesgoBadge($riesgo['nivel']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $estadoRiesgoClases = [
                                        'abierto' => 'bg-danger',
                                        'mitigado' => 'bg-warning text-dark',
                                        'cerrado' => 'bg-success',
                                        'materializado' => 'bg-dark'
                                    ];
                                    $claseEstado = $estadoRiesgoClases[$riesgo['estado']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $claseEstado ?>"><?= ucfirst(sanitize($riesgo['estado'])) ?></span>
                                </td>
                                <td><?= $riesgo['responsable'] ? sanitize($riesgo['responsable']) : '<span class="text-muted">-</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-shield-check fs-1 d-block mb-2"></i>
                No hay riesgos registrados para esta iniciativa.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Proyectos Vinculados -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-kanban me-2"></i>Proyectos Vinculados (<?= count($proyectos) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($proyectos)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Responsable</th>
                            <th class="text-center">Avance</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Prioridad</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proyectos as $proy):
                            $estadoProyClases = [
                                'pendiente' => 'bg-secondary',
                                'en_progreso' => 'bg-primary',
                                'completado' => 'bg-success',
                                'cancelado' => 'bg-danger',
                                'suspendido' => 'bg-warning text-dark'
                            ];
                            $claseProy = $estadoProyClases[$proy['estado']] ?? 'bg-secondary';
                        ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>index.php?page=proyectos&action=detalle&id=<?= $proy['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= sanitize($proy['nombre']) ?>
                                    </a>
                                </td>
                                <td><?= $proy['responsable'] ? sanitize($proy['responsable']) : '<span class="text-muted">-</span>' ?></td>
                                <td class="text-center" style="min-width:120px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <?php
                                            $avProy = intval($proy['avance']);
                                            $barColor = $avProy >= 75 ? 'bg-success' : ($avProy >= 40 ? 'bg-warning' : 'bg-danger');
                                            ?>
                                            <div class="progress-bar <?= $barColor ?>" style="width: <?= $avProy ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $avProy ?>%</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $claseProy ?>"><?= ucfirst(str_replace('_', ' ', sanitize($proy['estado']))) ?></span>
                                </td>
                                <td class="text-center">
                                    <?= prioridadBadge($proy['prioridad']) ?>
                                </td>
                                <td><?= $proy['fecha_inicio'] ? formatDate($proy['fecha_inicio']) : '<span class="text-muted">-</span>' ?></td>
                                <td><?= $proy['fecha_fin'] ? formatDate($proy['fecha_fin']) : '<span class="text-muted">-</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-kanban fs-1 d-block mb-2"></i>
                No hay proyectos vinculados a esta iniciativa.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Boton volver -->
<div class="mb-4">
    <a href="<?= BASE_URL ?>index.php?page=iniciativas" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a Iniciativas
    </a>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
