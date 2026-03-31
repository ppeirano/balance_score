<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Bitacora.php';

$pdo = getDB();
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Obtener período activo
$periodoActivo = getPeriodoActivo($pdo);

switch ($page) {
    case 'dashboard':
        require __DIR__ . '/views/dashboard/index.php';
        break;
    case 'mapa':
        switch ($action) {
            case 'guardar_relacion':
                $stmt = $pdo->prepare("INSERT INTO relaciones_causa_efecto (iniciativa_origen_id, iniciativa_destino_id, descripcion) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['iniciativa_origen_id'], $_POST['iniciativa_destino_id'], trim($_POST['descripcion'] ?? '')]);
                flash('success', 'Relación creada.');
                redirect('index.php?page=mapa');
                break;
            case 'eliminar_relacion':
                if ($id) {
                    $pdo->prepare("DELETE FROM relaciones_causa_efecto WHERE id = ?")->execute([$id]);
                    flash('success', 'Relación eliminada.');
                }
                redirect('index.php?page=mapa');
                break;
            default:
                require __DIR__ . '/views/mapa/index.php';
        }
        break;
    case 'iniciativas':
        switch ($action) {
            case 'crear':
            case 'editar':
                require __DIR__ . '/views/iniciativas/form.php';
                break;
            case 'guardar':
                require __DIR__ . '/models/Iniciativa.php';
                Iniciativa::guardar($pdo, $_POST);
                break;
            case 'eliminar':
                require __DIR__ . '/models/Iniciativa.php';
                if ($id) Iniciativa::eliminar($pdo, $id);
                else redirect('index.php?page=iniciativas');
                break;
            case 'detalle':
                require __DIR__ . '/views/iniciativas/detalle.php';
                break;
            default:
                require __DIR__ . '/views/iniciativas/index.php';
        }
        break;
    case 'planes':
        switch ($action) {
            case 'crear':
            case 'editar':
                require __DIR__ . '/views/planes/form.php';
                break;
            case 'detalle':
                require __DIR__ . '/views/planes/detalle.php';
                break;
            case 'guardar':
                require __DIR__ . '/models/PlanAccion.php';
                PlanAccion::guardar($pdo, $_POST);
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmtNombre = $pdo->prepare("SELECT nombre FROM planes_accion WHERE id = ?");
                    $stmtNombre->execute([$id]);
                    $planNombre = $stmtNombre->fetchColumn() ?: 'Desconocido';
                    $pdo->prepare("DELETE FROM planes_accion WHERE id = ?")->execute([$id]);
                    Bitacora::registrar($pdo, 'plan_accion', $id, $planNombre, 'eliminado');
                    flash('success', 'Plan de acción eliminado.');
                }
                $filterParams = [];
                if (!empty($_POST['ie'])) $filterParams[] = 'ie=' . (int)$_POST['ie'];
                if (!empty($_POST['estado'])) $filterParams[] = 'estado=' . urlencode($_POST['estado']);
                $filterQuery = $filterParams ? '&' . implode('&', $filterParams) : '';
                redirect('index.php?page=planes' . $filterQuery);
                break;
            default:
                require __DIR__ . '/views/planes/index.php';
        }
        break;
    case 'actividades':
        switch ($action) {
            case 'crear':
            case 'editar':
                require __DIR__ . '/views/actividades/form.php';
                break;
            case 'guardar':
                require __DIR__ . '/models/Actividad.php';
                Actividad::guardar($pdo, $_POST);
                break;
            case 'cambiar_estado':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $estado = $_POST['estado'] ?? 'pendiente';
                    $stmtAct = $pdo->prepare("SELECT descripcion, plan_accion_id FROM actividades WHERE id = ?");
                    $stmtAct->execute([$id]);
                    $actInfo = $stmtAct->fetch();
                    $pdo->prepare("UPDATE actividades SET estado = ? WHERE id = ?")->execute([$estado, $id]);
                    Bitacora::registrar($pdo, 'actividad', $id, $actInfo['descripcion'] ?? '', 'estado_cambiado', "Estado → $estado");
                    // Recalcular avance del PDA
                    if ($actInfo) {
                        $avance = calcularAvancePDA($pdo, $actInfo['plan_accion_id']);
                        $pdo->prepare("UPDATE planes_accion SET avance = ? WHERE id = ?")->execute([$avance, $actInfo['plan_accion_id']]);
                    }
                    flash('success', 'Estado actualizado.');
                }
                $planId = $_POST['plan_accion_id'] ?? '';
                redirect('index.php?page=planes&action=detalle&id=' . $planId);
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmt = $pdo->prepare("SELECT descripcion, plan_accion_id FROM actividades WHERE id = ?");
                    $stmt->execute([$id]);
                    $act = $stmt->fetch();
                    $pdo->prepare("DELETE FROM actividades WHERE id = ?")->execute([$id]);
                    Bitacora::registrar($pdo, 'actividad', $id, $act['descripcion'] ?? '', 'eliminado');
                    if ($act) {
                        $avance = calcularAvancePDA($pdo, $act['plan_accion_id']);
                        $pdo->prepare("UPDATE planes_accion SET avance = ? WHERE id = ?")->execute([$avance, $act['plan_accion_id']]);
                    }
                    flash('success', 'Actividad eliminada.');
                    redirect('index.php?page=planes&action=detalle&id=' . ($act['plan_accion_id'] ?? ''));
                }
                break;
            default:
                redirect('index.php?page=planes');
        }
        break;
    case 'notas_actividad':
        require __DIR__ . '/models/NotaActividad.php';
        switch ($action) {
            case 'guardar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    NotaActividad::guardar($pdo, $_POST, $_FILES);
                }
                break;
            case 'subir_imagen':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['imagen'])) {
                    $resultado = NotaActividad::subirImagen($_FILES['imagen']);
                    header('Content-Type: application/json');
                    echo json_encode($resultado);
                    exit;
                }
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'No se recibió imagen.']);
                exit;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    NotaActividad::eliminar($pdo, $id);
                }
                break;
            default:
                redirect('index.php?page=planes');
        }
        break;
    case 'proyectos':
        require __DIR__ . '/models/Proyecto.php';
        switch ($action) {
            case 'index':
                require __DIR__ . '/views/proyectos/index.php';
                break;
            case 'crear':
            case 'editar':
                require __DIR__ . '/views/proyectos/form.php';
                break;
            case 'guardar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    Proyecto::guardar($pdo, $_POST);
                }
                break;
            case 'detalle':
                require __DIR__ . '/views/proyectos/detalle.php';
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmtN = $pdo->prepare("SELECT nombre FROM proyectos WHERE id = ?");
                    $stmtN->execute([$id]);
                    $proyNombre = $stmtN->fetchColumn() ?: 'Desconocido';
                    $pdo->prepare("DELETE FROM proyectos WHERE id = ?")->execute([$id]);
                    Bitacora::registrar($pdo, 'proyecto', $id, $proyNombre, 'eliminado');
                    flash('success', 'Proyecto eliminado.');
                }
                redirect('index.php?page=proyectos');
                break;
            case 'guardar_vinculo':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    Proyecto::guardarVinculo($pdo, $_POST);
                }
                break;
            case 'eliminar_vinculo':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    Proyecto::eliminarVinculo($pdo, $id);
                }
                break;
            case 'guardar_entregable':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    Proyecto::guardarEntregable($pdo, $_POST);
                }
                break;
            case 'eliminar_entregable':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    Proyecto::eliminarEntregable($pdo, $id);
                }
                break;
            case 'cambiar_estado_entregable':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $estado = $_POST['estado'] ?? 'pendiente';
                    $pdo->prepare("UPDATE proyecto_entregables SET estado = ? WHERE id = ?")->execute([$estado, $id]);
                    flash('success', 'Estado del entregable actualizado.');
                    $proyectoId = $_POST['proyecto_id'] ?? '';
                    redirect('index.php?page=proyectos&action=detalle&id=' . $proyectoId);
                }
                break;
            case 'guardar_actividad':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    Proyecto::guardarActividad($pdo, $_POST);
                }
                break;
            case 'eliminar_actividad':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    Proyecto::eliminarActividad($pdo, $id);
                }
                break;
            case 'cambiar_estado_actividad':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $estado = $_POST['estado'] ?? 'pendiente';
                    $pdo->prepare("UPDATE proyecto_actividades SET estado = ? WHERE id = ?")->execute([$estado, $id]);
                    $proyectoId = $_POST['proyecto_id'] ?? '';
                    if ($proyectoId) {
                        $avance = calcularAvanceProyecto($pdo, $proyectoId);
                        $pdo->prepare("UPDATE proyectos SET avance = ? WHERE id = ?")->execute([max(0, $avance), $proyectoId]);
                    }
                    flash('success', 'Estado actualizado.');
                    redirect('index.php?page=proyectos&action=detalle&id=' . $proyectoId);
                }
                break;
            case 'guardar_nota':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    Proyecto::guardarNota($pdo, $_POST, $_FILES);
                }
                break;
            case 'eliminar_nota':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    Proyecto::eliminarNota($pdo, $id);
                }
                break;
            case 'subir_imagen_nota':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['imagen'])) {
                    $resultado = Proyecto::subirImagenNota($_FILES['imagen']);
                    header('Content-Type: application/json');
                    echo json_encode($resultado);
                    exit;
                }
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'No se recibió imagen.']);
                exit;
            default:
                redirect('index.php?page=proyectos');
        }
        break;
    case 'kpis':
        switch ($action) {
            case 'crear':
            case 'editar':
                require __DIR__ . '/views/kpis/form.php';
                break;
            case 'guardar':
                require __DIR__ . '/models/Kpi.php';
                Kpi::guardar($pdo, $_POST);
                break;
            case 'historial':
                require __DIR__ . '/views/kpis/historial.php';
                break;
            case 'registrar_valor':
                require __DIR__ . '/models/Kpi.php';
                Kpi::registrarValor($pdo, $_POST);
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmtN = $pdo->prepare("SELECT nombre FROM kpis WHERE id = ?");
                    $stmtN->execute([$id]);
                    $kpiNombre = $stmtN->fetchColumn() ?: 'Desconocido';
                    $pdo->prepare("DELETE FROM kpis WHERE id = ?")->execute([$id]);
                    Bitacora::registrar($pdo, 'kpi', $id, $kpiNombre, 'eliminado');
                    flash('success', 'KPI eliminado.');
                }
                $filtros = [];
                if (!empty($_POST['ie'])) $filtros[] = 'ie=' . (int)$_POST['ie'];
                if (!empty($_POST['tipo'])) $filtros[] = 'tipo=' . urlencode($_POST['tipo']);
                redirect('index.php?page=kpis' . ($filtros ? '&' . implode('&', $filtros) : ''));
                break;
            case 'procesar_documento':
                header('Content-Type: application/json');
                if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['documento'])) {
                    echo json_encode(['error' => 'No se recibió ningún documento.']);
                    exit;
                }
                $archivo = $_FILES['documento'];
                if ($archivo['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['error' => 'Error al subir el archivo.']);
                    exit;
                }
                if ($archivo['size'] > 10 * 1024 * 1024) {
                    echo json_encode(['error' => 'El archivo supera el límite de 10MB.']);
                    exit;
                }
                $allowedTypes = [
                    'application/pdf', 'image/png', 'image/jpeg', 'image/jpg',
                    'text/csv', 'text/plain',
                    'application/vnd.ms-excel', 'application/csv'
                ];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $archivo['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mimeType, $allowedTypes)) {
                    echo json_encode(['error' => 'Formato de archivo no soportado. Usá PDF, imagen (PNG/JPG), CSV o texto.']);
                    exit;
                }
                require_once __DIR__ . '/models/ClaudeApi.php';
                $resultado = ClaudeApi::extraerKpisDeDocumento($pdo, $archivo['tmp_name'], $mimeType);
                if ($resultado === false) {
                    echo json_encode(['error' => 'Error al comunicarse con la IA. Verificá que la API key esté configurada.']);
                    exit;
                }
                if (isset($resultado['error'])) {
                    echo json_encode(['error' => $resultado['error']]);
                    exit;
                }
                $_SESSION['kpi_propuestas'] = $resultado;
                echo json_encode(['ok' => true, 'redirect' => BASE_URL . 'index.php?page=kpis&action=revision_ia']);
                exit;
            case 'revision_ia':
                require __DIR__ . '/views/kpis/revision_ia.php';
                break;
            case 'confirmar_ia':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    redirect('index.php?page=kpis');
                    break;
                }
                require_once __DIR__ . '/models/Kpi.php';
                $valoresSeleccionados = $_POST['valores'] ?? [];
                $dataRows = $_POST['data'] ?? [];
                $count = 0;
                foreach ($dataRows as $idx => $row) {
                    if (!isset($valoresSeleccionados[$idx])) continue;
                    $kpiId = (int)($row['kpi_id'] ?? 0);
                    $valor = $row['valor'] ?? '';
                    $periodo = $row['periodo'] ?? '';
                    $observaciones = $row['observaciones'] ?? '';
                    if (!$kpiId || $valor === '') continue;
                    Kpi::registrarValorSilencioso($pdo, [
                        'kpi_id' => $kpiId,
                        'valor' => $valor,
                        'periodo' => $periodo,
                        'observaciones' => $observaciones
                    ]);
                    $count++;
                }
                if ($count > 0) {
                    flash('success', $count . ' valor' . ($count > 1 ? 'es' : '') . ' de KPI registrado' . ($count > 1 ? 's' : '') . ' correctamente.');
                } else {
                    flash('warning', 'No se seleccionó ningún valor para registrar.');
                }
                redirect('index.php?page=kpis');
                break;
            default:
                require __DIR__ . '/views/kpis/index.php';
        }
        break;
    case 'reuniones':
        switch ($action) {
            case 'crear':
            case 'editar':
                require __DIR__ . '/views/reuniones/form.php';
                break;
            case 'guardar':
                require __DIR__ . '/models/Reunion.php';
                Reunion::guardar($pdo, $_POST);
                break;
            case 'generar_agenda':
                header('Content-Type: application/json');
                require __DIR__ . '/models/ClaudeApi.php';
                $responsable = $_POST['responsable'] ?? '';
                if (empty($responsable)) {
                    echo json_encode(['error' => 'Seleccione un responsable.']);
                    exit;
                }
                $agenda = ClaudeApi::generarAgenda($pdo, $responsable);
                if ($agenda === false) {
                    echo json_encode(['error' => 'Error al comunicarse con la API. Verifique que la API key este configurada.']);
                } else {
                    echo json_encode(['agenda' => $agenda]);
                }
                exit;
            case 'detalle':
                require __DIR__ . '/views/reuniones/detalle.php';
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmtN = $pdo->prepare("SELECT titulo FROM reuniones WHERE id = ?");
                    $stmtN->execute([$id]);
                    $reuNombre = $stmtN->fetchColumn() ?: 'Desconocida';
                    $pdo->prepare("DELETE FROM reuniones WHERE id = ?")->execute([$id]);
                    Bitacora::registrar($pdo, 'reunion', $id, $reuNombre, 'eliminado');
                    flash('success', 'Reunión eliminada.');
                }
                redirect('index.php?page=reuniones');
                break;
            default:
                require __DIR__ . '/views/reuniones/index.php';
        }
        break;
    case 'compromisos':
        switch ($action) {
            case 'guardar':
                require __DIR__ . '/models/Compromiso.php';
                Compromiso::guardar($pdo, $_POST);
                break;
            case 'cambiar_estado':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $estado = $_POST['estado'] ?? 'pendiente';
                    $stmtC = $pdo->prepare("SELECT descripcion FROM compromisos WHERE id = ?");
                    $stmtC->execute([$id]);
                    $compDesc = $stmtC->fetchColumn() ?: '';
                    $pdo->prepare("UPDATE compromisos SET estado = ? WHERE id = ?")->execute([$estado, $id]);
                    Bitacora::registrar($pdo, 'compromiso', $id, $compDesc, 'estado_cambiado', "Estado → $estado");
                    flash('success', 'Compromiso actualizado.');
                }
                $reunionId = $_POST['reunion_id'] ?? '';
                redirect('index.php?page=reuniones&action=detalle&id=' . $reunionId);
                break;
            default:
                redirect('index.php?page=reuniones');
        }
        break;
    case 'riesgos':
        switch ($action) {
            case 'crear':
            case 'editar':
                require __DIR__ . '/views/riesgos/form.php';
                break;
            case 'guardar':
                require __DIR__ . '/models/Riesgo.php';
                Riesgo::guardar($pdo, $_POST);
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmtN = $pdo->prepare("SELECT descripcion FROM riesgos WHERE id = ?");
                    $stmtN->execute([$id]);
                    $riesgoDesc = $stmtN->fetchColumn() ?: 'Desconocido';
                    $pdo->prepare("DELETE FROM riesgos WHERE id = ?")->execute([$id]);
                    Bitacora::registrar($pdo, 'riesgo', $id, $riesgoDesc, 'eliminado');
                    flash('success', 'Restricción/Riesgo eliminado.');
                }
                redirect('index.php?page=riesgos');
                break;
            default:
                require __DIR__ . '/views/riesgos/index.php';
        }
        break;
    case 'bitacora':
        require __DIR__ . '/views/bitacora/index.php';
        break;
    case 'organigrama':
        require __DIR__ . '/views/organigrama/index.php';
        break;
    case 'responsables':
        require __DIR__ . '/views/responsables/index.php';
        break;
    case 'admin_responsables':
        require __DIR__ . '/models/Responsable.php';
        switch ($action) {
            case 'guardar':
                Responsable::guardar($pdo, $_POST);
                break;
            case 'eliminar':
                if ($id) Responsable::eliminar($pdo, $id);
                else redirect('index.php?page=admin_responsables');
                break;
            default:
                require __DIR__ . '/views/admin_responsables/index.php';
        }
        break;
    case 'reportes':
        require __DIR__ . '/views/reportes/generar.php';
        break;
    case 'periodos':
        switch ($action) {
            case 'guardar':
                require __DIR__ . '/models/Periodo.php';
                Periodo::guardar($pdo, $_POST);
                break;
            case 'activar':
                if ($id) {
                    $pdo->exec("UPDATE periodos_estrategicos SET activo = 0");
                    $pdo->prepare("UPDATE periodos_estrategicos SET activo = 1 WHERE id = ?")->execute([$id]);
                    flash('success', 'Período activado.');
                }
                redirect('index.php?page=periodos');
                break;
            default:
                require __DIR__ . '/views/periodos/index.php';
        }
        break;
    case 'evaluacion':
        switch ($action) {
            case 'solicitar':
                require __DIR__ . '/models/ClaudeApi.php';
                ClaudeApi::solicitarEvaluacion($pdo, $_POST);
                break;
            case 'historial':
                require __DIR__ . '/views/evaluacion/historial.php';
                break;
            default:
                require __DIR__ . '/views/evaluacion/index.php';
        }
        break;
    case 'recursos':
        switch ($action) {
            case 'guardar':
                require __DIR__ . '/models/Recurso.php';
                Recurso::guardar($pdo, $_POST);
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmt = $pdo->prepare("SELECT actividad_id FROM recursos WHERE id = ?");
                    $stmt->execute([$id]);
                    $rec = $stmt->fetch();
                    $pdo->prepare("DELETE FROM recursos WHERE id = ?")->execute([$id]);
                    flash('success', 'Recurso eliminado.');
                    // Buscar plan_accion_id de la actividad
                    if ($rec) {
                        $stmt2 = $pdo->prepare("SELECT plan_accion_id FROM actividades WHERE id = ?");
                        $stmt2->execute([$rec['actividad_id']]);
                        $act = $stmt2->fetch();
                        redirect('index.php?page=planes&action=detalle&id=' . ($act['plan_accion_id'] ?? ''));
                    }
                }
                redirect('index.php?page=planes');
                break;
            default:
                redirect('index.php?page=planes');
        }
        break;
    case 'hitos':
        switch ($action) {
            case 'guardar':
                require __DIR__ . '/models/Hito.php';
                Hito::guardar($pdo, $_POST);
                break;
            case 'cambiar_estado':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $estado = $_POST['estado'] ?? 'pendiente';
                    $fechaReal = ($estado === 'alcanzado') ? date('Y-m-d') : null;
                    $pdo->prepare("UPDATE hitos SET estado = ?, fecha_real = ? WHERE id = ?")->execute([$estado, $fechaReal, $id]);
                    flash('success', 'Hito actualizado.');
                }
                $planId = $_POST['plan_accion_id'] ?? '';
                redirect('index.php?page=planes&action=detalle&id=' . $planId);
                break;
            default:
                redirect('index.php?page=planes');
        }
        break;
    case 'calendario':
        switch ($action) {
            case 'guardar':
                require __DIR__ . '/models/Seguimiento.php';
                Seguimiento::guardar($pdo, $_POST);
                break;
            case 'completar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    require __DIR__ . '/models/Seguimiento.php';
                    Seguimiento::completar($pdo, $id);
                }
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    require __DIR__ . '/models/Seguimiento.php';
                    Seguimiento::eliminar($pdo, $id);
                }
                break;
            case 'json':
                require __DIR__ . '/models/Seguimiento.php';
                header('Content-Type: application/json');
                echo Seguimiento::getAllAsJson($pdo);
                exit;
            default:
                require __DIR__ . '/views/calendario/index.php';
        }
        break;
    case 'adjuntos':
        switch ($action) {
            case 'subir':
                require __DIR__ . '/models/ArchivoAdjunto.php';
                ArchivoAdjunto::subir($pdo, $_POST, $_FILES);
                $redir = $_POST['redirect'] ?? 'index.php?page=dashboard';
                header("Location: " . $redir);
                exit;
                break;
            case 'descargar':
                require __DIR__ . '/models/ArchivoAdjunto.php';
                ArchivoAdjunto::descargar($pdo, $id);
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    require __DIR__ . '/models/ArchivoAdjunto.php';
                    ArchivoAdjunto::eliminar($pdo, $id);
                }
                $redir = $_POST['redirect'] ?? 'index.php?page=dashboard';
                header("Location: " . $redir);
                exit;
                break;
        }
        break;
    default:
        require __DIR__ . '/views/dashboard/index.php';
}
