<?php
require_once __DIR__ . '/../../models/Usuario.php';
$usuarios = Usuario::getAll($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Usuarios</h4>
    <a href="<?= BASE_URL ?>index.php?page=admin_usuarios&action=crear" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Usuario
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Email</th>
                    <th>Responsable</th>
                    <th>Perfil</th>
                    <th>&Uacute;ltimo login</th>
                    <th>Estado</th>
                    <th style="width:100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= sanitize($u['email']) ?></td>
                    <td>
                        <?php if ($u['responsable_nombre']): ?>
                            <?= sanitize($u['responsable_nombre']) ?>
                            <?php if ($u['responsable_cargo']): ?>
                                <small class="text-muted">(<?= sanitize($u['responsable_cargo']) ?>)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['perfil'] === 'admin'): ?>
                            <span class="badge bg-primary">Admin</span>
                        <?php else: ?>
                            <span class="badge-neutral">Consultor</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $u['ultimo_login'] ? formatDate($u['ultimo_login']) : '<span class="text-muted">Nunca</span>' ?>
                    </td>
                    <td>
                        <?php if ($u['activo']): ?>
                            <span class="badge-ok">Activo</span>
                        <?php else: ?>
                            <span class="badge-bad">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="<?= BASE_URL ?>index.php?page=admin_usuarios&action=editar&id=<?= (int)$u['id'] ?>"
                               class="btn-action btn-action-primary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="<?= BASE_URL ?>index.php?page=admin_usuarios&action=eliminar&id=<?= (int)$u['id'] ?>"
                                  class="d-inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                                <button type="submit" class="btn-action btn-action-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
