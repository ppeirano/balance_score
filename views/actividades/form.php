<?php
// Si estamos editando, cargar la actividad existente
$actividad = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM actividades WHERE id = ?");
    $stmt->execute([$id]);
    $actividad = $stmt->fetch();
    if (!$actividad) {
        flash('error', 'Actividad no encontrada.');
        redirect('index.php?page=planes');
    }
}

$esEdicion = ($actividad !== null);

// Determinar plan_accion_id: desde la actividad si editamos, o desde GET si creamos
$planAccionId = $esEdicion ? $actividad['plan_accion_id'] : (isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : null);

// Obtener lista de planes para dropdown (en caso de que no venga plan_id)
$stmtPlanes = $pdo->query("
    SELECT pa.id, pa.codigo, pa.nombre, ie.codigo AS ie_codigo
    FROM planes_accion pa
    JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
    ORDER BY ie.codigo, pa.prioridad
");
$planes = $stmtPlanes->fetchAll();

$pageTitle = $esEdicion ? 'Editar Actividad' : 'Nueva Actividad';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-<?= $esEdicion ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
        <?= $esEdicion ? 'Editar Actividad' : 'Nueva Actividad' ?>
    </h2>
    <?php if ($planAccionId): ?>
        <a href="<?= BASE_URL ?>index.php?page=planes&action=detalle&id=<?= $planAccionId ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver al Plan
        </a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>index.php?page=planes" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=actividades&action=guardar">
            <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= $actividad['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="plan_accion_id" class="form-label">Plan de Acción <span class="text-danger">*</span></label>
                    <?php if ($planAccionId): ?>
                        <input type="hidden" name="plan_accion_id" value="<?= $planAccionId ?>">
                        <?php
                        // Mostrar nombre del plan seleccionado
                        $planNombre = '';
                        foreach ($planes as $p) {
                            if ($p['id'] == $planAccionId) {
                                $planNombre = $p['ie_codigo'] . ' / ' . $p['codigo'] . ' - ' . $p['nombre'];
                                break;
                            }
                        }
                        ?>
                        <input type="text" class="form-control" value="<?= sanitize($planNombre) ?>" disabled>
                    <?php else: ?>
                        <select class="form-select" id="plan_accion_id" name="plan_accion_id" required>
                            <option value="">-- Seleccionar Plan --</option>
                            <?php foreach ($planes as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= sanitize($p['ie_codigo']) ?> / <?= sanitize($p['codigo']) ?> - <?= sanitize($p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="codigo" name="codigo"
                           value="<?= $esEdicion ? sanitize($actividad['codigo']) : '' ?>" required
                           placeholder="Ej: A1, A2...">
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?= $esEdicion ? sanitize($actividad['descripcion']) : '' ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="responsable" class="form-label">Responsable</label>
                    <input type="text" class="form-control" id="responsable" name="responsable"
                           value="<?= $esEdicion ? sanitize($actividad['responsable'] ?? '') : '' ?>">
                </div>
                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <?php
                        $estados = ['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completado' => 'Completado', 'cancelado' => 'Cancelado'];
                        foreach ($estados as $val => $label):
                        ?>
                            <option value="<?= $val ?>"
                                <?= ($esEdicion && $actividad['estado'] === $val) ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_limite" class="form-label">Fecha Límite</label>
                    <input type="date" class="form-control" id="fecha_limite" name="fecha_limite"
                           value="<?= $esEdicion ? sanitize($actividad['fecha_limite'] ?? '') : '' ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= $esEdicion ? sanitize($actividad['observaciones'] ?? '') : '' ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $esEdicion ? 'Actualizar' : 'Crear' ?> Actividad
                </button>
                <?php if ($esEdicion): ?>
                    <a href="<?= BASE_URL ?>index.php?page=calendario&tipo=actividad&entidad_id=<?= $actividad['id'] ?>&nombre=<?= urlencode($actividad['codigo'] . ' - ' . $actividad['descripcion']) ?>"
                       class="btn btn-outline-info">
                        <i class="bi bi-calendar-event me-1"></i>Agendar Seguimiento
                    </a>
                <?php endif; ?>
                <?php if ($planAccionId): ?>
                    <a href="<?= BASE_URL ?>index.php?page=planes&action=detalle&id=<?= $planAccionId ?>" class="btn btn-secondary">Cancelar</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>index.php?page=planes" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($esEdicion): ?>
<!-- Archivos Adjuntos -->
<?php
    require_once __DIR__ . '/../../models/ArchivoAdjunto.php';
    $adjuntos = ArchivoAdjunto::getByEntidad($pdo, 'actividad', $actividad['id']);
?>
<div class="card mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-paperclip me-2"></i>Archivos Adjuntos</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($adjuntos)): ?>
            <ul class="list-group mb-3">
                <?php foreach ($adjuntos as $adj): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-file-earmark me-1"></i>
                            <?= sanitize($adj['nombre_original']) ?>
                            <small class="text-muted ms-2">(<?= round($adj['tamano'] / 1024, 1) ?> KB)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>index.php?page=adjuntos&action=descargar&id=<?= $adj['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Descargar">
                                <i class="bi bi-download"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>index.php?page=adjuntos&action=eliminar&id=<?= $adj['id'] ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este archivo?');">
                                <input type="hidden" name="redirect" value="index.php?page=actividades&action=editar&id=<?= $actividad['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>index.php?page=adjuntos&action=subir" enctype="multipart/form-data">
            <input type="hidden" name="entidad_tipo" value="actividad">
            <input type="hidden" name="entidad_id" value="<?= $actividad['id'] ?>">
            <input type="hidden" name="redirect" value="index.php?page=actividades&action=editar&id=<?= $actividad['id'] ?>">
            <div class="input-group">
                <input type="file" class="form-control" name="archivo" required>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-upload me-1"></i>Subir Archivo
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
