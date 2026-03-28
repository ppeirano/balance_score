<?php
require_once __DIR__ . '/../../models/Reunion.php';
$reuniones = Reunion::getAll($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-end align-items-center mb-4">
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
                <th class="text-center">Compromisos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reuniones as $r): ?>
            <tr>
                <td><?= formatDate($r['fecha']) ?></td>
                <td><a href="<?= BASE_URL ?>index.php?page=reuniones&action=detalle&id=<?= $r['id'] ?>"><?= sanitize($r['titulo']) ?></a></td>
                <td><small><?= sanitize($r['participantes'] ?? '') ?></small></td>
                <td class="text-center">
                    <?php
                    $stmtC = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN estado != 'completado' THEN 1 ELSE 0 END) as pendientes FROM compromisos WHERE reunion_id = ?");
                    $stmtC->execute([$r['id']]);
                    $compData = $stmtC->fetch();
                    $totalC = intval($compData['total']);
                    $pendC = intval($compData['pendientes']);
                    if ($totalC === 0): ?>
                        <span class="text-muted">-</span>
                    <?php elseif ($pendC > 0): ?>
                        <span class="badge-warn"><?= $pendC ?> pendiente<?= $pendC > 1 ? 's' : '' ?></span>
                    <?php else: ?>
                        <span class="badge-ok">Todos cumplidos</span>
                    <?php endif; ?>
                </td>
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
            <tr><td colspan="5" class="text-center text-muted">No hay reuniones registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
