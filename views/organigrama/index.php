<?php
require_once __DIR__ . '/../../models/Responsable.php';

$tree = Responsable::getTree($pdo);

require_once __DIR__ . '/../layout/header.php';

// Función recursiva para renderizar el árbol
function renderNodo($nodo, $depth = 0) {
    $iniciales = '';
    $partes = explode(' ', trim($nodo['nombre']));
    foreach ($partes as $p) {
        if (!empty($p)) $iniciales .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    $iniciales = mb_substr($iniciales, 0, 2);

    $colors = ['#3b82f6','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#6366f1','#14b8a6','#f97316'];
    $colorIdx = crc32($nodo['nombre']) % count($colors);
    $avatarColor = $colors[abs($colorIdx)];

    $cantIE = count($nodo['iniciativas']);
    $cantHijos = count($nodo['hijos']);
    $cantRiesgos = count($nodo['riesgos'] ?? []);
    $ieId = 'orgIE' . $nodo['id'];
    $childId = 'orgChild' . $nodo['id'];
    $riskId = 'orgRisk' . $nodo['id'];
    // Depth 0 = root, children shown. Depth 1+ = collapsed
    $childrenOpen = ($depth === 0);
?>
    <div class="org-node">
        <div class="org-card">
            <div class="org-avatar" style="background-color: <?= $avatarColor ?>;">
                <?= $iniciales ?>
            </div>
            <div class="org-name"><?= sanitize($nodo['nombre']) ?></div>
            <div class="org-cargo"><?= sanitize($nodo['cargo'] ?? '') ?></div>
            <div class="org-badges">
                <?php if ($cantIE > 0): ?>
                    <span class="org-ie-count" data-bs-toggle="collapse" data-bs-target="#<?= $ieId ?>" role="button" title="Ver IE asignadas">
                        <i class="bi bi-bullseye"></i> <?= $cantIE ?>
                    </span>
                <?php endif; ?>
                <?php if ($cantRiesgos > 0): ?>
                    <span class="org-risk-count" data-bs-toggle="collapse" data-bs-target="#<?= $riskId ?>" role="button" title="Ver restricciones y riesgos">
                        <i class="bi bi-exclamation-triangle"></i> <?= $cantRiesgos ?>
                    </span>
                <?php endif; ?>
                <?php if ($cantHijos > 0): ?>
                    <span class="org-child-count" data-bs-toggle="collapse" data-bs-target="#<?= $childId ?>" role="button" title="Ver reportes directos">
                        <i class="bi bi-people"></i> <?= $cantHijos ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($cantIE > 0): ?>
        <div class="collapse" id="<?= $ieId ?>">
            <div class="org-ie-list">
                <?php foreach ($nodo['iniciativas'] as $ie):
                    $avance = (int)$ie['avance'];
                    $barColor = $avance >= 70 ? '#22c55e' : ($avance >= 40 ? '#f59e0b' : '#ef4444');
                ?>
                    <a href="<?= BASE_URL ?>index.php?page=iniciativas&action=detalle&id=<?= (int)$ie['id'] ?>"
                       class="org-ie-item">
                        <span class="org-ie-code" style="background-color: <?= sanitize($ie['perspectiva_color']) ?>;">
                            <?= sanitize($ie['codigo']) ?>
                        </span>
                        <span class="org-ie-name"><?= sanitize($ie['nombre']) ?></span>
                        <div class="org-ie-progress">
                            <div class="progress">
                                <div class="progress-bar" style="width: <?= $avance ?>%; background-color: <?= $barColor ?>;"></div>
                            </div>
                            <small><?= $avance ?>%</small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($cantRiesgos > 0): ?>
        <div class="collapse" id="<?= $riskId ?>">
            <div class="org-risk-list">
                <?php foreach ($nodo['riesgos'] as $riesgo):
                    $nivelColors = ['critico' => '#ef4444', 'alto' => '#f59e0b', 'medio' => '#3b82f6', 'bajo' => '#22c55e'];
                    $nivelColor = $nivelColors[$riesgo['nivel']] ?? '#6b7280';
                ?>
                    <a href="<?= BASE_URL ?>index.php?page=riesgos&action=editar&id=<?= (int)$riesgo['id'] ?>"
                       class="org-risk-item">
                        <span class="org-risk-nivel" style="background-color: <?= $nivelColor ?>;">
                            <?= ucfirst($riesgo['nivel']) ?>
                        </span>
                        <span class="org-risk-desc"><?= sanitize(mb_substr($riesgo['descripcion'], 0, 60)) ?><?= mb_strlen($riesgo['descripcion']) > 60 ? '...' : '' ?></span>
                        <?php if ($riesgo['ie_codigo']): ?>
                            <span class="org-risk-ie"><?= sanitize($riesgo['ie_codigo']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($cantHijos > 0): ?>
        <div class="collapse <?= $childrenOpen ? 'show' : '' ?>" id="<?= $childId ?>">
            <div class="org-children">
                <?php foreach ($nodo['hijos'] as $hijo): ?>
                    <?= renderNodo($hijo, $depth + 1) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php
}
?>

<style>
.org-wrapper {
    overflow-x: auto;
    padding: 10px 0;
}
.org-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: fit-content;
}
.org-node {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}
.org-card {
    background: #fff;
    border: 1px solid #eaecf0;
    border-radius: 12px;
    padding: 10px 10px 8px;
    text-align: center;
    width: 120px;
    transition: all 0.2s;
    position: relative;
    z-index: 2;
}
.org-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 14px rgba(59,130,246,0.1);
}
.org-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    margin: 0 auto 5px;
}
.org-name {
    font-size: 11px;
    font-weight: 600;
    color: #1b2333;
    line-height: 1.25;
    margin-bottom: 1px;
}
.org-cargo {
    font-size: 9.5px;
    color: #8a96aa;
    margin-bottom: 5px;
    line-height: 1.2;
}
.org-badges {
    display: flex;
    gap: 4px;
    justify-content: center;
}
.org-ie-count, .org-child-count, .org-risk-count {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 9px;
    font-weight: 500;
    border-radius: 8px;
    padding: 1px 6px;
    cursor: pointer;
    transition: all 0.15s;
}
.org-ie-count {
    color: #3b82f6;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
}
.org-ie-count:hover {
    background: #dbeafe;
}
.org-child-count {
    color: #8b5cf6;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
}
.org-child-count:hover {
    background: #ede9fe;
}
.org-risk-count {
    color: #ef4444;
    background: #fef2f2;
    border: 1px solid #fecaca;
}
.org-risk-count:hover {
    background: #fee2e2;
}

/* IE list */
.org-ie-list {
    background: #fff;
    border: 1px solid #eaecf0;
    border-radius: 10px;
    padding: 4px;
    margin-top: 4px;
    width: 240px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    z-index: 3;
    position: relative;
}
.org-ie-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 6px;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
}
.org-ie-item:hover {
    background: #f8fafc;
    color: inherit;
}
.org-ie-code {
    font-size: 9px;
    font-weight: 600;
    color: #fff;
    padding: 1px 6px;
    border-radius: 6px;
    flex-shrink: 0;
}
.org-ie-name {
    font-size: 10px;
    font-weight: 500;
    color: #374151;
    flex-grow: 1;
    line-height: 1.2;
}
.org-ie-progress {
    display: flex;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
    width: 50px;
}
.org-ie-progress .progress {
    flex-grow: 1;
    height: 3px;
}
.org-ie-progress small {
    font-size: 9px;
    font-weight: 600;
    color: #6b7280;
    min-width: 22px;
    text-align: right;
}

/* Risk list */
.org-risk-list {
    background: #fff;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 4px;
    margin-top: 4px;
    width: 240px;
    box-shadow: 0 4px 12px rgba(239,68,68,0.08);
    z-index: 3;
    position: relative;
}
.org-risk-item {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 6px;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
}
.org-risk-item:hover {
    background: #fef2f2;
    color: inherit;
}
.org-risk-nivel {
    font-size: 8px;
    font-weight: 700;
    color: #fff;
    padding: 1px 5px;
    border-radius: 6px;
    flex-shrink: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.org-risk-desc {
    font-size: 10px;
    font-weight: 500;
    color: #374151;
    flex-grow: 1;
    line-height: 1.2;
}
.org-risk-ie {
    font-size: 8px;
    font-weight: 600;
    color: #6b7280;
    background: #f3f4f6;
    padding: 1px 4px;
    border-radius: 4px;
    flex-shrink: 0;
}

/* Children & connectors */
.org-children {
    display: flex;
    gap: 8px;
    padding-top: 20px;
    position: relative;
    justify-content: center;
}
/* Vertical line: parent down to children level */
.org-children > .org-node::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 50%;
    width: 1px;
    height: 20px;
    background: #d1d5db;
}
/* Only child: no horizontal rail needed, just the vertical */
.org-children > .org-node:only-child::after {
    display: none;
}

/* Responsive */
@media (max-width: 768px) {
    .org-children {
        flex-direction: column;
        align-items: center;
        gap: 4px;
        padding-top: 12px;
        padding-left: 24px;
    }
    .org-children::before { height: 12px; left: 24px; }
    .org-children::after { display: none; }
    .org-children > .org-node::before { display: none; }
    .org-children > .org-node::after { display: none !important; }
}

.org-empty {
    text-align: center;
    padding: 60px 20px;
    color: #8a96aa;
}
.org-empty i {
    font-size: 3rem;
    display: block;
    margin-bottom: 12px;
    color: #d1d5db;
}
</style>

<div class="card">
    <div class="card-body p-3">
        <?php if (empty($tree)): ?>
            <div class="org-empty">
                <i class="bi bi-diagram-2"></i>
                <h6>No hay estructura definida</h6>
                <p>Asigná la jerarquía en <a href="<?= BASE_URL ?>index.php?page=admin_responsables">Admin. Responsables</a> usando el campo "Reporta a".</p>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-2">
                <i class="bi bi-info-circle me-1"></i>Click en <span class="org-ie-count" style="cursor:default;"><i class="bi bi-bullseye"></i> N</span> para ver IE asignadas, <span class="org-risk-count" style="cursor:default;"><i class="bi bi-exclamation-triangle"></i> N</span> para restricciones/riesgos, <span class="org-child-count" style="cursor:default;"><i class="bi bi-people"></i> N</span> para reportes directos.
            </p>
            <div class="org-wrapper">
                <div class="org-tree">
                    <?php foreach ($tree as $raiz): ?>
                        <?= renderNodo($raiz, 0) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Draw horizontal connector rails between sibling nodes
function drawOrgRails() {
    document.querySelectorAll('.org-children').forEach(function(container) {
        // Remove old rails
        container.querySelectorAll('.org-rail').forEach(function(r) { r.remove(); });

        var nodes = Array.from(container.children).filter(function(el) {
            return el.classList.contains('org-node');
        });
        if (nodes.length < 2) return;

        var first = nodes[0];
        var last = nodes[nodes.length - 1];
        var containerRect = container.getBoundingClientRect();
        var firstRect = first.getBoundingClientRect();
        var lastRect = last.getBoundingClientRect();

        var left = firstRect.left + firstRect.width / 2 - containerRect.left;
        var right = lastRect.left + lastRect.width / 2 - containerRect.left;

        var rail = document.createElement('div');
        rail.className = 'org-rail';
        rail.style.cssText = 'position:absolute;top:0;height:1px;background:#d1d5db;left:' + left + 'px;width:' + (right - left) + 'px;';
        container.appendChild(rail);
    });
}
// Run on load and on collapse toggle
drawOrgRails();
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(el) {
    var target = document.querySelector(el.getAttribute('data-bs-target'));
    if (target) {
        target.addEventListener('shown.bs.collapse', function() { setTimeout(drawOrgRails, 50); });
        target.addEventListener('hidden.bs.collapse', function() { setTimeout(drawOrgRails, 50); });
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
