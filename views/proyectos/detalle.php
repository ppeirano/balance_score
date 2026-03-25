<?php
require_once __DIR__ . '/../../models/Proyecto.php';
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/PlanAccion.php';
require_once __DIR__ . '/../../models/Responsable.php';

$proyecto = Proyecto::getById($pdo, $id);
if (!$proyecto) { flash('error', 'Proyecto no encontrado.'); redirect('index.php?page=proyectos'); }
$vinculos = Proyecto::getVinculos($pdo, $id);
$entregables = Proyecto::getEntregables($pdo, $id);
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
<div class="row mb-4">
    <div class="col-md-8">
        <?php if ($proyecto['descripcion']): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div style="white-space: pre-wrap;"><?= sanitize($proyecto['descripcion']) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted mb-1">Avance</label>
                    <div class="progress" style="height: 24px;">
                        <div class="progress-bar <?= $proyecto['avance'] >= 75 ? 'bg-success' : ($proyecto['avance'] >= 40 ? 'bg-primary' : 'bg-warning') ?>"
                             style="width: <?= $proyecto['avance'] ?>%"><?= $proyecto['avance'] ?>%</div>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted d-block">Inicio</small>
                        <strong><?= formatDate($proyecto['fecha_inicio']) ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Fin</small>
                        <strong><?= formatDate($proyecto['fecha_fin']) ?></strong>
                    </div>
                </div>
                <?php if ($proyecto['presupuesto']): ?>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted d-block">Presupuesto</small>
                        <strong class="fs-5">$ <?= number_format($proyecto['presupuesto'], 2, ',', '.') ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
        <!-- Form nuevo vínculo -->
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
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vinculos as $v): ?>
                <tr>
                    <td>
                        <?php if ($v['entidad_tipo'] === 'iniciativa'): ?>
                            <span class="badge bg-primary">IE</span>
                        <?php else: ?>
                            <span class="badge bg-info text-dark">PDA</span>
                        <?php endif; ?>
                    </td>
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

<!-- Entregables -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Entregables</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nuevoEntregable">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
    </div>
    <div class="card-body">
        <!-- Form nuevo entregable -->
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
                            <input type="date" class="form-control form-control-sm" name="fecha_prevista" placeholder="Fecha prevista">
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
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=eliminar_entregable&id=<?= $e['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este entregable?')">
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

<script>
function toggleVinculoDropdown() {
    const tipo = document.getElementById('vinculoTipo').value;
    const options = document.querySelectorAll('#vinculoEntidad option[data-tipo]');
    const select = document.getElementById('vinculoEntidad');
    select.value = '';
    options.forEach(opt => {
        opt.style.display = opt.dataset.tipo === tipo ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
