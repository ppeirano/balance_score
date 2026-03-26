<?php
require_once __DIR__ . '/../../models/Proyecto.php';
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/PlanAccion.php';
require_once __DIR__ . '/../../models/Responsable.php';
require_once __DIR__ . '/../../models/ArchivoAdjunto.php';

$proyecto = Proyecto::getById($pdo, $id);
if (!$proyecto) { flash('error', 'Proyecto no encontrado.'); redirect('index.php?page=proyectos'); }
$vinculos = Proyecto::getVinculos($pdo, $id);
$entregables = Proyecto::getEntregables($pdo, $id);
$actividades = Proyecto::getActividades($pdo, $id);
$notas = Proyecto::getNotas($pdo, $id);
$adjuntos = ArchivoAdjunto::getByEntidad($pdo, 'proyecto', $id);
$iniciativas = Iniciativa::getAll($pdo);
$planes = PlanAccion::getAll($pdo);
$responsables = Responsable::getAll($pdo);

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4><i class="bi bi-kanban me-2"></i><?= sanitize($proyecto['nombre']) ?></h4>
        <p class="text-muted mb-0">
            <?= estadoBadge($proyecto['estado']) ?>
            <?= prioridadBadge($proyecto['prioridad']) ?>
            <?php if ($proyecto['responsable']): ?>
                <span class="ms-2"><i class="bi bi-person me-1"></i><?= sanitize($proyecto['responsable']) ?></span>
            <?php endif; ?>
            <?php if ($proyecto['periodo_nombre']): ?>
                <span class="ms-2"><i class="bi bi-calendar-range me-1"></i><?= sanitize($proyecto['periodo_nombre']) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>index.php?page=proyectos&action=editar&id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <a href="<?= BASE_URL ?>index.php?page=proyectos" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
</div>

<!-- Info general -->
<div class="card mb-4">
    <div class="card-body">
        <?php if ($proyecto['descripcion']): ?>
            <p class="mb-3" style="white-space: pre-wrap;"><?= sanitize($proyecto['descripcion']) ?></p>
        <?php endif; ?>
        <div class="row align-items-center">
            <div class="col-md-<?= $proyecto['presupuesto'] ? '4' : '6' ?>">
                <label class="form-label text-muted mb-1 small">Avance</label>
                <div class="progress" style="height: 22px;">
                    <div class="progress-bar <?= $proyecto['avance'] >= 75 ? 'bg-success' : ($proyecto['avance'] >= 40 ? 'bg-primary' : 'bg-warning') ?>"
                         style="width: <?= $proyecto['avance'] ?>%"><?= $proyecto['avance'] ?>%</div>
                </div>
            </div>
            <div class="col-md-<?= $proyecto['presupuesto'] ? '2' : '3' ?> text-center">
                <small class="text-muted d-block">Inicio</small>
                <strong><?= formatDate($proyecto['fecha_inicio']) ?></strong>
            </div>
            <div class="col-md-<?= $proyecto['presupuesto'] ? '2' : '3' ?> text-center">
                <small class="text-muted d-block">Fin</small>
                <strong><?= formatDate($proyecto['fecha_fin']) ?></strong>
            </div>
            <?php if ($proyecto['presupuesto']): ?>
            <div class="col-md-4 text-center">
                <small class="text-muted d-block">Presupuesto</small>
                <strong class="fs-5">$ <?= number_format($proyecto['presupuesto'], 2, ',', '.') ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Actividades + Gantt -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-list-task me-2"></i>Actividades</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nuevaActividad">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="nuevaActividad">
            <div class="card card-body bg-light">
                <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=guardar_actividad">
                    <input type="hidden" name="proyecto_id" value="<?= $id ?>">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="act_nombre" placeholder="Nombre de la actividad *" required>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" name="act_responsable">
                                <option value="">Responsable...</option>
                                <?php foreach ($responsables as $resp): ?>
                                    <option value="<?= sanitize($resp['nombre']) ?>"><?= sanitize($resp['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control form-control-sm" name="act_fecha_inicio" title="Fecha inicio">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control form-control-sm" name="act_fecha_fin" title="Fecha fin">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12">
                            <textarea class="form-control form-control-sm" name="act_descripcion" rows="2" placeholder="Descripción / detalle de la actividad (opcional)"></textarea>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de actividades -->
        <table class="table table-sm mb-4">
            <thead>
                <tr>
                    <th>Actividad</th>
                    <th>Responsable</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actividades as $a): ?>
                <tr class="act-row-<?= $a['id'] ?>">
                    <td>
                        <span class="fw-semibold"><?= sanitize($a['nombre']) ?></span>
                        <?php if ($a['descripcion']): ?>
                            <br><small class="text-muted" style="white-space: pre-wrap;"><?= sanitize($a['descripcion']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($a['responsable'] ?? '-') ?></td>
                    <td><?= formatDate($a['fecha_inicio']) ?></td>
                    <td><?= formatDate($a['fecha_fin']) ?></td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=cambiar_estado_actividad&id=<?= $a['id'] ?>" class="d-inline">
                            <input type="hidden" name="proyecto_id" value="<?= $id ?>">
                            <select name="estado" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                                <?php foreach (['pendiente'=>'Pendiente','en_progreso'=>'En progreso','completado'=>'Completado','cancelado'=>'Cancelado'] as $val=>$lab): ?>
                                    <option value="<?= $val ?>" <?= $a['estado'] == $val ? 'selected' : '' ?>><?= $lab ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="toggleEditAct(<?= $a['id'] ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=eliminar_actividad&id=<?= $a['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <!-- Fila de edición (oculta por defecto) -->
                <tr class="edit-act-<?= $a['id'] ?>" style="display:none; background: #f8f9fa;">
                    <td colspan="6">
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=guardar_actividad">
                            <input type="hidden" name="proyecto_id" value="<?= $id ?>">
                            <input type="hidden" name="actividad_id" value="<?= $a['id'] ?>">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-0">Nombre</label>
                                    <input type="text" class="form-control form-control-sm" name="act_nombre" value="<?= sanitize($a['nombre']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-0">Responsable</label>
                                    <select class="form-select form-select-sm" name="act_responsable">
                                        <option value="">Responsable...</option>
                                        <?php foreach ($responsables as $resp): ?>
                                            <option value="<?= sanitize($resp['nombre']) ?>" <?= ($a['responsable'] ?? '') == $resp['nombre'] ? 'selected' : '' ?>><?= sanitize($resp['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted mb-0">Inicio</label>
                                    <input type="date" class="form-control form-control-sm" name="act_fecha_inicio" value="<?= $a['fecha_inicio'] ?? '' ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted mb-0">Fin</label>
                                    <input type="date" class="form-control form-control-sm" name="act_fecha_fin" value="<?= $a['fecha_fin'] ?? '' ?>">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small text-muted mb-0">Estado</label>
                                    <select class="form-select form-select-sm" name="act_estado">
                                        <?php foreach (['pendiente'=>'Pendiente','en_progreso'=>'En progreso','completado'=>'Completado','cancelado'=>'Cancelado'] as $val=>$lab): ?>
                                            <option value="<?= $val ?>" <?= $a['estado'] == $val ? 'selected' : '' ?>><?= $lab ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-12">
                                    <label class="form-label small text-muted mb-0">Descripción</label>
                                    <textarea class="form-control form-control-sm" name="act_descripcion" rows="3" placeholder="Detalle de la actividad..."><?= sanitize($a['descripcion'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="toggleEditAct(<?= $a['id'] ?>)">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($actividades)): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin actividades registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Gantt Chart -->
        <?php
        $actConFechas = array_filter($actividades, fn($a) => $a['fecha_inicio'] && $a['fecha_fin']);
        if (!empty($actConFechas)):
            $allStarts = array_map(fn($a) => strtotime($a['fecha_inicio']), $actConFechas);
            $allEnds = array_map(fn($a) => strtotime($a['fecha_fin']), $actConFechas);
            $ganttStart = min($allStarts);
            $ganttEnd = max($allEnds);
            $totalDays = max(($ganttEnd - $ganttStart) / 86400 + 1, 1);

            // Línea de hoy
            $hoy = strtotime(date('Y-m-d'));
            $hoyPct = ($hoy >= $ganttStart && $hoy <= $ganttEnd) ? (($hoy - $ganttStart) / 86400) / $totalDays * 100 : null;

            // Meses
            $monthStart = strtotime(date('Y-m-01', $ganttStart));
            $months = [];
            while ($monthStart <= $ganttEnd) {
                $mStart = max($monthStart, $ganttStart);
                $mEnd = min(strtotime('+1 month', $monthStart) - 86400, $ganttEnd);
                $widthPct = (($mEnd - $mStart) / 86400 + 1) / $totalDays * 100;
                $label = date('M', $monthStart);
                $year = date('Y', $monthStart);
                $months[] = ['label' => $label, 'year' => $year, 'width' => $widthPct];
                $monthStart = strtotime('+1 month', $monthStart);
            }
        ?>
        <h6 class="mb-3"><i class="bi bi-bar-chart-steps me-2"></i>Diagrama Gantt</h6>
        <style>
            .gantt-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
            .gantt-table th, .gantt-table td { padding: 0; vertical-align: middle; }
            .gantt-table .gt-label { width: 220px; min-width: 220px; padding: 6px 12px 6px 0; font-size: 0.83rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-bottom: 1px solid #f0f0f0; }
            .gantt-table .gt-chart { position: relative; height: 30px; border-bottom: 1px solid #f0f0f0; }
            .gantt-bar { position: absolute; top: 4px; height: 22px; border-radius: 4px; min-width: 6px; display: flex; align-items: center; padding: 0 6px; font-size: 0.72rem; color: #fff; font-weight: 500; overflow: hidden; white-space: nowrap; box-shadow: 0 1px 3px rgba(0,0,0,0.15); transition: opacity 0.2s; }
            .gantt-bar:hover { opacity: 0.85; }
            .gantt-bar.st-pendiente { background: linear-gradient(135deg, #6c757d, #868e96); }
            .gantt-bar.st-en_progreso { background: linear-gradient(135deg, #0d6efd, #4d94ff); }
            .gantt-bar.st-completado { background: linear-gradient(135deg, #198754, #28a76d); }
            .gantt-bar.st-cancelado { background: linear-gradient(135deg, #dc3545, #e06570); opacity: 0.6; }
            .gantt-month-hd { font-size: 0.75rem; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 2px 0; text-align: center; border-left: 1px solid #dee2e6; }
            .gantt-month-hd:first-child { border-left: none; }
            .gantt-today { position: absolute; top: 0; bottom: 0; width: 2px; background: #dc3545; z-index: 2; }
            .gantt-today::before { content: 'Hoy'; position: absolute; top: -18px; left: -12px; font-size: 0.65rem; color: #dc3545; font-weight: 600; }
            .gantt-grid-line { position: absolute; top: 0; bottom: 0; width: 1px; background: #f0f0f0; }
        </style>
        <div style="overflow-x: auto;">
        <table class="gantt-table" style="min-width: 600px;">
            <colgroup>
                <col style="width: 220px;">
                <col>
            </colgroup>
            <!-- Month headers -->
            <thead><tr>
                <th></th>
                <th style="padding:0;">
                    <div style="display:flex; width:100%;">
                        <?php foreach ($months as $m): ?>
                            <div class="gantt-month-hd" style="width: <?= round($m['width'], 2) ?>%; min-width:0;">
                                <?= $m['label'] ?><br><small style="font-size:0.65rem;opacity:0.7"><?= $m['year'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </th>
            </tr></thead>
            <tbody>
            <?php foreach ($actConFechas as $a):
                $aStart = strtotime($a['fecha_inicio']);
                $aEnd = strtotime($a['fecha_fin']);
                $left = ($aStart - $ganttStart) / 86400 / $totalDays * 100;
                $width = max(($aEnd - $aStart) / 86400 + 1, 1) / $totalDays * 100;
                $duracion = max(round(($aEnd - $aStart) / 86400) + 1, 1);
            ?>
            <tr>
                <td class="gt-label" title="<?= sanitize($a['nombre']) ?>"><?= sanitize($a['nombre']) ?></td>
                <td class="gt-chart">
                    <?php // Grid lines por mes
                    $gridMonth = strtotime(date('Y-m-01', $ganttStart));
                    while ($gridMonth <= $ganttEnd) {
                        $gridMonth = strtotime('+1 month', $gridMonth);
                        if ($gridMonth <= $ganttEnd) {
                            $gPct = ($gridMonth - $ganttStart) / 86400 / $totalDays * 100;
                            echo '<div class="gantt-grid-line" style="left:' . round($gPct, 2) . '%"></div>';
                        }
                    }
                    ?>
                    <?php if ($hoyPct !== null): ?>
                        <div class="gantt-today" style="left: <?= round($hoyPct, 2) ?>%"></div>
                    <?php endif; ?>
                    <div class="gantt-bar st-<?= $a['estado'] ?>"
                         style="left: <?= round($left, 2) ?>%; width: <?= round($width, 2) ?>%"
                         title="<?= sanitize($a['nombre']) ?>&#10;<?= formatDate($a['fecha_inicio']) ?> - <?= formatDate($a['fecha_fin']) ?> (<?= $duracion ?> d&iacute;as)&#10;<?= sanitize($a['responsable'] ?? '') ?>">
                        <?= $width > 10 ? sanitize($a['responsable'] ?? '') : '' ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="d-flex gap-3 mt-2" style="font-size: 0.75rem;">
            <span><span class="d-inline-block rounded" style="width:12px;height:12px;background:#6c757d;"></span> Pendiente</span>
            <span><span class="d-inline-block rounded" style="width:12px;height:12px;background:#0d6efd;"></span> En progreso</span>
            <span><span class="d-inline-block rounded" style="width:12px;height:12px;background:#198754;"></span> Completado</span>
            <span><span class="d-inline-block rounded" style="width:12px;height:12px;background:#dc3545;opacity:0.6;"></span> Cancelado</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Entregables -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Entregables</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nuevoEntregable">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="nuevoEntregable">
            <div class="card card-body bg-light">
                <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=guardar_entregable">
                    <input type="hidden" name="proyecto_id" value="<?= $id ?>">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="nombre" placeholder="Nombre del entregable *" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="entregable_descripcion" placeholder="Descripción (opcional)">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select form-select-sm" name="entregable_responsable">
                                <option value="">Responsable...</option>
                                <?php foreach ($responsables as $resp): ?>
                                    <option value="<?= sanitize($resp['nombre']) ?>"><?= sanitize($resp['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" name="fecha_prevista">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" name="entregable_estado">
                                <option value="pendiente">Pendiente</option>
                                <option value="en_progreso">En progreso</option>
                                <option value="completado">Completado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-sm btn-primary">Guardar Entregable</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Responsable</th>
                    <th>Fecha Prevista</th>
                    <th>Fecha Real</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entregables as $e): ?>
                <tr>
                    <td class="fw-semibold"><?= sanitize($e['nombre']) ?></td>
                    <td><small><?= sanitize($e['descripcion'] ?? '-') ?></small></td>
                    <td><?= sanitize($e['responsable'] ?? '-') ?></td>
                    <td><?= formatDate($e['fecha_prevista']) ?></td>
                    <td><?= formatDate($e['fecha_real']) ?></td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=cambiar_estado_entregable&id=<?= $e['id'] ?>" class="d-inline">
                            <input type="hidden" name="proyecto_id" value="<?= $id ?>">
                            <select name="estado" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                                <option value="pendiente" <?= $e['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="en_progreso" <?= $e['estado'] == 'en_progreso' ? 'selected' : '' ?>>En progreso</option>
                                <option value="completado" <?= $e['estado'] == 'completado' ? 'selected' : '' ?>>Completado</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=eliminar_entregable&id=<?= $e['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($entregables)): ?>
                <tr><td colspan="7" class="text-center text-muted">Sin entregables registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Vínculos -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Vínculos con IEs y Planes</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nuevoVinculo">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="nuevoVinculo">
            <div class="card card-body bg-light">
                <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=guardar_vinculo">
                    <input type="hidden" name="proyecto_id" value="<?= $id ?>">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" name="entidad_tipo" id="vinculoTipo" onchange="toggleVinculoDropdown()">
                                <option value="iniciativa">Iniciativa Estratégica</option>
                                <option value="plan_accion">Plan de Acción</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" name="entidad_id" id="vinculoEntidad" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($iniciativas as $ie): ?>
                                    <option value="<?= $ie['id'] ?>" data-tipo="iniciativa">
                                        <?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php foreach ($planes as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-tipo="plan_accion" style="display:none;">
                                        <?= sanitize(($p['iniciativa_codigo'] ?? '') . ' / ' . $p['codigo'] . ' - ' . $p['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Vincular</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <table class="table table-sm">
            <thead><tr><th>Tipo</th><th>Código</th><th>Nombre</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($vinculos as $v): ?>
                <tr>
                    <td><?= $v['entidad_tipo'] === 'iniciativa' ? '<span class="badge bg-primary">IE</span>' : '<span class="badge bg-info text-dark">PDA</span>' ?></td>
                    <td><?= sanitize($v['entidad_codigo'] ?? '-') ?></td>
                    <td><?= sanitize($v['entidad_nombre'] ?? '-') ?></td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=eliminar_vinculo&id=<?= $v['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar vínculo?')">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($vinculos)): ?>
                <tr><td colspan="4" class="text-center text-muted">Sin vínculos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Historial de Notas -->
<style>
    .pnota-item:hover .btn-eliminar-pnota { opacity: 1; }
    .btn-eliminar-pnota { opacity: 0; transition: opacity 0.2s; }
    .pnota-timeline { position: relative; padding-left: 1.5rem; }
    .pnota-timeline::before { content: ''; position: absolute; left: 0.45rem; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
    .pnota-item { position: relative; }
    .pnota-item::before { content: ''; position: absolute; left: -1.05rem; top: 0.75rem; width: 8px; height: 8px; border-radius: 50%; background: #6c757d; border: 2px solid #fff; box-shadow: 0 0 0 2px #dee2e6; }
</style>
<div class="card mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Historial de Notas</h5>
        <?php if (!empty($notas)): ?>
            <span class="badge bg-light text-dark"><?= count($notas) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=guardar_nota" class="mb-4" id="formNotaProy">
            <input type="hidden" name="proyecto_id" value="<?= $id ?>">
            <input type="hidden" name="imagen" id="pNotaImagen" value="">
            <textarea class="form-control mb-2" name="texto" id="pNotaTexto" rows="2" placeholder="Escribir una nota... (pod&#233;s pegar im&#225;genes con Ctrl+V)" required></textarea>
            <div id="pNotaImagenPreview" class="mb-2" style="display:none;">
                <div class="position-relative d-inline-block">
                    <img id="pNotaImagenImg" src="" class="img-thumbnail" style="max-height: 150px;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="quitarImagenPNota()"><i class="bi bi-x"></i></button>
                </div>
                <small class="text-muted d-block mt-1"><i class="bi bi-image me-1"></i>Imagen adjunta</small>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Agregar nota</button>
            </div>
        </form>

        <?php if (!empty($notas)): ?>
            <div class="pnota-timeline">
                <?php foreach ($notas as $nota): ?>
                    <div class="pnota-item mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <small class="text-muted d-block mb-1"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($nota['created_at'])) ?></small>
                                <p class="mb-0"><?= nl2br(sanitize($nota['texto'])) ?></p>
                                <?php if ($nota['imagen']): ?>
                                    <div class="mt-2">
                                        <a href="<?= BASE_URL ?>uploads/notas/<?= sanitize($nota['imagen']) ?>" target="_blank">
                                            <img src="<?= BASE_URL ?>uploads/notas/<?= sanitize($nota['imagen']) ?>" class="img-thumbnail" style="max-height: 300px; cursor: pointer;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=eliminar_nota&id=<?= $nota['id'] ?>" class="ms-2" onsubmit="return confirm('¿Eliminar esta nota?');">
                                <button type="submit" class="btn btn-sm btn-link text-danger btn-eliminar-pnota p-0"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-3">
                <i class="bi bi-chat-left-text d-block mb-2" style="font-size: 1.5rem;"></i>
                No hay notas registradas aún.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Archivos Adjuntos -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-paperclip me-2"></i>Archivos Adjuntos</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($adjuntos)): ?>
            <ul class="list-group mb-3">
                <?php foreach ($adjuntos as $adj): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-file-earmark me-1"></i>
                            <?= sanitize($adj['nombre_original']) ?>
                            <small class="text-muted ms-2">(<?= round($adj['tamano'] / 1024, 1) ?> KB)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>index.php?page=adjuntos&action=descargar&id=<?= $adj['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                            <form method="POST" action="<?= BASE_URL ?>index.php?page=adjuntos&action=eliminar&id=<?= $adj['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar?');">
                                <input type="hidden" name="redirect" value="index.php?page=proyectos&action=detalle&id=<?= $id ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="POST" action="<?= BASE_URL ?>index.php?page=adjuntos&action=subir" enctype="multipart/form-data">
            <input type="hidden" name="entidad_tipo" value="proyecto">
            <input type="hidden" name="entidad_id" value="<?= $id ?>">
            <input type="hidden" name="redirect" value="index.php?page=proyectos&action=detalle&id=<?= $id ?>">
            <div class="input-group">
                <input type="file" class="form-control" name="archivo" required>
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i>Subir</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleEditAct(id) {
    const editRow = document.querySelector('.edit-act-' + id);
    const viewRow = document.querySelector('.act-row-' + id);
    if (editRow.style.display === 'none') {
        editRow.style.display = '';
        viewRow.style.opacity = '0.4';
    } else {
        editRow.style.display = 'none';
        viewRow.style.opacity = '1';
    }
}

function toggleVinculoDropdown() {
    const tipo = document.getElementById('vinculoTipo').value;
    const options = document.querySelectorAll('#vinculoEntidad option[data-tipo]');
    document.getElementById('vinculoEntidad').value = '';
    options.forEach(opt => { opt.style.display = opt.dataset.tipo === tipo ? '' : 'none'; });
}

// Paste de imágenes en notas de proyecto
document.getElementById('pNotaTexto').addEventListener('paste', function(e) {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            e.preventDefault();
            const file = items[i].getAsFile();
            const formData = new FormData();
            formData.append('imagen', file, file.name || 'pasted-image.png');
            document.getElementById('pNotaImagenPreview').style.display = 'block';
            document.getElementById('pNotaImagenImg').src = URL.createObjectURL(file);
            fetch('<?= BASE_URL ?>index.php?page=proyectos&action=subir_imagen_nota', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) { document.getElementById('pNotaImagen').value = data.filename; }
                    else { alert('Error: ' + (data.error || 'desconocido')); quitarImagenPNota(); }
                })
                .catch(() => { alert('Error de conexión.'); quitarImagenPNota(); });
            return;
        }
    }
});

function quitarImagenPNota() {
    document.getElementById('pNotaImagen').value = '';
    document.getElementById('pNotaImagenPreview').style.display = 'none';
    document.getElementById('pNotaImagenImg').src = '';
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
