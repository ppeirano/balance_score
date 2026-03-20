<?php
require_once __DIR__ . '/../../models/Iniciativa.php';

// Filtros
$filtroIE = isset($_GET['ie']) ? (int)$_GET['ie'] : null;
$filtroEstado = $_GET['estado'] ?? '';

// Obtener todas las iniciativas para el dropdown de filtro
$iniciativas = Iniciativa::getAll($pdo);

// Construir query con JOIN
$sql = "SELECT pa.*, ie.codigo AS ie_codigo, ie.nombre AS ie_nombre
        FROM planes_accion pa
        JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
        WHERE 1=1";
$params = [];

if ($filtroIE) {
    $sql .= " AND pa.iniciativa_id = ?";
    $params[] = $filtroIE;
}
if ($filtroEstado !== '') {
    $sql .= " AND pa.estado = ?";
    $params[] = $filtroEstado;
}

$sql .= " ORDER BY ie.codigo, pa.prioridad";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$planes = $stmt->fetchAll();

$pageTitle = 'Planes de Acción';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-list-check me-2"></i>Planes de Acción</h2>
    <a href="<?= BASE_URL ?>index.php?page=planes&action=crear" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Plan de Acción
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="planes">
            <div class="col-md-4">
                <label for="ie" class="form-label">Filtrar por Iniciativa Estratégica</label>
                <select name="ie" id="ie" class="form-select">
                    <option value="">-- Todas --</option>
                    <?php foreach ($iniciativas as $ie): ?>
                        <option value="<?= $ie['id'] ?>" <?= ($filtroIE == $ie['id']) ? 'selected' : '' ?>>
                            <?= sanitize($ie['codigo']) ?> - <?= sanitize($ie['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Filtrar por Estado</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="">-- Todos --</option>
                    <option value="pendiente" <?= ($filtroEstado === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                    <option value="en_progreso" <?= ($filtroEstado === 'en_progreso') ? 'selected' : '' ?>>En progreso</option>
                    <option value="completado" <?= ($filtroEstado === 'completado') ? 'selected' : '' ?>>Completado</option>
                    <option value="cancelado" <?= ($filtroEstado === 'cancelado') ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?= BASE_URL ?>index.php?page=planes" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Planes -->
<div class="card">
    <div class="card-body">
        <?php if (empty($planes)): ?>
            <div class="alert alert-info mb-0">No se encontraron planes de acción.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>IE</th>
                            <th>Owner</th>
                            <th>Avance</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planes as $plan): ?>
                            <tr>
                                <td><strong><?= sanitize($plan['codigo']) ?></strong></td>
                                <td><?= sanitize($plan['nombre']) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= sanitize($plan['ie_codigo']) ?>
                                    </span>
                                </td>
                                <td><?= sanitize($plan['owner'] ?? '-') ?></td>
                                <td style="min-width: 120px;">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?= $plan['avance'] >= 75 ? 'bg-success' : ($plan['avance'] >= 40 ? 'bg-warning' : 'bg-danger') ?>"
                                             role="progressbar"
                                             style="width: <?= (int)$plan['avance'] ?>%"
                                             aria-valuenow="<?= (int)$plan['avance'] ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <?= (int)$plan['avance'] ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?= estadoBadge($plan['estado']) ?></td>
                                <td><?= (int)$plan['prioridad'] ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>index.php?page=planes&action=detalle&id=<?= $plan['id'] ?>"
                                           class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>index.php?page=planes&action=editar&id=<?= $plan['id'] ?>"
                                           class="btn btn-outline-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="<?= BASE_URL ?>index.php?page=planes&action=eliminar&id=<?= $plan['id'] ?>"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Está seguro de que desea eliminar este plan de acción?');">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
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
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
