<?php
require_once __DIR__ . '/../../models/Iniciativa.php';

$iniciativa = null;
if ($id) {
    $iniciativa = Iniciativa::getById($pdo, $id);
    if (!$iniciativa) {
        flash('error', 'Iniciativa no encontrada.');
        redirect('index.php?page=iniciativas');
    }
}
$esEdicion = ($iniciativa !== null);

// Perspectivas y períodos para los dropdowns
$perspectivas = $pdo->query("SELECT * FROM perspectivas ORDER BY orden")->fetchAll();
$periodos = $pdo->query("SELECT * FROM periodos_estrategicos ORDER BY fecha_inicio DESC")->fetchAll();

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-<?= $esEdicion ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
        <?= $esEdicion ? 'Editar Iniciativa Estratégica' : 'Nueva Iniciativa Estratégica' ?>
    </h2>
    <a href="<?= BASE_URL ?>index.php?page=iniciativas" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=iniciativas&action=guardar">
            <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= $iniciativa['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="codigo" name="codigo"
                           value="<?= $esEdicion ? sanitize($iniciativa['codigo']) : '' ?>" required
                           placeholder="Ej: IE7">
                </div>
                <div class="col-md-10">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                           value="<?= $esEdicion ? sanitize($iniciativa['nombre']) : '' ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= $esEdicion ? sanitize($iniciativa['descripcion'] ?? '') : '' ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="perspectiva_id" class="form-label">Perspectiva <span class="text-danger">*</span></label>
                    <select class="form-select" id="perspectiva_id" name="perspectiva_id" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($perspectivas as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= ($esEdicion && $iniciativa['perspectiva_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= sanitize($p['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="periodo_id" class="form-label">Período</label>
                    <select class="form-select" id="periodo_id" name="periodo_id">
                        <option value="">-- Ninguno --</option>
                        <?php foreach ($periodos as $per): ?>
                            <option value="<?= $per['id'] ?>"
                                <?= ($esEdicion && ($iniciativa['periodo_id'] ?? '') == $per['id']) ? 'selected' : '' ?>>
                                <?= sanitize($per['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="orden" class="form-label">Orden</label>
                    <input type="number" class="form-control" id="orden" name="orden"
                           value="<?= $esEdicion ? (int)$iniciativa['orden'] : 0 ?>" min="0">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $esEdicion ? 'Actualizar' : 'Crear' ?>
                </button>
                <a href="<?= BASE_URL ?>index.php?page=iniciativas" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
