<?php
require_once __DIR__ . '/../../models/Riesgo.php';
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/PlanAccion.php';
require_once __DIR__ . '/../../models/Responsable.php';

$riesgo = null;
if ($id) {
    $riesgo = Riesgo::getById($pdo, $id);
}
$iniciativas = Iniciativa::getAll($pdo);
$planes = PlanAccion::getAll($pdo);
$responsables = Responsable::getAll($pdo);

require_once __DIR__ . '/../layout/header.php';
$esEditar = ($riesgo !== null);
?>

<h4 class="mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i><?= $esEditar ? 'Editar' : 'Nueva' ?> Restricci&oacute;n / Riesgo
</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=riesgos&action=guardar">
            <?php if ($esEditar): ?>
                <input type="hidden" name="id" value="<?= $riesgo['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Descripci&oacute;n de la Restricci&oacute;n / Riesgo *</label>
                <textarea class="form-control" name="descripcion" rows="3" required><?= sanitize($riesgo['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">IE Asociada</label>
                    <select class="form-select" name="iniciativa_id">
                        <option value="">Ninguna</option>
                        <?php foreach ($iniciativas as $ie): ?>
                            <option value="<?= $ie['id'] ?>" <?= ($riesgo && $riesgo['iniciativa_id'] == $ie['id']) ? 'selected' : '' ?>>
                                <?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">PDA Asociado</label>
                    <select class="form-select" name="plan_accion_id">
                        <option value="">Ninguno</option>
                        <?php foreach ($planes as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($riesgo && $riesgo['plan_accion_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= sanitize($p['iniciativa_codigo'] . ' - ' . $p['codigo'] . ': ' . $p['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Probabilidad *</label>
                    <select class="form-select" name="probabilidad" required>
                        <option value="baja" <?= ($riesgo && $riesgo['probabilidad'] == 'baja') ? 'selected' : '' ?>>Baja</option>
                        <option value="media" <?= (!$riesgo || $riesgo['probabilidad'] == 'media') ? 'selected' : '' ?>>Media</option>
                        <option value="alta" <?= ($riesgo && $riesgo['probabilidad'] == 'alta') ? 'selected' : '' ?>>Alta</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Impacto *</label>
                    <select class="form-select" name="impacto" required>
                        <option value="bajo" <?= ($riesgo && $riesgo['impacto'] == 'bajo') ? 'selected' : '' ?>>Bajo</option>
                        <option value="medio" <?= (!$riesgo || $riesgo['impacto'] == 'medio') ? 'selected' : '' ?>>Medio</option>
                        <option value="alto" <?= ($riesgo && $riesgo['impacto'] == 'alto') ? 'selected' : '' ?>>Alto</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Responsable</label>
                    <select class="form-select" name="responsable">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($responsables as $resp): ?>
                            <option value="<?= sanitize($resp['nombre']) ?>"
                                <?= ($riesgo && ($riesgo['responsable'] ?? '') === $resp['nombre']) ? 'selected' : '' ?>>
                                <?= sanitize($resp['nombre']) ?><?= $resp['cargo'] ? ' (' . sanitize($resp['cargo']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Plan de Mitigación</label>
                    <textarea class="form-control" name="plan_mitigacion" rows="3"><?= sanitize($riesgo['plan_mitigacion'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <?php foreach (['abierto','mitigado','cerrado','materializado'] as $e): ?>
                            <option value="<?= $e ?>" <?= ($riesgo && $riesgo['estado'] == $e) ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="<?= BASE_URL ?>index.php?page=riesgos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
