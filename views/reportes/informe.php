<?php
require_once __DIR__ . '/../../models/Perspectiva.php';
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/PlanAccion.php';
require_once __DIR__ . '/../../models/Kpi.php';
require_once __DIR__ . '/../../models/Riesgo.php';
require_once __DIR__ . '/../../models/Proyecto.php';
require_once __DIR__ . '/../../models/Compromiso.php';

// --- Datos ---
$periodo = getPeriodoActivo($pdo);
$perspectivas = $pdo->query("SELECT * FROM perspectivas ORDER BY orden DESC")->fetchAll();

// Iniciativas por perspectiva
$iniciativas = $pdo->query("
    SELECT ie.*, p.nombre AS perspectiva_nombre, p.color AS perspectiva_color, p.icono AS perspectiva_icono
    FROM iniciativas_estrategicas ie
    JOIN perspectivas p ON ie.perspectiva_id = p.id
    ORDER BY p.orden DESC, CAST(SUBSTRING(ie.codigo, 3) AS UNSIGNED) ASC
")->fetchAll();
$ieByPersp = [];
foreach ($iniciativas as $ie) {
    $ieByPersp[$ie['perspectiva_id']][] = $ie;
}

// Avance ponderado por IE (desde PDAs)
$allPdas = $pdo->query("
    SELECT pa.id, pa.nombre, pa.owner, pa.avance, pa.estado, pa.peso, pa.iniciativa_id
    FROM planes_accion pa ORDER BY pa.nombre
")->fetchAll();
$pdaByIE = [];
$avanceByIE = [];
foreach ($allPdas as $pda) {
    $pdaByIE[$pda['iniciativa_id']][] = $pda;
}
foreach ($iniciativas as $ie) {
    $avanceByIE[$ie['id']] = Iniciativa::calcularAvance($pdo, $ie['id']);
}

// KPIs activos por IE (con campos para recalcular semáforo)
$allKpis = $pdo->query("
    SELECT k.id, k.nombre, k.valor_actual, k.meta, k.unidad, k.estado_semaforo,
           k.es_entero, k.tipo, k.valor_cualitativo, k.iniciativa_id,
           k.umbral_verde, k.umbral_amarillo, k.direccion
    FROM kpis k WHERE k.activo = 1 ORDER BY k.nombre
")->fetchAll();
$kpisByIE = [];
$semaforoCounts = ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'gris' => 0];
foreach ($allKpis as $kpi) {
    $semaforo = $kpi['tipo'] === 'cuantitativo'
        ? calcularSemaforo($kpi['valor_actual'], $kpi['meta'], $kpi['umbral_verde'], $kpi['umbral_amarillo'], $kpi['direccion'])
        : ($kpi['estado_semaforo'] ?? 'gris');
    $kpi['semaforo_calc'] = $semaforo;
    $semaforoCounts[$semaforo] = ($semaforoCounts[$semaforo] ?? 0) + 1;
    if ($kpi['iniciativa_id']) {
        $kpisByIE[$kpi['iniciativa_id']][] = $kpi;
    }
}

// Riesgos abiertos por IE
$allRiesgos = $pdo->query("
    SELECT r.id, r.descripcion, r.nivel, r.probabilidad, r.impacto, r.plan_mitigacion, r.responsable, r.iniciativa_id
    FROM riesgos r WHERE r.estado = 'abierto'
    ORDER BY FIELD(r.nivel, 'critico', 'alto', 'medio', 'bajo')
")->fetchAll();
$riesgosByIE = [];
$riesgosByNivel = ['critico' => 0, 'alto' => 0, 'medio' => 0, 'bajo' => 0];
foreach ($allRiesgos as $r) {
    $riesgosByNivel[$r['nivel']] = ($riesgosByNivel[$r['nivel']] ?? 0) + 1;
    if ($r['iniciativa_id']) {
        $riesgosByIE[$r['iniciativa_id']][] = $r;
    }
}

// Matriz de riesgos
$matriz = Riesgo::getMatriz($pdo);

// Proyectos
$proyectos = Proyecto::getAll($pdo);

// Compromisos pendientes
$compromisos = Compromiso::getPendientes($pdo);

require_once __DIR__ . '/../layout/header.php';
?>

<style>
/* Informe styles */
.informe {
    max-width: 1000px;
    margin: 0 auto;
    font-size: 11px;
    color: #1b2333;
}
.informe-header {
    text-align: center;
    padding: 20px 0 10px;
    border-bottom: 2px solid #1b2333;
    margin-bottom: 20px;
}
.informe-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1b2333;
    margin-bottom: 2px;
}
.informe-header .informe-subtitle {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}
.informe-header .informe-date {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 4px;
}

/* Resumen */
.informe-resumen {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}
.resumen-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
}
.resumen-card .resumen-title {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    margin-bottom: 6px;
}
.resumen-card .resumen-value {
    font-size: 20px;
    font-weight: 700;
}
.resumen-card .resumen-detail {
    font-size: 9px;
    color: #6b7280;
    margin-top: 4px;
}
.resumen-semaforos {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 6px;
}
.resumen-semaforos .sem-item {
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    font-weight: 600;
}
.sem-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

/* Perspectiva section */
.informe-perspectiva {
    margin-bottom: 20px;
    page-break-inside: avoid;
}
.perspectiva-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 12px;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
}
.perspectiva-header i {
    font-size: 14px;
}
.perspectiva-avance {
    margin-left: auto;
    font-size: 12px;
    font-weight: 700;
    background: rgba(255,255,255,0.25);
    padding: 2px 10px;
    border-radius: 12px;
}

/* IE block */
.informe-ie {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    page-break-inside: avoid;
}
.ie-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.ie-header .ie-code {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    color: #fff;
}
.ie-header .ie-name {
    font-size: 12px;
    font-weight: 600;
    color: #1b2333;
    flex: 1;
}
.ie-header .ie-avance-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
}
.ie-avance-bar {
    width: 60px;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}
.ie-avance-bar-fill {
    height: 100%;
    border-radius: 3px;
}
.ie-avance-text {
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    min-width: 30px;
    text-align: right;
}

/* Subsection titles */
.ie-section-title {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    margin: 8px 0 4px;
    padding-bottom: 2px;
    border-bottom: 1px solid #f3f4f6;
}

/* Tables inside IE */
.informe-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}
.informe-table th {
    text-align: left;
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #9ca3af;
    padding: 3px 6px;
    border-bottom: 1px solid #e5e7eb;
}
.informe-table td {
    padding: 4px 6px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}
.informe-table tr:last-child td {
    border-bottom: none;
}

/* Inline progress bar for tables */
.inline-bar {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.inline-bar-track {
    width: 40px;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    display: inline-block;
}
.inline-bar-fill {
    height: 100%;
    border-radius: 2px;
    display: block;
}
.inline-bar-text {
    font-size: 9px;
    font-weight: 600;
    color: #6b7280;
}

/* Semáforo in table */
.sem-dot-sm {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    vertical-align: middle;
}

/* Risk nivel inline */
.nivel-badge {
    font-size: 7px;
    font-weight: 700;
    color: #fff;
    padding: 1px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Matriz de riesgos */
.informe-matriz {
    margin: 0 auto 20px;
    page-break-inside: avoid;
}
.informe-matriz h3 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 10px;
}
.matriz-table {
    border-collapse: separate;
    border-spacing: 4px;
    margin: 0 auto;
}
.matriz-table th, .matriz-table td {
    text-align: center;
    padding: 6px 10px;
    font-size: 10px;
}
.matriz-table th {
    font-weight: 600;
    color: #6b7280;
}
.matriz-cell {
    border-radius: 6px;
    min-width: 50px;
    font-weight: 700;
    font-size: 14px;
    color: #fff;
}

/* Proyectos section */
.informe-section {
    margin-bottom: 20px;
    page-break-inside: avoid;
}
.informe-section h3 {
    font-size: 14px;
    font-weight: 600;
    color: #1b2333;
    margin-bottom: 10px;
    padding-bottom: 4px;
    border-bottom: 2px solid #e5e7eb;
}

/* Print */
@media print {
    body { font-size: 10px; }
    .sidebar, .navbar, .btn, .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .informe { max-width: 100%; }
    .informe-perspectiva { page-break-inside: avoid; }
    .informe-ie { page-break-inside: avoid; }
    .informe-section { page-break-inside: avoid; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>

<div class="informe">

    <!-- A) Encabezado -->
    <div class="informe-header">
        <h1>Informe de Estrategia</h1>
        <div class="informe-subtitle">Temis Lostalo<?= $periodo ? ' — ' . sanitize($periodo['nombre']) : '' ?></div>
        <div class="informe-date">Generado el <?= date('d/m/Y H:i') ?></div>
    </div>

    <!-- B) Resumen Ejecutivo -->
    <?php
    // Calcular avance general por perspectiva
    $avancePersp = [];
    foreach ($perspectivas as $persp) {
        $ies = $ieByPersp[$persp['id']] ?? [];
        if (empty($ies)) { $avancePersp[$persp['id']] = 0; continue; }
        $total = 0;
        foreach ($ies as $ie) {
            $total += ($avanceByIE[$ie['id']] ?? 0);
        }
        $avancePersp[$persp['id']] = round($total / count($ies));
    }
    $avanceGlobal = !empty($avancePersp) ? round(array_sum($avancePersp) / count($avancePersp)) : 0;
    ?>
    <div class="informe-resumen">
        <div class="resumen-card">
            <div class="resumen-title">Avance Global</div>
            <div class="resumen-value" style="color: <?= $avanceGlobal >= 70 ? '#22c55e' : ($avanceGlobal >= 40 ? '#f59e0b' : '#ef4444') ?>;">
                <?= $avanceGlobal ?>%
            </div>
            <div class="resumen-detail">Promedio ponderado de IEs</div>
        </div>
        <div class="resumen-card">
            <div class="resumen-title">KPIs</div>
            <div class="resumen-value"><?= count($allKpis) ?></div>
            <div class="resumen-semaforos">
                <span class="sem-item"><span class="sem-dot" style="background:#22c55e;"></span> <?= $semaforoCounts['verde'] ?></span>
                <span class="sem-item"><span class="sem-dot" style="background:#f59e0b;"></span> <?= $semaforoCounts['amarillo'] ?></span>
                <span class="sem-item"><span class="sem-dot" style="background:#ef4444;"></span> <?= $semaforoCounts['rojo'] ?></span>
                <span class="sem-item"><span class="sem-dot" style="background:#9ca3af;"></span> <?= $semaforoCounts['gris'] ?></span>
            </div>
        </div>
        <div class="resumen-card">
            <div class="resumen-title">Riesgos Abiertos</div>
            <div class="resumen-value"><?= count($allRiesgos) ?></div>
            <div class="resumen-detail">
                <?= $riesgosByNivel['critico'] ?> críticos · <?= $riesgosByNivel['alto'] ?> altos
            </div>
        </div>
        <div class="resumen-card">
            <div class="resumen-title">Compromisos Pend.</div>
            <div class="resumen-value"><?= count($compromisos) ?></div>
            <div class="resumen-detail">Sin completar</div>
        </div>
    </div>

    <!-- Avance por perspectiva -->
    <div style="margin-bottom: 20px;">
        <?php foreach ($perspectivas as $persp):
            $av = $avancePersp[$persp['id']];
            $barColor = $av >= 70 ? '#22c55e' : ($av >= 40 ? '#f59e0b' : '#ef4444');
        ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <span style="font-size:10px;font-weight:600;color:<?= sanitize($persp['color']) ?>;min-width:160px;">
                <i class="bi <?= sanitize($persp['icono'] ?? 'bi-circle') ?>"></i>
                <?= sanitize($persp['nombre']) ?>
            </span>
            <div style="flex:1;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                <div style="width:<?= $av ?>%;height:100%;background:<?= $barColor ?>;border-radius:4px;"></div>
            </div>
            <span style="font-size:11px;font-weight:700;color:#6b7280;min-width:35px;text-align:right;"><?= $av ?>%</span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- C) Detalle por Perspectiva -->
    <?php foreach ($perspectivas as $persp):
        $ies = $ieByPersp[$persp['id']] ?? [];
    ?>
    <div class="informe-perspectiva">
        <div class="perspectiva-header" style="background-color: <?= sanitize($persp['color']) ?>;">
            <i class="bi <?= sanitize($persp['icono'] ?? 'bi-circle') ?>"></i>
            <?= sanitize($persp['nombre']) ?>
            <span class="perspectiva-avance"><?= $avancePersp[$persp['id']] ?>%</span>
        </div>

        <?php if (empty($ies)): ?>
            <p style="color:#9ca3af;font-size:10px;padding-left:12px;">Sin iniciativas estratégicas</p>
        <?php endif; ?>

        <?php foreach ($ies as $ie):
            $ieAvance = $avanceByIE[$ie['id']] ?? 0;
            $barColor = $ieAvance >= 70 ? '#22c55e' : ($ieAvance >= 40 ? '#f59e0b' : '#ef4444');
            $ieKpis = $kpisByIE[$ie['id']] ?? [];
            $iePdas = $pdaByIE[$ie['id']] ?? [];
            $ieRiesgos = $riesgosByIE[$ie['id']] ?? [];
        ?>
        <div class="informe-ie" style="border-left: 3px solid <?= sanitize($ie['perspectiva_color']) ?>;">
            <div class="ie-header">
                <span class="ie-code" style="background-color: <?= sanitize($ie['perspectiva_color']) ?>;"><?= sanitize($ie['codigo']) ?></span>
                <span class="ie-name"><?= sanitize($ie['nombre']) ?></span>
                <div class="ie-avance-wrap">
                    <div class="ie-avance-bar">
                        <div class="ie-avance-bar-fill" style="width:<?= $ieAvance ?>%;background:<?= $barColor ?>;"></div>
                    </div>
                    <span class="ie-avance-text"><?= $ieAvance ?>%</span>
                </div>
            </div>

            <?php if (!empty($ieKpis)): ?>
            <div class="ie-section-title"><i class="bi bi-speedometer2"></i> KPIs</div>
            <table class="informe-table">
                <thead>
                    <tr>
                        <th style="width:20px;"></th>
                        <th>Nombre</th>
                        <th>Valor Actual</th>
                        <th>Meta</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ieKpis as $kpi):
                        $sColors = ['verde' => '#22c55e', 'amarillo' => '#f59e0b', 'rojo' => '#ef4444', 'gris' => '#9ca3af'];
                        $sColor = $sColors[$kpi['semaforo_calc']] ?? '#9ca3af';
                        $valorDisplay = $kpi['tipo'] === 'cualitativo'
                            ? ($kpi['valor_cualitativo'] ?: '-')
                            : formatKpiValor($kpi['valor_actual'], $kpi['es_entero']);
                        $metaDisplay = $kpi['tipo'] === 'cualitativo' ? '-' : formatKpiValor($kpi['meta'], $kpi['es_entero']);
                    ?>
                    <tr>
                        <td><span class="sem-dot-sm" style="background-color:<?= $sColor ?>;"></span></td>
                        <td><?= sanitize($kpi['nombre']) ?></td>
                        <td style="font-weight:600;"><?= $valorDisplay ?></td>
                        <td><?= $metaDisplay ?></td>
                        <td><?= sanitize($kpi['unidad'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if (!empty($iePdas)): ?>
            <div class="ie-section-title"><i class="bi bi-list-check"></i> Planes de Acción</div>
            <table class="informe-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Owner</th>
                        <th>Avance</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($iePdas as $pda):
                        $pdaAv = (int)$pda['avance'];
                        $pdaColor = $pdaAv >= 70 ? '#22c55e' : ($pdaAv >= 40 ? '#f59e0b' : '#ef4444');
                    ?>
                    <tr>
                        <td><?= sanitize($pda['nombre']) ?></td>
                        <td><?= sanitize($pda['owner'] ?? '-') ?></td>
                        <td>
                            <span class="inline-bar">
                                <span class="inline-bar-track"><span class="inline-bar-fill" style="width:<?= $pdaAv ?>%;background:<?= $pdaColor ?>;"></span></span>
                                <span class="inline-bar-text"><?= $pdaAv ?>%</span>
                            </span>
                        </td>
                        <td><?= estadoBadge($pda['estado']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if (!empty($ieRiesgos)): ?>
            <div class="ie-section-title"><i class="bi bi-exclamation-triangle"></i> Restricciones y Riesgos</div>
            <table class="informe-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Nivel</th>
                        <th>Descripción</th>
                        <th>Plan de Mitigación</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $nivelColors = ['critico' => '#ef4444', 'alto' => '#f59e0b', 'medio' => '#3b82f6', 'bajo' => '#22c55e'];
                    foreach ($ieRiesgos as $riesgo):
                        $nColor = $nivelColors[$riesgo['nivel']] ?? '#6b7280';
                    ?>
                    <tr>
                        <td><span class="nivel-badge" style="background-color:<?= $nColor ?>;"><?= ucfirst($riesgo['nivel']) ?></span></td>
                        <td><?= sanitize($riesgo['descripcion']) ?></td>
                        <td><?= sanitize($riesgo['plan_mitigacion'] ?? '-') ?></td>
                        <td><?= sanitize($riesgo['responsable'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- D) Matriz de Riesgos -->
    <div class="informe-section informe-matriz">
        <h3><i class="bi bi-grid-3x3 me-2"></i>Matriz de Riesgos</h3>
        <?php
        $probs = ['alta', 'media', 'baja'];
        $impactos = ['bajo', 'medio', 'alto'];
        $cellColors = [
            'alta' =>  ['bajo' => '#f59e0b', 'medio' => '#ef4444', 'alto' => '#dc2626'],
            'media' => ['bajo' => '#22c55e', 'medio' => '#f59e0b', 'alto' => '#ef4444'],
            'baja' =>  ['bajo' => '#22c55e', 'medio' => '#22c55e', 'alto' => '#f59e0b'],
        ];
        ?>
        <table class="matriz-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Impacto Bajo</th>
                    <th>Impacto Medio</th>
                    <th>Impacto Alto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($probs as $prob): ?>
                <tr>
                    <th style="text-align:right;padding-right:10px;">Prob. <?= ucfirst($prob) ?></th>
                    <?php foreach ($impactos as $imp):
                        $count = $matriz[$prob][$imp]['total'] ?? 0;
                        $bg = $cellColors[$prob][$imp];
                    ?>
                    <td class="matriz-cell" style="background-color:<?= $bg ?>;"><?= $count ?: '' ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- E) Proyectos -->
    <div class="informe-section">
        <h3><i class="bi bi-kanban me-2"></i>Proyectos</h3>
        <?php if (!empty($proyectos)): ?>
        <table class="informe-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Responsable</th>
                    <th>Avance</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proyectos as $proy):
                    $proyAvance = calcularAvanceProyecto($pdo, $proy['id']);
                    $sinAct = $proyAvance === -1;
                    $av = $sinAct ? 0 : $proyAvance;
                    $barColor = $sinAct ? '#9ca3af' : ($av >= 70 ? '#22c55e' : ($av >= 40 ? '#f59e0b' : '#ef4444'));
                ?>
                <tr>
                    <td style="font-weight:500;"><?= sanitize($proy['nombre']) ?></td>
                    <td><?= sanitize($proy['responsable'] ?? '-') ?></td>
                    <td>
                        <span class="inline-bar">
                            <span class="inline-bar-track"><span class="inline-bar-fill" style="width:<?= $av ?>%;background:<?= $barColor ?>;"></span></span>
                            <span class="inline-bar-text"><?= $sinAct ? 'S/A' : $av . '%' ?></span>
                        </span>
                    </td>
                    <td><?= estadoBadge($proy['estado']) ?></td>
                    <td><?= prioridadBadge($proy['prioridad']) ?></td>
                    <td><?= $proy['fecha_inicio'] ? formatDate($proy['fecha_inicio']) : '-' ?></td>
                    <td><?= $proy['fecha_fin'] ? formatDate($proy['fecha_fin']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#9ca3af;font-size:10px;">Sin proyectos registrados</p>
        <?php endif; ?>
    </div>

    <!-- F) Compromisos Pendientes -->
    <div class="informe-section">
        <h3><i class="bi bi-check2-square me-2"></i>Compromisos Pendientes</h3>
        <?php if (!empty($compromisos)): ?>
        <table class="informe-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Responsable</th>
                    <th>Fecha Límite</th>
                    <th>Estado</th>
                    <th>Reunión</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compromisos as $comp): ?>
                <tr>
                    <td><?= sanitize($comp['descripcion']) ?></td>
                    <td><?= sanitize($comp['responsable'] ?? '-') ?></td>
                    <td><?= $comp['fecha_limite'] ? formatDate($comp['fecha_limite']) : '-' ?></td>
                    <td><?= estadoBadge($comp['estado']) ?></td>
                    <td style="font-size:9px;color:#6b7280;"><?= sanitize($comp['reunion_titulo'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#9ca3af;font-size:10px;">No hay compromisos pendientes</p>
        <?php endif; ?>
    </div>

    <!-- Botón imprimir (no-print) -->
    <div class="text-center no-print" style="margin: 20px 0;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i>Imprimir / Guardar PDF
        </button>
        <a href="<?= BASE_URL ?>index.php?page=reportes" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
