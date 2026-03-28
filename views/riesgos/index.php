<?php
require_once __DIR__ . '/../../models/Riesgo.php';
$riesgos = Riesgo::getAll($pdo);
$matriz = Riesgo::getMatriz($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-exclamation-triangle me-2"></i>Gestión de Riesgos</h4>
    <a href="<?= BASE_URL ?>index.php?page=riesgos&action=crear" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Riesgo
    </a>
</div>

<!-- Matriz de Riesgos Visual -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Matriz de Riesgos</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="risk-matrix" style="table-layout:fixed; width:100%">
                <thead>
                    <tr>
                        <th class="text-center" style="width:25%">Probabilidad \ Impacto</th>
                        <th class="text-center" style="width:25%">Bajo</th>
                        <th class="text-center" style="width:25%">Medio</th>
                        <th class="text-center" style="width:25%">Alto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $probs = ['alta', 'media', 'baja'];
                    $impacts = ['bajo', 'medio', 'alto'];
                    foreach ($probs as $prob):
                    ?>
                    <tr>
                        <td class="fw-bold text-center"><?= ucfirst($prob) ?></td>
                        <?php foreach ($impacts as $imp):
                            $nivel = calcularNivelRiesgo($prob, $imp);
                            $count = $matriz[$prob][$imp]['total'] ?? 0;
                        ?>
                        <td class="risk-cell-<?= $nivel ?>">
                            <?php if ($count > 0): ?>
                                <strong><?= $count ?></strong> riesgo<?= $count > 1 ? 's' : '' ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Listado de Riesgos -->
<div class="card">
    <div class="card-header"><h6 class="mb-0">Listado de Riesgos</h6></div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nivel</th>
                    <th>Descripción</th>
                    <th>IE / PDA</th>
                    <th>Probabilidad</th>
                    <th>Impacto</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riesgos as $r): ?>
                <tr>
                    <td><?= nivelRiesgoBadge($r['nivel']) ?></td>
                    <td><?= sanitize(mb_substr($r['descripcion'], 0, 80)) ?><?= mb_strlen($r['descripcion']) > 80 ? '...' : '' ?></td>
                    <td>
                        <?= $r['iniciativa_codigo'] ? '<span class="badge-info">' . sanitize($r['iniciativa_codigo']) . '</span>' : '' ?>
                        <?= $r['plan_nombre'] ? '<small class="text-muted">' . sanitize($r['plan_nombre']) . '</small>' : '' ?>
                    </td>
                    <td><?= ucfirst($r['probabilidad']) ?></td>
                    <td><?= ucfirst($r['impacto']) ?></td>
                    <td><?= sanitize($r['responsable'] ?? '-') ?></td>
                    <td><?= estadoBadge($r['estado']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>index.php?page=riesgos&action=editar&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=riesgos&action=eliminar&id=<?= $r['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($riesgos)): ?>
                <tr><td colspan="8" class="text-center text-muted">No hay riesgos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
