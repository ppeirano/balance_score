<?php
require_once __DIR__ . '/../../models/Reunion.php';
require_once __DIR__ . '/../../models/Iniciativa.php';

$reunion = null;
if ($id) {
    $reunion = Reunion::getById($pdo, $id);
}
$iniciativas = Iniciativa::getAll($pdo);

require_once __DIR__ . '/../layout/header.php';
$esEditar = ($reunion !== null);
?>

<h4 class="mb-4">
    <i class="bi bi-people me-2"></i><?= $esEditar ? 'Editar' : 'Nueva' ?> Reunión
</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=reuniones&action=guardar">
            <?php if ($esEditar): ?>
                <input type="hidden" name="id" value="<?= $reunion['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Título *</label>
                    <input type="text" class="form-control" name="titulo" value="<?= sanitize($reunion['titulo'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha *</label>
                    <input type="datetime-local" class="form-control" name="fecha" value="<?= $reunion ? date('Y-m-d\TH:i', strtotime($reunion['fecha'])) : date('Y-m-d\TH:i') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">IE Vinculada</label>
                    <select class="form-select" name="iniciativa_id">
                        <option value="">General</option>
                        <?php foreach ($iniciativas as $ie): ?>
                            <option value="<?= $ie['id'] ?>" <?= ($reunion && $reunion['iniciativa_id'] == $ie['id']) ? 'selected' : '' ?>>
                                <?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Participantes</label>
                <input type="text" class="form-control" name="participantes" value="<?= sanitize($reunion['participantes'] ?? '') ?>" placeholder="Nombres separados por coma">
            </div>

            <div class="mb-3">
                <label class="form-label">Minuta</label>
                <textarea class="form-control" name="minuta" rows="8" placeholder="Contenido de la minuta: notas, decisiones, temas tratados..."><?= sanitize($reunion['minuta'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="<?= BASE_URL ?>index.php?page=reuniones" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
