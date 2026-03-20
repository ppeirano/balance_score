<?php
require_once __DIR__ . '/../../models/Iniciativa.php';

$tipoReporte = $_GET['tipo'] ?? null;

// Si se pide exportar CSV
if ($tipoReporte === 'csv_planes') {
    $stmt = $pdo->query("SELECT pa.codigo, pa.nombre, pa.owner, pa.avance, pa.estado, pa.prioridad, pa.peso,
                          ie.codigo as ie_codigo, ie.nombre as ie_nombre
                          FROM planes_accion pa
                          JOIN iniciativas_estrategicas ie ON pa.iniciativa_id = ie.id
                          ORDER BY ie.codigo, pa.codigo");
    $planes = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="planes_accion_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($output, ['IE', 'Código', 'Nombre', 'Owner', 'Avance %', 'Estado', 'Prioridad', 'Peso %']);
    foreach ($planes as $p) {
        fputcsv($output, [$p['ie_codigo'], $p['codigo'], $p['nombre'], $p['owner'], $p['avance'], $p['estado'], $p['prioridad'], $p['peso']]);
    }
    fclose($output);
    exit;
}

if ($tipoReporte === 'csv_kpis') {
    $stmt = $pdo->query("SELECT k.nombre, k.tipo, k.unidad, k.meta, k.valor_actual, k.frecuencia,
                          ie.codigo as ie_codigo
                          FROM kpis k
                          LEFT JOIN iniciativas_estrategicas ie ON k.iniciativa_id = ie.id
                          WHERE k.activo = 1 ORDER BY ie.codigo");
    $kpis = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kpis_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['IE', 'Nombre', 'Tipo', 'Unidad', 'Meta', 'Valor Actual', 'Frecuencia']);
    foreach ($kpis as $k) {
        fputcsv($output, [$k['ie_codigo'], $k['nombre'], $k['tipo'], $k['unidad'], $k['meta'], $k['valor_actual'], $k['frecuencia']]);
    }
    fclose($output);
    exit;
}

if ($tipoReporte === 'csv_actividades') {
    $stmt = $pdo->query("SELECT a.codigo, a.descripcion, a.responsable, a.estado, a.fecha_limite,
                          pa.codigo as plan_codigo, pa.nombre as plan_nombre,
                          ie.codigo as ie_codigo
                          FROM actividades a
                          JOIN planes_accion pa ON a.plan_accion_id = pa.id
                          JOIN iniciativas_estrategicas ie ON pa.iniciativa_id = ie.id
                          ORDER BY ie.codigo, pa.codigo, a.codigo");
    $actividades = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="actividades_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['IE', 'PDA', 'Código', 'Descripción', 'Responsable', 'Estado', 'Fecha Límite']);
    foreach ($actividades as $a) {
        fputcsv($output, [$a['ie_codigo'], $a['plan_codigo'], $a['codigo'], $a['descripcion'], $a['responsable'], $a['estado'], $a['fecha_limite']]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../layout/header.php';
?>

<h4 class="mb-4"><i class="bi bi-file-earmark-pdf me-2"></i>Reportes y Exportación</h4>

<div class="row">
    <!-- Exportar CSV -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-filetype-csv me-2"></i>Planes de Acción</h5>
                <p class="card-text text-muted">Exportar listado completo de PDAs con estado y avance.</p>
                <a href="<?= BASE_URL ?>index.php?page=reportes&tipo=csv_planes" class="btn btn-success">
                    <i class="bi bi-download me-1"></i>Descargar CSV
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-filetype-csv me-2"></i>KPIs</h5>
                <p class="card-text text-muted">Exportar indicadores con metas y valores actuales.</p>
                <a href="<?= BASE_URL ?>index.php?page=reportes&tipo=csv_kpis" class="btn btn-success">
                    <i class="bi bi-download me-1"></i>Descargar CSV
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-filetype-csv me-2"></i>Actividades</h5>
                <p class="card-text text-muted">Exportar todas las actividades con estado y responsable.</p>
                <a href="<?= BASE_URL ?>index.php?page=reportes&tipo=csv_actividades" class="btn btn-success">
                    <i class="bi bi-download me-1"></i>Descargar CSV
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Imprimir Dashboard -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><i class="bi bi-printer me-2"></i>Imprimir Reporte</h5>
        <p class="text-muted">Podés imprimir cualquier página de la aplicación usando Ctrl+P. El layout se optimiza automáticamente para impresión (se ocultan menú y botones).</p>
        <a href="<?= BASE_URL ?>index.php?page=dashboard" onclick="setTimeout(()=>window.print(), 500)" class="btn btn-outline-primary">
            <i class="bi bi-printer me-1"></i>Imprimir Dashboard
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
