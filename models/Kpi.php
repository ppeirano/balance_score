<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Kpi {

    static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT k.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo
            FROM kpis k
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = k.iniciativa_id
            ORDER BY k.id
        ");
        return $stmt->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT k.*, ie.nombre AS iniciativa_nombre, ie.codigo AS iniciativa_codigo
            FROM kpis k
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = k.iniciativa_id
            WHERE k.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("
                UPDATE kpis
                SET iniciativa_id = ?, plan_accion_id = ?, periodo_id = ?,
                    nombre = ?, tipo = ?, unidad = ?, meta = ?,
                    umbral_verde = ?, umbral_amarillo = ?, direccion = ?,
                    escala_cualitativa = ?, opciones_cualitativas = ?,
                    frecuencia = ?, activo = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['iniciativa_id'] ?: null,
                $data['plan_accion_id'] ?: null,
                $data['periodo_id'] ?: null,
                $data['nombre'],
                $data['tipo'] ?? 'cuantitativo',
                $data['unidad'] ?: null,
                $data['meta'] ?: null,
                $data['umbral_verde'] ?? 90,
                $data['umbral_amarillo'] ?? 70,
                $data['direccion'] ?? 'mayor_mejor',
                $data['escala_cualitativa'] ?: null,
                $data['opciones_cualitativas'] ?: null,
                $data['frecuencia'] ?? 'mensual',
                $data['activo'] ?? 1,
                $data['id']
            ]);
            flash('success', 'KPI actualizado correctamente.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO kpis (iniciativa_id, plan_accion_id, periodo_id, nombre, tipo, unidad, meta, umbral_verde, umbral_amarillo, direccion, escala_cualitativa, opciones_cualitativas, frecuencia, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['iniciativa_id'] ?: null,
                $data['plan_accion_id'] ?: null,
                $data['periodo_id'] ?: null,
                $data['nombre'],
                $data['tipo'] ?? 'cuantitativo',
                $data['unidad'] ?: null,
                $data['meta'] ?: null,
                $data['umbral_verde'] ?? 90,
                $data['umbral_amarillo'] ?? 70,
                $data['direccion'] ?? 'mayor_mejor',
                $data['escala_cualitativa'] ?: null,
                $data['opciones_cualitativas'] ?: null,
                $data['frecuencia'] ?? 'mensual',
                $data['activo'] ?? 1
            ]);
            flash('success', 'KPI creado correctamente.');
        }
        redirect('kpis.php');
    }

    static function registrarValor($pdo, $data) {
        // Insert into historial
        $stmt = $pdo->prepare("
            INSERT INTO kpi_historial (kpi_id, valor, valor_cualitativo, semaforo, periodo, observaciones)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $kpi = self::getById($pdo, $data['kpi_id']);
        $semaforo = null;

        if ($kpi['tipo'] === 'cuantitativo') {
            $semaforo = calcularSemaforo(
                $data['valor'],
                $kpi['meta'],
                $kpi['umbral_verde'],
                $kpi['umbral_amarillo'],
                $kpi['direccion']
            );
            $stmt->execute([
                $data['kpi_id'],
                $data['valor'],
                null,
                $semaforo,
                $data['periodo'],
                $data['observaciones'] ?: null
            ]);

            // Update current value on kpis table
            $upd = $pdo->prepare("UPDATE kpis SET valor_actual = ?, estado_semaforo = ? WHERE id = ?");
            $upd->execute([$data['valor'], $semaforo, $data['kpi_id']]);
        } else {
            // Cualitativo
            $semaforo = self::calcularSemaforoKpi(array_merge($kpi, ['valor_cualitativo' => $data['valor_cualitativo']]));
            $stmt->execute([
                $data['kpi_id'],
                null,
                $data['valor_cualitativo'],
                $semaforo,
                $data['periodo'],
                $data['observaciones'] ?: null
            ]);

            $upd = $pdo->prepare("UPDATE kpis SET valor_cualitativo = ?, estado_semaforo = ? WHERE id = ?");
            $upd->execute([$data['valor_cualitativo'], $semaforo, $data['kpi_id']]);
        }

        flash('success', 'Valor registrado correctamente.');
        redirect('kpi_detalle.php?id=' . $data['kpi_id']);
    }

    static function getHistorial($pdo, $kpiId) {
        $stmt = $pdo->prepare("
            SELECT * FROM kpi_historial
            WHERE kpi_id = ?
            ORDER BY periodo
        ");
        $stmt->execute([$kpiId]);
        return $stmt->fetchAll();
    }

    static function calcularSemaforoKpi($kpi) {
        if ($kpi['tipo'] === 'cuantitativo') {
            return calcularSemaforo(
                $kpi['valor_actual'],
                $kpi['meta'],
                $kpi['umbral_verde'],
                $kpi['umbral_amarillo'],
                $kpi['direccion']
            );
        }

        // Cualitativo: map value to traffic light
        $valor = $kpi['valor_cualitativo'] ?? null;
        if (!$valor) return 'gris';

        $escala = $kpi['escala_cualitativa'] ?? '';
        if ($escala === 'alto_medio_bajo') {
            $mapa = ['Alto' => 'verde', 'Medio' => 'amarillo', 'Bajo' => 'rojo'];
            return $mapa[$valor] ?? 'gris';
        }

        // Default: first option = verde, second = amarillo, rest = rojo
        $opciones = explode(',', $kpi['opciones_cualitativas'] ?? '');
        $idx = array_search($valor, $opciones);
        if ($idx === 0) return 'verde';
        if ($idx === 1) return 'amarillo';
        if ($idx !== false) return 'rojo';

        return 'gris';
    }
}
