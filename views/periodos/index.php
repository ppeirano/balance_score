<?php
require_once __DIR__ . '/../../models/Periodo.php';
$periodos = Periodo::getAll($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<h4 class="mb-4"><i class="bi bi-calendar-range me-2"></i>Períodos Estratégicos</h4>

<!-- Formulario nuevo período -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Nuevo Período</h6></div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=periodos&action=guardar" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" placeholder="Ej: 2026-2027" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" name="fecha_fin" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de períodos -->
<div class="card">
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Nombre</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($periodos as $p): ?>
                <tr>
                    <td><?= sanitize($p['nombre']) ?></td>
                    <td><?= formatDate($p['fecha_inicio']) ?></td>
                    <td><?= formatDate($p['fecha_fin']) ?></td>
                    <td>
                        <?php if ($p['activo']): ?>
                            <span class="badge-ok">Activo</span>
                        <?php else: ?>
                            <span class="badge-neutral">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$p['activo']): ?>
                        <a href="<?= BASE_URL ?>index.php?page=periodos&action=activar&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('¿Activar este período?')">
                            <i class="bi bi-check-lg me-1"></i>Activar
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
