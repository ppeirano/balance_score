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

// Calcular avance por IE
$avanceByIE = [];
foreach ($iniciativas as $ie) {
    $stmt = $pdo->prepare("SELECT avance, peso FROM planes_accion WHERE iniciativa_id = ?");
    $stmt->execute([$ie['id']]);
    $pdas = $stmt->fetchAll();
    $pesoTotal = array_sum(array_column($pdas, 'peso'));
    $avance = 0;
    if ($pesoTotal > 0) {
        foreach ($pdas as $pda) {
            $avance += ($pda['avance'] * $pda['peso'] / $pesoTotal);
        }
    }
    $avanceByIE[$ie['id']] = round($avance);
}

require_once __DIR__ . '/../layout/header.php';
?>

<style>
.mapa-banda {
    border-radius: 14px;
    padding: 20px 24px 16px;
    position: relative;
    margin-bottom: 0;
}
.mapa-banda-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 14px;
}
.mapa-ie-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px 14px 12px;
    text-align: center;
    min-width: 160px;
    max-width: 220px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-top: 3px solid transparent;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
    display: block;
}
.mapa-ie-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    color: inherit;
}
.mapa-ie-card .ie-code {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 8px;
}
.mapa-ie-card .ie-name {
    font-size: 12.5px;
    font-weight: 500;
    color: #1b2333;
    line-height: 1.3;
    margin-bottom: 8px;
}
.mapa-ie-card .ie-avance {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
}
.mapa-ie-card .ie-avance .progress {
    flex-grow: 1;
    height: 4px;
    max-width: 80px;
}
.mapa-ie-card .ie-avance small {
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
}
.mapa-conector {
    text-align: center;
    padding: 6px 0;
    color: #d1d5db;
    font-size: 1.2rem;
}
</style>

<!-- Mapa Estratégico -->
<div class="card mb-4">
    <div class="card-body p-4">
        <?php foreach ($perspectivas as $idx => $persp): ?>
            <?php $iesPersp = $ieByPerspectiva[$persp['id']] ?? []; ?>
            <div class="mapa-banda" style="background-color: <?= sanitize($persp['color']) ?>0D; border: 1px solid <?= sanitize($persp['color']) ?>25;">

                <div class="mapa-banda-label" style="background-color: <?= sanitize($persp['color']) ?>;">
                    <i class="bi <?= sanitize($persp['icono'] ?? 'bi-circle') ?>"></i>
                    <?= sanitize($persp['nombre']) ?>
                </div>

                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <?php if (!empty($iesPersp)): ?>
                        <?php foreach ($iesPersp as $ie):
                            $avance = $avanceByIE[$ie['id']] ?? 0;
                            $barColor = $avance >= 70 ? '#22c55e' : ($avance >= 40 ? '#f59e0b' : '#ef4444');
                        ?>
                            <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=detalle&id=<?= (int)$ie['id'] ?>"
                               class="mapa-ie-card" style="border-top-color: <?= sanitize($ie['perspectiva_color']) ?>;">
                                <span class="ie-code" style="background-color: <?= sanitize($ie['perspectiva_color']) ?>;">
                                    <?= sanitize($ie['codigo']) ?>
                                </span>
                                <div class="ie-name"><?= sanitize($ie['nombre']) ?></div>
                                <div class="ie-avance">
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?= $avance ?>%; background-color: <?= $barColor ?>;"></div>
                                    </div>
                                    <small><?= $avance ?>%</small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted small py-2">Sin iniciativas en esta perspectiva</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($idx < count($perspectivas) - 1): ?>
                <div class="mapa-conector">
                    <i class="bi bi-chevron-down"></i>
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
                            <button type="submit" class="btn-action btn-action-danger" title="Eliminar">
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
