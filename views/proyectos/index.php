<?php
require_once __DIR__ . '/../../models/Proyecto.php';
$proyectos = Proyecto::getAll($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="<?= BASE_URL ?>index.php?page=proyectos&action=crear" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Proyecto
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Avance</th>
                    <th>Fechas</th>
                    <th>Presupuesto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proyectos as $p): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>index.php?page=proyectos&action=detalle&id=<?= $p['id'] ?>" class="text-decoration-none fw-semibold">
                            <?= sanitize($p['nombre']) ?>
                        </a>
                    </td>
                    <td><?= sanitize($p['responsable'] ?? '-') ?></td>
                    <td><?= estadoBadge($p['estado']) ?></td>
                    <td><?= prioridadBadge($p['prioridad']) ?></td>
                    <td style="min-width: 120px;">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar <?= $p['avance'] >= 75 ? 'bg-success' : ($p['avance'] >= 40 ? 'bg-primary' : 'bg-warning') ?>"
                                 style="width: <?= $p['avance'] ?>%"><?= $p['avance'] ?>%</div>
                        </div>
                    </td>
                    <td>
                        <small>
                            <?= formatDate($p['fecha_inicio']) ?><br>
                            <?= formatDate($p['fecha_fin']) ?>
                        </small>
                    </td>
                    <td>
                        <?= $p['presupuesto'] ? '$ ' . number_format($p['presupuesto'], 2, ',', '.') : '-' ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>index.php?page=proyectos&action=detalle&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></a>
                        <a href="<?= BASE_URL ?>index.php?page=proyectos&action=editar&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=proyectos&action=eliminar&id=<?= $p['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este proyecto y todos sus entregables?')">
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($proyectos)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay proyectos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
