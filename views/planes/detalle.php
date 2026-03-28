<?php
require_once __DIR__ . '/../../models/Iniciativa.php';

if (!$id) {
    flash('error', 'ID de plan no especificado.');
    redirect('index.php?page=planes');
}

// Obtener PDA con datos de IE
$stmt = $pdo->prepare("
    SELECT pa.*, ie.codigo AS ie_codigo, ie.nombre AS ie_nombre
    FROM planes_accion pa
    JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
    WHERE pa.id = ?
");
$stmt->execute([$id]);
$plan = $stmt->fetch();

if (!$plan) {
    flash('error', 'Plan de acción no encontrado.');
    redirect('index.php?page=planes');
}

// Obtener actividades del plan
$stmtAct = $pdo->prepare("SELECT * FROM actividades WHERE plan_accion_id = ? ORDER BY codigo");
$stmtAct->execute([$id]);
$actividades = $stmtAct->fetchAll();

// Obtener recursos por actividad
$recursos = [];
if (!empty($actividades)) {
    $actIds = array_column($actividades, 'id');
    $placeholders = implode(',', array_fill(0, count($actIds), '?'));
    $stmtRec = $pdo->prepare("SELECT * FROM recursos WHERE actividad_id IN ($placeholders) ORDER BY actividad_id, id");
    $stmtRec->execute($actIds);
    foreach ($stmtRec->fetchAll() as $rec) {
        $recursos[$rec['actividad_id']][] = $rec;
    }
}

// Obtener archivos adjuntos por actividad
$adjuntosAct = [];
if (!empty($actividades)) {
    $actIds = array_column($actividades, 'id');
    $placeholders = implode(',', array_fill(0, count($actIds), '?'));
    $stmtAdj = $pdo->prepare("SELECT * FROM archivos_adjuntos WHERE entidad_tipo = 'actividad' AND entidad_id IN ($placeholders) ORDER BY entidad_id, created_at DESC");
    $stmtAdj->execute($actIds);
    foreach ($stmtAdj->fetchAll() as $adj) {
        $adjuntosAct[$adj['entidad_id']][] = $adj;
    }
}

// Obtener hitos del plan
$stmtHitos = $pdo->prepare("SELECT * FROM hitos WHERE plan_accion_id = ? ORDER BY fecha_prevista");
$stmtHitos->execute([$id]);
$hitos = $stmtHitos->fetchAll();

// Obtener archivos adjuntos del plan
$stmtArchivos = $pdo->prepare("SELECT * FROM archivos_adjuntos WHERE entidad_tipo = 'plan_accion' AND entidad_id = ? ORDER BY created_at DESC");
$stmtArchivos->execute([$id]);
$archivos = $stmtArchivos->fetchAll();

$pageTitle = 'Detalle: ' . $plan['nombre'];
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Encabezado del PDA -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2>
            <i class="bi bi-list-check me-2"></i>
            <?= sanitize($plan['nombre']) ?>
            <small class="text-muted">(<?= sanitize($plan['codigo']) ?>)</small>
        </h2>
        <div class="d-flex gap-2 flex-wrap mt-2">
            <span class="badge-info fs-6"><?= sanitize($plan['ie_codigo']) ?> - <?= sanitize($plan['ie_nombre']) ?></span>
            <?= estadoBadge($plan['estado']) ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>index.php?page=calendario&tipo=plan&entidad_id=<?= $plan['id'] ?>&nombre=<?= urlencode($plan['codigo'] . ' - ' . $plan['nombre']) ?>" class="btn btn-outline-info">
            <i class="bi bi-calendar-event me-1"></i>Agendar Seguimiento
        </a>
        <a href="<?= BASE_URL ?>index.php?page=planes&action=editar&id=<?= $plan['id'] ?>" class="btn btn-warning">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <a href="<?= BASE_URL ?>index.php?page=planes" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Info general -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Owner:</strong><br>
                <?= sanitize($plan['owner'] ?? '-') ?>
            </div>
            <div class="col-md-3">
                <strong>Fecha Inicio:</strong><br>
                <?= formatDate($plan['fecha_inicio']) ?>
            </div>
            <div class="col-md-3">
                <strong>Fecha Fin:</strong><br>
                <?= formatDate($plan['fecha_fin']) ?>
            </div>
            <div class="col-md-3">
                <strong>Prioridad:</strong> <?= (int)$plan['prioridad'] ?>
                &nbsp;|&nbsp;
                <strong>Peso:</strong> <?= sanitize($plan['peso']) ?>%
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <strong>Avance General:</strong>
                <div class="progress mt-1" style="height: 30px;">
                    <div class="progress-bar <?= $plan['avance'] >= 75 ? 'bg-success' : ($plan['avance'] >= 40 ? 'bg-warning' : 'bg-danger') ?> fs-6"
                         role="progressbar"
                         style="width: <?= (int)$plan['avance'] ?>%"
                         aria-valuenow="<?= (int)$plan['avance'] ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <?= (int)$plan['avance'] ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actividades -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-check2-square me-2"></i>Actividades</h5>
        <a href="<?= BASE_URL ?>index.php?page=actividades&action=crear&plan_id=<?= $plan['id'] ?>" class="btn btn-sm btn-light">
            <i class="bi bi-plus-lg me-1"></i>Nueva Actividad
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($actividades)): ?>
            <div class="alert alert-info mb-0">No hay actividades registradas para este plan.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Responsable</th>
                            <th>Estado</th>
                            <th>Fecha Límite</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $act): ?>
                            <tr>
                                <td><strong><?= sanitize($act['codigo']) ?></strong></td>
                                <td><?= sanitize($act['descripcion']) ?></td>
                                <td><?= sanitize($act['responsable'] ?? '-') ?></td>
                                <td>
                                    <form method="POST" action="<?= BASE_URL ?>index.php?page=actividades&action=cambiar_estado&id=<?= $act['id'] ?>" class="d-inline">
                                        <input type="hidden" name="plan_accion_id" value="<?= $plan['id'] ?>">
                                        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 130px;">
                                            <option value="pendiente" <?= ($act['estado'] === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                            <option value="en_progreso" <?= ($act['estado'] === 'en_progreso') ? 'selected' : '' ?>>En progreso</option>
                                            <option value="completado" <?= ($act['estado'] === 'completado') ? 'selected' : '' ?>>Completado</option>
                                            <option value="cancelado" <?= ($act['estado'] === 'cancelado') ? 'selected' : '' ?>>Cancelado</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?= formatDate($act['fecha_limite']) ?></td>
                                <td>
                                    <?= sanitize($act['observaciones'] ?? '-') ?>
                                    <?php if (!empty($adjuntosAct[$act['id']])): ?>
                                        <span class="badge-neutral ms-1" title="<?= count($adjuntosAct[$act['id']]) ?> archivo(s) adjunto(s)">
                                            <i class="bi bi-paperclip"></i> <?= count($adjuntosAct[$act['id']]) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="<?= BASE_URL ?>index.php?page=calendario&tipo=actividad&entidad_id=<?= $act['id'] ?>&nombre=<?= urlencode($act['codigo'] . ' - ' . $act['descripcion']) ?>"
                                           class="btn-action btn-action-info" title="Agendar seguimiento">
                                            <i class="bi bi-calendar-event"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>index.php?page=actividades&action=editar&id=<?= $act['id'] ?>"
                                           class="btn-action btn-action-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="<?= BASE_URL ?>index.php?page=actividades&action=eliminar&id=<?= $act['id'] ?>"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Está seguro de que desea eliminar esta actividad?');">
                                            <button type="submit" class="btn-action btn-action-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <!-- Recursos de esta actividad -->
                            <?php if (!empty($recursos[$act['id']])): ?>
                                <tr class="table-light">
                                    <td colspan="7" class="ps-5">
                                        <strong><i class="bi bi-box-seam me-1"></i>Recursos:</strong>
                                        <table class="table table-sm table-bordered mt-1 mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Tipo</th>
                                                    <th>Descripción</th>
                                                    <th>Cantidad</th>
                                                    <th>Unidad</th>
                                                    <th>Costo Estimado</th>
                                                    <th>Notas</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recursos[$act['id']] as $rec): ?>
                                                    <tr>
                                                        <td><span class="badge-neutral"><?= sanitize(ucfirst($rec['tipo'])) ?></span></td>
                                                        <td><?= sanitize($rec['descripcion']) ?></td>
                                                        <td><?= $rec['cantidad'] !== null ? sanitize($rec['cantidad']) : '-' ?></td>
                                                        <td><?= sanitize($rec['unidad'] ?? '-') ?></td>
                                                        <td><?= $rec['costo_estimado'] !== null ? '$' . number_format($rec['costo_estimado'], 2) : '-' ?></td>
                                                        <td><?= sanitize($rec['notas'] ?? '-') ?></td>
                                                        <td>
                                                            <form method="POST" action="<?= BASE_URL ?>index.php?page=recursos&action=eliminar&id=<?= $rec['id'] ?>"
                                                                  class="d-inline"
                                                                  onsubmit="return confirm('¿Está seguro de que desea eliminar este recurso?');">
                                                                <button type="submit" class="btn-action btn-action-danger" title="Eliminar">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hitos -->
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-flag me-2"></i>Hitos</h5>
        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalNuevoHito">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Hito
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($hitos)): ?>
            <div class="alert alert-info mb-0">No hay hitos registrados para este plan.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Fecha Prevista</th>
                            <th>Fecha Real</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hitos as $hito): ?>
                            <tr>
                                <td><?= sanitize($hito['nombre']) ?></td>
                                <td><?= formatDate($hito['fecha_prevista']) ?></td>
                                <td><?= formatDate($hito['fecha_real']) ?></td>
                                <td>
                                    <?php
                                    $hitoBadges = [
                                        'pendiente' => 'badge-neutral',
                                        'alcanzado' => 'badge-ok',
                                        'retrasado' => 'badge-bad'
                                    ];
                                    $hitoBadge = $hitoBadges[$hito['estado']] ?? 'badge-neutral';
                                    ?>
                                    <span class="<?= $hitoBadge ?>"><?= ucfirst(sanitize($hito['estado'])) ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>index.php?page=calendario&tipo=hito&entidad_id=<?= $hito['id'] ?>&nombre=<?= urlencode($hito['nombre']) ?>"
                                       class="btn-action btn-action-info" title="Agendar seguimiento">
                                        <i class="bi bi-calendar-event"></i>
                                    </a>
                                    <?php if ($hito['estado'] !== 'alcanzado'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>index.php?page=hitos&action=cambiar_estado&id=<?= $hito['id'] ?>" class="d-inline">
                                            <input type="hidden" name="plan_accion_id" value="<?= $plan['id'] ?>">
                                            <input type="hidden" name="estado" value="alcanzado">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como alcanzado">
                                                <i class="bi bi-check-circle me-1"></i>Alcanzado
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
        <?php if (!empty($archivos)): ?>
            <ul class="list-group mb-3">
                <?php foreach ($archivos as $archivo): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-file-earmark me-1"></i>
                            <?= sanitize($archivo['nombre_original']) ?>
                            <small class="text-muted ms-2">(<?= round($archivo['tamano'] / 1024, 1) ?> KB)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>index.php?page=adjuntos&action=descargar&id=<?= $archivo['id'] ?>"
                               class="btn-action btn-action-primary" title="Descargar">
                                <i class="bi bi-download"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>index.php?page=adjuntos&action=eliminar&id=<?= $archivo['id'] ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Está seguro de que desea eliminar este archivo?');">
                                <input type="hidden" name="redirect" value="index.php?page=planes&action=detalle&id=<?= $plan['id'] ?>">
                                <button type="submit" class="btn-action btn-action-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Formulario de subida -->
        <form method="POST" action="<?= BASE_URL ?>index.php?page=adjuntos&action=subir" enctype="multipart/form-data">
            <input type="hidden" name="entidad_tipo" value="plan_accion">
            <input type="hidden" name="entidad_id" value="<?= $plan['id'] ?>">
            <input type="hidden" name="redirect" value="index.php?page=planes&action=detalle&id=<?= $plan['id'] ?>">
            <div class="input-group">
                <input type="file" class="form-control" name="archivo" required>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-upload me-1"></i>Subir Archivo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nuevo Hito -->
<div class="modal fade" id="modalNuevoHito" tabindex="-1" aria-labelledby="modalNuevoHitoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>index.php?page=hitos&action=guardar">
                <input type="hidden" name="plan_accion_id" value="<?= $plan['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoHitoLabel">Nuevo Hito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="hito_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hito_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="hito_fecha_prevista" class="form-label">Fecha Prevista</label>
                        <input type="date" class="form-control" id="hito_fecha_prevista" name="fecha_prevista">
                    </div>
                    <div class="mb-3">
                        <label for="hito_observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="hito_observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Crear Hito
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
