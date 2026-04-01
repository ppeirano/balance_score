<?php
require_once __DIR__ . '/../../models/Riesgo.php';
$riesgos = Riesgo::getAll($pdo);
$matriz = Riesgo::getMatriz($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<?php if (isAdmin()): ?>
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="<?= BASE_URL ?>index.php?page=riesgos&action=crear" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nueva Restricci&oacute;n / Riesgo
    </a>
</div>
<?php endif; ?>

<!-- Matriz de Riesgos Visual -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-grid-3x3 me-2"></i>Matriz de Restricciones y Riesgos</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="risk-matrix">
                <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th colspan="3" class="risk-axis-label pb-2"><i class="bi bi-arrow-right me-1"></i>Impacto</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th></th>
                        <th style="width:28%">Bajo</th>
                        <th style="width:28%">Medio</th>
                        <th style="width:28%">Alto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $probs = ['alta', 'media', 'baja'];
                    $impacts = ['bajo', 'medio', 'alto'];
                    foreach ($probs as $i => $prob):
                    ?>
                    <tr>
                        <?php if ($i === 0): ?>
                            <th class="risk-prob-header" rowspan="3"><i class="bi bi-arrow-up me-1"></i>Probabilidad</th>
                        <?php endif; ?>
                        <td class="risk-prob-label"><?= ucfirst($prob) ?></td>
                        <?php foreach ($impacts as $imp):
                            $nivel = calcularNivelRiesgo($prob, $imp);
                            $count = $matriz[$prob][$imp]['total'] ?? 0;
                        ?>
                        <td class="<?= $count > 0 ? 'risk-cell-' . $nivel : 'risk-cell-empty' ?>">
                            <?php if ($count > 0): ?>
                                <span class="risk-count"><?= $count ?></span>
                                <span class="risk-count-label">riesgo<?= $count > 1 ? 's' : '' ?></span>
                            <?php else: ?>
                                &mdash;
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
    <div class="card-header"><h6 class="mb-0">Listado de Restricciones y Riesgos</h6></div>
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
                        <div class="d-flex gap-1 justify-content-end">
                            <?php if (isAdmin()): ?>
                            <a href="<?= BASE_URL ?>index.php?page=riesgos&action=editar&id=<?= $r['id'] ?>" class="btn-action btn-action-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="<?= BASE_URL ?>index.php?page=riesgos&action=eliminar&id=<?= $r['id'] ?>" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                                <button class="btn-action btn-action-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($riesgos)): ?>
                <tr><td colspan="8" class="text-center text-muted">No hay restricciones ni riesgos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
