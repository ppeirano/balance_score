<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Periodo {

    static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM periodos_estrategicos ORDER BY fecha_inicio DESC");
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE periodos_estrategicos
                SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, activo = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['nombre'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['activo'] ?? 0,
                $data['id']
            ]);
            flash('success', 'Periodo actualizado correctamente.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO periodos_estrategicos (nombre, fecha_inicio, fecha_fin, activo)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['nombre'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['activo'] ?? 0
            ]);
            flash('success', 'Periodo creado correctamente.');
        }

        // If this period is set as active, deactivate others
        if (!empty($data['activo'])) {
            $id = !empty($data['id']) ? $data['id'] : $pdo->lastInsertId();
            $stmt = $pdo->prepare("UPDATE periodos_estrategicos SET activo = 0 WHERE id != ?");
            $stmt->execute([$id]);
        }

        redirect('index.php?page=periodos');
    }
}
