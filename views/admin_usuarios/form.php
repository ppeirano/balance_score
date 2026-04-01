<?php
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Responsable.php';

$usuario = null;
if ($id) {
    $usuario = Usuario::getById($pdo, $id);
}
$responsables = Responsable::getAll($pdo);
$esEditar = ($usuario !== null);

require_once __DIR__ . '/../layout/header.php';
?>

<h4 class="mb-4">
    <i class="bi bi-person-gear me-2"></i><?= $esEditar ? 'Editar' : 'Nuevo' ?> Usuario
</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=admin_usuarios&action=guardar">
            <?php if ($esEditar): ?>
                <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email"
                           value="<?= sanitize($usuario['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= $esEditar ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *' ?></label>
                    <input type="password" class="form-control" name="password" <?= $esEditar ? '' : 'required' ?>>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Responsable vinculado</label>
                    <select class="form-select" name="responsable_id">
                        <option value="">-- Sin vincular --</option>
                        <?php foreach ($responsables as $resp): ?>
                            <option value="<?= (int)$resp['id'] ?>"
                                <?= ($usuario && ($usuario['responsable_id'] ?? '') == $resp['id']) ? 'selected' : '' ?>>
                                <?= sanitize($resp['nombre']) ?><?= $resp['cargo'] ? ' (' . sanitize($resp['cargo']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Perfil *</label>
                    <select class="form-select" name="perfil">
                        <option value="admin" <?= ($usuario['perfil'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="consultor" <?= ($usuario['perfil'] ?? 'consultor') === 'consultor' ? 'selected' : '' ?>>Consultor</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                               <?= ($usuario['activo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="<?= BASE_URL ?>index.php?page=admin_usuarios" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
