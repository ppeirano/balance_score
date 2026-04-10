<?php
require_once __DIR__ . '/../../models/Reunion.php';
require_once __DIR__ . '/../../models/Compromiso.php';

$reunion = Reunion::getById($pdo, $id);
if (!$reunion) {
    flash('error', 'Reunión no encontrada.');
    redirect('index.php?page=reuniones');
}
$compromisos = Compromiso::getByReunion($pdo, $id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Minuta - <?= sanitize($reunion['titulo']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #222;
            line-height: 1.4;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 30px;
            font-size: 12px;
        }
        .print-toolbar {
            background: #f3f4f6;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #d1d5db;
        }
        .print-toolbar button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
        }
        .print-toolbar button:hover { background: #2563eb; }
        .print-toolbar a {
            color: #4b5563;
            text-decoration: none;
            font-size: 13px;
        }
        .print-toolbar a:hover { text-decoration: underline; }

        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header .brand {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0 0 6px 0;
            color: #111827;
            font-weight: 700;
        }
        .header .meta {
            color: #6b7280;
            font-size: 12px;
        }
        .header .meta span { margin-right: 16px; }
        .header .meta strong { color: #374151; }

        .section {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 13px;
            color: #111827;
            margin: 0 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section .content {
            padding: 0;
        }

        .participantes-box {
            background: #f9fafb;
            padding: 8px 14px;
            border-left: 3px solid #3b82f6;
            border-radius: 3px;
            font-size: 12px;
        }

        .minuta-box {
            white-space: pre-wrap;
            font-size: 12px;
            line-height: 1.5;
            padding: 2px 0;
        }
        .minuta-box p { margin: 0 0 6px 0; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 4px;
        }
        table th {
            background: #f3f4f6;
            color: #374151;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 2px solid #d1d5db;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pendiente { background: #fef3c7; color: #92400e; }
        .badge-en_progreso { background: #dbeafe; color: #1e40af; }
        .badge-completado { background: #d1fae5; color: #065f46; }

        .empty {
            color: #9ca3af;
            font-style: italic;
            font-size: 13px;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
        }

        /* Estilos específicos para impresión */
        @media print {
            body {
                padding: 0;
                margin: 0;
                max-width: none;
            }
            .print-toolbar { display: none; }
            .header { page-break-after: avoid; }
            .section { page-break-inside: avoid; }
            @page {
                margin: 1.2cm 1.5cm;
                size: A4;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <div>
            <a href="<?= BASE_URL ?>index.php?page=reuniones&action=detalle&id=<?= (int)$id ?>">&larr; Volver al detalle</a>
        </div>
        <button onclick="window.print()">Imprimir / Guardar como PDF</button>
    </div>

    <div class="header">
        <div class="brand">BSC Temis Lostalo &middot; Minuta de Reunión</div>
        <h1><?= sanitize($reunion['titulo']) ?></h1>
        <div class="meta">
            <span><strong>Fecha:</strong> <?= formatDate($reunion['fecha']) ?></span>
            <?php if (!empty($reunion['iniciativa_codigo'])): ?>
                <span><strong>Iniciativa:</strong> <?= sanitize($reunion['iniciativa_codigo']) ?> - <?= sanitize($reunion['iniciativa_nombre'] ?? '') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($reunion['participantes'])): ?>
    <div class="section">
        <h2>Participantes</h2>
        <div class="participantes-box">
            <?= sanitize($reunion['participantes']) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>Minuta</h2>
        <div class="content">
            <?php if (!empty($reunion['minuta'])): ?>
                <div class="minuta-box"><?= sanitize($reunion['minuta']) ?></div>
            <?php else: ?>
                <p class="empty">Sin minuta registrada.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h2>Compromisos</h2>
        <?php if (!empty($compromisos)): ?>
        <table>
            <thead>
                <tr>
                    <th style="width:45%;">Descripción</th>
                    <th style="width:20%;">Responsable</th>
                    <th style="width:18%;">Fecha Límite</th>
                    <th style="width:17%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compromisos as $c): ?>
                <tr>
                    <td>
                        <?= sanitize($c['descripcion']) ?>
                        <?php if (!empty($c['plan_nombre'])): ?>
                        <br><small style="color:#6b7280;">PDA: <?= sanitize($c['plan_nombre']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($c['responsable'] ?? '-') ?></td>
                    <td><?= $c['fecha_limite'] ? formatDate($c['fecha_limite']) : '-' ?></td>
                    <td>
                        <span class="badge badge-<?= sanitize($c['estado']) ?>">
                            <?= sanitize(str_replace('_', ' ', $c['estado'])) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="empty">Sin compromisos registrados.</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        Documento generado el <?= date('d/m/Y H:i') ?> &middot; BSC Temis Lostalo
    </div>

    <script>
        // Auto-abrir diálogo de impresión si viene con ?auto=1
        if (window.location.search.includes('auto=1')) {
            window.addEventListener('load', () => setTimeout(() => window.print(), 500));
        }
    </script>
</body>
</html>
