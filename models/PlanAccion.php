<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class PlanAccion {

    static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT pa.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo
            FROM planes_accion pa
            JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
            ORDER BY CAST(SUBSTRING(ie.codigo, 3) AS UNSIGNED), pa.prioridad
        ");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT pa.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo,
                   p.nombre AS perspectiva_nombre
            FROM planes_accion pa
            JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
            JOIN perspectivas p ON p.id = ie.perspectiva_id
            WHERE pa.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function getByIniciativa($pdo, $inicId) {
        $stmt = $pdo->prepare("
            SELECT * FROM planes_accion
            WHERE iniciativa_id = ?
            ORDER BY prioridad
        ");
        $stmt->execute([$inicId]);
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE planes_accion
                SET iniciativa_id = ?, periodo_id = ?, codigo = ?, nombre = ?,
                    owner = ?, fecha_inicio = ?, fecha_fin = ?,
                    prioridad = ?, peso = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['iniciativa_id'],
                $data['periodo_id'] ?: null,
                $data['codigo'],
                $data['nombre'],
                $data['owner'] ?: null,
                $data['fecha_inicio'] ?: null,
                $data['fecha_fin'] ?: null,
                $data['prioridad'] ?? 1,
                $data['peso'] ?? 0,
                $data['estado'] ?? 'pendiente',
                $data['id']
            ]);
            flash('success', 'Plan de accion actualizado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'plan_accion', $data['id'], $data['nombre'], 'editado');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO planes_accion (iniciativa_id, periodo_id, codigo, nombre, owner, fecha_inicio, fecha_fin, prioridad, peso, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['iniciativa_id'],
                $data['periodo_id'] ?: null,
                $data['codigo'],
                $data['nombre'],
                $data['owner'] ?: null,
                $data['fecha_inicio'] ?: null,
                $data['fecha_fin'] ?: null,
                $data['prioridad'] ?? 1,
                $data['peso'] ?? 0,
                $data['estado'] ?? 'pendiente'
            ]);
            flash('success', 'Plan de accion creado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'plan_accion', $pdo->lastInsertId(), $data['nombre'], 'creado');
        }
        redirect('index.php?page=planes');
    }

    static function getOwners($pdo) {
        $stmt = $pdo->query("SELECT DISTINCT owner FROM planes_accion WHERE owner IS NOT NULL AND owner != '' ORDER BY owner");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
