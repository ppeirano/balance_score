<?php
require_once __DIR__ . '/../../models/Responsable.php';
$responsables = Responsable::getAllInclInactivos($pdo);

$editando = null;
if (isset($_GET['editar'])) {
    $editando = Responsable::getById($pdo, (int)$_GET['editar']);
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-person-gear me-2"></i>Administrar Responsables</h4>
</div>

<!-- Formulario agregar/editar -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><?= $editando ? 'Editar Responsable' : 'Nuevo Responsable' ?></h6>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=admin_responsables&action=guardar" class="row g-3 align-items-end">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= $editando['id'] ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre"
                       value="<?= $editando ? sanitize($editando['nombre']) : '' ?>" required
                       placeholder="Ej: G. Vigetti">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cargo</label>
                <input type="text" class="form-control" name="cargo"
                       value="<?= $editando ? sanitize($editando['cargo'] ?? '') : '' ?>"
                       placeholder="Ej: Gerente Comercial">
            </div>
            <?php if ($editando): ?>
            <div class="col-md-2">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="activo" id="activo"
                           <?= $editando['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-1"></i><?= $editando ? 'Actualizar' : 'Agregar' ?>
                </button>
            </div>
            <?php if ($editando): ?>
            <div class="col-12">
                <a href="<?= BASE_URL ?>index.php?page=admin_responsables" class="btn btn-sm btn-outline-secondary">Cancelar edicion</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista -->
<div class="card">
    <div class="card-body">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Estado</th>
                    <th style="width:150px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responsables as $r): ?>
                <tr class="<?= !$r['activo'] ? 'text-muted' : '' ?>">
                    <td><?= sanitize($r['nombre']) ?></td>
                    <td><?= sanitize($r['cargo'] ?? '-') ?></td>
                    <td>
                        <?php if ($r['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>index.php?page=admin_responsables&editar=<?= $r['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=admin_responsables&action=eliminar&id=<?= $r['id'] ?>"
                              class="d-inline" onsubmit="return confirm('¿Eliminar este responsable?');">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($responsables)): ?>
                <tr><td colspan="4" class="text-center text-muted">No hay responsables registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
