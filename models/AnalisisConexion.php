<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class AnalisisConexion {

    static function getAllByHoja($pdo, $hojaId) {
        $stmt = $pdo->prepare("
            SELECT c.*, o.nombre AS origen_nombre, d.nombre AS destino_nombre
            FROM analisis_conexiones c
            JOIN analisis_nodos o ON c.nodo_origen_id = o.id
            JOIN analisis_nodos d ON c.nodo_destino_id = d.id
            WHERE o.hoja_id = ?
            ORDER BY c.id
        ");
        $stmt->execute([(int)$hojaId]);
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE analisis_conexiones
                SET nodo_origen_id = ?, nodo_destino_id = ?, tipo_relacion = ?, descripcion = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['nodo_origen_id'],
                (int)$data['nodo_destino_id'],
                $data['tipo_relacion'],
                $data['descripcion'] ?: null,
                $data['id']
            ]);
            flash('success', 'Conexión actualizada.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'analisis_conexion', $data['id'], $data['tipo_relacion'], 'editado');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO analisis_conexiones (nodo_origen_id, nodo_destino_id, tipo_relacion, descripcion)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$data['nodo_origen_id'],
                (int)$data['nodo_destino_id'],
                $data['tipo_relacion'],
                $data['descripcion'] ?: null
            ]);
            flash('success', 'Conexión creada.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'analisis_conexion', $pdo->lastInsertId(), $data['tipo_relacion'], 'creado');
        }
        $hojaId = $data['hoja_id'] ?? '';
        redirect('index.php?page=analisis_ie&hoja=' . (int)$hojaId);
    }

    static function eliminar($pdo, $id) {
        $pdo->prepare("DELETE FROM analisis_conexiones WHERE id = ?")->execute([$id]);
        require_once __DIR__ . '/Bitacora.php';
        Bitacora::registrar($pdo, 'analisis_conexion', $id, '', 'eliminado');
        flash('success', 'Conexión eliminada.');
    }
}
