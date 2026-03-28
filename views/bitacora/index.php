<?php
require_once __DIR__ . '/../../models/Bitacora.php';
require_once __DIR__ . '/../layout/header.php';

$filtroEntidad = $_GET['entidad_tipo'] ?? '';
$filtroAccion = $_GET['accion'] ?? '';
$pagina = max(1, (int)($_GET['p'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

$filtros = [];
if ($filtroEntidad) $filtros['entidad_tipo'] = $filtroEntidad;
if ($filtroAccion) $filtros['accion'] = $filtroAccion;

$total = Bitacora::contar($pdo, $filtros);
$registros = Bitacora::getAll($pdo, $filtros, $porPagina, $offset);
$totalPaginas = max(1, ceil($total / $porPagina));

$iconosEntidad = [
    'iniciativa' => 'bi-bullseye',
    'plan_accion' => 'bi-list-check',
    'actividad' => 'bi-check2-square',
    'kpi' => 'bi-graph-up',
    'proyecto' => 'bi-kanban',
    'riesgo' => 'bi-exclamation-triangle',
    'reunion' => 'bi-people',
    'compromiso' => 'bi-handshake',
];

$nombresEntidad = [
    'iniciativa' => 'Iniciativa',
    'plan_accion' => 'Plan de Acción',
    'actividad' => 'Actividad',
    'kpi' => 'KPI',
    'proyecto' => 'Proyecto',
    'riesgo' => 'Riesgo',
    'reunion' => 'Reunión',
    'compromiso' => 'Compromiso',
];

$coloresAccion = [
    'creado' => 'badge-ok',
    'editado' => 'badge-info',
    'eliminado' => 'badge-bad',
    'estado_cambiado' => 'badge-warn',
];

$nombresAccion = [
    'creado' => 'Creado',
    'editado' => 'Editado',
    'eliminado' => 'Eliminado',
    'estado_cambiado' => 'Estado cambiado',
];

// URLs de detalle por tipo
function urlDetalle($tipo, $id) {
    $urls = [
        'iniciativa' => "index.php?page=iniciativas&action=detalle&id=$id",
        'plan_accion' => "index.php?page=planes&action=detalle&id=$id",
        'proyecto' => "index.php?page=proyectos&action=detalle&id=$id",
        'reunion' => "index.php?page=reuniones&action=detalle&id=$id",
    ];
    return $urls[$tipo] ?? null;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-clock-history me-2"></i>Bitácora de Cambios</h4>
    <span class="text-muted"><?= $total ?> registro<?= $total !== 1 ? 's' : '' ?></span>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="bitacora">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-0">Entidad</label>
                <select class="form-select form-select-sm" name="entidad_tipo">
                    <option value="">-- Todas --</option>
                    <?php foreach ($nombresEntidad as $val => $lab): ?>
                        <option value="<?= $val ?>" <?= $filtroEntidad === $val ? 'selected' : '' ?>><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-0">Acción</label>
                <select class="form-select form-select-sm" name="accion">
                    <option value="">-- Todas --</option>
                    <?php foreach ($nombresAccion as $val => $lab): ?>
                        <option value="<?= $val ?>" <?= $filtroAccion === $val ? 'selected' : '' ?>><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            </div>
            <?php if ($filtroEntidad || $filtroAccion): ?>
            <div class="col-md-2">
                <a href="<?= BASE_URL ?>index.php?page=bitacora" class="btn btn-sm btn-outline-secondary w-100">Limpiar</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Timeline -->
<?php if (empty($registros)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-clock-history d-block mb-2" style="font-size: 2rem;"></i>
        No hay registros en la bitácora.
    </div>
<?php else: ?>

<style>
    .bitacora-timeline { position: relative; padding-left: 2rem; }
    .bitacora-timeline::before { content: ''; position: absolute; left: 0.65rem; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
    .bitacora-item { position: relative; margin-bottom: 0.75rem; }
    .bitacora-item::before {
        content: '';
        position: absolute;
        left: -1.35rem;
        top: 0.65rem;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #6c757d;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
    .bitacora-item.accion-creado::before { background: #198754; }
    .bitacora-item.accion-editado::before { background: #0d6efd; }
    .bitacora-item.accion-eliminado::before { background: #dc3545; }
    .bitacora-item.accion-estado_cambiado::before { background: #ffc107; }
    .bitacora-card { background: #fff; border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 0.75rem 1rem; transition: box-shadow 0.2s; }
    .bitacora-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .bitacora-fecha-grupo { font-size: 0.85rem; font-weight: 600; color: #495057; margin-bottom: 0.5rem; margin-top: 1rem; padding-left: 0.5rem; }
    .bitacora-fecha-grupo:first-child { margin-top: 0; }
</style>

<div class="bitacora-timeline">
    <?php
    $fechaAnterior = '';
    foreach ($registros as $r):
        $fechaActual = date('d/m/Y', strtotime($r['created_at']));
        if ($fechaActual !== $fechaAnterior):
            $fechaAnterior = $fechaActual;
    ?>
        <div class="bitacora-fecha-grupo">
            <i class="bi bi-calendar3 me-1"></i><?= $fechaActual ?>
        </div>
    <?php endif; ?>
    <div class="bitacora-item accion-<?= $r['accion'] ?>">
        <div class="bitacora-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi <?= $iconosEntidad[$r['entidad_tipo']] ?? 'bi-record' ?> text-muted"></i>
                        <span class="<?= $coloresAccion[$r['accion']] ?? 'badge-neutral' ?>" style="font-size: 0.7rem;">
                            <?= $nombresAccion[$r['accion']] ?? $r['accion'] ?>
                        </span>
                        <small class="text-muted"><?= $nombresEntidad[$r['entidad_tipo']] ?? $r['entidad_tipo'] ?></small>
                    </div>
                    <div>
                        <?php
                        $url = ($r['accion'] !== 'eliminado') ? urlDetalle($r['entidad_tipo'], $r['entidad_id']) : null;
                        $nombre = sanitize($r['entidad_nombre']);
                        if (mb_strlen($nombre) > 100) $nombre = mb_substr($nombre, 0, 100) . '...';
                        ?>
                        <?php if ($url): ?>
                            <a href="<?= BASE_URL . $url ?>" class="text-decoration-none fw-semibold"><?= $nombre ?></a>
                        <?php else: ?>
                            <span class="fw-semibold <?= $r['accion'] === 'eliminado' ? 'text-muted text-decoration-line-through' : '' ?>"><?= $nombre ?></span>
                        <?php endif; ?>
                        <?php if ($r['descripcion']): ?>
                            <small class="text-muted ms-2"><?= sanitize($r['descripcion']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <small class="text-muted text-nowrap ms-3"><?= date('H:i', strtotime($r['created_at'])) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Paginación -->
<?php if ($totalPaginas > 1): ?>
<nav class="mt-4">
    <ul class="pagination pagination-sm justify-content-center">
        <?php
        $queryBase = 'index.php?page=bitacora';
        if ($filtroEntidad) $queryBase .= '&entidad_tipo=' . urlencode($filtroEntidad);
        if ($filtroAccion) $queryBase .= '&accion=' . urlencode($filtroAccion);
        ?>
        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= BASE_URL . $queryBase ?>&p=<?= $pagina - 1 ?>">Anterior</a>
        </li>
        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p == $pagina ? 'active' : '' ?>">
                <a class="page-link" href="<?= BASE_URL . $queryBase ?>&p=<?= $p ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= BASE_URL . $queryBase ?>&p=<?= $pagina + 1 ?>">Siguiente</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
