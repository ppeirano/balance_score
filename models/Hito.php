<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Hito {

    static function getByPlan($pdo, $planId) {
        $stmt = $pdo->prepare("
            SELECT * FROM hitos
            WHERE plan_accion_id = ?
            ORDER BY fecha_prevista
        ");
        $stmt->execute([$planId]);
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE hitos
                SET plan_accion_id = ?, nombre = ?, fecha_prevista = ?,
                    fecha_real = ?, estado = ?, observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['plan_accion_id'],
                $data['nombre'],
                $data['fecha_prevista'] ?: null,
                $data['fecha_real'] ?: null,
                $data['estado'] ?? 'pendiente',
                $data['observaciones'] ?: null,
                $data['id']
            ]);
            flash('success', 'Hito actualizado correctamente.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO hitos (plan_accion_id, nombre, fecha_prevista, fecha_real, estado, observaciones)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['plan_accion_id'],
                $data['nombre'],
                $data['fecha_prevista'] ?: null,
                $data['fecha_real'] ?: null,
                $data['estado'] ?? 'pendiente',
                $data['observaciones'] ?: null
            ]);
            flash('success', 'Hito creado correctamente.');
        }
        redirect('plan_detalle.php?id=' . $data['plan_accion_id']);
    }
}
