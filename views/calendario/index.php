<?php
require_once __DIR__ . '/../../models/Seguimiento.php';

// Obtener planes, actividades y hitos para los dropdowns del modal
$planes = $pdo->query("
    SELECT pa.id, pa.codigo, pa.nombre, ie.codigo AS ie_codigo
    FROM planes_accion pa
    JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
    ORDER BY ie.codigo, pa.prioridad
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

$pageTitle = 'Calendario de Seguimientos';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-calendar-event me-2"></i>Calendario de Seguimientos</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSeguimiento" onclick="nuevoSeguimiento()">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Seguimiento
    </button>
</div>

<!-- Leyenda -->
<div class="d-flex gap-3 mb-3">
    <span><span class="badge" style="background-color:#0d6efd">&nbsp;&nbsp;</span> Plan de Acción</span>
    <span><span class="badge" style="background-color:#198754">&nbsp;&nbsp;</span> Actividad</span>
    <span><span class="badge" style="background-color:#fd7e14">&nbsp;&nbsp;</span> Hito</span>
    <span><span class="badge bg-secondary">&nbsp;&nbsp;</span> Completado</span>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendario"></div>
    </div>
</div>

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
                <form method="POST" id="formEliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este seguimiento?');">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                </form>
                <button type="button" class="btn btn-primary" id="btnEditar">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
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
                ? '<span class="badge bg-success">Completado</span>'
                : '<span class="badge bg-warning text-dark">Pendiente</span>';

            document.getElementById('formEliminar').action =
                '<?= BASE_URL ?>index.php?page=calendario&action=eliminar&id=' + ev.id;

            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        }
    });
    calendar.render();

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
