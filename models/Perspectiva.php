<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Perspectiva {

    static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM perspectivas ORDER BY orden");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM perspectivas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function getWithIniciativas($pdo) {
        $stmt = $pdo->query("
            SELECT p.*,
                   COUNT(ie.id) AS total_iniciativas,
                   GROUP_CONCAT(ie.nombre SEPARATOR '||') AS iniciativas_nombres
            FROM perspectivas p
            LEFT JOIN iniciativas_estrategicas ie ON ie.perspectiva_id = p.id
            GROUP BY p.id
            ORDER BY p.orden
        ");
        return $stmt->fetchAll();
    }
}
