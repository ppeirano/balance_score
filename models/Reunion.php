<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Reunion {

    static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT r.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo
            FROM reuniones r
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = r.iniciativa_id
            ORDER BY r.fecha DESC
        ");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT r.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo
            FROM reuniones r
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = r.iniciativa_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE reuniones
                SET iniciativa_id = ?, titulo = ?, fecha = ?, participantes = ?, minuta = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['iniciativa_id'] ?: null,
                $data['titulo'],
                $data['fecha'],
                $data['participantes'] ?: null,
                $data['minuta'] ?: null,
                $data['id']
            ]);
            flash('success', 'Reunion actualizada correctamente.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO reuniones (iniciativa_id, titulo, fecha, participantes, minuta)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['iniciativa_id'] ?: null,
                $data['titulo'],
                $data['fecha'],
                $data['participantes'] ?: null,
                $data['minuta'] ?: null
            ]);
            flash('success', 'Reunion creada correctamente.');
        }
        redirect('reuniones.php');
    }
}
