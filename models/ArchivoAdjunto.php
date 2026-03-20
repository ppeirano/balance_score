<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class ArchivoAdjunto {

    static function getByEntidad($pdo, $tipo, $id) {
        $stmt = $pdo->prepare("
            SELECT * FROM archivos_adjuntos
            WHERE entidad_tipo = ? AND entidad_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$tipo, $id]);
        return $stmt->fetchAll();
    }

    static function subir($pdo, $data, $files) {
        if (empty($files['archivo']['name'])) {
            flash('error', 'No se selecciono ningun archivo.');
            return false;
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $nombreOriginal = $files['archivo']['name'];
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nombreArchivo = uniqid('adj_') . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;

        if (!move_uploaded_file($files['archivo']['tmp_name'], $rutaDestino)) {
            flash('error', 'Error al subir el archivo.');
            return false;
        }

        $stmt = $pdo->prepare("
            INSERT INTO archivos_adjuntos (entidad_tipo, entidad_id, nombre_original, nombre_archivo, tipo_mime, tamano)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['entidad_tipo'],
            $data['entidad_id'],
            $nombreOriginal,
            $nombreArchivo,
            $files['archivo']['type'],
            $files['archivo']['size']
        ]);

        flash('success', 'Archivo subido correctamente.');
        return true;
    }

    static function descargar($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM archivos_adjuntos WHERE id = ?");
        $stmt->execute([$id]);
        $archivo = $stmt->fetch();

        if (!$archivo) {
            flash('error', 'Archivo no encontrado.');
            return false;
        }

        $ruta = __DIR__ . '/../uploads/' . $archivo['nombre_archivo'];
        if (!file_exists($ruta)) {
            flash('error', 'El archivo fisico no existe.');
            return false;
        }

        header('Content-Type: ' . ($archivo['tipo_mime'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $archivo['nombre_original'] . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM archivos_adjuntos WHERE id = ?");
        $stmt->execute([$id]);
        $archivo = $stmt->fetch();

        if (!$archivo) {
            flash('error', 'Archivo no encontrado.');
            return false;
        }

        // Delete physical file
        $ruta = __DIR__ . '/../uploads/' . $archivo['nombre_archivo'];
        if (file_exists($ruta)) {
            unlink($ruta);
        }

        // Delete DB record
        $stmt = $pdo->prepare("DELETE FROM archivos_adjuntos WHERE id = ?");
        $stmt->execute([$id]);

        flash('success', 'Archivo eliminado correctamente.');
        return true;
    }
}
