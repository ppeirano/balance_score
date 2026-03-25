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
}
