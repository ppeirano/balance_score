<?php
session_start();

// Calcular semáforo para KPI cuantitativo
function calcularSemaforo($valor, $meta, $umbralVerde, $umbralAmarillo, $direccion) {
    if ($valor === null || $meta === null) return 'gris';

    if ($direccion === 'mayor_mejor') {
        $porcentaje = ($meta != 0) ? ($valor / $meta) * 100 : 0;
        if ($porcentaje >= $umbralVerde) return 'verde';
        if ($porcentaje >= $umbralAmarillo) return 'amarillo';
        return 'rojo';
    } else { // menor_mejor
        $porcentaje = ($valor != 0) ? ($meta / $valor) * 100 : 100;
        if ($porcentaje >= $umbralVerde) return 'verde';
        if ($porcentaje >= $umbralAmarillo) return 'amarillo';
        return 'rojo';
    }
}

// Badge HTML del semáforo
function semaforoBadge($estado) {
    $colores = [
        'verde' => '#198754',
        'amarillo' => '#ffc107',
        'rojo' => '#dc3545',
        'gris' => '#6c757d'
    ];
    $color = $colores[$estado] ?? '#6c757d';
    return '<span class="status-circle" style="background-color:' . $color . ';" title="' . ucfirst($estado) . '"></span>';
}

// Badge de estado de proyecto/PDA
function estadoBadge($estado) {
    $clases = [
        'pendiente' => 'bg-secondary',
        'en_progreso' => 'bg-primary',
        'completado' => 'bg-success',
        'cancelado' => 'bg-danger'
    ];
    $clase = $clases[$estado] ?? 'bg-secondary';
    $texto = str_replace('_', ' ', ucfirst($estado));
    return '<span class="badge ' . $clase . '">' . $texto . '</span>';
}

// Badge de prioridad
function prioridadBadge($prioridad) {
    $map = [
        1 => ['texto' => 'Muy Alta', 'badge' => 'bg-danger'],
        2 => ['texto' => 'Alta', 'badge' => 'bg-danger'],
        3 => ['texto' => 'Media', 'badge' => 'bg-warning text-dark'],
        4 => ['texto' => 'Baja', 'badge' => 'bg-info text-dark'],
        5 => ['texto' => 'Muy Baja', 'badge' => 'bg-secondary'],
    ];
    $prio = $map[(int)$prioridad] ?? $map[3];
    return '<span class="badge ' . $prio['badge'] . '">' . $prio['texto'] . '</span>';
}

// Flash messages
function flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function mostrarFlash() {
    $success = getFlash('success');
    $error = getFlash('error');
    if ($success) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' .
             htmlspecialchars($success) .
             '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    if ($error) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
             htmlspecialchars($error) .
             '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Sanitizar input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

// Verificar si una página está activa en el menú
function activeNav($page, $current) {
    return ($page === $current) ? 'active' : '';
}

// Formatear fecha
function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

// Obtener período activo
function getPeriodoActivo($pdo) {
    $stmt = $pdo->query("SELECT * FROM periodos_estrategicos WHERE activo = 1 LIMIT 1");
    return $stmt->fetch();
}

// Calcular nivel de riesgo
function calcularNivelRiesgo($probabilidad, $impacto) {
    $matrix = [
        'alta' => ['alto' => 'critico', 'medio' => 'alto', 'bajo' => 'medio'],
        'media' => ['alto' => 'alto', 'medio' => 'medio', 'bajo' => 'bajo'],
        'baja' => ['alto' => 'medio', 'medio' => 'bajo', 'bajo' => 'bajo']
    ];
    return $matrix[$probabilidad][$impacto] ?? 'bajo';
}

function nivelRiesgoBadge($nivel) {
    $clases = [
        'critico' => 'bg-danger',
        'alto' => 'bg-warning text-dark',
        'medio' => 'bg-info text-dark',
        'bajo' => 'bg-success'
    ];
    $clase = $clases[$nivel] ?? 'bg-secondary';
    return '<span class="badge ' . $clase . '">' . ucfirst($nivel) . '</span>';
}

// Calcular avance de PDA basado en actividades
function calcularAvancePDA($pdo, $planId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completadas FROM actividades WHERE plan_accion_id = ?");
    $stmt->execute([$planId]);
    $result = $stmt->fetch();
    if ($result['total'] == 0) return 0;
    return round(($result['completadas'] / $result['total']) * 100);
}
