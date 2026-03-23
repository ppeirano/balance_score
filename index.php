<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

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
        require __DIR__ . '/views/mapa/index.php';
        break;
    case 'iniciativas':
        switch ($action) {
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
                    $stmt = $pdo->prepare("DELETE FROM planes_accion WHERE id = ?");
                    $stmt->execute([$id]);
                    flash('success', 'Plan de acción eliminado.');
                }
                redirect('index.php?page=planes');
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
                    $stmt = $pdo->prepare("UPDATE actividades SET estado = ? WHERE id = ?");
                    $stmt->execute([$estado, $id]);
                    // Recalcular avance del PDA
                    $stmt2 = $pdo->prepare("SELECT plan_accion_id FROM actividades WHERE id = ?");
                    $stmt2->execute([$id]);
                    $act = $stmt2->fetch();
                    if ($act) {
                        $avance = calcularAvancePDA($pdo, $act['plan_accion_id']);
                        $pdo->prepare("UPDATE planes_accion SET avance = ? WHERE id = ?")->execute([$avance, $act['plan_accion_id']]);
                    }
                    flash('success', 'Estado actualizado.');
                }
                $planId = $_POST['plan_accion_id'] ?? '';
                redirect('index.php?page=planes&action=detalle&id=' . $planId);
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $stmt = $pdo->prepare("SELECT plan_accion_id FROM actividades WHERE id = ?");
                    $stmt->execute([$id]);
                    $act = $stmt->fetch();
                    $pdo->prepare("DELETE FROM actividades WHERE id = ?")->execute([$id]);
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
                    $pdo->prepare("DELETE FROM kpis WHERE id = ?")->execute([$id]);
                    flash('success', 'KPI eliminado.');
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
            case 'detalle':
                require __DIR__ . '/views/reuniones/detalle.php';
                break;
            case 'eliminar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
                    $pdo->prepare("DELETE FROM reuniones WHERE id = ?")->execute([$id]);
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
                    $pdo->prepare("UPDATE compromisos SET estado = ? WHERE id = ?")->execute([$estado, $id]);
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
                    $pdo->prepare("DELETE FROM riesgos WHERE id = ?")->execute([$id]);
                    flash('success', 'Riesgo eliminado.');
                }
                redirect('index.php?page=riesgos');
                break;
            default:
                require __DIR__ . '/views/riesgos/index.php';
        }
        break;
    case 'responsables':
        require __DIR__ . '/views/responsables/index.php';
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
