<?php
$propuestas = $_SESSION['kpi_propuestas']['propuestas'] ?? [];
unset($_SESSION['kpi_propuestas']);

require_once __DIR__ . '/../layout/header.php';
?>

<?php if (empty($propuestas)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>La IA no encontro datos de KPIs en el documento subido.
</div>
<a href="<?= BASE_URL ?>index.php?page=kpis" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Volver a KPIs
</a>
<?php else: ?>

<?php
// Contar total de valores propuestos
$totalValores = 0;
foreach ($propuestas as $prop) {
    $totalValores += count($prop['valores'] ?? []);
}
?>

<div class="alert alert-info small">
    <i class="bi bi-robot me-1"></i>La IA encontro <strong><?= $totalValores ?> valores</strong> para <strong><?= count($propuestas) ?> KPIs</strong>. Revisa las propuestas y confirma las que quieras cargar.
</div>

<form method="POST" action="<?= BASE_URL ?>index.php?page=kpis&action=confirmar_ia">
    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" class="form-check-input" id="selectAll" checked>
                        </th>
                        <th>KPI</th>
                        <th>Valor Propuesto</th>
                        <th>Per&iacute;odo</th>
                        <th>Valor Actual</th>
                        <th>Confianza</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rowIdx = 0;
                    foreach ($propuestas as $prop):
                        $kpiId = (int)$prop['kpi_id'];
                        $kpiNombre = $prop['kpi_nombre'] ?? '';
                        $unidad = $prop['unidad'] ?? '';
                        $valorActual = $prop['valor_actual'] ?? null;
                        $esEntero = $prop['es_entero'] ?? false;
                        $valores = $prop['valores'] ?? [];
                        $isFirst = true;

                        foreach ($valores as $val):
                            $confianza = $val['confianza'] ?? 'media';
                            $confColors = ['alta' => 'badge-ok', 'media' => 'badge-warn', 'baja' => 'badge-bad'];
                            $confClass = $confColors[$confianza] ?? 'badge-neutral';
                            $checked = $confianza !== 'baja' ? 'checked' : '';
                    ?>
                    <tr class="<?= $isFirst ? '' : 'table-light' ?>">
                        <td>
                            <input type="checkbox" class="form-check-input row-check" name="valores[<?= $rowIdx ?>]" value="1" <?= $checked ?>>
                            <input type="hidden" name="data[<?= $rowIdx ?>][kpi_id]" value="<?= $kpiId ?>">
                            <input type="hidden" name="data[<?= $rowIdx ?>][valor]" value="<?= sanitize($val['valor'] ?? '') ?>">
                            <input type="hidden" name="data[<?= $rowIdx ?>][periodo]" value="<?= sanitize($val['periodo'] ?? '') ?>">
                            <input type="hidden" name="data[<?= $rowIdx ?>][observaciones]" value="<?= sanitize(($val['observaciones'] ?? '') . ' [IA - ' . $confianza . ']') ?>">
                        </td>
                        <td>
                            <?php if ($isFirst): ?>
                                <strong><?= sanitize($kpiNombre) ?></strong>
                                <?php if ($unidad): ?><small class="text-muted">(<?= sanitize($unidad) ?>)</small><?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">&nbsp;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= formatKpiValor($val['valor'] ?? null, $esEntero) ?></strong>
                            <?php if ($unidad): ?><small class="text-muted"><?= sanitize($unidad) ?></small><?php endif; ?>
                        </td>
                        <td><?= !empty($val['periodo']) ? formatDate($val['periodo']) : '-' ?></td>
                        <td>
                            <?php if ($isFirst && $valorActual !== null): ?>
                                <?= formatKpiValor($valorActual, $esEntero) ?>
                                <?php if ($unidad): ?><small class="text-muted"><?= sanitize($unidad) ?></small><?php endif; ?>
                            <?php else: ?>
                                <?= $isFirst ? '-' : '' ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $confClass ?>"><?= ucfirst($confianza) ?></span></td>
                        <td><small class="text-muted"><?= sanitize($val['observaciones'] ?? '') ?></small></td>
                    </tr>
                    <?php
                            $isFirst = false;
                            $rowIdx++;
                        endforeach;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?= BASE_URL ?>index.php?page=kpis" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-lg me-1"></i>Confirmar seleccionados
        </button>
    </div>
</form>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(function(cb) {
        cb.checked = this.checked;
    }.bind(this));
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
