<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Actividad {

    static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT a.*, pa.nombre AS plan_nombre, pa.codigo AS plan_codigo
            FROM actividades a
            JOIN planes_accion pa ON pa.id = a.plan_accion_id
            ORDER BY pa.id, a.id
        ");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT a.*, pa.nombre AS plan_nombre, pa.codigo AS plan_codigo
            FROM actividades a
            JOIN planes_accion pa ON pa.id = a.plan_accion_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function getByPlan($pdo, $planId) {
        $stmt = $pdo->prepare("
            SELECT * FROM actividades
            WHERE plan_accion_id = ?
            ORDER BY id
        ");
        $stmt->execute([$planId]);
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE actividades
                SET plan_accion_id = ?, codigo = ?, descripcion = ?,
                    responsable = ?, estado = ?, fecha_limite = ?, observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['plan_accion_id'],
                $data['codigo'],
                $data['descripcion'],
                $data['responsable'] ?: null,
                $data['estado'] ?? 'pendiente',
                $data['fecha_limite'] ?: null,
                $data['observaciones'] ?: null,
                $data['id']
            ]);
            flash('success', 'Actividad actualizada correctamente.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO actividades (plan_accion_id, codigo, descripcion, responsable, estado, fecha_limite, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['plan_accion_id'],
                $data['codigo'],
                $data['descripcion'],
                $data['responsable'] ?: null,
                $data['estado'] ?? 'pendiente',
                $data['fecha_limite'] ?: null,
                $data['observaciones'] ?: null
            ]);
            flash('success', 'Actividad creada correctamente.');
        }

        // Recalculate PDA avance
        $avance = calcularAvancePDA($pdo, $data['plan_accion_id']);
        $stmt = $pdo->prepare("UPDATE planes_accion SET avance = ? WHERE id = ?");
        $stmt->execute([$avance, $data['plan_accion_id']]);

        redirect('plan_detalle.php?id=' . $data['plan_accion_id']);
    }
}
