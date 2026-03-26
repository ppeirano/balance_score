<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Proyecto {

    static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT p.*, pe.nombre AS periodo_nombre
            FROM proyectos p
            LEFT JOIN periodos_estrategicos pe ON pe.id = p.periodo_id
            ORDER BY p.prioridad, p.nombre
        ");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT p.*, pe.nombre AS periodo_nombre
            FROM proyectos p
            LEFT JOIN periodos_estrategicos pe ON pe.id = p.periodo_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE proyectos
                SET nombre = ?, descripcion = ?, responsable = ?, estado = ?,
                    fecha_inicio = ?, fecha_fin = ?, presupuesto = ?,
                    prioridad = ?, avance = ?, periodo_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?: null,
                $data['responsable'] ?: null,
                $data['estado'] ?? 'pendiente',
                $data['fecha_inicio'] ?: null,
                $data['fecha_fin'] ?: null,
                $data['presupuesto'] ?: null,
                $data['prioridad'] ?? 1,
                $data['avance'] ?? 0,
                $data['periodo_id'] ?: null,
                $data['id']
            ]);
            flash('success', 'Proyecto actualizado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'proyecto', $data['id'], $data['nombre'], 'editado');
            redirect('index.php?page=proyectos&action=detalle&id=' . $data['id']);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO proyectos (nombre, descripcion, responsable, estado, fecha_inicio, fecha_fin, presupuesto, prioridad, avance, periodo_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?: null,
                $data['responsable'] ?: null,
                $data['estado'] ?? 'pendiente',
                $data['fecha_inicio'] ?: null,
                $data['fecha_fin'] ?: null,
                $data['presupuesto'] ?: null,
                $data['prioridad'] ?? 1,
                $data['avance'] ?? 0,
                $data['periodo_id'] ?: null
            ]);
            $newId = $pdo->lastInsertId();
            flash('success', 'Proyecto creado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'proyecto', $newId, $data['nombre'], 'creado');
            redirect('index.php?page=proyectos&action=detalle&id=' . $newId);
        }
    }

    static function getVinculos($pdo, $proyectoId) {
        $stmt = $pdo->prepare("
            SELECT pv.*,
                CASE
                    WHEN pv.entidad_tipo = 'iniciativa' THEN ie.codigo
                    WHEN pv.entidad_tipo = 'plan_accion' THEN pa.codigo
                END AS entidad_codigo,
                CASE
                    WHEN pv.entidad_tipo = 'iniciativa' THEN ie.nombre
                    WHEN pv.entidad_tipo = 'plan_accion' THEN pa.nombre
                END AS entidad_nombre
            FROM proyecto_vinculos pv
            LEFT JOIN iniciativas_estrategicas ie ON pv.entidad_tipo = 'iniciativa' AND ie.id = pv.entidad_id
            LEFT JOIN planes_accion pa ON pv.entidad_tipo = 'plan_accion' AND pa.id = pv.entidad_id
            WHERE pv.proyecto_id = ?
            ORDER BY pv.entidad_tipo, pv.id
        ");
        $stmt->execute([$proyectoId]);
        return $stmt->fetchAll();
    }

    static function guardarVinculo($pdo, $data) {
        // Evitar duplicados
        $stmt = $pdo->prepare("SELECT id FROM proyecto_vinculos WHERE proyecto_id = ? AND entidad_tipo = ? AND entidad_id = ?");
        $stmt->execute([$data['proyecto_id'], $data['entidad_tipo'], $data['entidad_id']]);
        if ($stmt->fetch()) {
            flash('warning', 'Este vínculo ya existe.');
            redirect('index.php?page=proyectos&action=detalle&id=' . $data['proyecto_id']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO proyecto_vinculos (proyecto_id, entidad_tipo, entidad_id) VALUES (?, ?, ?)");
        $stmt->execute([$data['proyecto_id'], $data['entidad_tipo'], $data['entidad_id']]);
        flash('success', 'Vínculo agregado.');
        redirect('index.php?page=proyectos&action=detalle&id=' . $data['proyecto_id']);
    }

    static function eliminarVinculo($pdo, $id) {
        $stmt = $pdo->prepare("SELECT proyecto_id FROM proyecto_vinculos WHERE id = ?");
        $stmt->execute([$id]);
        $vinculo = $stmt->fetch();
        $pdo->prepare("DELETE FROM proyecto_vinculos WHERE id = ?")->execute([$id]);
        flash('success', 'Vínculo eliminado.');
        if ($vinculo) {
            redirect('index.php?page=proyectos&action=detalle&id=' . $vinculo['proyecto_id']);
        } else {
            redirect('index.php?page=proyectos');
        }
    }

    static function getEntregables($pdo, $proyectoId) {
        $stmt = $pdo->prepare("
            SELECT * FROM proyecto_entregables
            WHERE proyecto_id = ?
            ORDER BY fecha_prevista, id
        ");
        $stmt->execute([$proyectoId]);
        return $stmt->fetchAll();
    }

    static function guardarEntregable($pdo, $data) {
        if (!empty($data['entregable_id'])) {
            $stmt = $pdo->prepare("
                UPDATE proyecto_entregables
                SET nombre = ?, descripcion = ?, responsable = ?,
                    fecha_prevista = ?, fecha_real = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['nombre'],
                $data['entregable_descripcion'] ?: null,
                $data['entregable_responsable'] ?: null,
                $data['fecha_prevista'] ?: null,
                $data['fecha_real'] ?: null,
                $data['entregable_estado'] ?? 'pendiente',
                $data['entregable_id']
            ]);
            flash('success', 'Entregable actualizado.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO proyecto_entregables (proyecto_id, nombre, descripcion, responsable, fecha_prevista, fecha_real, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['proyecto_id'],
                $data['nombre'],
                $data['entregable_descripcion'] ?: null,
                $data['entregable_responsable'] ?: null,
                $data['fecha_prevista'] ?: null,
                $data['fecha_real'] ?: null,
                $data['entregable_estado'] ?? 'pendiente'
            ]);
            flash('success', 'Entregable agregado.');
        }
        redirect('index.php?page=proyectos&action=detalle&id=' . $data['proyecto_id']);
    }

    static function eliminarEntregable($pdo, $id) {
        $stmt = $pdo->prepare("SELECT proyecto_id FROM proyecto_entregables WHERE id = ?");
        $stmt->execute([$id]);
        $entregable = $stmt->fetch();
        $pdo->prepare("DELETE FROM proyecto_entregables WHERE id = ?")->execute([$id]);
        flash('success', 'Entregable eliminado.');
        if ($entregable) {
            redirect('index.php?page=proyectos&action=detalle&id=' . $entregable['proyecto_id']);
        } else {
            redirect('index.php?page=proyectos');
        }
    }

    // --- Actividades de proyecto ---

    static function getActividades($pdo, $proyectoId) {
        $stmt = $pdo->prepare("
            SELECT * FROM proyecto_actividades
            WHERE proyecto_id = ?
            ORDER BY orden, fecha_inicio, id
        ");
        $stmt->execute([$proyectoId]);
        return $stmt->fetchAll();
    }

    static function guardarActividad($pdo, $data) {
        if (!empty($data['actividad_id'])) {
            $stmt = $pdo->prepare("
                UPDATE proyecto_actividades
                SET nombre = ?, descripcion = ?, responsable = ?, fecha_inicio = ?, fecha_fin = ?, estado = ?, orden = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['act_nombre'],
                $data['act_descripcion'] ?: null,
                $data['act_responsable'] ?: null,
                $data['act_fecha_inicio'] ?: null,
                $data['act_fecha_fin'] ?: null,
                $data['act_estado'] ?? 'pendiente',
                $data['act_orden'] ?? 0,
                $data['actividad_id']
            ]);
            flash('success', 'Actividad actualizada.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO proyecto_actividades (proyecto_id, nombre, descripcion, responsable, fecha_inicio, fecha_fin, estado, orden)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['proyecto_id'],
                $data['act_nombre'],
                $data['act_descripcion'] ?: null,
                $data['act_responsable'] ?: null,
                $data['act_fecha_inicio'] ?: null,
                $data['act_fecha_fin'] ?: null,
                $data['act_estado'] ?? 'pendiente',
                $data['act_orden'] ?? 0
            ]);
            flash('success', 'Actividad agregada.');
        }
        redirect('index.php?page=proyectos&action=detalle&id=' . $data['proyecto_id']);
    }

    static function eliminarActividad($pdo, $id) {
        $stmt = $pdo->prepare("SELECT proyecto_id FROM proyecto_actividades WHERE id = ?");
        $stmt->execute([$id]);
        $act = $stmt->fetch();
        $pdo->prepare("DELETE FROM proyecto_actividades WHERE id = ?")->execute([$id]);
        flash('success', 'Actividad eliminada.');
        if ($act) {
            redirect('index.php?page=proyectos&action=detalle&id=' . $act['proyecto_id']);
        } else {
            redirect('index.php?page=proyectos');
        }
    }

    // --- Notas de proyecto ---

    static function getNotas($pdo, $proyectoId) {
        $stmt = $pdo->prepare("
            SELECT * FROM notas_proyecto
            WHERE proyecto_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$proyectoId]);
        return $stmt->fetchAll();
    }

    static function subirImagenNota($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['ok' => false, 'error' => 'Tipo de archivo no permitido.'];
        }
        $uploadDir = __DIR__ . '/../uploads/notas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
        $nombreArchivo = uniqid('pnota_') . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $nombreArchivo)) {
            return ['ok' => false, 'error' => 'Error al guardar la imagen.'];
        }
        return ['ok' => true, 'filename' => $nombreArchivo];
    }

    static function guardarNota($pdo, $data, $files = []) {
        $imagen = $data['imagen'] ?? null;
        if (!$imagen && !empty($files['imagen']['name'])) {
            $result = self::subirImagenNota($files['imagen']);
            if ($result['ok']) $imagen = $result['filename'];
        }
        $stmt = $pdo->prepare("INSERT INTO notas_proyecto (proyecto_id, texto, imagen) VALUES (?, ?, ?)");
        $stmt->execute([$data['proyecto_id'], $data['texto'], $imagen]);
        flash('success', 'Nota agregada.');
        redirect('index.php?page=proyectos&action=detalle&id=' . $data['proyecto_id']);
    }

    static function eliminarNota($pdo, $id) {
        $stmt = $pdo->prepare("SELECT proyecto_id, imagen FROM notas_proyecto WHERE id = ?");
        $stmt->execute([$id]);
        $nota = $stmt->fetch();
        if ($nota && $nota['imagen']) {
            $ruta = __DIR__ . '/../uploads/notas/' . $nota['imagen'];
            if (file_exists($ruta)) unlink($ruta);
        }
        $pdo->prepare("DELETE FROM notas_proyecto WHERE id = ?")->execute([$id]);
        flash('success', 'Nota eliminada.');
        if ($nota) {
            redirect('index.php?page=proyectos&action=detalle&id=' . $nota['proyecto_id']);
        } else {
            redirect('index.php?page=proyectos');
        }
    }
}
