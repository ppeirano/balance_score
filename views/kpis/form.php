<?php
require_once __DIR__ . '/../../models/Kpi.php';
require_once __DIR__ . '/../../models/Iniciativa.php';
require_once __DIR__ . '/../../models/PlanAccion.php';
require_once __DIR__ . '/../../models/Responsable.php';

$kpi = null;
if ($id) {
    $kpi = Kpi::getById($pdo, $id);
}
$iniciativas = Iniciativa::getAll($pdo);
$planes = PlanAccion::getAll($pdo);
$responsables = Responsable::getAll($pdo);

require_once __DIR__ . '/../layout/header.php';
$esEditar = ($kpi !== null);
$tipo = $kpi['tipo'] ?? 'cuantitativo';
?>

<h4 class="mb-4">
    <i class="bi bi-graph-up me-2"></i><?= $esEditar ? 'Editar' : 'Nuevo' ?> KPI
</h4>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>index.php?page=kpis&action=guardar">
            <?php if ($esEditar): ?>
                <input type="hidden" name="id" value="<?= (int)$kpi['id'] ?>">
            <?php endif; ?>
            <?php
                $filtroIE = $_GET['filtro_ie'] ?? '';
                $filtroTipo = $_GET['filtro_tipo'] ?? '';
            ?>
            <?php if ($filtroIE): ?><input type="hidden" name="filtro_ie" value="<?= (int)$filtroIE ?>"><?php endif; ?>
            <?php if ($filtroTipo): ?><input type="hidden" name="filtro_tipo" value="<?= sanitize($filtroTipo) ?>"><?php endif; ?>
            <?php if ($periodoActivo): ?>
                <input type="hidden" name="periodo_id" value="<?= (int)$periodoActivo['id'] ?>">
            <?php endif; ?>

            <!-- Nombre -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre del KPI *</label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?= sanitize($kpi['nombre'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Iniciativa Estrat&eacute;gica</label>
                    <select class="form-select" name="iniciativa_id" id="iniciativa_id">
                        <option value="">Sin asignar</option>
                        <?php foreach ($iniciativas as $ie): ?>
                            <option value="<?= (int)$ie['id'] ?>" <?= ($kpi && $kpi['iniciativa_id'] == $ie['id']) ? 'selected' : '' ?>>
                                <?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Plan de Acci&oacute;n</label>
                    <select class="form-select" name="plan_accion_id" id="plan_accion_id">
                        <option value="">Ninguno</option>
                        <?php foreach ($planes as $pa): ?>
                            <option value="<?= (int)$pa['id'] ?>"
                                    data-ie="<?= (int)$pa['iniciativa_id'] ?>"
                                    <?= ($kpi && $kpi['plan_accion_id'] == $pa['id']) ? 'selected' : '' ?>>
                                <?= sanitize($pa['codigo'] . ' - ' . $pa['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Responsable -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Responsable</label>
                    <select class="form-select" name="responsable">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($responsables as $resp): ?>
                            <option value="<?= sanitize($resp['nombre']) ?>"
                                <?= ($kpi && ($kpi['responsable'] ?? '') === $resp['nombre']) ? 'selected' : '' ?>>
                                <?= sanitize($resp['nombre']) ?><?= $resp['cargo'] ? ' (' . sanitize($resp['cargo']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Tipo -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de KPI *</label>
                    <div class="d-flex gap-4 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipoCuantitativo"
                                   value="cuantitativo" <?= $tipo === 'cuantitativo' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tipoCuantitativo">Cuantitativo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" id="tipoCualitativo"
                                   value="cualitativo" <?= $tipo === 'cualitativo' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tipoCualitativo">Cualitativo</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Frecuencia</label>
                    <select class="form-select" name="frecuencia">
                        <option value="mensual" <?= ($kpi['frecuencia'] ?? 'mensual') === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        <option value="trimestral" <?= ($kpi['frecuencia'] ?? '') === 'trimestral' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="anual" <?= ($kpi['frecuencia'] ?? '') === 'anual' ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
            </div>

            <!-- Campos Cuantitativos -->
            <div id="camposCuantitativos" class="<?= $tipo === 'cualitativo' ? 'd-none' : '' ?>">
                <hr>
                <h6 class="text-muted mb-3"><i class="bi bi-123 me-1"></i>Configuraci&oacute;n Cuantitativa</h6>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Unidad de medida</label>
                        <select class="form-select" name="unidad">
                            <option value="">Sin unidad</option>
                            <option value="%" <?= ($kpi['unidad'] ?? '') === '%' ? 'selected' : '' ?>>%</option>
                            <option value="USD" <?= ($kpi['unidad'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="ARS" <?= ($kpi['unidad'] ?? '') === 'ARS' ? 'selected' : '' ?>>ARS</option>
                            <option value="unidades" <?= ($kpi['unidad'] ?? '') === 'unidades' ? 'selected' : '' ?>>unidades</option>
                            <option value="índice" <?= ($kpi['unidad'] ?? '') === 'índice' ? 'selected' : '' ?>>índice</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Meta</label>
                        <input type="number" class="form-control" name="meta" step="any"
                               value="<?= sanitize($kpi['meta'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Umbral Verde (%)</label>
                        <input type="number" class="form-control" name="umbral_verde" step="1" min="0" max="100"
                               value="<?= sanitize($kpi['umbral_verde'] ?? '90') ?>">
                        <small class="text-muted">&ge; este % = verde</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Umbral Amarillo (%)</label>
                        <input type="number" class="form-control" name="umbral_amarillo" step="1" min="0" max="100"
                               value="<?= sanitize($kpi['umbral_amarillo'] ?? '70') ?>">
                        <small class="text-muted">&ge; este % = amarillo</small>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Direcci&oacute;n</label>
                        <select class="form-select" name="direccion">
                            <option value="mayor_mejor" <?= ($kpi['direccion'] ?? 'mayor_mejor') === 'mayor_mejor' ? 'selected' : '' ?>>Mayor es mejor</option>
                            <option value="menor_mejor" <?= ($kpi['direccion'] ?? '') === 'menor_mejor' ? 'selected' : '' ?>>Menor es mejor</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="es_entero" id="esEntero" value="1"
                                   <?= ($kpi['es_entero'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="esEntero">Valor entero</label>
                            <div class="form-text">Sin decimales (ej: unidades, cantidad)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campos Cualitativos -->
            <div id="camposCualitativos" class="<?= $tipo === 'cuantitativo' ? 'd-none' : '' ?>">
                <hr>
                <h6 class="text-muted mb-3"><i class="bi bi-list-ul me-1"></i>Configuraci&oacute;n Cualitativa</h6>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Escala Cualitativa</label>
                        <select class="form-select" name="escala_cualitativa" id="escala_cualitativa">
                            <option value="cumple_no_cumple" <?= ($kpi['escala_cualitativa'] ?? '') === 'cumple_no_cumple' ? 'selected' : '' ?>>Cumple / No cumple</option>
                            <option value="alto_medio_bajo" <?= ($kpi['escala_cualitativa'] ?? '') === 'alto_medio_bajo' ? 'selected' : '' ?>>Alto / Medio / Bajo</option>
                            <option value="custom" <?= ($kpi['escala_cualitativa'] ?? '') === 'custom' ? 'selected' : '' ?>>Personalizada</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Opciones Cualitativas</label>
                        <input type="text" class="form-control" name="opciones_cualitativas" id="opciones_cualitativas"
                               value="<?= sanitize($kpi['opciones_cualitativas'] ?? '') ?>"
                               placeholder="Opciones separadas por coma (ej: Excelente,Bueno,Regular,Deficiente)">
                        <small class="text-muted">Para escala personalizada. La primera opci&oacute;n = verde, segunda = amarillo, resto = rojo.</small>
                    </div>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="<?= BASE_URL ?>index.php?page=kpis" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoCuantitativo = document.getElementById('tipoCuantitativo');
    const tipoCualitativo = document.getElementById('tipoCualitativo');
    const camposCuantitativos = document.getElementById('camposCuantitativos');
    const camposCualitativos = document.getElementById('camposCualitativos');

    function toggleTipo() {
        if (tipoCuantitativo.checked) {
            camposCuantitativos.classList.remove('d-none');
            camposCualitativos.classList.add('d-none');
        } else {
            camposCuantitativos.classList.add('d-none');
            camposCualitativos.classList.remove('d-none');
        }
    }

    tipoCuantitativo.addEventListener('change', toggleTipo);
    tipoCualitativo.addEventListener('change', toggleTipo);

    // Toggle meta step based on es_entero checkbox
    const esEntero = document.getElementById('esEntero');
    const metaInput = document.querySelector('input[name="meta"]');
    function toggleMetaStep() {
        metaInput.step = esEntero.checked ? '1' : 'any';
    }
    esEntero.addEventListener('change', toggleMetaStep);
    toggleMetaStep();

    // Filter planes by selected iniciativa
    const iniciativaSelect = document.getElementById('iniciativa_id');
    const planSelect = document.getElementById('plan_accion_id');
    const allPlanes = Array.from(planSelect.options);

    iniciativaSelect.addEventListener('change', function() {
        const ieId = this.value;
        planSelect.innerHTML = '';
        allPlanes.forEach(function(opt) {
            if (!opt.value || !ieId || opt.dataset.ie === ieId) {
                planSelect.appendChild(opt.cloneNode(true));
            }
        });
        planSelect.value = '';
    });

    // Escala cualitativa presets
    const escalaSelect = document.getElementById('escala_cualitativa');
    const opcionesInput = document.getElementById('opciones_cualitativas');

    escalaSelect.addEventListener('change', function() {
        if (this.value === 'cumple_no_cumple') {
            opcionesInput.value = 'Cumple,No cumple';
            opcionesInput.readOnly = true;
        } else if (this.value === 'alto_medio_bajo') {
            opcionesInput.value = 'Alto,Medio,Bajo';
            opcionesInput.readOnly = true;
        } else {
            opcionesInput.value = '';
            opcionesInput.readOnly = false;
            opcionesInput.focus();
        }
    });

    // Set initial state for escala presets
    if (escalaSelect.value === 'cumple_no_cumple' && !opcionesInput.value) {
        opcionesInput.value = 'Cumple,No cumple';
        opcionesInput.readOnly = true;
    } else if (escalaSelect.value === 'alto_medio_bajo' && !opcionesInput.value) {
        opcionesInput.value = 'Alto,Medio,Bajo';
        opcionesInput.readOnly = true;
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
