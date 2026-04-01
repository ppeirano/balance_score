<?php
require_once __DIR__ . '/../../models/Seguimiento.php';

// Obtener planes, actividades y hitos para los dropdowns del modal
$planes = $pdo->query("
    SELECT pa.id, pa.codigo, pa.nombre, ie.codigo AS ie_codigo
    FROM planes_accion pa
    JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
    ORDER BY CAST(SUBSTRING(ie.codigo, 3) AS UNSIGNED), pa.prioridad
")->fetchAll();

$actividades = $pdo->query("
    SELECT a.id, a.codigo, a.descripcion, pa.codigo AS plan_codigo
    FROM actividades a
    JOIN planes_accion pa ON pa.id = a.plan_accion_id
    ORDER BY pa.codigo, a.codigo
")->fetchAll();

$hitos = $pdo->query("
    SELECT h.id, h.nombre, pa.codigo AS plan_codigo
    FROM hitos h
    JOIN planes_accion pa ON pa.id = h.plan_accion_id
    ORDER BY pa.codigo, h.fecha_prevista
")->fetchAll();

// Obtener próximos seguimientos (no completados, desde hoy)
$proximosSeguimientos = $pdo->query("
    SELECT s.*,
        CASE s.entidad_tipo
            WHEN 'plan' THEN CONCAT(pa.codigo, ' - ', pa.nombre)
            WHEN 'actividad' THEN CONCAT('Act. ', a.codigo, ' - ', a.descripcion)
            WHEN 'hito' THEN h.nombre
        END AS entidad_nombre
    FROM seguimientos s
    LEFT JOIN planes_accion pa ON s.entidad_tipo = 'plan' AND s.entidad_id = pa.id
    LEFT JOIN actividades a ON s.entidad_tipo = 'actividad' AND s.entidad_id = a.id
    LEFT JOIN hitos h ON s.entidad_tipo = 'hito' AND s.entidad_id = h.id
    WHERE s.fecha >= CURDATE() AND s.completado = 0
    ORDER BY s.fecha ASC, s.hora ASC
    LIMIT 20
")->fetchAll();

$pageTitle = 'Calendario de Seguimientos';
require_once __DIR__ . '/../layout/header.php';
?>

<?php if (isAdmin()): ?>
<div class="d-flex justify-content-end align-items-center mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSeguimiento" onclick="nuevoSeguimiento()">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Seguimiento
    </button>
</div>
<?php endif; ?>

<!-- Leyenda -->
<div class="d-flex gap-3 mb-3">
    <span><span class="badge" style="background-color:#0d6efd">&nbsp;&nbsp;</span> Plan de Acción</span>
    <span><span class="badge" style="background-color:#198754">&nbsp;&nbsp;</span> Actividad</span>
    <span><span class="badge" style="background-color:#fd7e14">&nbsp;&nbsp;</span> Hito</span>
    <span><span class="badge-neutral">&nbsp;&nbsp;</span> Completado</span>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendario"></div>
    </div>
</div>

<!-- Próximos Eventos -->
<div class="card mt-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Próximos Eventos</h5>
        <span class="badge-neutral"><?= count($proximosSeguimientos) ?> pendiente(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($proximosSeguimientos)): ?>
            <div class="alert alert-info mb-0 m-3">No hay seguimientos próximos pendientes.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Asociado a</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $hoy = date('Y-m-d');
                        $tipoClases = ['plan' => 'badge-info', 'actividad' => 'badge-ok', 'hito' => 'badge-warn'];
                        $tipoLabels = ['plan' => 'Plan', 'actividad' => 'Actividad', 'hito' => 'Hito'];
                        foreach ($proximosSeguimientos as $seg):
                            $esHoy = ($seg['fecha'] === $hoy);
                            $esMañana = ($seg['fecha'] === date('Y-m-d', strtotime('+1 day')));
                        ?>
                            <tr class="<?= $esHoy ? 'table-warning' : '' ?>">
                                <td>
                                    <?php if ($esHoy): ?>
                                        <span class="badge-warn">Hoy</span>
                                    <?php elseif ($esMañana): ?>
                                        <span class="badge-info">Mañana</span>
                                    <?php else: ?>
                                        <?= date('d/m/Y', strtotime($seg['fecha'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $seg['hora'] ? date('H:i', strtotime($seg['hora'])) : '<span class="text-muted">-</span>' ?></td>
                                <td><strong><?= sanitize($seg['titulo']) ?></strong></td>
                                <td>
                                    <span class="<?= $tipoClases[$seg['entidad_tipo']] ?? 'badge-neutral' ?>">
                                        <?= $tipoLabels[$seg['entidad_tipo']] ?? $seg['entidad_tipo'] ?>
                                    </span>
                                </td>
                                <td><?= sanitize($seg['entidad_nombre'] ?? '-') ?></td>
                                <td class="text-muted"><?= sanitize(mb_strimwidth($seg['descripcion'] ?? '-', 0, 60, '...')) ?></td>
                                <td>
                                    <?php if (isAdmin()): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" title="Marcar como completado"
                                            onclick="completarSeguimiento(<?= $seg['id'] ?>)">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Form oculto para completar seguimiento -->
<form id="formCompletar" method="POST" style="display:none;">
    <!-- action se setea dinámicamente via JS -->
</form>

<!-- Modal Crear/Editar Seguimiento -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>index.php?page=calendario&action=guardar">
                <input type="hidden" name="id" id="seg_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Seguimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="seg_titulo" class="form-label">Titulo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="seg_titulo" name="titulo" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="seg_entidad_tipo" class="form-label">Asociar a <span class="text-danger">*</span></label>
                            <select class="form-select" id="seg_entidad_tipo" name="entidad_tipo" required onchange="cambiarEntidad()">
                                <option value="plan">Plan de Acción</option>
                                <option value="actividad">Actividad</option>
                                <option value="hito">Hito</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="seg_entidad_id" class="form-label">Entidad <span class="text-danger">*</span></label>
                            <select class="form-select" id="seg_entidad_id" name="entidad_id" required>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="seg_fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="seg_fecha" name="fecha" required>
                        </div>
                        <div class="col-md-6">
                            <label for="seg_hora" class="form-label">Hora</label>
                            <input type="time" class="form-control" id="seg_hora" name="hora">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="seg_descripcion" class="form-label">Descripcion</label>
                        <textarea class="form-control" id="seg_descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="seg_completado" name="completado" value="1">
                        <label class="form-check-label" for="seg_completado">Completado</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalleTitulo"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Asociado a:</strong> <span id="detalleEntidad"></span></p>
                <p><strong>Fecha:</strong> <span id="detalleFecha"></span></p>
                <p><strong>Hora:</strong> <span id="detalleHora"></span></p>
                <p><strong>Descripcion:</strong></p>
                <p id="detalleDescripcion" class="text-muted"></p>
                <p><strong>Estado:</strong> <span id="detalleEstado"></span></p>
            </div>
            <div class="modal-footer">
                <?php if (isAdmin()): ?>
                <form method="POST" id="formEliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este seguimiento?');">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                </form>
                <button type="button" class="btn btn-primary" id="btnEditar">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Datos de entidades para los dropdowns
const entidades = {
    plan: <?= json_encode(array_map(function($p) {
        return ['id' => $p['id'], 'label' => $p['ie_codigo'] . ' / ' . $p['codigo'] . ' - ' . $p['nombre']];
    }, $planes)) ?>,
    actividad: <?= json_encode(array_map(function($a) {
        return ['id' => $a['id'], 'label' => $a['plan_codigo'] . ' / ' . $a['codigo'] . ' - ' . $a['descripcion']];
    }, $actividades)) ?>,
    hito: <?= json_encode(array_map(function($h) {
        return ['id' => $h['id'], 'label' => $h['plan_codigo'] . ' - ' . $h['nombre']];
    }, $hitos)) ?>
};

const tipoLabels = { plan: 'Plan de Acción', actividad: 'Actividad', hito: 'Hito' };

function completarSeguimiento(id) {
    if (!confirm('¿Marcar este seguimiento como completado?')) return;
    const form = document.getElementById('formCompletar');
    form.action = '<?= BASE_URL ?>index.php?page=calendario&action=completar&id=' + id;
    form.submit();
}

function cambiarEntidad() {
    const tipo = document.getElementById('seg_entidad_tipo').value;
    const select = document.getElementById('seg_entidad_id');
    select.innerHTML = '';
    (entidades[tipo] || []).forEach(function(e) {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = e.label;
        select.appendChild(opt);
    });
}

function nuevoSeguimiento(fecha) {
    document.getElementById('seg_id').value = '';
    document.getElementById('seg_titulo').value = '';
    document.getElementById('seg_descripcion').value = '';
    document.getElementById('seg_fecha').value = fecha || '';
    document.getElementById('seg_hora').value = '';
    document.getElementById('seg_completado').checked = false;
    document.getElementById('seg_entidad_tipo').value = 'plan';
    document.getElementById('modalTitle').textContent = 'Nuevo Seguimiento';
    cambiarEntidad();
}

// Variable para almacenar el evento seleccionado actualmente
let eventoActual = null;

// Auto-abrir modal si vienen parámetros por URL
function autoAbrirDesdeURL() {
    const params = new URLSearchParams(window.location.search);
    const tipo = params.get('tipo');
    const entidadId = params.get('entidad_id');
    const nombre = params.get('nombre');
    if (tipo && entidadId) {
        document.getElementById('seg_entidad_tipo').value = tipo;
        cambiarEntidad();
        document.getElementById('seg_entidad_id').value = entidadId;
        document.getElementById('seg_titulo').value = 'Seguimiento: ' + (nombre || '');
        document.getElementById('seg_fecha').value = '';
        document.getElementById('seg_hora').value = '';
        document.getElementById('seg_descripcion').value = '';
        document.getElementById('seg_completado').checked = false;
        document.getElementById('seg_id').value = '';
        document.getElementById('modalTitle').textContent = 'Nuevo Seguimiento';
        new bootstrap.Modal(document.getElementById('modalSeguimiento')).show();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const calEl = document.getElementById('calendario');
    const calendar = new FullCalendar.Calendar(calEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            list: 'Lista'
        },
        events: '<?= BASE_URL ?>index.php?page=calendario&action=json',
        dateClick: function(info) {
            nuevoSeguimiento(info.dateStr);
            new bootstrap.Modal(document.getElementById('modalSeguimiento')).show();
        },
        eventClick: function(info) {
            const ev = info.event;
            const props = ev.extendedProps;

            eventoActual = {
                id: ev.id,
                titulo: ev.title,
                entidad_tipo: props.entidad_tipo,
                entidad_id: props.entidad_id,
                fecha: ev.startStr.substring(0, 10),
                hora: props.hora || '',
                descripcion: props.descripcion || '',
                completado: props.completado
            };

            // Detalle modal
            document.getElementById('detalleTitulo').textContent = ev.title;
            document.getElementById('detalleEntidad').textContent =
                tipoLabels[props.entidad_tipo] + ': ' + (props.entidad_nombre || '');
            document.getElementById('detalleFecha').textContent = ev.startStr.substring(0, 10);
            document.getElementById('detalleHora').textContent = props.hora || 'Sin hora';
            document.getElementById('detalleDescripcion').textContent = props.descripcion || 'Sin descripción';
            document.getElementById('detalleEstado').innerHTML = props.completado
                ? '<span class="badge-ok">Completado</span>'
                : '<span class="badge-warn">Pendiente</span>';

            document.getElementById('formEliminar').action =
                '<?= BASE_URL ?>index.php?page=calendario&action=eliminar&id=' + ev.id;

            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        }
    });
    calendar.render();

    // Auto-abrir modal si hay parámetros en la URL
    autoAbrirDesdeURL();

    // Botón editar en detalle modal
    document.getElementById('btnEditar').addEventListener('click', function() {
        bootstrap.Modal.getInstance(document.getElementById('modalDetalle')).hide();

        if (eventoActual) {
            document.getElementById('seg_id').value = eventoActual.id;
            document.getElementById('seg_titulo').value = eventoActual.titulo;
            document.getElementById('seg_entidad_tipo').value = eventoActual.entidad_tipo;
            cambiarEntidad();
            document.getElementById('seg_entidad_id').value = eventoActual.entidad_id;
            document.getElementById('seg_fecha').value = eventoActual.fecha;
            document.getElementById('seg_hora').value = eventoActual.hora;
            document.getElementById('seg_descripcion').value = eventoActual.descripcion;
            document.getElementById('seg_completado').checked = eventoActual.completado;
            document.getElementById('modalTitle').textContent = 'Editar Seguimiento';
        }

        setTimeout(function() {
            new bootstrap.Modal(document.getElementById('modalSeguimiento')).show();
        }, 300);
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
