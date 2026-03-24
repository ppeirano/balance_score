<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Responsable {

    static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM responsables WHERE activo = 1 ORDER BY nombre");
        return $stmt->fetchAll();
    }

    static function getAllInclInactivos($pdo) {
        $stmt = $pdo->query("SELECT * FROM responsables ORDER BY activo DESC, nombre");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM responsables WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("UPDATE responsables SET nombre = ?, cargo = ?, activo = ? WHERE id = ?");
            $stmt->execute([
                trim($data['nombre']),
                trim($data['cargo'] ?? ''),
                isset($data['activo']) ? 1 : 0,
                $data['id']
            ]);
            flash('success', 'Responsable actualizado.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO responsables (nombre, cargo, activo) VALUES (?, ?, 1)");
            $stmt->execute([
                trim($data['nombre']),
                trim($data['cargo'] ?? '')
            ]);
            flash('success', 'Responsable creado.');
        }
        redirect('index.php?page=admin_responsables');
    }

    static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM responsables WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Responsable eliminado.');
        redirect('index.php?page=admin_responsables');
    }
}
