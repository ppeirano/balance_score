<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Iniciativa {

    static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT ie.*, p.nombre AS perspectiva_nombre, p.color AS perspectiva_color
            FROM iniciativas_estrategicas ie
            JOIN perspectivas p ON p.id = ie.perspectiva_id
            ORDER BY ie.orden
        ");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT ie.*, p.nombre AS perspectiva_nombre, p.color AS perspectiva_color
            FROM iniciativas_estrategicas ie
            JOIN perspectivas p ON p.id = ie.perspectiva_id
            WHERE ie.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function getByPerspectiva($pdo, $perspId) {
        $stmt = $pdo->prepare("
            SELECT ie.*, p.nombre AS perspectiva_nombre
            FROM iniciativas_estrategicas ie
            JOIN perspectivas p ON p.id = ie.perspectiva_id
            WHERE ie.perspectiva_id = ?
            ORDER BY ie.orden
        ");
        $stmt->execute([$perspId]);
        return $stmt->fetchAll();
    }

    static function getWithPlanes($pdo, $id) {
        $iniciativa = self::getById($pdo, $id);
        if (!$iniciativa) return null;

        $stmt = $pdo->prepare("
            SELECT * FROM planes_accion
            WHERE iniciativa_id = ?
            ORDER BY prioridad
        ");
        $stmt->execute([$id]);
        $iniciativa['planes'] = $stmt->fetchAll();

        return $iniciativa;
    }

    static function calcularAvance($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT peso, avance FROM planes_accion
            WHERE iniciativa_id = ?
        ");
        $stmt->execute([$id]);
        $planes = $stmt->fetchAll();

        $totalPeso = 0;
        $sumaPonderada = 0;
        foreach ($planes as $plan) {
            $totalPeso += $plan['peso'];
            $sumaPonderada += $plan['peso'] * $plan['avance'];
        }

        if ($totalPeso == 0) return 0;
        return round($sumaPonderada / $totalPeso);
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("UPDATE iniciativas_estrategicas SET
                perspectiva_id = ?, periodo_id = ?, codigo = ?, nombre = ?, descripcion = ?, orden = ?
                WHERE id = ?");
            $stmt->execute([
                $data['perspectiva_id'],
                $data['periodo_id'] ?: null,
                trim($data['codigo']),
                trim($data['nombre']),
                trim($data['descripcion'] ?? ''),
                (int)($data['orden'] ?? 0),
                $data['id']
            ]);
            flash('success', 'Iniciativa actualizada.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO iniciativas_estrategicas
                (perspectiva_id, periodo_id, codigo, nombre, descripcion, orden)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['perspectiva_id'],
                $data['periodo_id'] ?: null,
                trim($data['codigo']),
                trim($data['nombre']),
                trim($data['descripcion'] ?? ''),
                (int)($data['orden'] ?? 0)
            ]);
            flash('success', 'Iniciativa creada.');
        }
        redirect('index.php?page=iniciativas');
    }

    static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM iniciativas_estrategicas WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Iniciativa eliminada.');
        redirect('index.php?page=iniciativas');
    }
}
