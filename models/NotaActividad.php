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

    static function subirImagen($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['ok' => false, 'error' => 'Tipo de archivo no permitido.'];
        }

        $uploadDir = __DIR__ . '/../uploads/notas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
        $nombreArchivo = uniqid('nota_') . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;

        if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
            return ['ok' => false, 'error' => 'Error al guardar la imagen.'];
        }

        return ['ok' => true, 'filename' => $nombreArchivo];
    }

    static function guardar($pdo, $data, $files = []) {
        $imagen = $data['imagen'] ?? null;

        // Si se subió una imagen por file input (fallback)
        if (!$imagen && !empty($files['imagen']['name'])) {
            $result = self::subirImagen($files['imagen']);
            if ($result['ok']) {
                $imagen = $result['filename'];
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO notas_actividad (actividad_id, texto, imagen)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['actividad_id'],
            $data['texto'],
            $imagen
        ]);
        flash('success', 'Nota agregada correctamente.');
        redirect('index.php?page=actividades&action=editar&id=' . $data['actividad_id']);
    }

    static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("SELECT actividad_id, imagen FROM notas_actividad WHERE id = ?");
        $stmt->execute([$id]);
        $nota = $stmt->fetch();

        // Eliminar archivo de imagen si existe
        if ($nota && $nota['imagen']) {
            $ruta = __DIR__ . '/../uploads/notas/' . $nota['imagen'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }

        $pdo->prepare("DELETE FROM notas_actividad WHERE id = ?")->execute([$id]);

        flash('success', 'Nota eliminada.');
        if ($nota) {
            redirect('index.php?page=actividades&action=editar&id=' . $nota['actividad_id']);
        } else {
            redirect('index.php?page=planes');
        }
    }
}
