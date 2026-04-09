<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class AnalisisNodo {

    static function getAllByHoja($pdo, $hojaId) {
        $stmt = $pdo->prepare("SELECT * FROM analisis_nodos WHERE hoja_id = ? ORDER BY orden ASC, nombre ASC");
        $stmt->execute([(int)$hojaId]);
        return $stmt->fetchAll();
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
                    observaciones = ?, forma = ?, color = ?, tamano = ?, orden = ?, hoja_destino_id = ?
                WHERE id = ?
            ");
            $hojaDestino = !empty($data['hoja_destino_id']) ? (int)$data['hoja_destino_id'] : null;
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
                $hojaDestino,
                $data['id']
            ]);
            flash('success', 'Elemento actualizado.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'analisis_nodo', $data['id'], $data['nombre'], 'editado');
        } else {
            $hojaDestino = !empty($data['hoja_destino_id']) ? (int)$data['hoja_destino_id'] : null;
            $stmt = $pdo->prepare("
                INSERT INTO analisis_nodos (hoja_id, nombre, tipo, descripcion, estado, observaciones, forma, color, tamano, orden, hoja_destino_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$data['hoja_id'],
                $data['nombre'],
                $data['tipo'],
                $data['descripcion'] ?: null,
                $data['estado'] ?? 'activo',
                $data['observaciones'] ?: null,
                $data['forma'] ?? 'box',
                $data['color'] ?? '#4A90D9',
                $data['tamano'] ?? 'M',
                (int)($data['orden'] ?? 0),
                $hojaDestino
            ]);
            flash('success', 'Elemento creado.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'analisis_nodo', $pdo->lastInsertId(), $data['nombre'], 'creado');
        }
        $hojaId = $data['hoja_id'] ?? '';
        redirect('index.php?page=analisis_ie&hoja=' . (int)$hojaId);
    }

    static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("SELECT nombre, hoja_id FROM analisis_nodos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $nombre = $row ? $row['nombre'] : 'Desconocido';
        $hojaId = $row ? $row['hoja_id'] : '';
        $pdo->prepare("DELETE FROM analisis_nodos WHERE id = ?")->execute([$id]);
        require_once __DIR__ . '/Bitacora.php';
        Bitacora::registrar($pdo, 'analisis_nodo', $id, $nombre, 'eliminado');
        flash('success', 'Elemento eliminado.');
        return $hojaId;
    }

    static function guardarPosicion($pdo, $id, $x, $y) {
        $stmt = $pdo->prepare("UPDATE analisis_nodos SET pos_x = ?, pos_y = ? WHERE id = ?");
        $stmt->execute([(float)$x, (float)$y, (int)$id]);
    }
}
