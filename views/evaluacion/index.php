<?php
require_once __DIR__ . '/../../models/Iniciativa.php';
$iniciativas = Iniciativa::getAll($pdo);

// Verificar si hay API key configurada
$apiKeyConfigurada = !empty(CLAUDE_API_KEY);

require_once __DIR__ . '/../layout/header.php';
?>

<h4 class="mb-4"><i class="bi bi-robot me-2"></i>Evaluación Estratégica con IA</h4>

<?php if (!$apiKeyConfigurada): ?>
<div class="alert alert-warning">
    <i class="bi bi-key me-2"></i>
    <strong>API Key no configurada.</strong> Configurá tu API key de Claude en <code>config/database.php</code> (constante CLAUDE_API_KEY).
</div>
<?php endif; ?>

<div class="row">
    <!-- Evaluación General -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-clipboard2-data me-2"></i>Evaluación General</h5>
                <p class="card-text text-muted">Análisis completo del BSC: avance de todas las IE, estado de KPIs, riesgos críticos y recomendaciones estratégicas.</p>
                <form method="POST" action="<?= BASE_URL ?>index.php?page=evaluacion&action=solicitar">
                    <input type="hidden" name="tipo" value="general">
                    <button type="submit" class="btn btn-primary" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                        <i class="bi bi-send me-1"></i>Solicitar Evaluación
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Evaluación por IE -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-bullseye me-2"></i>Evaluación por Iniciativa</h5>
                <p class="card-text text-muted">Análisis focalizado en una IE específica con sus PDAs, KPIs y riesgos.</p>
                <form method="POST" action="<?= BASE_URL ?>index.php?page=evaluacion&action=solicitar">
                    <input type="hidden" name="tipo" value="iniciativa">
                    <div class="mb-2">
                        <select class="form-select" name="entidad_id" required>
                            <option value="">Seleccionar IE...</option>
                            <?php foreach ($iniciativas as $ie): ?>
                                <option value="<?= $ie['id'] ?>"><?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                        <i class="bi bi-send me-1"></i>Solicitar Evaluación
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Preparación de Reunión -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-people me-2"></i>Preparación de Reunión</h5>
                <p class="card-text text-muted">Genera agenda y puntos clave para la próxima reunión con un responsable.</p>
                <form method="POST" action="<?= BASE_URL ?>index.php?page=evaluacion&action=solicitar">
                    <input type="hidden" name="tipo" value="reunion">
                    <div class="mb-2">
                        <select class="form-select" name="entidad_id" required>
                            <option value="">Seleccionar IE...</option>
                            <?php foreach ($iniciativas as $ie): ?>
                                <option value="<?= $ie['id'] ?>"><?= sanitize($ie['codigo'] . ' - ' . $ie['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                        <i class="bi bi-send me-1"></i>Generar Agenda
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Análisis de Riesgos -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-exclamation-triangle me-2"></i>Análisis de Riesgos</h5>
                <p class="card-text text-muted">Evalúa la matriz de riesgos completa y sugiere priorización y acciones de mitigación.</p>
                <form method="POST" action="<?= BASE_URL ?>index.php?page=evaluacion&action=solicitar">
                    <input type="hidden" name="tipo" value="riesgos">
                    <button type="submit" class="btn btn-primary" <?= !$apiKeyConfigurada ? 'disabled' : '' ?>>
                        <i class="bi bi-send me-1"></i>Analizar Riesgos
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Última evaluación (si existe en sesión) -->
<?php if (isset($_SESSION['ultima_evaluacion'])): ?>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-robot me-2"></i>Resultado de la Evaluación</h6>
        <a href="<?= BASE_URL ?>index.php?page=evaluacion&action=historial" class="btn btn-sm btn-outline-secondary">Ver Historial</a>
    </div>
    <div class="card-body ia-response">
        <?= $_SESSION['ultima_evaluacion'] ?>
    </div>
</div>
<?php unset($_SESSION['ultima_evaluacion']); endif; ?>

<div class="mt-3">
    <a href="<?= BASE_URL ?>index.php?page=evaluacion&action=historial" class="btn btn-outline-secondary">
        <i class="bi bi-clock-history me-1"></i>Ver Historial de Evaluaciones
    </a>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
