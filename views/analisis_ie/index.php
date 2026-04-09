<?php
require_once __DIR__ . '/../../models/AnalisisNodo.php';
require_once __DIR__ . '/../../models/AnalisisConexion.php';

$nodos = AnalisisNodo::getAll($pdo);
$conexiones = AnalisisConexion::getAll($pdo);

require_once __DIR__ . '/../layout/header.php';

$tipoDefaults = [
    'iniciativa'  => ['forma' => 'box',      'color' => '#4A90D9'],
    'plan'        => ['forma' => 'ellipse',   'color' => '#7B68EE'],
    'habilitador' => ['forma' => 'diamond',   'color' => '#50C878'],
    'riesgo'      => ['forma' => 'triangle',  'color' => '#FF6B6B'],
    'restriccion' => ['forma' => 'hexagon',   'color' => '#FFA500'],
    'kpi'         => ['forma' => 'circle',    'color' => '#20B2AA'],
];

$relacionLabels = [
    'habilita'   => ['label' => 'Habilita',    'color' => '#50C878'],
    'depende_de' => ['label' => 'Depende de',   'color' => '#4A90D9'],
    'bloquea'    => ['label' => 'Bloquea',      'color' => '#FF6B6B'],
    'genera'     => ['label' => 'Genera',       'color' => '#FFA500'],
    'mitiga'     => ['label' => 'Mitiga',       'color' => '#7B68EE'],
];
?>

<style>
#grafo-container {
    width: 100%;
    height: 70vh;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fafbfc;
    position: relative;
}
.modo-badge { font-size: 11px; padding: 4px 10px; border-radius: 12px; }
.leyenda-item { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; margin-right: 12px; }
.leyenda-dot { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
.forma-option { cursor: pointer; padding: 6px 10px; border: 2px solid transparent; border-radius: 6px; text-align: center; transition: all 0.15s; }
.forma-option:hover, .forma-option.selected { border-color: #0d6efd; background: #e8f0fe; }
.color-option { width: 28px; height: 28px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; transition: all 0.15s; display: inline-block; }
.color-option:hover, .color-option.selected { border-color: #333; transform: scale(1.15); }
.presentacion-bar {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: rgba(255,255,255,0.95); border-top: 1px solid #dee2e6;
    padding: 10px 20px; display: flex; align-items: center; justify-content: center; gap: 16px;
    border-radius: 0 0 8px 8px; z-index: 10;
}
.info-panel {
    position: absolute; top: 10px; right: 10px; width: 280px;
    background: #fff; border: 1px solid #dee2e6; border-radius: 8px;
    padding: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); z-index: 10;
    display: none;
}
</style>

<?php mostrarFlash(); ?>

<!-- Toolbar -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="btnToggleSidebar" onclick="document.body.classList.toggle('sidebar-hidden');" title="Ocultar/Mostrar menú lateral">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
        <div class="btn-group" role="group">
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomIn()" title="Acercar">
                <i class="bi bi-zoom-in"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomOut()" title="Alejar">
                <i class="bi bi-zoom-out"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="network.fit({animation:true})" title="Ajustar a pantalla">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="exportarImagen()" title="Exportar como imagen">
                <i class="bi bi-image"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="reordenarGrafo()" title="Reordenar nodos automáticamente">
                <i class="bi bi-grid-3x3-gap"></i>
            </button>
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-primary active" id="btnModoDiseno" onclick="setModo('diseno')">
                <i class="bi bi-pencil-square me-1"></i>Diseño
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnModoPresentacion" onclick="setModo('presentacion')">
                <i class="bi bi-easel me-1"></i>Presentación
            </button>
        </div>
        <div id="subModoPresentacion" class="btn-group d-none" role="group">
            <button type="button" class="btn btn-sm btn-outline-secondary active" id="btnCompleta" onclick="setSubModo('completa')">Completa</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAnimada" onclick="setSubModo('animada')">Animada</button>
        </div>
    </div>
    <div id="toolbarDiseno" class="d-flex gap-2">
        <?php if (isAdmin()): ?>
        <button class="btn btn-sm btn-primary" onclick="abrirModalNodo()">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Elemento
        </button>
        <button class="btn btn-sm btn-outline-primary" onclick="abrirModalConexion()" <?= empty($nodos) ? 'disabled' : '' ?>>
            <i class="bi bi-arrow-right-circle me-1"></i>Nueva Conexión
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Leyenda -->
<div class="mb-2">
    <?php foreach ($tipoDefaults as $tipo => $def): ?>
    <span class="leyenda-item">
        <span class="leyenda-dot" style="background:<?= $def['color'] ?>;"></span>
        <?= ucfirst($tipo) ?>
    </span>
    <?php endforeach; ?>
    <span class="text-muted mx-2">|</span>
    <?php foreach ($relacionLabels as $key => $rel): ?>
    <span class="leyenda-item">
        <span style="color:<?= $rel['color'] ?>; font-weight:bold;">→</span>
        <?= $rel['label'] ?>
    </span>
    <?php endforeach; ?>
</div>

<!-- Grafo -->
<div style="position:relative;">
    <div id="grafo-container"></div>

    <!-- Panel info (al seleccionar nodo) -->
    <div class="info-panel" id="infoPanel">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0" id="infoNombre"></h6>
            <button class="btn-close btn-sm" onclick="cerrarInfo()"></button>
        </div>
        <span class="badge mb-2" id="infoTipo"></span>
        <p class="small text-muted mb-1" id="infoDesc"></p>
        <p class="small mb-1"><strong>Estado:</strong> <span id="infoEstado"></span></p>
        <p class="small mb-2" id="infoObsWrap"><strong>Obs:</strong> <span id="infoObs"></span></p>
        <?php if (isAdmin()): ?>
        <div class="d-flex gap-2 mt-2" id="infoBotones">
            <button class="btn btn-sm btn-outline-primary" onclick="editarNodoSeleccionado()">
                <i class="bi bi-pencil me-1"></i>Editar
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="eliminarNodoSeleccionado()">
                <i class="bi bi-trash me-1"></i>Eliminar
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Barra de presentación animada -->
    <div class="presentacion-bar d-none" id="barraAnimacion">
        <button class="btn btn-sm btn-outline-secondary" onclick="pasoAnterior()" id="btnAnterior" disabled>
            <i class="bi bi-chevron-left"></i>
        </button>
        <span class="fw-bold" id="pasoIndicador">Paso 0 / 0</span>
        <button class="btn btn-sm btn-primary" onclick="pasoSiguiente()" id="btnSiguiente">
            <i class="bi bi-chevron-right"></i> Siguiente
        </button>
        <button class="btn btn-sm btn-outline-dark ms-3" onclick="mostrarTodo()">
            Mostrar todo
        </button>
    </div>
</div>

<!-- Modal: Nodo -->
<div class="modal fade" id="modalNodo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>index.php?page=analisis_ie&action=guardar_nodo">
                <input type="hidden" name="id" id="nodoId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNodoTitulo">Nuevo Elemento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="nodoNombre" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo" id="nodoTipo" required onchange="sugerirDefaults()">
                                <option value="">Seleccionar...</option>
                                <option value="iniciativa">Iniciativa</option>
                                <option value="plan">Plan</option>
                                <option value="habilitador">Habilitador</option>
                                <option value="riesgo">Riesgo</option>
                                <option value="restriccion">Restricción</option>
                                <option value="kpi">KPI</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado" id="nodoEstado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="critico">Crítico</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Orden</label>
                            <input type="number" class="form-control" name="orden" id="nodoOrden" value="0" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="nodoDescripcion" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" id="nodoObservaciones" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Forma</label>
                        <input type="hidden" name="forma" id="nodoForma" value="box">
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="forma-option selected" data-forma="box" onclick="selForma('box')">
                                <i class="bi bi-square-fill"></i><br><small>Box</small>
                            </div>
                            <div class="forma-option" data-forma="ellipse" onclick="selForma('ellipse')">
                                <i class="bi bi-circle-fill"></i><br><small>Ellipse</small>
                            </div>
                            <div class="forma-option" data-forma="diamond" onclick="selForma('diamond')">
                                <i class="bi bi-diamond-fill"></i><br><small>Diamond</small>
                            </div>
                            <div class="forma-option" data-forma="triangle" onclick="selForma('triangle')">
                                <i class="bi bi-triangle-fill"></i><br><small>Triangle</small>
                            </div>
                            <div class="forma-option" data-forma="star" onclick="selForma('star')">
                                <i class="bi bi-star-fill"></i><br><small>Star</small>
                            </div>
                            <div class="forma-option" data-forma="hexagon" onclick="selForma('hexagon')">
                                <i class="bi bi-hexagon-fill"></i><br><small>Hexagon</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="hidden" name="color" id="nodoColor" value="#4A90D9">
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <?php
                            $colores = ['#4A90D9','#7B68EE','#50C878','#FF6B6B','#FFA500','#20B2AA','#FF69B4','#778899','#DAA520','#DC143C'];
                            foreach ($colores as $c): ?>
                            <span class="color-option <?= $c === '#4A90D9' ? 'selected' : '' ?>"
                                  style="background:<?= $c ?>;" data-color="<?= $c ?>"
                                  onclick="selColor('<?= $c ?>')"></span>
                            <?php endforeach; ?>
                            <input type="color" class="form-control form-control-color ms-2" id="nodoColorCustom"
                                   value="#4A90D9" onchange="selColor(this.value)" title="Color personalizado"
                                   style="width:32px;height:32px;padding:2px;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tamaño</label>
                        <div class="btn-group w-100" role="group">
                            <input type="hidden" name="tamano" id="nodoTamano" value="M">
                            <button type="button" class="btn btn-sm btn-outline-secondary tamano-btn" data-tamano="S" onclick="selTamano('S')">S</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary tamano-btn active" data-tamano="M" onclick="selTamano('M')">M</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary tamano-btn" data-tamano="L" onclick="selTamano('L')">L</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary tamano-btn" data-tamano="XL" onclick="selTamano('XL')">XL</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Conexión -->
<div class="modal fade" id="modalConexion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>index.php?page=analisis_ie&action=guardar_conexion">
                <input type="hidden" name="id" id="conexionId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConexionTitulo">Nueva Conexión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Desde (origen) <span class="text-danger">*</span></label>
                        <select class="form-select" name="nodo_origen_id" id="conexionOrigen" required>
                            <option value="">Seleccionar nodo...</option>
                            <?php foreach ($nodos as $n): ?>
                            <option value="<?= (int)$n['id'] ?>"><?= sanitize($n['nombre']) ?> (<?= $n['tipo'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de relación <span class="text-danger">*</span></label>
                        <select class="form-select" name="tipo_relacion" id="conexionTipo" required>
                            <option value="">Seleccionar...</option>
                            <option value="habilita">Habilita</option>
                            <option value="depende_de">Depende de</option>
                            <option value="bloquea">Bloquea</option>
                            <option value="genera">Genera</option>
                            <option value="mitiga">Mitiga</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasta (destino) <span class="text-danger">*</span></label>
                        <select class="form-select" name="nodo_destino_id" id="conexionDestino" required>
                            <option value="">Seleccionar nodo...</option>
                            <?php foreach ($nodos as $n): ?>
                            <option value="<?= (int)$n['id'] ?>"><?= sanitize($n['nombre']) ?> (<?= $n['tipo'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <input type="text" class="form-control" name="descripcion" id="conexionDescripcion" placeholder="Opcional...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form oculto para eliminar nodo -->
<form id="formEliminarNodo" method="POST" style="display:none;">
</form>

<!-- Form oculto para eliminar conexión -->
<form id="formEliminarConexion" method="POST" style="display:none;">
</form>

<script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
<script>
// === DATA ===
const nodosData = <?= json_encode(array_values($nodos)) ?>;
const conexionesData = <?= json_encode(array_values($conexiones)) ?>;
const tipoDefaults = <?= json_encode($tipoDefaults) ?>;
const relacionColors = <?= json_encode(array_map(fn($r) => $r['color'], $relacionLabels)) ?>;
const relacionLabels = <?= json_encode(array_map(fn($r) => $r['label'], $relacionLabels)) ?>;
const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;

const tamanoConfig = {
    'S':  { size: 15, font: 10, width: 80 },
    'M':  { size: 25, font: 14, width: 120 },
    'L':  { size: 35, font: 18, width: 170 },
    'XL': { size: 50, font: 24, width: 230 }
};

function selTamano(t) {
    document.getElementById('nodoTamano').value = t;
    document.querySelectorAll('.tamano-btn').forEach(b => b.classList.toggle('active', b.dataset.tamano === t));
}

// === VIS.JS SETUP ===
let network, nodes, edges;
let modoActual = 'diseno';
let subModo = 'completa';
let nodoSeleccionado = null;
let pasoActual = 0;
let nodosOrdenados = [];

function reordenarGrafo() {
    // Limpiar posiciones fijas para que la física pueda moverlos
    nodes.forEach(n => {
        nodes.update({ id: n.id, x: undefined, y: undefined, fixed: false });
    });

    // Activar física con layout jerárquico temporal
    network.setOptions({
        physics: {
            enabled: true,
            solver: 'forceAtlas2Based',
            forceAtlas2Based: {
                gravitationalConstant: -80,
                centralGravity: 0.01,
                springLength: 150,
                springConstant: 0.08,
                damping: 0.4
            },
            stabilization: { iterations: 300 }
        }
    });

    network.once('stabilizationIterationsDone', () => {
        network.setOptions({ physics: { enabled: false } });
        network.fit({ animation: true });
        // Guardar posiciones nuevas
        guardarTodasLasPosiciones();
    });
}

function guardarTodasLasPosiciones() {
    const positions = network.getPositions();
    const datos = Object.entries(positions).map(([id, pos]) => ({
        id: parseInt(id), x: pos.x, y: pos.y
    }));
    if (datos.length === 0) return;
    datos.forEach(d => {
        fetch(BASE_URL + 'index.php?page=analisis_ie&action=api_posicion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(d)
        });
    });
}

function exportarImagen() {
    const scale = 3;
    const canvas = document.querySelector('#grafo-container canvas');
    if (!canvas) return;

    // Ajustar vista para capturar todo
    network.fit();

    // Esperar a que termine el fit
    setTimeout(() => {
        // Crear canvas de alta resolución
        const hiRes = document.createElement('canvas');
        hiRes.width = canvas.width * scale;
        hiRes.height = canvas.height * scale;
        const ctx = hiRes.getContext('2d');

        // Fondo blanco
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, hiRes.width, hiRes.height);

        // Dibujar el canvas original escalado
        ctx.scale(scale, scale);
        ctx.drawImage(canvas, 0, 0);

        // Descargar
        const link = document.createElement('a');
        link.download = 'diseno_ie_' + new Date().toISOString().slice(0,10) + '.png';
        link.href = hiRes.toDataURL('image/png', 1.0);
        link.click();
    }, 500);
}

function zoomIn() {
    const scale = network.getScale();
    network.moveTo({ scale: scale * 1.3, animation: { duration: 300 } });
}
function zoomOut() {
    const scale = network.getScale();
    network.moveTo({ scale: scale / 1.3, animation: { duration: 300 } });
}

function initGrafo() {
    const container = document.getElementById('grafo-container');

    // Build nodes
    const allHavePos = nodosData.length > 0 && nodosData.every(n => n.pos_x !== null && n.pos_y !== null);
    const visNodes = nodosData.map(n => {
        const tc = tamanoConfig[n.tamano] || tamanoConfig['M'];
        const node = {
            id: n.id,
            label: n.nombre,
            shape: n.forma || 'box',
            size: tc.size,
            widthConstraint: { minimum: tc.width, maximum: tc.width },
            color: {
                background: n.color || '#4A90D9',
                border: shadeColor(n.color || '#4A90D9', -20),
                highlight: { background: shadeColor(n.color || '#4A90D9', 20), border: shadeColor(n.color || '#4A90D9', -30) }
            },
            font: { color: getContrastColor(n.color || '#4A90D9'), size: tc.font, face: 'Inter, sans-serif' },
            title: buildTooltip(n),
            _data: n
        };
        if (n.pos_x !== null && n.pos_y !== null) {
            node.x = parseFloat(n.pos_x);
            node.y = parseFloat(n.pos_y);
        }
        return node;
    });

    // Build edges
    const visEdges = conexionesData.map(c => ({
        id: c.id,
        from: c.nodo_origen_id,
        to: c.nodo_destino_id,
        label: relacionLabels[c.tipo_relacion] || c.tipo_relacion,
        arrows: 'to',
        color: { color: relacionColors[c.tipo_relacion] || '#999', highlight: relacionColors[c.tipo_relacion] || '#999' },
        font: { size: 11, color: '#666', strokeWidth: 2, strokeColor: '#fff' },
        smooth: { type: 'curvedCW', roundness: 0.15 },
        width: 2,
        _data: c
    }));

    nodes = new vis.DataSet(visNodes);
    edges = new vis.DataSet(visEdges);

    const options = {
        nodes: {
            borderWidth: 2,
            shadow: { enabled: true, size: 6, x: 2, y: 2 },
            margin: { top: 8, bottom: 8, left: 12, right: 12 }
        },
        edges: {
            smooth: { type: 'curvedCW', roundness: 0.15 }
        },
        physics: {
            enabled: !allHavePos,
            solver: 'forceAtlas2Based',
            forceAtlas2Based: { gravitationalConstant: -60, springLength: 160, springConstant: 0.08 },
            stabilization: { iterations: 200 }
        },
        interaction: {
            hover: true,
            tooltipDelay: 200,
            navigationButtons: false,
            keyboard: false
        }
    };

    network = new vis.Network(container, { nodes, edges }, options);

    // Events
    network.on('dragEnd', function(params) {
        if (modoActual !== 'diseno' || !isAdmin) return;
        if (params.nodes.length === 1) {
            const nodeId = params.nodes[0];
            const pos = network.getPositions([nodeId])[nodeId];
            fetch('<?= BASE_URL ?>index.php?page=analisis_ie&action=api_posicion', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: nodeId, x: pos.x, y: pos.y })
            });
        }
    });

    network.on('selectNode', function(params) {
        if (params.nodes.length === 1) {
            nodoSeleccionado = params.nodes[0];
            mostrarInfo(nodoSeleccionado);
        }
    });

    network.on('deselectNode', function() {
        nodoSeleccionado = null;
        cerrarInfo();
    });

    network.on('doubleClick', function(params) {
        if (modoActual !== 'diseno' || !isAdmin) return;
        if (params.nodes.length === 1) {
            editarNodo(params.nodes[0]);
        } else if (params.edges.length === 1 && params.nodes.length === 0) {
            editarConexion(params.edges[0]);
        }
    });

    // After stabilization, disable physics so nodes stay where dragged
    network.on('stabilizationIterationsDone', function() {
        network.setOptions({ physics: { enabled: false } });
    });

    // Sorted nodes for presentation
    nodosOrdenados = [...nodosData].sort((a, b) => (a.orden || 0) - (b.orden || 0) || a.id - b.id);
}

// === MODES ===
function setModo(modo) {
    modoActual = modo;
    const btnD = document.getElementById('btnModoDiseno');
    const btnP = document.getElementById('btnModoPresentacion');
    const subP = document.getElementById('subModoPresentacion');
    const toolD = document.getElementById('toolbarDiseno');
    const infoB = document.getElementById('infoBotones');

    if (modo === 'diseno') {
        btnD.className = 'btn btn-sm btn-primary active';
        btnP.className = 'btn btn-sm btn-outline-primary';
        subP.classList.add('d-none');
        toolD.classList.remove('d-none');
        document.getElementById('barraAnimacion').classList.add('d-none');
        if (infoB) infoB.classList.remove('d-none');
        network.setOptions({ interaction: { dragNodes: isAdmin, dragView: true } });
        resetPresentacion();
    } else {
        btnD.className = 'btn btn-sm btn-outline-primary';
        btnP.className = 'btn btn-sm btn-primary active';
        subP.classList.remove('d-none');
        toolD.classList.add('d-none');
        if (infoB) infoB.classList.add('d-none');
        network.setOptions({ interaction: { dragNodes: false, dragView: true } });
        setSubModo(subModo);
    }
    cerrarInfo();
}

function setSubModo(sm) {
    subModo = sm;
    const btnC = document.getElementById('btnCompleta');
    const btnA = document.getElementById('btnAnimada');
    const barra = document.getElementById('barraAnimacion');

    if (sm === 'completa') {
        btnC.className = 'btn btn-sm btn-outline-secondary active';
        btnA.className = 'btn btn-sm btn-outline-secondary';
        barra.classList.add('d-none');
        resetPresentacion();
    } else {
        btnC.className = 'btn btn-sm btn-outline-secondary';
        btnA.className = 'btn btn-sm btn-outline-secondary active';
        barra.classList.remove('d-none');
        iniciarAnimacion();
    }
}

// === ANIMATED PRESENTATION ===
function iniciarAnimacion() {
    pasoActual = 0;
    // Hide all nodes and edges
    nodosOrdenados.forEach(n => {
        nodes.update({ id: n.id, hidden: true });
    });
    edges.forEach(e => {
        edges.update({ id: e.id, hidden: true });
    });
    actualizarBarraAnimacion();
}

function pasoSiguiente() {
    if (pasoActual >= nodosOrdenados.length) return;
    const nodo = nodosOrdenados[pasoActual];
    nodes.update({ id: nodo.id, hidden: false });
    // Show edges where both nodes are visible
    mostrarConexionesVisibles();
    pasoActual++;
    actualizarBarraAnimacion();
    network.fit({ animation: { duration: 400, easingFunction: 'easeInOutQuad' } });
}

function pasoAnterior() {
    if (pasoActual <= 0) return;
    pasoActual--;
    const nodo = nodosOrdenados[pasoActual];
    nodes.update({ id: nodo.id, hidden: true });
    // Hide edges connected to hidden nodes
    mostrarConexionesVisibles();
    actualizarBarraAnimacion();
}

function mostrarTodo() {
    nodosOrdenados.forEach(n => {
        nodes.update({ id: n.id, hidden: false });
    });
    edges.forEach(e => {
        edges.update({ id: e.id, hidden: false });
    });
    pasoActual = nodosOrdenados.length;
    actualizarBarraAnimacion();
    network.fit({ animation: { duration: 500, easingFunction: 'easeInOutQuad' } });
}

function resetPresentacion() {
    nodosOrdenados.forEach(n => {
        nodes.update({ id: n.id, hidden: false });
    });
    edges.forEach(e => {
        edges.update({ id: e.id, hidden: false });
    });
    pasoActual = nodosOrdenados.length;
}

function mostrarConexionesVisibles() {
    const visibleIds = new Set();
    nodosOrdenados.forEach(n => {
        const nodeData = nodes.get(n.id);
        if (nodeData && !nodeData.hidden) visibleIds.add(parseInt(n.id));
    });
    edges.forEach(e => {
        const fromVisible = visibleIds.has(parseInt(e.from));
        const toVisible = visibleIds.has(parseInt(e.to));
        edges.update({ id: e.id, hidden: !(fromVisible && toVisible) });
    });
}

function actualizarBarraAnimacion() {
    document.getElementById('pasoIndicador').textContent = `Paso ${pasoActual} / ${nodosOrdenados.length}`;
    document.getElementById('btnAnterior').disabled = pasoActual <= 0;
    document.getElementById('btnSiguiente').disabled = pasoActual >= nodosOrdenados.length;
}

// Keyboard navigation for presentation
document.addEventListener('keydown', function(e) {
    if (modoActual !== 'presentacion' || subModo !== 'animada') return;
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
    if (e.key === 'ArrowRight' || e.key === ' ') {
        e.preventDefault();
        pasoSiguiente();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        pasoAnterior();
    }
});

// === NODE INFO PANEL ===
function mostrarInfo(nodeId) {
    const node = nodes.get(nodeId);
    if (!node || !node._data) return;
    const d = node._data;
    document.getElementById('infoNombre').textContent = d.nombre;
    const tipoBadge = document.getElementById('infoTipo');
    tipoBadge.textContent = d.tipo;
    tipoBadge.style.backgroundColor = d.color || '#4A90D9';
    tipoBadge.style.color = getContrastColor(d.color || '#4A90D9');
    document.getElementById('infoDesc').textContent = d.descripcion || 'Sin descripción';
    document.getElementById('infoEstado').textContent = d.estado || 'activo';
    const obsWrap = document.getElementById('infoObsWrap');
    const obs = document.getElementById('infoObs');
    if (d.observaciones) {
        obs.textContent = d.observaciones;
        obsWrap.style.display = '';
    } else {
        obsWrap.style.display = 'none';
    }
    document.getElementById('infoPanel').style.display = 'block';
}

function cerrarInfo() {
    document.getElementById('infoPanel').style.display = 'none';
}

// === MODALS ===
function abrirModalNodo(data) {
    document.getElementById('nodoId').value = data ? data.id : '';
    document.getElementById('nodoNombre').value = data ? data.nombre : '';
    document.getElementById('nodoTipo').value = data ? data.tipo : '';
    document.getElementById('nodoEstado').value = data ? (data.estado || 'activo') : 'activo';
    document.getElementById('nodoOrden').value = data ? (data.orden || 0) : 0;
    document.getElementById('nodoDescripcion').value = data ? (data.descripcion || '') : '';
    document.getElementById('nodoObservaciones').value = data ? (data.observaciones || '') : '';
    selForma(data ? (data.forma || 'box') : 'box');
    selColor(data ? (data.color || '#4A90D9') : '#4A90D9');
    selTamano(data ? (data.tamano || 'M') : 'M');
    document.getElementById('modalNodoTitulo').textContent = data ? 'Editar Elemento' : 'Nuevo Elemento';
    new bootstrap.Modal(document.getElementById('modalNodo')).show();
}

function abrirModalConexion(data) {
    document.getElementById('conexionId').value = data ? data.id : '';
    document.getElementById('conexionOrigen').value = data ? data.nodo_origen_id : '';
    document.getElementById('conexionTipo').value = data ? data.tipo_relacion : '';
    document.getElementById('conexionDestino').value = data ? data.nodo_destino_id : '';
    document.getElementById('conexionDescripcion').value = data ? (data.descripcion || '') : '';
    document.getElementById('modalConexionTitulo').textContent = data ? 'Editar Conexión' : 'Nueva Conexión';
    new bootstrap.Modal(document.getElementById('modalConexion')).show();
}

function editarNodo(nodeId) {
    const node = nodes.get(nodeId);
    if (node && node._data) abrirModalNodo(node._data);
}

function editarConexion(edgeId) {
    const edge = edges.get(edgeId);
    if (edge && edge._data) abrirModalConexion(edge._data);
}

function editarNodoSeleccionado() {
    if (nodoSeleccionado) editarNodo(nodoSeleccionado);
}

function eliminarNodoSeleccionado() {
    if (!nodoSeleccionado) return;
    const node = nodes.get(nodoSeleccionado);
    if (!node) return;
    if (!confirm('¿Eliminar "' + node.label + '" y todas sus conexiones?')) return;
    const form = document.getElementById('formEliminarNodo');
    form.action = '<?= BASE_URL ?>index.php?page=analisis_ie&action=eliminar_nodo&id=' + nodoSeleccionado;
    form.submit();
}

// === FORM HELPERS ===
function selForma(forma) {
    document.getElementById('nodoForma').value = forma;
    document.querySelectorAll('.forma-option').forEach(el => {
        el.classList.toggle('selected', el.dataset.forma === forma);
    });
}

function selColor(color) {
    document.getElementById('nodoColor').value = color;
    document.getElementById('nodoColorCustom').value = color;
    document.querySelectorAll('.color-option').forEach(el => {
        el.classList.toggle('selected', el.dataset.color === color);
    });
}

function sugerirDefaults() {
    const tipo = document.getElementById('nodoTipo').value;
    if (tipo && tipoDefaults[tipo]) {
        selForma(tipoDefaults[tipo].forma);
        selColor(tipoDefaults[tipo].color);
    }
}

// === UTILS ===
function buildTooltip(n) {
    let html = '<b>' + escHtml(n.nombre) + '</b><br>';
    html += '<i>' + n.tipo + '</i>';
    if (n.descripcion) html += '<br>' + escHtml(n.descripcion);
    if (n.estado && n.estado !== 'activo') html += '<br>Estado: ' + n.estado;
    return html;
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function shadeColor(color, percent) {
    const num = parseInt(color.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = Math.min(255, Math.max(0, (num >> 16) + amt));
    const G = Math.min(255, Math.max(0, (num >> 8 & 0x00FF) + amt));
    const B = Math.min(255, Math.max(0, (num & 0x0000FF) + amt));
    return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
}

function getContrastColor(hex) {
    const r = parseInt(hex.slice(1,3), 16);
    const g = parseInt(hex.slice(3,5), 16);
    const b = parseInt(hex.slice(5,7), 16);
    return (r * 0.299 + g * 0.587 + b * 0.114) > 150 ? '#333' : '#fff';
}

// === INIT ===
document.addEventListener('DOMContentLoaded', initGrafo);
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
