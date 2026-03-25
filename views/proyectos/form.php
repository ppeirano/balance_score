<?php
require_once __DIR__ . '/../../models/Proyecto.php';
require_once __DIR__ . '/../../models/Responsable.php';
require_once __DIR__ . '/../../models/Periodo.php';

$proyecto = null;
if ($id) {
    $proyecto = Proyecto::getById($pdo, $id);
    if (!$proyecto) {
        flash('error', 'Proyecto no encontrado.');
        redirect('index.php?page=proyectos');
    }
}
$responsables = Responsable::getAll($pdo);
$periodos = Periodo::getAll($pdo);
$esEditar = ($proyecto !== null);

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>
        <i class="bi bi-<?= $esEditar ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
        <?= $esEditar ? 'Editar Proyecto' : 'Nuevo Proyecto' ?>
    </h4>
    <a href="<?= BASE_URL ?>index.php?page=proyectos" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=guardar">
            <?php if ($esEditar): ?>
                <input type="hidden" name="id" value="<?= $proyecto['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Nombre del Proyecto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?= sanitize($proyecto['nombre'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Responsable</label>
                    <select class="form-select" name="responsable">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($responsables as $resp): ?>
                            <option value="<?= sanitize($resp['nombre']) ?>"
                                <?= ($proyecto && ($proyecto['responsable'] ?? '') === $resp['nombre']) ? 'selected' : '' ?>>
                                <?= sanitize($resp['nombre']) ?><?= $resp['cargo'] ? ' (' . sanitize($resp['cargo']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" name="descripcion" rows="3"><?= sanitize($proyecto['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <?php
                        $estados = ['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completado' => 'Completado', 'cancelado' => 'Cancelado', 'suspendido' => 'Suspendido'];
                        foreach ($estados as $val => $label):
                        ?>
                            <option value="<?= $val ?>"
                                <?= ($proyecto && $proyecto['estado'] === $val) ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prioridad</label>
                    <select class="form-select" name="prioridad">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"
                                <?= ($proyecto && (int)$proyecto['prioridad'] === $i) ? 'selected' : '' ?>>
                                <?= $i ?> - <?= ['Muy Alta','Alta','Media','Baja','Muy Baja'][$i-1] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio"
                           value="<?= sanitize($proyecto['fecha_inicio'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" name="fecha_fin"
                           value="<?= sanitize($proyecto['fecha_fin'] ?? '') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Presupuesto ($)</label>
                    <input type="number" class="form-control" name="presupuesto" step="0.01" min="0"
                           value="<?= $proyecto['presupuesto'] ?? '' ?>" placeholder="0.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Avance: <span id="avanceLabel"><?= $proyecto['avance'] ?? 0 ?>%</span></label>
                    <input type="range" class="form-range" name="avance" min="0" max="100"
                           value="<?= $proyecto['avance'] ?? 0 ?>"
                           oninput="document.getElementById('avanceLabel').textContent = this.value + '%'">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Período</label>
                    <select class="form-select" name="periodo_id">
                        <option value="">-- Ninguno --</option>
                        <?php foreach ($periodos as $per): ?>
                            <option value="<?= $per['id'] ?>"
                                <?= ($proyecto && ($proyecto['periodo_id'] ?? '') == $per['id']) ? 'selected' : '' ?>>
                                <?= sanitize($per['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $esEditar ? 'Actualizar' : 'Crear' ?> Proyecto
                </button>
                <a href="<?= BASE_URL ?>index.php?page=proyectos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
