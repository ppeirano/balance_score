<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class NotaActividad {

    static function getByActividad($pdo, $actividadId) {
        $stmt = $pdo->prepare("
            SELECT * FROM notas_actividad
            WHERE actividad_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$actividadId]);
        return $stmt->fetchAll();
    }

    static function guardar($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO notas_actividad (actividad_id, texto)
            VALUES (?, ?)
        ");
        $stmt->execute([
            $data['actividad_id'],
            $data['texto']
        ]);
        flash('success', 'Nota agregada correctamente.');
        redirect('index.php?page=actividades&action=editar&id=' . $data['actividad_id']);
    }

    static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("SELECT actividad_id FROM notas_actividad WHERE id = ?");
        $stmt->execute([$id]);
        $nota = $stmt->fetch();

        $pdo->prepare("DELETE FROM notas_actividad WHERE id = ?")->execute([$id]);

        flash('success', 'Nota eliminada.');
        if ($nota) {
            redirect('index.php?page=actividades&action=editar&id=' . $nota['actividad_id']);
        } else {
            redirect('index.php?page=planes');
        }
    }
}
