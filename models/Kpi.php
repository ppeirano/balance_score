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
                    es_entero = ?,
                    escala_cualitativa = ?, opciones_cualitativas = ?,
                    frecuencia = ?, activo = ?, responsable = ?
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
                $data['es_entero'] ?? 0,
                $data['escala_cualitativa'] ?: null,
                $data['opciones_cualitativas'] ?: null,
                $data['frecuencia'] ?? 'mensual',
                $data['activo'] ?? 1,
                $data['responsable'] ?: null,
                $data['id']
            ]);
            flash('success', 'KPI actualizado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'kpi', $data['id'], $data['nombre'], 'editado');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO kpis (iniciativa_id, plan_accion_id, periodo_id, nombre, tipo, unidad, meta, umbral_verde, umbral_amarillo, direccion, es_entero, escala_cualitativa, opciones_cualitativas, frecuencia, activo, responsable)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $data['es_entero'] ?? 0,
                $data['escala_cualitativa'] ?: null,
                $data['opciones_cualitativas'] ?: null,
                $data['frecuencia'] ?? 'mensual',
                $data['activo'] ?? 1,
                $data['responsable'] ?: null
            ]);
            flash('success', 'KPI creado correctamente.');
            require_once __DIR__ . '/Bitacora.php';
            Bitacora::registrar($pdo, 'kpi', $pdo->lastInsertId(), $data['nombre'], 'creado');
        }
        $filtros = [];
        if (!empty($data['filtro_ie'])) $filtros[] = 'ie=' . (int)$data['filtro_ie'];
        if (!empty($data['filtro_tipo'])) $filtros[] = 'tipo=' . urlencode($data['filtro_tipo']);
        redirect('index.php?page=kpis' . ($filtros ? '&' . implode('&', $filtros) : ''));
    }

    static function registrarValorSilencioso($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO kpi_historial (kpi_id, valor, valor_cualitativo, semaforo, periodo, observaciones)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $kpi = self::getById($pdo, $data['kpi_id']);
        if (!$kpi) return false;
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

            $upd = $pdo->prepare("UPDATE kpis SET valor_actual = ?, estado_semaforo = ? WHERE id = ?");
            $upd->execute([$data['valor'], $semaforo, $data['kpi_id']]);
        } else {
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

        return true;
    }

    static function registrarValor($pdo, $data) {
        self::registrarValorSilencioso($pdo, $data);
        flash('success', 'Valor registrado correctamente.');
        redirect('index.php?page=kpis&action=historial&id=' . $data['kpi_id']);
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

    static function actualizarHistorial($pdo, $kpiId, $registros) {
        $kpi = self::getById($pdo, $kpiId);
        if (!$kpi) return;

        $stmtUpdate = $pdo->prepare("
            UPDATE kpi_historial SET valor = ?, valor_cualitativo = ?, semaforo = ?, periodo = ?, observaciones = ?
            WHERE id = ? AND kpi_id = ?
        ");

        foreach ($registros as $hId => $row) {
            $hId = (int)$hId;
            $periodo = $row['periodo'] ?? '';
            $observaciones = $row['observaciones'] ?? '';

            if ($kpi['tipo'] === 'cuantitativo') {
                $valor = $row['valor'] ?? '';
                if ($valor === '') continue;
                $semaforo = calcularSemaforo($valor, $kpi['meta'], $kpi['umbral_verde'], $kpi['umbral_amarillo'], $kpi['direccion']);
                $stmtUpdate->execute([$valor, null, $semaforo, $periodo, $observaciones ?: null, $hId, $kpiId]);
            } else {
                $valorCual = $row['valor_cualitativo'] ?? '';
                if ($valorCual === '') continue;
                $semaforo = self::calcularSemaforoKpi(array_merge($kpi, ['valor_cualitativo' => $valorCual]));
                $stmtUpdate->execute([null, $valorCual, $semaforo, $periodo, $observaciones ?: null, $hId, $kpiId]);
            }
        }

        self::recalcularValorActual($pdo, $kpiId);
    }

    static function eliminarHistorial($pdo, $historialId, $kpiId) {
        $pdo->prepare("DELETE FROM kpi_historial WHERE id = ? AND kpi_id = ?")->execute([$historialId, $kpiId]);
        self::recalcularValorActual($pdo, $kpiId);
    }

    static function recalcularValorActual($pdo, $kpiId) {
        $kpi = self::getById($pdo, $kpiId);
        if (!$kpi) return;

        $stmt = $pdo->prepare("SELECT * FROM kpi_historial WHERE kpi_id = ? ORDER BY periodo DESC, id DESC LIMIT 1");
        $stmt->execute([$kpiId]);
        $ultimo = $stmt->fetch();

        if ($ultimo) {
            if ($kpi['tipo'] === 'cuantitativo') {
                $pdo->prepare("UPDATE kpis SET valor_actual = ?, estado_semaforo = ? WHERE id = ?")
                    ->execute([$ultimo['valor'], $ultimo['semaforo'], $kpiId]);
            } else {
                $pdo->prepare("UPDATE kpis SET valor_cualitativo = ?, estado_semaforo = ? WHERE id = ?")
                    ->execute([$ultimo['valor_cualitativo'], $ultimo['semaforo'], $kpiId]);
            }
        } else {
            $pdo->prepare("UPDATE kpis SET valor_actual = NULL, valor_cualitativo = NULL, estado_semaforo = NULL WHERE id = ?")
                ->execute([$kpiId]);
        }
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
