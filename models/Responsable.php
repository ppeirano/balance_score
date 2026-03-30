<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Responsable {

    static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM responsables WHERE activo = 1 ORDER BY nombre");
        return $stmt->fetchAll();
    }

    static function getAllInclInactivos($pdo) {
        $stmt = $pdo->query("SELECT * FROM responsables ORDER BY activo DESC, nombre");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM responsables WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        $reportaA = !empty($data['reporta_a_id']) ? (int)$data['reporta_a_id'] : null;

        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("UPDATE responsables SET nombre = ?, cargo = ?, reporta_a_id = ?, activo = ? WHERE id = ?");
            $stmt->execute([
                trim($data['nombre']),
                trim($data['cargo'] ?? ''),
                $reportaA,
                isset($data['activo']) ? 1 : 0,
                $data['id']
            ]);
            flash('success', 'Responsable actualizado.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO responsables (nombre, cargo, reporta_a_id, activo) VALUES (?, ?, ?, 1)");
            $stmt->execute([
                trim($data['nombre']),
                trim($data['cargo'] ?? ''),
                $reportaA
            ]);
            flash('success', 'Responsable creado.');
        }
        redirect('index.php?page=admin_responsables');
    }

    static function eliminar($pdo, $id) {
        // Desvincular hijos antes de eliminar
        $pdo->prepare("UPDATE responsables SET reporta_a_id = NULL WHERE reporta_a_id = ?")->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM responsables WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Responsable eliminado.');
        redirect('index.php?page=admin_responsables');
    }

    /**
     * Retorna el árbol jerárquico completo de responsables activos.
     * Cada nodo tiene: datos del responsable + 'hijos' (array recursivo) + 'iniciativas' (IE asignadas)
     */
    static function getTree($pdo) {
        // Todos los responsables activos
        $stmt = $pdo->query("SELECT * FROM responsables WHERE activo = 1 ORDER BY nombre");
        $todos = $stmt->fetchAll();

        // IE asignadas por responsable (via planes_accion.owner)
        $ieByResp = [];
        $stmtIE = $pdo->query("
            SELECT DISTINCT ie.id, ie.codigo, ie.nombre, ie.perspectiva_id,
                   p.color AS perspectiva_color, p.nombre AS perspectiva_nombre,
                   pa.owner
            FROM iniciativas_estrategicas ie
            JOIN planes_accion pa ON pa.iniciativa_id = ie.id
            JOIN perspectivas p ON ie.perspectiva_id = p.id
            WHERE pa.owner IS NOT NULL AND pa.owner != ''
            ORDER BY ie.codigo
        ");
        foreach ($stmtIE->fetchAll() as $row) {
            $ieByResp[$row['owner']][$row['id']] = $row;
        }

        // Avance ponderado por IE
        $avanceByIE = [];
        $stmtAv = $pdo->query("SELECT iniciativa_id, avance, peso FROM planes_accion");
        $pdaRows = $stmtAv->fetchAll();
        $grouped = [];
        foreach ($pdaRows as $r) {
            $grouped[$r['iniciativa_id']][] = $r;
        }
        foreach ($grouped as $ieId => $pdas) {
            $pesoTotal = array_sum(array_column($pdas, 'peso'));
            $av = 0;
            if ($pesoTotal > 0) {
                foreach ($pdas as $pda) {
                    $av += ($pda['avance'] * $pda['peso'] / $pesoTotal);
                }
            }
            $avanceByIE[$ieId] = round($av);
        }

        // Riesgos abiertos por responsable
        $riesgosByResp = [];
        $stmtR = $pdo->query("
            SELECT r.id, r.descripcion, r.nivel, r.probabilidad, r.impacto, r.estado, r.responsable,
                   ie.codigo AS ie_codigo
            FROM riesgos r
            LEFT JOIN iniciativas_estrategicas ie ON r.iniciativa_id = ie.id
            WHERE r.responsable IS NOT NULL AND r.responsable != '' AND r.estado = 'abierto'
            ORDER BY FIELD(r.nivel, 'critico', 'alto', 'medio', 'bajo')
        ");
        foreach ($stmtR->fetchAll() as $row) {
            $riesgosByResp[$row['responsable']][] = $row;
        }

        // PDAs por IE y owner (para explotar IE)
        $pdaByIEOwner = [];
        $stmtPDA = $pdo->query("
            SELECT pa.id, pa.nombre, pa.avance, pa.estado, pa.iniciativa_id, pa.owner
            FROM planes_accion pa
            WHERE pa.owner IS NOT NULL AND pa.owner != ''
            ORDER BY pa.nombre
        ");
        foreach ($stmtPDA->fetchAll() as $row) {
            $pdaByIEOwner[$row['owner']][$row['iniciativa_id']][] = $row;
        }

        // Riesgos abiertos por IE (para explotar IE)
        $riesgosByIE = [];
        $stmtRI = $pdo->query("
            SELECT r.id, r.descripcion, r.nivel, r.iniciativa_id
            FROM riesgos r
            WHERE r.estado = 'abierto' AND r.iniciativa_id IS NOT NULL
            ORDER BY FIELD(r.nivel, 'critico', 'alto', 'medio', 'bajo')
        ");
        foreach ($stmtRI->fetchAll() as $row) {
            $riesgosByIE[$row['iniciativa_id']][] = $row;
        }

        // KPIs activos por IE (para explotar IE)
        $kpisByIE = [];
        $stmtK = $pdo->query("
            SELECT k.id, k.nombre, k.valor_actual, k.meta, k.unidad, k.estado_semaforo,
                   k.es_entero, k.tipo, k.valor_cualitativo, k.iniciativa_id,
                   k.umbral_verde, k.umbral_amarillo, k.direccion
            FROM kpis k
            WHERE k.activo = 1 AND k.iniciativa_id IS NOT NULL
            ORDER BY k.nombre
        ");
        foreach ($stmtK->fetchAll() as $row) {
            $kpisByIE[$row['iniciativa_id']][] = $row;
        }

        // Construir árbol
        $byId = [];
        foreach ($todos as &$r) {
            $r['hijos'] = [];
            $ies = $ieByResp[$r['nombre']] ?? [];
            foreach ($ies as &$ie) {
                $ie['avance'] = $avanceByIE[$ie['id']] ?? 0;
                $ie['planes'] = $pdaByIEOwner[$r['nombre']][$ie['id']] ?? [];
                $ie['riesgos_ie'] = $riesgosByIE[$ie['id']] ?? [];
                $ie['kpis'] = $kpisByIE[$ie['id']] ?? [];
            }
            $r['iniciativas'] = array_values($ies);
            $r['riesgos'] = $riesgosByResp[$r['nombre']] ?? [];
            $byId[$r['id']] = &$r;
        }
        unset($r);

        $tree = [];
        foreach ($byId as &$node) {
            if (!empty($node['reporta_a_id']) && isset($byId[$node['reporta_a_id']])) {
                $byId[$node['reporta_a_id']]['hijos'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }
}
