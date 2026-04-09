<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class AnalisisHoja {

    static function getAll($pdo) {
        return $pdo->query("SELECT * FROM analisis_hojas ORDER BY orden ASC, id ASC")->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM analisis_hojas WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    static function crear($pdo, $nombre = null) {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM analisis_hojas")->fetchColumn();
        $nombre = $nombre ?: 'Hoja ' . ($count + 1);
        $stmt = $pdo->prepare("INSERT INTO analisis_hojas (nombre, orden) VALUES (?, ?)");
        $stmt->execute([$nombre, $count]);
        return $pdo->lastInsertId();
    }

    static function renombrar($pdo, $id, $nombre) {
        $stmt = $pdo->prepare("UPDATE analisis_hojas SET nombre = ? WHERE id = ?");
        $stmt->execute([trim($nombre), (int)$id]);
    }

    static function eliminar($pdo, $id) {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM analisis_hojas")->fetchColumn();
        if ($total <= 1) {
            flash('warning', 'No se puede eliminar la última hoja.');
            return false;
        }
        $pdo->prepare("DELETE FROM analisis_hojas WHERE id = ?")->execute([(int)$id]);
        flash('success', 'Hoja eliminada.');
        return true;
    }
}
