<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Riesgo {

    static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT r.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo,
                   pa.nombre AS plan_nombre
            FROM riesgos r
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = r.iniciativa_id
            LEFT JOIN planes_accion pa ON pa.id = r.plan_accion_id
            ORDER BY FIELD(r.nivel, 'critico', 'alto', 'medio', 'bajo'), r.id
        ");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT r.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo,
                   pa.nombre AS plan_nombre
            FROM riesgos r
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = r.iniciativa_id
            LEFT JOIN planes_accion pa ON pa.id = r.plan_accion_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        $nivel = calcularNivelRiesgo($data['probabilidad'], $data['impacto']);

        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE riesgos
                SET iniciativa_id = ?, plan_accion_id = ?, descripcion = ?,
                    probabilidad = ?, impacto = ?, nivel = ?,
                    plan_mitigacion = ?, responsable = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['iniciativa_id'] ?: null,
                $data['plan_accion_id'] ?: null,
                $data['descripcion'],
                $data['probabilidad'],
                $data['impacto'],
                $nivel,
                $data['plan_mitigacion'] ?: null,
                $data['responsable'] ?: null,
                $data['estado'] ?? 'abierto',
                $data['id']
            ]);
            flash('success', 'Riesgo actualizado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'riesgo', $data['id'], $data['descripcion'], 'editado');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO riesgos (iniciativa_id, plan_accion_id, descripcion, probabilidad, impacto, nivel, plan_mitigacion, responsable, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['iniciativa_id'] ?: null,
                $data['plan_accion_id'] ?: null,
                $data['descripcion'],
                $data['probabilidad'],
                $data['impacto'],
                $nivel,
                $data['plan_mitigacion'] ?: null,
                $data['responsable'] ?: null,
                $data['estado'] ?? 'abierto'
            ]);
            flash('success', 'Riesgo creado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'riesgo', $pdo->lastInsertId(), $data['descripcion'], 'creado');
        }
        redirect('index.php?page=riesgos');
    }

    static function getMatriz($pdo) {
        $stmt = $pdo->query("
            SELECT probabilidad, impacto, COUNT(*) AS total,
                   GROUP_CONCAT(id) AS ids
            FROM riesgos
            WHERE estado = 'abierto'
            GROUP BY probabilidad, impacto
        ");
        $rows = $stmt->fetchAll();

        $matriz = [];
        foreach ($rows as $row) {
            $matriz[$row['probabilidad']][$row['impacto']] = [
                'total' => $row['total'],
                'ids' => explode(',', $row['ids'])
            ];
        }
        return $matriz;
    }
}
