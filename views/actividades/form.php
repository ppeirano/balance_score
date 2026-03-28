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

// Obtener lista de responsables
require_once __DIR__ . '/../../models/Responsable.php';
$responsables = Responsable::getAll($pdo);

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
                    <select class="form-select" id="responsable" name="responsable">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($responsables as $resp): ?>
                            <option value="<?= sanitize($resp['nombre']) ?>"
                                <?= ($esEdicion && ($actividad['responsable'] ?? '') === $resp['nombre']) ? 'selected' : '' ?>>
                                <?= sanitize($resp['nombre']) ?><?= $resp['cargo'] ? ' (' . sanitize($resp['cargo']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
<!-- Historial de Notas -->
<?php
    require_once __DIR__ . '/../../models/NotaActividad.php';
    $notas = NotaActividad::getByActividad($pdo, $actividad['id']);
?>
<style>
    .nota-item:hover .btn-eliminar-nota { opacity: 1; }
    .btn-eliminar-nota { opacity: 0; transition: opacity 0.2s; }
    .nota-timeline { position: relative; padding-left: 1.5rem; }
    .nota-timeline::before {
        content: '';
        position: absolute;
        left: 0.45rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    .nota-item { position: relative; }
    .nota-item::before {
        content: '';
        position: absolute;
        left: -1.05rem;
        top: 0.75rem;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #6c757d;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
</style>
<div class="card mt-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Historial de Notas</h5>
        <?php if (!empty($notas)): ?>
            <span class="badge-neutral"><?= count($notas) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <!-- Agregar nueva nota -->
        <form method="POST" action="<?= BASE_URL ?>index.php?page=notas_actividad&action=guardar" class="mb-4" id="formNota">
            <input type="hidden" name="actividad_id" value="<?= $actividad['id'] ?>">
            <input type="hidden" name="imagen" id="notaImagen" value="">
            <textarea class="form-control mb-2" name="texto" id="notaTexto" rows="2" placeholder="Escribir una nota... (pod&#233;s pegar im&#225;genes con Ctrl+V)" required></textarea>
            <!-- Preview de imagen pegada -->
            <div id="notaImagenPreview" class="mb-2" style="display:none;">
                <div class="position-relative d-inline-block">
                    <img id="notaImagenImg" src="" class="img-thumbnail" style="max-height: 150px;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="quitarImagenNota()" title="Quitar imagen">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <small class="text-muted d-block mt-1"><i class="bi bi-image me-1"></i>Imagen adjunta</small>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-send me-1"></i>Agregar nota
                </button>
            </div>
        </form>

        <?php if (!empty($notas)): ?>
            <div class="nota-timeline">
                <?php foreach ($notas as $nota): ?>
                    <div class="nota-item mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <small class="text-muted d-block mb-1">
                                    <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($nota['created_at'])) ?>
                                </small>
                                <p class="mb-0"><?= nl2br(sanitize($nota['texto'])) ?></p>
                                <?php if ($nota['imagen']): ?>
                                    <div class="mt-2">
                                        <a href="<?= BASE_URL ?>uploads/notas/<?= sanitize($nota['imagen']) ?>" target="_blank">
                                            <img src="<?= BASE_URL ?>uploads/notas/<?= sanitize($nota['imagen']) ?>" class="img-thumbnail" style="max-height: 300px; cursor: pointer;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>index.php?page=notas_actividad&action=eliminar&id=<?= $nota['id'] ?>"
                                  class="ms-2"
                                  onsubmit="return confirm('¿Eliminar esta nota?');">
                                <button type="submit" class="btn btn-sm btn-link text-danger btn-eliminar-nota p-0" title="Eliminar nota">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-3">
                <i class="bi bi-chat-left-text d-block mb-2" style="font-size: 1.5rem;"></i>
                No hay notas registradas aún.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Paste de imágenes en el textarea de notas
document.getElementById('notaTexto').addEventListener('paste', function(e) {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            e.preventDefault();
            const file = items[i].getAsFile();
            subirImagenNota(file);
            return;
        }
    }
});

function subirImagenNota(file) {
    const formData = new FormData();
    formData.append('imagen', file, file.name || 'pasted-image.png');

    const preview = document.getElementById('notaImagenPreview');
    const img = document.getElementById('notaImagenImg');
    preview.style.display = 'block';
    img.src = URL.createObjectURL(file);

    fetch('<?= BASE_URL ?>index.php?page=notas_actividad&action=subir_imagen', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            document.getElementById('notaImagen').value = data.filename;
        } else {
            alert('Error al subir imagen: ' + (data.error || 'desconocido'));
            quitarImagenNota();
        }
    })
    .catch(() => {
        alert('Error de conexión al subir la imagen.');
        quitarImagenNota();
    });
}

function quitarImagenNota() {
    document.getElementById('notaImagen').value = '';
    document.getElementById('notaImagenPreview').style.display = 'none';
    document.getElementById('notaImagenImg').src = '';
}
</script>

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
