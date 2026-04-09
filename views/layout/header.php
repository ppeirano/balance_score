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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%25' stop-color='%233b82f6'/><stop offset='100%25' stop-color='%236366f1'/></linearGradient></defs><rect width='32' height='32' rx='8' fill='url(%23g)'/><circle cx='16' cy='16' r='10' fill='none' stroke='white' stroke-width='2'/><circle cx='16' cy='16' r='5.5' fill='none' stroke='white' stroke-width='1.5'/><circle cx='16' cy='16' r='2' fill='white'/></svg>" type="image/svg+xml">
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
                            <div class="sidebar-logo-icon"><i class="bi bi-crosshair"></i></div>
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
                    <div class="sidebar-section-label" data-bs-toggle="collapse" data-bs-target="#navPrincipal">
                        Principal <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="collapse show" id="navPrincipal">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('dashboard', $page) ?>" href="<?= BASE_URL ?>index.php?page=dashboard">
                                    <i class="bi bi-speedometer2 me-2"></i><span class="nav-text">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('mapa', $page) ?>" href="<?= BASE_URL ?>index.php?page=mapa">
                                    <i class="bi bi-diagram-3 me-2"></i><span class="nav-text">Mapa Estratégico</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('analisis_ie', $page) ?>" href="<?= BASE_URL ?>index.php?page=analisis_ie">
                                    <i class="bi bi-bezier2 me-2"></i><span class="nav-text">Diseño IE</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('organigrama', $page) ?>" href="<?= BASE_URL ?>index.php?page=organigrama">
                                    <i class="bi bi-diagram-2 me-2"></i><span class="nav-text">Organigrama</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('iniciativas', $page) ?>" href="<?= BASE_URL ?>index.php?page=iniciativas">
                                    <i class="bi bi-bullseye me-2"></i><span class="nav-text">Iniciativas (IE)</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('planes', $page) ?>" href="<?= BASE_URL ?>index.php?page=planes">
                                    <i class="bi bi-list-check me-2"></i><span class="nav-text">Planes de Acción</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('kpis', $page) ?>" href="<?= BASE_URL ?>index.php?page=kpis">
                                    <i class="bi bi-graph-up me-2"></i><span class="nav-text">KPIs</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <hr>

                    <!-- Gestión -->
                    <div class="sidebar-section-label" data-bs-toggle="collapse" data-bs-target="#navGestion">
                        Gestión <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="collapse show" id="navGestion">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('reuniones', $page) ?>" href="<?= BASE_URL ?>index.php?page=reuniones">
                                    <i class="bi bi-people me-2"></i><span class="nav-text">Reuniones</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('riesgos', $page) ?>" href="<?= BASE_URL ?>index.php?page=riesgos">
                                    <i class="bi bi-exclamation-triangle me-2"></i><span class="nav-text">Restricciones y Riesgos</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('proyectos', $page) ?>" href="<?= BASE_URL ?>index.php?page=proyectos">
                                    <i class="bi bi-kanban me-2"></i><span class="nav-text">Proyectos</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('bitacora', $page) ?>" href="<?= BASE_URL ?>index.php?page=bitacora">
                                    <i class="bi bi-clock-history me-2"></i><span class="nav-text">Bitácora</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('calendario', $page) ?>" href="<?= BASE_URL ?>index.php?page=calendario">
                                    <i class="bi bi-calendar-event me-2"></i><span class="nav-text">Calendario</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('responsables', $page) ?>" href="<?= BASE_URL ?>index.php?page=responsables">
                                    <i class="bi bi-person-badge me-2"></i><span class="nav-text">Responsables</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <hr>

                    <!-- Reportes -->
                    <div class="sidebar-section-label collapsed" data-bs-toggle="collapse" data-bs-target="#navReportes">
                        Reportes <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="collapse" id="navReportes">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('reportes', $page) ?>" href="<?= BASE_URL ?>index.php?page=reportes">
                                    <i class="bi bi-file-earmark-pdf me-2"></i><span class="nav-text">Reportes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('evaluacion', $page) ?>" href="<?= BASE_URL ?>index.php?page=evaluacion">
                                    <i class="bi bi-robot me-2"></i><span class="nav-text">Evaluación IA</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <hr>

                    <?php if (isAdmin()): ?>
                    <!-- Admin -->
                    <div class="sidebar-section-label collapsed" data-bs-toggle="collapse" data-bs-target="#navAdmin">
                        Admin <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="collapse" id="navAdmin">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('admin_responsables', $page) ?>" href="<?= BASE_URL ?>index.php?page=admin_responsables">
                                    <i class="bi bi-person-gear me-2"></i><span class="nav-text">Admin. Responsables</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('periodos', $page) ?>" href="<?= BASE_URL ?>index.php?page=periodos">
                                    <i class="bi bi-calendar-range me-2"></i><span class="nav-text">Períodos</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= activeNav('admin_usuarios', $page) ?>" href="<?= BASE_URL ?>index.php?page=admin_usuarios">
                                    <i class="bi bi-people me-2"></i><span class="nav-text">Usuarios</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-10 ms-sm-auto main-content">
                <?php
                $pageTitles = [
                    'dashboard' => 'Dashboard Estratégico',
                    'mapa' => 'Mapa Estratégico',
                    'analisis_ie' => 'Diseño IE',
                    'organigrama' => 'Organigrama CODI',
                    'iniciativas' => 'Iniciativas Estratégicas',
                    'planes' => 'Planes de Acción',
                    'kpis' => 'Indicadores (KPIs)',
                    'reuniones' => 'Reuniones',
                    'riesgos' => 'Restricciones y Riesgos',
                    'proyectos' => 'Proyectos',
                    'bitacora' => 'Bitácora de Cambios',
                    'calendario' => 'Calendario',
                    'responsables' => 'Vista por Responsable',
                    'reportes' => 'Reportes',
                    'evaluacion' => 'Evaluación IA',
                    'admin_responsables' => 'Admin. Responsables',
                    'periodos' => 'Períodos Estratégicos',
                    'admin_usuarios' => 'Usuarios',
                    'actividades' => 'Actividades',
                ];
                $topbarTitle = $pageTitles[$page] ?? ucfirst($page);
                ?>
                <div class="view-topbar d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-link text-dark d-md-none me-2 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        <div class="view-topbar-title"><?= $topbarTitle ?></div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php $cu = currentUser(); if ($cu): ?>
                        <small class="text-muted d-none d-sm-inline">
                            <i class="bi bi-person-circle me-1"></i><?= sanitize($cu['nombre'] ?: $cu['email']) ?>
                            <?php if ($cu['perfil'] === 'admin'): ?>
                                <span class="badge bg-primary ms-1" style="font-size:10px;">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary ms-1" style="font-size:10px;">Consultor</span>
                            <?php endif; ?>
                        </small>
                        <a href="<?= BASE_URL ?>index.php?page=logout" class="btn btn-outline-secondary btn-sm" title="Cerrar sesión">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="px-md-4 px-3 pb-4">
                <?php mostrarFlash(); ?>
