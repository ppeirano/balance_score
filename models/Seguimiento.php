<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Seguimiento {

    static function getAll($pdo) {
        return $pdo->query("
            SELECT s.*,
                CASE s.entidad_tipo
                    WHEN 'plan' THEN CONCAT(pa.codigo, ' - ', pa.nombre)
                    WHEN 'actividad' THEN CONCAT('Act. ', a.codigo, ' - ', a.descripcion)
                    WHEN 'hito' THEN h.nombre
                END AS entidad_nombre
            FROM seguimientos s
            LEFT JOIN planes_accion pa ON s.entidad_tipo = 'plan' AND s.entidad_id = pa.id
            LEFT JOIN actividades a ON s.entidad_tipo = 'actividad' AND s.entidad_id = a.id
            LEFT JOIN hitos h ON s.entidad_tipo = 'hito' AND s.entidad_id = h.id
            ORDER BY s.fecha
        ")->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM seguimientos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function getAllAsJson($pdo) {
        $seguimientos = self::getAll($pdo);
        $colores = [
            'plan' => '#0d6efd',
            'actividad' => '#198754',
            'hito' => '#fd7e14',
        ];
        $events = [];
        foreach ($seguimientos as $s) {
            $start = $s['fecha'];
            if ($s['hora']) {
                $start .= 'T' . $s['hora'];
            }
            $events[] = [
                'id' => $s['id'],
                'title' => $s['titulo'],
                'start' => $start,
                'backgroundColor' => $s['completado'] ? '#6c757d' : ($colores[$s['entidad_tipo']] ?? '#0d6efd'),
                'borderColor' => $s['completado'] ? '#6c757d' : ($colores[$s['entidad_tipo']] ?? '#0d6efd'),
                'extendedProps' => [
                    'entidad_tipo' => $s['entidad_tipo'],
                    'entidad_id' => $s['entidad_id'],
                    'entidad_nombre' => $s['entidad_nombre'] ?? '',
                    'descripcion' => $s['descripcion'] ?? '',
                    'hora' => $s['hora'] ?? '',
                    'completado' => (bool) $s['completado'],
                ],
            ];
        }
        return json_encode($events);
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE seguimientos
                SET entidad_tipo = ?, entidad_id = ?, titulo = ?, descripcion = ?,
                    fecha = ?, hora = ?, completado = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['entidad_tipo'],
                $data['entidad_id'],
                $data['titulo'],
                $data['descripcion'] ?: null,
                $data['fecha'],
                $data['hora'] ?: null,
                isset($data['completado']) ? 1 : 0,
                $data['id'],
            ]);
            flash('success', 'Seguimiento actualizado.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO seguimientos (entidad_tipo, entidad_id, titulo, descripcion, fecha, hora, completado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['entidad_tipo'],
                $data['entidad_id'],
                $data['titulo'],
                $data['descripcion'] ?: null,
                $data['fecha'],
                $data['hora'] ?: null,
                isset($data['completado']) ? 1 : 0,
            ]);
            flash('success', 'Seguimiento creado.');
        }
        redirect('index.php?page=calendario');
    }

    static function eliminar($pdo, $id) {
        $pdo->prepare("DELETE FROM seguimientos WHERE id = ?")->execute([$id]);
        flash('success', 'Seguimiento eliminado.');
        redirect('index.php?page=calendario');
    }
}
