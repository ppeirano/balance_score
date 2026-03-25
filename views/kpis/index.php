<?php
require_once __DIR__ . '/../../models/Kpi.php';
require_once __DIR__ . '/../../models/Iniciativa.php';

$kpis = Kpi::getAll($pdo);
$iniciativas = Iniciativa::getAll($pdo);

// Filters
$filtroIE = $_GET['ie'] ?? '';
$filtroTipo = $_GET['tipo'] ?? '';

if ($filtroIE) {
    $kpis = array_filter($kpis, fn($k) => $k['iniciativa_id'] == $filtroIE);
}
if ($filtroTipo) {
    $kpis = array_filter($kpis, fn($k) => $k['tipo'] === $filtroTipo);
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up me-2"></i>Indicadores (KPIs)</h2>
    <a href="<?= BASE_URL ?>index.php?page=kpis&action=crear<?= $filtroIE ? '&filtro_ie=' . (int)$filtroIE : '' ?><?= $filtroTipo ? '&filtro_tipo=' . urlencode($filtroTipo) : '' ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo KPI
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="kpis">
            <div class="col-md-4">
                <label class="form-label">Iniciativa Estrat&eacute;gica</label>
                <select class="form-select" name="ie">
                    <option value="">Todas</option>
                    <?php foreach ($iniciativas as $ie): ?>
                        <option value="<?= (int)$ie['id'] ?>" <?= $filtroIE == $ie['id'] ? 'selected' : '' ?>>
                            <?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="tipo">
                    <option value="">Todos</option>
                    <option value="cuantitativo" <?= $filtroTipo === 'cuantitativo' ? 'selected' : '' ?>>Cuantitativo</option>
                    <option value="cualitativo" <?= $filtroTipo === 'cualitativo' ? 'selected' : '' ?>>Cualitativo</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="<?= BASE_URL ?>index.php?page=kpis" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de KPIs -->
<?php if (!empty($kpis)): ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">Sem&aacute;foro</th>
                    <th>Nombre</th>
                    <th>IE</th>
                    <th>Tipo</th>
                    <th>Meta</th>
                    <th>Valor Actual</th>
                    <th>Unidad</th>
                    <th>Frecuencia</th>
                    <th style="width: 150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kpis as $kpi): ?>
                    <?php
                    if ($kpi['tipo'] === 'cuantitativo') {
                        $semaforo = calcularSemaforo(
                            $kpi['valor_actual'],
                            $kpi['meta'],
                            $kpi['umbral_verde'],
                            $kpi['umbral_amarillo'],
                            $kpi['direccion']
                        );
                    } else {
                        $semaforo = $kpi['estado_semaforo'] ?? 'gris';
                    }
                    ?>
                    <tr>
                        <td class="text-center"><?= semaforoBadge($semaforo) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>index.php?page=kpis&action=historial&id=<?= (int)$kpi['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= sanitize($kpi['nombre']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($kpi['iniciativa_nombre']): ?>
                                <small><?= sanitize($kpi['iniciativa_codigo'] . ' - ' . $kpi['iniciativa_nombre']) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Sin asignar</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                                <span class="badge bg-info text-dark">Cuantitativo</span>
                            <?php else: ?>
                                <span class="badge bg-purple text-white" style="background-color: #6f42c1;">Cualitativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                                <?= sanitize($kpi['meta'] ?? '-') ?>
                            <?php else: ?>
                                <?= sanitize($kpi['escala_cualitativa'] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($kpi['tipo'] === 'cuantitativo'): ?>
                                <?= $kpi['valor_actual'] !== null ? sanitize($kpi['valor_actual']) : '<span class="text-muted">-</span>' ?>
                            <?php else: ?>
                                <?= $kpi['valor_cualitativo'] ? sanitize($kpi['valor_cualitativo']) : '<span class="text-muted">-</span>' ?>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($kpi['unidad'] ?? '-') ?></td>
                        <td><span class="badge bg-secondary"><?= sanitize(ucfirst($kpi['frecuencia'] ?? '-')) ?></span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>index.php?page=kpis&action=historial&id=<?= (int)$kpi['id'] ?>"
                                   class="btn btn-outline-info" title="Historial">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                                <a href="<?= BASE_URL ?>index.php?page=kpis&action=editar&id=<?= (int)$kpi['id'] ?><?= $filtroIE ? '&filtro_ie=' . (int)$filtroIE : '' ?><?= $filtroTipo ? '&filtro_tipo=' . urlencode($filtroTipo) : '' ?>"
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>index.php?page=kpis&action=eliminar&id=<?= (int)$kpi['id'] ?>"
                                      class="d-inline" onsubmit="return confirm('¿Eliminar este KPI?');">
                                    <?php if ($filtroIE): ?><input type="hidden" name="ie" value="<?= (int)$filtroIE ?>"><?php endif; ?>
                                    <?php if ($filtroTipo): ?><input type="hidden" name="tipo" value="<?= sanitize($filtroTipo) ?>"><?php endif; ?>
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
<?php else: ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No hay KPIs registrados.
    <a href="<?= BASE_URL ?>index.php?page=kpis&action=crear">Crear el primero</a>.
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
