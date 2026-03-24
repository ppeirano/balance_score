<?php
require_once __DIR__ . '/../../models/ClaudeApi.php';
$evaluaciones = ClaudeApi::getHistorial($pdo);
require_once __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-clock-history me-2"></i>Historial de Evaluaciones IA</h4>
    <a href="<?= BASE_URL ?>index.php?page=evaluacion" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<?php foreach ($evaluaciones as $e): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
        <span>
            <span class="badge bg-primary"><?= ucfirst(sanitize($e['tipo'])) ?></span>
            <?= date('d/m/Y H:i', strtotime($e['created_at'])) ?>
        </span>
    </div>
    <div class="card-body">
        <details>
            <summary class="mb-2" style="cursor:pointer;">Ver evaluación completa</summary>
            <div class="ia-response mt-2 markdown-body" data-markdown="<?= htmlspecialchars($e['respuesta'], ENT_QUOTES, 'UTF-8') ?>"></div>
        </details>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($evaluaciones)): ?>
<div class="alert alert-info">No hay evaluaciones registradas.</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.markdown-body[data-markdown]').forEach(function(el) {
        el.innerHTML = marked.parse(el.dataset.markdown);
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
