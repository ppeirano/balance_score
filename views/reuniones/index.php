<?php
require_once __DIR__ . '/../../models/Reunion.php';
$reuniones = Reunion::getAll($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-people me-2"></i>Reuniones</h4>
    <a href="<?= BASE_URL ?>index.php?page=reuniones&action=crear" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nueva Reunión
    </a>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Título</th>
                <th>Participantes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reuniones as $r): ?>
            <tr>
                <td><?= formatDate($r['fecha']) ?></td>
                <td><a href="<?= BASE_URL ?>index.php?page=reuniones&action=detalle&id=<?= $r['id'] ?>"><?= sanitize($r['titulo']) ?></a></td>
                <td><small><?= sanitize($r['participantes'] ?? '') ?></small></td>
                <td>
                    <a href="<?= BASE_URL ?>index.php?page=reuniones&action=detalle&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                    <a href="<?= BASE_URL ?>index.php?page=reuniones&action=editar&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="<?= BASE_URL ?>index.php?page=reuniones&action=eliminar&id=<?= $r['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta reunión?')">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reuniones)): ?>
            <tr><td colspan="4" class="text-center text-muted">No hay reuniones registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
