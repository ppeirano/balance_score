<?php
require_once __DIR__ . '/../../models/Perspectiva.php';
require_once __DIR__ . '/../../models/Iniciativa.php';

// Obtener perspectivas ordenadas de base a resultado (Aprendizaje=4 arriba, Financiera=1 abajo)
$perspectivas = $pdo->query("SELECT * FROM perspectivas ORDER BY orden DESC")->fetchAll();

// Obtener todas las iniciativas
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

// Indexar iniciativas por id para busqueda rapida
$ieById = [];
foreach ($iniciativas as $ie) {
    $ieById[$ie['id']] = $ie;
}

// Obtener relaciones causa-efecto
$relaciones = $pdo->query("
    SELECT r.*,
           io.codigo AS origen_codigo, io.nombre AS origen_nombre,
           id2.codigo AS destino_codigo, id2.nombre AS destino_nombre
    FROM relaciones_causa_efecto r
    JOIN iniciativas_estrategicas io ON r.iniciativa_origen_id = io.id
    JOIN iniciativas_estrategicas id2 ON r.iniciativa_destino_id = id2.id
    ORDER BY io.orden ASC
")->fetchAll();

require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-diagram-3 me-2"></i>Mapa Estrat&eacute;gico</h2>
</div>

<p class="text-muted mb-4">
    Visualizaci&oacute;n de las perspectivas del Balanced Scorecard y sus iniciativas estrat&eacute;gicas.
    Las flechas representan relaciones causa-efecto entre iniciativas.
</p>

<!-- Mapa Estrategico: piramide BSC de base a resultado (Aprendizaje -> Financiera) -->
<div class="card mb-4">
    <div class="card-body p-3">
        <?php foreach ($perspectivas as $persp): ?>
            <?php $iesPersp = $ieByPerspectiva[$persp['id']] ?? []; ?>
            <div class="mb-3 p-3 rounded position-relative"
                 style="background-color: <?= sanitize($persp['color']) ?>15; border: 2px solid <?= sanitize($persp['color']) ?>40; min-height: 100px;">

                <!-- Etiqueta de perspectiva -->
                <div class="mb-2">
                    <span class="badge" style="background-color: <?= sanitize($persp['color']) ?>;">
                        <i class="bi <?= sanitize($persp['icono'] ?? 'bi-circle') ?> me-1"></i>
                        <?= sanitize($persp['nombre']) ?>
                    </span>
                </div>

                <!-- Iniciativas como tarjetas dentro de la banda -->
                <div class="row g-2 justify-content-center">
                    <?php if (!empty($iesPersp)): ?>
                        <?php foreach ($iesPersp as $ie): ?>
                            <div class="col-auto">
                                <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=detalle&id=<?= (int)$ie['id'] ?>"
                                   class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-3 h-100"
                                         style="border-left: 4px solid <?= sanitize($ie['perspectiva_color']) ?> !important; min-width: 160px; max-width: 220px;">
                                        <div class="card-body p-2 text-center">
                                            <span class="badge mb-1" style="background-color: <?= sanitize($ie['perspectiva_color']) ?>;">
                                                <?= sanitize($ie['codigo']) ?>
                                            </span>
                                            <div class="small fw-semibold text-dark"><?= sanitize($ie['nombre']) ?></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted small py-2">
                            Sin iniciativas en esta perspectiva
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Mostrar flecha visual entre bandas (excepto despues de la ultima)
            $lastPersp = end($perspectivas);
            if ($persp['id'] !== $lastPersp['id']):
            ?>
                <div class="text-center my-1">
                    <i class="bi bi-arrow-down fs-4 text-muted"></i>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Relaciones Causa-Efecto -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Relaciones Causa-Efecto</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nuevaRelacion">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
    </div>
    <div class="card-body">
        <!-- Formulario nueva relación -->
        <div class="collapse mb-3" id="nuevaRelacion">
            <div class="card card-body bg-light">
                <form method="POST" action="<?= BASE_URL ?>index.php?page=mapa&action=guardar_relacion" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Origen (causa)</label>
                        <select class="form-select form-select-sm" name="iniciativa_origen_id" required>
                            <option value="">-- Seleccionar IE --</option>
                            <?php foreach ($iniciativas as $ie): ?>
                                <option value="<?= $ie['id'] ?>"><?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Destino (efecto)</label>
                        <select class="form-select form-select-sm" name="iniciativa_destino_id" required>
                            <option value="">-- Seleccionar IE --</option>
                            <?php foreach ($iniciativas as $ie): ?>
                                <option value="<?= $ie['id'] ?>"><?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Descripción</label>
                        <input type="text" class="form-control form-control-sm" name="descripcion" placeholder="Ej: Capacitación mejora ejecución">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($relaciones)): ?>
            <div class="list-group list-group-flush">
                <?php foreach ($relaciones as $rel): ?>
                    <?php
                    $origenColor = $ieById[$rel['iniciativa_origen_id']]['perspectiva_color'] ?? '#6c757d';
                    $destinoColor = $ieById[$rel['iniciativa_destino_id']]['perspectiva_color'] ?? '#6c757d';
                    ?>
                    <div class="list-group-item d-flex align-items-center flex-wrap">
                        <span class="badge me-2" style="background-color: <?= sanitize($origenColor) ?>;">
                            <?= sanitize($rel['origen_codigo']) ?>
                        </span>
                        <span class="fw-semibold me-2"><?= sanitize($rel['origen_nombre']) ?></span>
                        <i class="bi bi-arrow-right text-primary mx-2"></i>
                        <span class="badge me-2" style="background-color: <?= sanitize($destinoColor) ?>;">
                            <?= sanitize($rel['destino_codigo']) ?>
                        </span>
                        <span class="fw-semibold me-2"><?= sanitize($rel['destino_nombre']) ?></span>
                        <?php if (!empty($rel['descripcion'])): ?>
                            <small class="text-muted ms-md-3 mt-1 mt-md-0">
                                <i class="bi bi-info-circle me-1"></i><?= sanitize($rel['descripcion']) ?>
                            </small>
                        <?php endif; ?>
                        <form method="POST" action="<?= BASE_URL ?>index.php?page=mapa&action=eliminar_relacion&id=<?= $rel['id'] ?>"
                              class="ms-auto" onsubmit="return confirm('¿Eliminar esta relación?');">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-3">
                <i class="bi bi-diagram-3 fs-1 d-block mb-2"></i>
                No hay relaciones causa-efecto registradas.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Leyenda -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-palette me-2"></i>Leyenda de Perspectivas</h6>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ($perspectivas as $persp): ?>
                <div class="col-md-3 col-6">
                    <span class="badge me-1" style="background-color: <?= sanitize($persp['color']) ?>;">
                        <i class="bi <?= sanitize($persp['icono'] ?? 'bi-circle') ?> me-1"></i>
                        <?= sanitize($persp['nombre']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
