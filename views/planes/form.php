<?php
require_once __DIR__ . '/../../models/Iniciativa.php';

// Obtener todas las iniciativas para el dropdown
$iniciativas = Iniciativa::getAll($pdo);

// Si estamos editando, cargar el plan existente
$plan = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM planes_accion WHERE id = ?");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    if (!$plan) {
        flash('error', 'Plan de acción no encontrado.');
        redirect('index.php?page=planes');
    }
}

$esEdicion = ($plan !== null);
$pageTitle = $esEdicion ? 'Editar Plan de Acción' : 'Nuevo Plan de Acción';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-<?= $esEdicion ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
        <?= $esEdicion ? 'Editar Plan de Acción' : 'Nuevo Plan de Acción' ?>
    </h2>
    <a href="<?= BASE_URL ?>index.php?page=planes" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=planes&action=guardar">
            <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= $plan['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                           value="<?= $esEdicion ? sanitize($plan['nombre']) : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="codigo" name="codigo"
                           value="<?= $esEdicion ? sanitize($plan['codigo']) : '' ?>" required
                           placeholder="Ej: P1, P2...">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="iniciativa_id" class="form-label">Iniciativa Estratégica <span class="text-danger">*</span></label>
                    <select class="form-select" id="iniciativa_id" name="iniciativa_id" required>
                        <option value="">-- Seleccionar IE --</option>
                        <?php foreach ($iniciativas as $ie): ?>
                            <option value="<?= $ie['id'] ?>"
                                <?= ($esEdicion && $plan['iniciativa_id'] == $ie['id']) ? 'selected' : '' ?>>
                                <?= sanitize($ie['codigo']) ?> - <?= sanitize($ie['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="owner" class="form-label">Owner</label>
                    <input type="text" class="form-control" id="owner" name="owner"
                           value="<?= $esEdicion ? sanitize($plan['owner'] ?? '') : '' ?>"
                           placeholder="Responsable del plan">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                           value="<?= $esEdicion ? sanitize($plan['fecha_inicio'] ?? '') : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                           value="<?= $esEdicion ? sanitize($plan['fecha_fin'] ?? '') : '' ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="prioridad" class="form-label">Prioridad</label>
                    <?php $prioridadActual = $esEdicion ? (int)$plan['prioridad'] : 3; ?>
                    <select class="form-select" id="prioridad" name="prioridad">
                        <option value="1" <?= $prioridadActual === 1 ? 'selected' : '' ?>>1 - Muy Alta</option>
                        <option value="2" <?= $prioridadActual === 2 ? 'selected' : '' ?>>2 - Alta</option>
                        <option value="3" <?= $prioridadActual === 3 ? 'selected' : '' ?>>3 - Media</option>
                        <option value="4" <?= $prioridadActual === 4 ? 'selected' : '' ?>>4 - Baja</option>
                        <option value="5" <?= $prioridadActual === 5 ? 'selected' : '' ?>>5 - Muy Baja</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="peso" class="form-label">Peso (%)</label>
                    <input type="number" class="form-control" id="peso" name="peso"
                           value="<?= $esEdicion ? sanitize($plan['peso']) : '0' ?>"
                           min="0" max="100" step="0.01">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <?php
                        $estados = ['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completado' => 'Completado', 'cancelado' => 'Cancelado'];
                        foreach ($estados as $val => $label):
                        ?>
                            <option value="<?= $val ?>"
                                <?= ($esEdicion && $plan['estado'] === $val) ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $esEdicion ? 'Actualizar' : 'Crear' ?> Plan
                </button>
                <a href="<?= BASE_URL ?>index.php?page=planes" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
