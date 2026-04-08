<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class AnalisisNodo {

    static function getAll($pdo) {
        return $pdo->query("SELECT * FROM analisis_nodos ORDER BY orden ASC, nombre ASC")->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM analisis_nodos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE analisis_nodos
                SET nombre = ?, tipo = ?, descripcion = ?, estado = ?,
                    observaciones = ?, forma = ?, color = ?, tamano = ?, orden = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['nombre'],
                $data['tipo'],
                $data['descripcion'] ?: null,
                $data['estado'] ?? 'activo',
                $data['observaciones'] ?: null,
                $data['forma'] ?? 'box',
                $data['color'] ?? '#4A90D9',
                $data['tamano'] ?? 'M',
                (int)($data['orden'] ?? 0),
                $data['id']
            ]);
            flash('success', 'Elemento actualizado.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'analisis_nodo', $data['id'], $data['nombre'], 'editado');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO analisis_nodos (nombre, tipo, descripcion, estado, observaciones, forma, color, tamano, orden)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['nombre'],
                $data['tipo'],
                $data['descripcion'] ?: null,
                $data['estado'] ?? 'activo',
                $data['observaciones'] ?: null,
                $data['forma'] ?? 'box',
                $data['color'] ?? '#4A90D9',
                $data['tamano'] ?? 'M',
                (int)($data['orden'] ?? 0)
            ]);
            flash('success', 'Elemento creado.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'analisis_nodo', $pdo->lastInsertId(), $data['nombre'], 'creado');
        }
        redirect('index.php?page=analisis_ie');
    }

    static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("SELECT nombre FROM analisis_nodos WHERE id = ?");
        $stmt->execute([$id]);
        $nombre = $stmt->fetchColumn() ?: 'Desconocido';
        $pdo->prepare("DELETE FROM analisis_nodos WHERE id = ?")->execute([$id]);
        require_once __DIR__ . '/Bitacora.php';
        Bitacora::registrar($pdo, 'analisis_nodo', $id, $nombre, 'eliminado');
        flash('success', 'Elemento eliminado.');
    }

    static function guardarPosicion($pdo, $id, $x, $y) {
        $stmt = $pdo->prepare("UPDATE analisis_nodos SET pos_x = ?, pos_y = ? WHERE id = ?");
        $stmt->execute([(float)$x, (float)$y, (int)$id]);
    }
}
