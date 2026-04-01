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
    ORDER BY CAST(SUBSTRING(ie.codigo, 3) AS UNSIGNED) ASC
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

// Calcular avance por IE + cargar PDAs por IE
$avanceByIE = [];
$pdaByIE = [];
$allPdas = $pdo->query("
    SELECT pa.id, pa.nombre, pa.avance, pa.estado, pa.peso, pa.iniciativa_id
    FROM planes_accion pa
    ORDER BY pa.nombre
")->fetchAll();
$grouped = [];
foreach ($allPdas as $pda) {
    $grouped[$pda['iniciativa_id']][] = $pda;
}
foreach ($grouped as $ieId => $pdas) {
    $pdaByIE[$ieId] = $pdas;
    $pesoTotal = array_sum(array_column($pdas, 'peso'));
    $avance = 0;
    if ($pesoTotal > 0) {
        foreach ($pdas as $pda) {
            $avance += ($pda['avance'] * $pda['peso'] / $pesoTotal);
        }
    }
    $avanceByIE[$ieId] = round($avance);
}

// Riesgos abiertos por IE
$riesgosByIE = [];
$stmtRiesgos = $pdo->query("
    SELECT r.id, r.descripcion, r.nivel, r.iniciativa_id
    FROM riesgos r
    WHERE r.estado = 'abierto' AND r.iniciativa_id IS NOT NULL
    ORDER BY FIELD(r.nivel, 'critico', 'alto', 'medio', 'bajo')
");
foreach ($stmtRiesgos->fetchAll() as $row) {
    $riesgosByIE[$row['iniciativa_id']][] = $row;
}

// KPIs activos por IE
$kpisByIE = [];
$stmtKpis = $pdo->query("
    SELECT k.id, k.nombre, k.valor_actual, k.meta, k.unidad, k.estado_semaforo,
           k.es_entero, k.tipo, k.valor_cualitativo, k.iniciativa_id,
           k.umbral_verde, k.umbral_amarillo, k.direccion
    FROM kpis k
    WHERE k.activo = 1 AND k.iniciativa_id IS NOT NULL
    ORDER BY k.nombre
");
foreach ($stmtKpis->fetchAll() as $row) {
    $kpisByIE[$row['iniciativa_id']][] = $row;
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
    max-width: 260px;
    width: 100%;
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

/* IE Wrapper para expand */
.mapa-ie-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 160px;
    max-width: 260px;
}
.mapa-ie-expandable {
    cursor: pointer;
}
.mapa-ie-expandable:hover .mapa-ie-toggle i {
    transform: translateY(1px);
}
.mapa-ie-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    margin-top: 6px;
    font-size: 9px;
    color: #9ca3af;
    transition: color 0.15s;
}
.mapa-ie-toggle i {
    font-size: 10px;
    transition: transform 0.2s;
}
[aria-expanded="true"] .mapa-ie-toggle i {
    transform: rotate(180deg) !important;
}
.mapa-ie-expandable:hover .mapa-ie-toggle {
    color: #6b7280;
}

/* Detail panel */
.mapa-ie-detail {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 8px;
    margin-top: 6px;
    width: 260px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
}
.mapa-detail-section {
    margin-bottom: 6px;
}
.mapa-detail-section:last-of-type {
    margin-bottom: 4px;
}
.mapa-detail-title {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
    padding: 3px 6px;
    margin-bottom: 2px;
}
.mapa-detail-title i {
    font-size: 9px;
    margin-right: 3px;
}
.mapa-detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 6px;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: background 0.12s;
}
.mapa-detail-item:hover {
    background: #f8fafc;
    color: inherit;
}
.mapa-detail-name {
    font-size: 10px;
    font-weight: 500;
    color: #374151;
    flex: 1;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mapa-detail-progress {
    display: flex;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
}
.mapa-detail-progress small {
    font-size: 9px;
    font-weight: 600;
    color: #6b7280;
}
.mapa-detail-nivel {
    font-size: 8px;
    font-weight: 700;
    color: #fff;
    padding: 1px 6px;
    border-radius: 6px;
    text-transform: uppercase;
    flex-shrink: 0;
    letter-spacing: 0.3px;
}
.mapa-detail-item .badge-neutral,
.mapa-detail-item .badge-info,
.mapa-detail-item .badge-ok,
.mapa-detail-item .badge-bad,
.mapa-detail-item .badge-warn {
    font-size: 8px;
    padding: 1px 6px;
    flex-shrink: 0;
}
.mapa-detail-link {
    display: block;
    text-align: center;
    font-size: 9px;
    font-weight: 600;
    color: #3b82f6;
    text-decoration: none;
    padding: 5px 0 2px;
    border-top: 1px solid #f3f4f6;
    margin-top: 4px;
    transition: color 0.15s;
}
.mapa-detail-link:hover {
    color: #1d4ed8;
}
.mapa-detail-semaforo {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.mapa-detail-kpi-valor {
    font-size: 9px;
    font-weight: 600;
    color: #6b7280;
    flex-shrink: 0;
    white-space: nowrap;
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
                            $iePlanes = $pdaByIE[$ie['id']] ?? [];
                            $ieRiesgos = $riesgosByIE[$ie['id']] ?? [];
                            $ieKpis = $kpisByIE[$ie['id']] ?? [];
                            $hasDetail = !empty($iePlanes) || !empty($ieRiesgos) || !empty($ieKpis);
                            $collapseId = 'mapaIE' . (int)$ie['id'];
                        ?>
                            <div class="mapa-ie-wrapper">
                                <div class="mapa-ie-card <?= $hasDetail ? 'mapa-ie-expandable' : '' ?>"
                                     style="border-top-color: <?= sanitize($ie['perspectiva_color']) ?>;"
                                     <?php if ($hasDetail): ?>data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" role="button"<?php endif; ?>>
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
                                    <?php if ($hasDetail): ?>
                                        <div class="mapa-ie-toggle">
                                            <i class="bi bi-chevron-down"></i>
                                            <span><?= count($iePlanes) ?> planes · <?= count($ieKpis) ?> kpis · <?= count($ieRiesgos) ?> riesgos</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($hasDetail): ?>
                                <div class="collapse" id="<?= $collapseId ?>">
                                    <div class="mapa-ie-detail">
                                        <?php if (!empty($ieKpis)): ?>
                                        <div class="mapa-detail-section">
                                            <div class="mapa-detail-title"><i class="bi bi-speedometer2"></i> KPIs</div>
                                            <?php
                                            $semaforoColors = ['verde' => '#22c55e', 'amarillo' => '#f59e0b', 'rojo' => '#ef4444', 'gris' => '#9ca3af'];
                                            foreach ($ieKpis as $kpi):
                                                $semaforo = $kpi['tipo'] === 'cuantitativo'
                                                    ? calcularSemaforo($kpi['valor_actual'], $kpi['meta'], $kpi['umbral_verde'], $kpi['umbral_amarillo'], $kpi['direccion'])
                                                    : ($kpi['estado_semaforo'] ?? 'gris');
                                                $sColor = $semaforoColors[$semaforo] ?? '#9ca3af';
                                                $valorDisplay = $kpi['tipo'] === 'cualitativo'
                                                    ? ($kpi['valor_cualitativo'] ?: '-')
                                                    : formatKpiValor($kpi['valor_actual'], $kpi['es_entero']);
                                                $metaDisplay = $kpi['tipo'] === 'cualitativo'
                                                    ? ''
                                                    : ' / ' . formatKpiValor($kpi['meta'], $kpi['es_entero']) . ($kpi['unidad'] ? ' ' . $kpi['unidad'] : '');
                                            ?>
                                                <a href="<?= BASE_URL ?>index.php?page=kpis&action=historial&id=<?= (int)$kpi['id'] ?>" class="mapa-detail-item">
                                                    <span class="mapa-detail-semaforo" style="background-color: <?= $sColor ?>;"></span>
                                                    <span class="mapa-detail-name"><?= sanitize(mb_substr($kpi['nombre'], 0, 40)) ?></span>
                                                    <span class="mapa-detail-kpi-valor"><?= $valorDisplay ?><?= $metaDisplay ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($iePlanes)): ?>
                                        <div class="mapa-detail-section">
                                            <div class="mapa-detail-title"><i class="bi bi-clipboard-check"></i> Planes de Acción</div>
                                            <?php foreach ($iePlanes as $pda):
                                                $pdaAvance = (int)$pda['avance'];
                                                $pdaColor = $pdaAvance >= 70 ? '#22c55e' : ($pdaAvance >= 40 ? '#f59e0b' : '#ef4444');
                                            ?>
                                                <a href="<?= BASE_URL ?>index.php?page=planes&action=detalle&id=<?= (int)$pda['id'] ?>" class="mapa-detail-item">
                                                    <span class="mapa-detail-name"><?= sanitize(mb_substr($pda['nombre'], 0, 50)) ?></span>
                                                    <div class="mapa-detail-progress">
                                                        <div class="progress" style="width:40px;height:3px;">
                                                            <div class="progress-bar" style="width:<?= $pdaAvance ?>%;background-color:<?= $pdaColor ?>;"></div>
                                                        </div>
                                                        <small><?= $pdaAvance ?>%</small>
                                                    </div>
                                                    <?= estadoBadge($pda['estado']) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($ieRiesgos)): ?>
                                        <div class="mapa-detail-section">
                                            <div class="mapa-detail-title"><i class="bi bi-exclamation-triangle"></i> Restricciones y Riesgos</div>
                                            <?php
                                            $nivelColors = ['critico' => '#ef4444', 'alto' => '#f59e0b', 'medio' => '#3b82f6', 'bajo' => '#22c55e'];
                                            foreach ($ieRiesgos as $riesgo):
                                                $nColor = $nivelColors[$riesgo['nivel']] ?? '#6b7280';
                                            ?>
                                                <a href="<?= BASE_URL ?>index.php?page=riesgos&action=editar&id=<?= (int)$riesgo['id'] ?>" class="mapa-detail-item">
                                                    <span class="mapa-detail-nivel" style="background-color:<?= $nColor ?>;"><?= ucfirst($riesgo['nivel']) ?></span>
                                                    <span class="mapa-detail-name"><?= sanitize(mb_substr($riesgo['descripcion'], 0, 55)) ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=detalle&id=<?= (int)$ie['id'] ?>" class="mapa-detail-link">
                                            <i class="bi bi-box-arrow-up-right"></i> Ver IE completa
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
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
