<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Recurso {

    static function getByActividad($pdo, $actId) {
        $stmt = $pdo->prepare("
            SELECT * FROM recursos
            WHERE actividad_id = ?
            ORDER BY id
        ");
        $stmt->execute([$actId]);
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE recursos
                SET actividad_id = ?, tipo = ?, descripcion = ?,
                    cantidad = ?, unidad = ?, costo_estimado = ?, notas = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['actividad_id'],
                $data['tipo'] ?? 'humano',
                $data['descripcion'],
                $data['cantidad'] ?: null,
                $data['unidad'] ?: null,
                $data['costo_estimado'] ?: null,
                $data['notas'] ?: null,
                $data['id']
            ]);
            flash('success', 'Recurso actualizado correctamente.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO recursos (actividad_id, tipo, descripcion, cantidad, unidad, costo_estimado, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['actividad_id'],
                $data['tipo'] ?? 'humano',
                $data['descripcion'],
                $data['cantidad'] ?: null,
                $data['unidad'] ?: null,
                $data['costo_estimado'] ?: null,
                $data['notas'] ?: null
            ]);
            flash('success', 'Recurso creado correctamente.');
        }
        $stmt2 = $pdo->prepare("SELECT plan_accion_id FROM actividades WHERE id = ?");
        $stmt2->execute([$data['actividad_id']]);
        $act = $stmt2->fetch();
        redirect('index.php?page=planes&action=detalle&id=' . ($act['plan_accion_id'] ?? ''));
    }
}
