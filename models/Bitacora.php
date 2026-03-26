<?php

class Bitacora {

    static function registrar($pdo, $entidadTipo, $entidadId, $entidadNombre, $accion, $descripcion = null) {
        $stmt = $pdo->prepare("
            INSERT INTO bitacora (entidad_tipo, entidad_id, entidad_nombre, accion, descripcion)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$entidadTipo, $entidadId, $entidadNombre, $accion, $descripcion]);
    }

    static function getAll($pdo, $filtros = [], $limit = 20, $offset = 0) {
        $where = [];
        $params = [];

        if (!empty($filtros['entidad_tipo'])) {
            $where[] = 'entidad_tipo = ?';
            $params[] = $filtros['entidad_tipo'];
        }
        if (!empty($filtros['accion'])) {
            $where[] = 'accion = ?';
            $params[] = $filtros['accion'];
        }

        $sql = "SELECT * FROM bitacora";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    static function contar($pdo, $filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['entidad_tipo'])) {
            $where[] = 'entidad_tipo = ?';
            $params[] = $filtros['entidad_tipo'];
        }
        if (!empty($filtros['accion'])) {
            $where[] = 'accion = ?';
            $params[] = $filtros['accion'];
        }

        $sql = "SELECT COUNT(*) FROM bitacora";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
