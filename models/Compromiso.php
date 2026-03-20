<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Compromiso {

    static function getByReunion($pdo, $reunionId) {
        $stmt = $pdo->prepare("
            SELECT c.*, a.descripcion AS actividad_descripcion, pa.nombre AS plan_nombre
            FROM compromisos c
            LEFT JOIN actividades a ON a.id = c.actividad_id
            LEFT JOIN planes_accion pa ON pa.id = c.plan_accion_id
            WHERE c.reunion_id = ?
            ORDER BY c.fecha_limite
        ");
        $stmt->execute([$reunionId]);
        return $stmt->fetchAll();
    }

    static function getPendientes($pdo) {
        $stmt = $pdo->query("
            SELECT c.*, r.titulo AS reunion_titulo, r.fecha AS reunion_fecha
            FROM compromisos c
            JOIN reuniones r ON r.id = c.reunion_id
            WHERE c.estado != 'completado'
            ORDER BY c.fecha_limite
        ");
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE compromisos
                SET reunion_id = ?, actividad_id = ?, plan_accion_id = ?,
                    descripcion = ?, responsable = ?, fecha_limite = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['reunion_id'],
                $data['actividad_id'] ?: null,
                $data['plan_accion_id'] ?: null,
                $data['descripcion'],
                $data['responsable'] ?: null,
                $data['fecha_limite'] ?: null,
                $data['estado'] ?? 'pendiente',
                $data['id']
            ]);
            flash('success', 'Compromiso actualizado correctamente.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO compromisos (reunion_id, actividad_id, plan_accion_id, descripcion, responsable, fecha_limite, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['reunion_id'],
                $data['actividad_id'] ?: null,
                $data['plan_accion_id'] ?: null,
                $data['descripcion'],
                $data['responsable'] ?: null,
                $data['fecha_limite'] ?: null,
                $data['estado'] ?? 'pendiente'
            ]);
            flash('success', 'Compromiso creado correctamente.');
        }
        redirect('reunion_detalle.php?id=' . $data['reunion_id']);
    }
}
