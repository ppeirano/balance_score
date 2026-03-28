<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSC Temis Lostalo - Gestión Estratégica</title>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-2 d-md-block sidebar offcanvas-md offcanvas-start">
                <div class="offcanvas-header d-md-none">
                    <h5 class="offcanvas-title text-white">Menú</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <!-- Brand -->
                    <div class="sidebar-brand">
                        <div class="sidebar-logo">
                            <div class="sidebar-logo-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                            <div>
                                <div class="sidebar-app-name">Temis Lostalo</div>
                                <div class="sidebar-app-sub">Gestión Estratégica</div>
                            </div>
                        </div>
                    </div>

                    <!-- Período activo -->
                    <?php if ($periodoActivo): ?>
                    <div class="sidebar-period">
                        <div class="sidebar-period-dot"></div>
                        <span class="sidebar-period-label">Período</span>
                        <span class="sidebar-period-value"><?= sanitize($periodoActivo['nombre']) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Principal -->
                    <div class="sidebar-section-label">Principal</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('dashboard', $page) ?>" href="<?= BASE_URL ?>index.php?page=dashboard">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('mapa', $page) ?>" href="<?= BASE_URL ?>index.php?page=mapa">
                                <i class="bi bi-diagram-3 me-2"></i>Mapa Estratégico
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('iniciativas', $page) ?>" href="<?= BASE_URL ?>index.php?page=iniciativas">
                                <i class="bi bi-bullseye me-2"></i>Iniciativas (IE)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('planes', $page) ?>" href="<?= BASE_URL ?>index.php?page=planes">
                                <i class="bi bi-list-check me-2"></i>Planes de Acción
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('kpis', $page) ?>" href="<?= BASE_URL ?>index.php?page=kpis">
                                <i class="bi bi-graph-up me-2"></i>KPIs
                            </a>
                        </li>
                    </ul>

                    <hr>

                    <!-- Gestión -->
                    <div class="sidebar-section-label">Gestión</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('reuniones', $page) ?>" href="<?= BASE_URL ?>index.php?page=reuniones">
                                <i class="bi bi-people me-2"></i>Reuniones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('riesgos', $page) ?>" href="<?= BASE_URL ?>index.php?page=riesgos">
                                <i class="bi bi-exclamation-triangle me-2"></i>Riesgos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('proyectos', $page) ?>" href="<?= BASE_URL ?>index.php?page=proyectos">
                                <i class="bi bi-kanban me-2"></i>Proyectos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('bitacora', $page) ?>" href="<?= BASE_URL ?>index.php?page=bitacora">
                                <i class="bi bi-clock-history me-2"></i>Bitácora
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('calendario', $page) ?>" href="<?= BASE_URL ?>index.php?page=calendario">
                                <i class="bi bi-calendar-event me-2"></i>Calendario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('responsables', $page) ?>" href="<?= BASE_URL ?>index.php?page=responsables">
                                <i class="bi bi-person-badge me-2"></i>Responsables
                            </a>
                        </li>
                    </ul>

                    <hr>

                    <!-- Reportes -->
                    <div class="sidebar-section-label">Reportes</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('reportes', $page) ?>" href="<?= BASE_URL ?>index.php?page=reportes">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('evaluacion', $page) ?>" href="<?= BASE_URL ?>index.php?page=evaluacion">
                                <i class="bi bi-robot me-2"></i>Evaluación IA
                            </a>
                        </li>
                    </ul>

                    <hr>

                    <!-- Admin -->
                    <div class="sidebar-section-label">Admin</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('admin_responsables', $page) ?>" href="<?= BASE_URL ?>index.php?page=admin_responsables">
                                <i class="bi bi-person-gear me-2"></i>Admin. Responsables
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= activeNav('periodos', $page) ?>" href="<?= BASE_URL ?>index.php?page=periodos">
                                <i class="bi bi-calendar-range me-2"></i>Períodos
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto main-content">
                <?php
                $pageTitles = [
                    'dashboard' => 'Dashboard Estratégico',
                    'mapa' => 'Mapa Estratégico',
                    'iniciativas' => 'Iniciativas Estratégicas',
                    'planes' => 'Planes de Acción',
                    'kpis' => 'Indicadores (KPIs)',
                    'reuniones' => 'Reuniones',
                    'riesgos' => 'Gestión de Riesgos',
                    'proyectos' => 'Proyectos',
                    'bitacora' => 'Bitácora de Cambios',
                    'calendario' => 'Calendario',
                    'responsables' => 'Vista por Responsable',
                    'reportes' => 'Reportes',
                    'evaluacion' => 'Evaluación IA',
                    'admin_responsables' => 'Admin. Responsables',
                    'periodos' => 'Períodos Estratégicos',
                    'actividades' => 'Actividades',
                ];
                $topbarTitle = $pageTitles[$page] ?? ucfirst($page);
                ?>
                <div class="view-topbar">
                    <button class="btn btn-link text-dark d-md-none me-2 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <div class="view-topbar-title"><?= $topbarTitle ?></div>
                </div>
                <div class="px-md-4 px-3 pb-4">
                <?php mostrarFlash(); ?>
