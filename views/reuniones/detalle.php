<?php
require_once __DIR__ . '/../../models/Reunion.php';
require_once __DIR__ . '/../../models/Compromiso.php';
require_once __DIR__ . '/../../models/PlanAccion.php';

$reunion = Reunion::getById($pdo, $id);
if (!$reunion) { flash('error', 'Reunión no encontrada.'); redirect('index.php?page=reuniones'); }
$compromisos = Compromiso::getByReunion($pdo, $id);
$planes = PlanAccion::getAll($pdo);

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4><i class="bi bi-people me-2"></i><?= sanitize($reunion['titulo']) ?></h4>
        <p class="text-muted mb-0">
            <i class="bi bi-calendar me-1"></i><?= formatDate($reunion['fecha']) ?>
            <?php if ($reunion['ie_codigo']): ?>
                <span class="badge bg-primary ms-2"><?= sanitize($reunion['ie_codigo']) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>index.php?page=reuniones&action=editar&id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <a href="<?= BASE_URL ?>index.php?page=reuniones" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
</div>

<!-- Participantes -->
<?php if ($reunion['participantes']): ?>
<div class="mb-3">
    <strong>Participantes:</strong> <?= sanitize($reunion['participantes']) ?>
</div>
<?php endif; ?>

<!-- Minuta -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Minuta</h6></div>
    <div class="card-body">
        <?php if ($reunion['minuta']): ?>
            <div style="white-space: pre-wrap;"><?= sanitize($reunion['minuta']) ?></div>
        <?php else: ?>
            <p class="text-muted">Sin minuta registrada.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Compromisos -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-check2-square me-2"></i>Compromisos</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nuevoCompromiso">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
    </div>
    <div class="card-body">
        <!-- Formulario nuevo compromiso -->
        <div class="collapse mb-3" id="nuevoCompromiso">
            <div class="card card-body bg-light">
                <form method="POST" action="<?= BASE_URL ?>index.php?page=compromisos&action=guardar">
                    <input type="hidden" name="reunion_id" value="<?= $id ?>">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" name="descripcion" placeholder="Descripción del compromiso" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="responsable" placeholder="Responsable">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" name="fecha_limite">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" name="plan_accion_id">
                                <option value="">Vincular a PDA (opcional)</option>
                                <?php foreach ($planes as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= sanitize($p['ie_codigo'] . ' - ' . $p['codigo'] . ': ' . $p['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-sm btn-primary">Guardar Compromiso</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de compromisos -->
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Responsable</th>
                    <th>Fecha Límite</th>
                    <th>PDA Vinculado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compromisos as $c): ?>
                <tr>
                    <td><?= sanitize($c['descripcion']) ?></td>
                    <td><?= sanitize($c['responsable'] ?? '-') ?></td>
                    <td><?= formatDate($c['fecha_limite']) ?></td>
                    <td><?= $c['plan_nombre'] ? sanitize($c['plan_nombre']) : '-' ?></td>
                    <td><?= estadoBadge($c['estado']) ?></td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=compromisos&action=cambiar_estado&id=<?= $c['id'] ?>" class="d-inline">
                            <input type="hidden" name="reunion_id" value="<?= $id ?>">
                            <select name="estado" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                                <option value="pendiente" <?= $c['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="en_progreso" <?= $c['estado'] == 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                                <option value="completado" <?= $c['estado'] == 'completado' ? 'selected' : '' ?>>Completado</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($compromisos)): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin compromisos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
