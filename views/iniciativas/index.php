<?php
require_once __DIR__ . '/../../models/Perspectiva.php';
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/PlanAccion.php';

// Obtener perspectivas ordenadas
$perspectivas = $pdo->query("SELECT * FROM perspectivas ORDER BY orden ASC")->fetchAll();

// Obtener todas las iniciativas con su perspectiva
$iniciativas = $pdo->query("
    SELECT ie.*, p.nombre AS perspectiva_nombre, p.color AS perspectiva_color
    FROM iniciativas_estrategicas ie
    JOIN perspectivas p ON ie.perspectiva_id = p.id
    ORDER BY ie.orden ASC
")->fetchAll();

// Agrupar iniciativas por perspectiva
$ieByPerspectiva = [];
foreach ($iniciativas as $ie) {
    $ieByPerspectiva[$ie['perspectiva_id']][] = $ie;
}

// Contar PDAs y calcular avance por iniciativa
$pdaStats = [];
$stmtPda = $pdo->prepare("
    SELECT COUNT(*) as total_pdas,
           COALESCE(AVG(avance), 0) as avance_promedio,
           SUM(CASE WHEN peso > 0 THEN avance * peso ELSE 0 END) as avance_ponderado,
           SUM(CASE WHEN peso > 0 THEN peso ELSE 0 END) as peso_total
    FROM planes_accion
    WHERE iniciativa_id = ?
");
foreach ($iniciativas as $ie) {
    $stmtPda->execute([$ie['id']]);
    $stats = $stmtPda->fetch();
    $avance = 0;
    if ($stats['peso_total'] > 0) {
        $avance = round($stats['avance_ponderado'] / $stats['peso_total']);
    } elseif ($stats['total_pdas'] > 0) {
        $avance = round($stats['avance_promedio']);
    }
    $pdaStats[$ie['id']] = [
        'total_pdas' => (int)$stats['total_pdas'],
        'avance' => $avance
    ];
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-bullseye me-2"></i>Iniciativas Estrat&eacute;gicas</h2>
    <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=crear" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nueva IE
    </a>
</div>

<div class="accordion" id="accordionPerspectivas">
    <?php foreach ($perspectivas as $index => $persp): ?>
        <?php $iesPersp = $ieByPerspectiva[$persp['id']] ?? []; ?>
        <?php if (empty($iesPersp)) continue; ?>
        <div class="accordion-item mb-3 border" style="border-left: 5px solid <?= sanitize($persp['color']) ?> !important;">
            <h2 class="accordion-header" id="heading<?= (int)$persp['id'] ?>">
                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse<?= (int)$persp['id'] ?>"
                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                        aria-controls="collapse<?= (int)$persp['id'] ?>">
                    <span class="badge me-2" style="background-color: <?= sanitize($persp['color']) ?>;">
                        <i class="bi <?= sanitize($persp['icono'] ?? 'bi-circle') ?> me-1"></i>
                        <?= sanitize($persp['nombre']) ?>
                    </span>
                    <small class="text-muted ms-2"><?= count($iesPersp) ?> iniciativa(s)</small>
                </button>
            </h2>
            <div id="collapse<?= (int)$persp['id'] ?>"
                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                 aria-labelledby="heading<?= (int)$persp['id'] ?>"
                 data-bs-parent="#accordionPerspectivas">
                <div class="accordion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">C&oacute;digo</th>
                                    <th>Nombre</th>
                                    <th class="text-center" style="width: 100px;">PDAs</th>
                                    <th style="width: 200px;">Avance</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($iesPersp as $ie): ?>
                                    <?php
                                    $stats = $pdaStats[$ie['id']];
                                    $avance = $stats['avance'];
                                    $barClass = 'bg-danger';
                                    if ($avance >= 70) $barClass = 'bg-success';
                                    elseif ($avance >= 40) $barClass = 'bg-warning';
                                    elseif ($avance >= 20) $barClass = 'bg-info';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge" style="background-color: <?= sanitize($persp['color']) ?>;">
                                                <?= sanitize($ie['codigo']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=detalle&id=<?= (int)$ie['id'] ?>" class="text-decoration-none fw-semibold">
                                                <?= sanitize($ie['nombre']) ?>
                                            </a>
                                            <?php if (!empty($ie['descripcion'])): ?>
                                                <br><small class="text-muted"><?= sanitize(mb_substr($ie['descripcion'], 0, 100)) ?><?= mb_strlen($ie['descripcion']) > 100 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary rounded-pill"><?= $stats['total_pdas'] ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                    <div class="progress-bar <?= $barClass ?>" role="progressbar"
                                                         style="width: <?= $avance ?>%;"
                                                         aria-valuenow="<?= $avance ?>" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small class="fw-bold"><?= $avance ?>%</small>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=detalle&id=<?= (int)$ie['id'] ?>"
                                                   class="btn btn-outline-primary" title="Ver detalle">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=editar&id=<?= (int)$ie['id'] ?>"
                                                   class="btn btn-outline-secondary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="<?= BASE_URL ?>index.php?page=iniciativas&action=eliminar&id=<?= (int)$ie['id'] ?>"
                                                      class="d-inline" onsubmit="return confirm('¿Eliminar esta IE y todos sus planes/actividades?');">
                                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($iniciativas)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>No hay iniciativas estrat&eacute;gicas registradas.
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
