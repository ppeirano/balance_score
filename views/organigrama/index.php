<?php
require_once __DIR__ . '/../../models/Responsable.php';

$tree = Responsable::getTree($pdo);

require_once __DIR__ . '/../layout/header.php';

// Función recursiva para renderizar el árbol
function renderNodo($nodo, $isRoot = false) {
    $iniciales = '';
    $partes = explode(' ', trim($nodo['nombre']));
    foreach ($partes as $p) {
        if (!empty($p)) $iniciales .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    $iniciales = mb_substr($iniciales, 0, 2);

    // Color del avatar basado en hash del nombre
    $colors = ['#3b82f6','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#6366f1','#14b8a6','#f97316'];
    $colorIdx = crc32($nodo['nombre']) % count($colors);
    $avatarColor = $colors[abs($colorIdx)];

    $cantIE = count($nodo['iniciativas']);
    $nodeId = 'orgNode' . $nodo['id'];
?>
    <div class="org-node">
        <div class="org-card" data-bs-toggle="collapse" data-bs-target="#<?= $nodeId ?>" role="button">
            <div class="org-avatar" style="background-color: <?= $avatarColor ?>;">
                <?= $iniciales ?>
            </div>
            <div class="org-name"><?= sanitize($nodo['nombre']) ?></div>
            <div class="org-cargo"><?= sanitize($nodo['cargo'] ?? '') ?></div>
            <?php if ($cantIE > 0): ?>
                <div class="org-ie-count">
                    <i class="bi bi-bullseye"></i> <?= $cantIE ?> IE
                </div>
            <?php endif; ?>
        </div>

        <?php if ($cantIE > 0): ?>
        <div class="collapse" id="<?= $nodeId ?>">
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

        <?php if (!empty($nodo['hijos'])): ?>
        <div class="org-children">
            <?php foreach ($nodo['hijos'] as $hijo): ?>
                <?= renderNodo($hijo) ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php
}
?>

<style>
/* ── ORGANIGRAMA TREE ─────────────────────────────────────── */
.org-wrapper {
    overflow-x: auto;
    padding: 20px 0;
}
.org-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
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
    border-radius: 14px;
    padding: 18px 16px 14px;
    text-align: center;
    width: 160px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    z-index: 2;
}
.org-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 16px rgba(59,130,246,0.12);
    transform: translateY(-2px);
}
.org-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    margin: 0 auto 8px;
    letter-spacing: 0.5px;
}
.org-name {
    font-size: 13px;
    font-weight: 600;
    color: #1b2333;
    line-height: 1.3;
    margin-bottom: 2px;
}
.org-cargo {
    font-size: 11px;
    color: #8a96aa;
    margin-bottom: 6px;
}
.org-ie-count {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    font-weight: 500;
    color: #3b82f6;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 1px 8px;
}

/* IE list desplegable */
.org-ie-list {
    background: #fff;
    border: 1px solid #eaecf0;
    border-radius: 10px;
    padding: 6px;
    margin-top: 6px;
    width: 260px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    z-index: 3;
    position: relative;
}
.org-ie-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
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
    font-size: 10px;
    font-weight: 600;
    color: #fff;
    padding: 1px 7px;
    border-radius: 8px;
    flex-shrink: 0;
}
.org-ie-name {
    font-size: 11px;
    font-weight: 500;
    color: #374151;
    flex-grow: 1;
    line-height: 1.2;
}
.org-ie-progress {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    width: 60px;
}
.org-ie-progress .progress {
    flex-grow: 1;
    height: 3px;
}
.org-ie-progress small {
    font-size: 10px;
    font-weight: 600;
    color: #6b7280;
    min-width: 26px;
    text-align: right;
}

/* Hijos y conectores */
.org-children {
    display: flex;
    gap: 12px;
    padding-top: 28px;
    position: relative;
    justify-content: center;
}
/* Línea vertical del padre al nivel de hijos */
.org-children::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 1px;
    height: 28px;
    background: #d1d5db;
}
/* Línea horizontal conectora entre hijos */
.org-children::after {
    content: '';
    position: absolute;
    top: 28px;
    height: 1px;
    background: #d1d5db;
}
/* Calcular ancho de la línea horizontal */
.org-children > .org-node {
    position: relative;
}
.org-children > .org-node::before {
    content: '';
    position: absolute;
    top: -28px;
    left: 50%;
    width: 1px;
    height: 28px;
    background: #d1d5db;
}
/* Línea horizontal entre primer y último hijo */
.org-children > .org-node:not(:only-child):first-child::after,
.org-children > .org-node:not(:only-child):last-child::after {
    content: '';
    position: absolute;
    top: 0px;
    height: 1px;
    background: #d1d5db;
}
.org-children > .org-node:not(:only-child):first-child::after {
    left: 50%;
    right: -6px;
}
.org-children > .org-node:not(:only-child):last-child::after {
    right: 50%;
    left: -6px;
}
.org-children > .org-node:not(:first-child):not(:last-child)::after {
    content: '';
    position: absolute;
    top: 0px;
    left: -6px;
    right: -6px;
    height: 1px;
    background: #d1d5db;
}

/* Responsive */
@media (max-width: 768px) {
    .org-children {
        flex-direction: column;
        align-items: center;
        gap: 0;
        padding-top: 16px;
        padding-left: 30px;
    }
    .org-children::before {
        height: 16px;
        left: 30px;
    }
    .org-children::after {
        display: none;
    }
    .org-children > .org-node::before {
        display: none;
    }
    .org-children > .org-node::after {
        display: none !important;
    }
    .org-children > .org-node {
        padding-top: 8px;
        position: relative;
    }
    .org-children > .org-node::before {
        display: block !important;
        content: '';
        position: absolute;
        top: 0;
        left: -15px;
        width: 15px;
        height: calc(50% + 4px);
        border-left: 1px solid #d1d5db;
        border-bottom: 1px solid #d1d5db;
        border-radius: 0 0 0 6px;
    }
}

/* Empty state */
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
    <div class="card-body">
        <?php if (empty($tree)): ?>
            <div class="org-empty">
                <i class="bi bi-diagram-2"></i>
                <h6>No hay estructura definida</h6>
                <p>Asigná la jerarquía en <a href="<?= BASE_URL ?>index.php?page=admin_responsables">Admin. Responsables</a> usando el campo "Reporta a".</p>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>Hacé click en cada persona para ver sus Iniciativas Estratégicas asignadas.
            </p>
            <div class="org-wrapper">
                <div class="org-tree">
                    <?php foreach ($tree as $raiz): ?>
                        <?= renderNodo($raiz, true) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
