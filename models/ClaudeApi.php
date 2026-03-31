<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class ClaudeApi {

    static function solicitarEvaluacion($pdo, $data) {
        $tipo = $data['tipo'];
        $entidadId = $data['entidad_id'] ?? null;
        $prompt = '';

        switch ($tipo) {
            case 'general':
                $contexto = self::recopilarDatosGenerales($pdo);
                $prompt = "Eres un consultor experto en Balanced Scorecard para un laboratorio farmaceutico. "
                    . "Analiza el siguiente estado del BSC y proporciona una evaluacion ejecutiva con recomendaciones estrategicas.\n\n"
                    . $contexto;
                break;

            case 'iniciativa':
                $contexto = self::recopilarDatosIniciativa($pdo, $entidadId);
                $prompt = "Eres un consultor experto en Balanced Scorecard. "
                    . "Analiza la siguiente Iniciativa Estrategica y sus planes de accion. "
                    . "Proporciona una evaluacion detallada con riesgos, fortalezas y recomendaciones.\n\n"
                    . $contexto;
                break;

            case 'plan_accion':
                $plan = $pdo->prepare("
                    SELECT pa.*, ie.nombre AS iniciativa_nombre
                    FROM planes_accion pa
                    JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
                    WHERE pa.id = ?
                ");
                $plan->execute([$entidadId]);
                $planData = $plan->fetch();

                $acts = $pdo->prepare("SELECT * FROM actividades WHERE plan_accion_id = ?");
                $acts->execute([$entidadId]);
                $actividades = $acts->fetchAll();

                $prompt = "Eres un consultor experto en gestion de proyectos. "
                    . "Analiza el siguiente Plan de Accion y sus actividades. "
                    . "Proporciona recomendaciones para mejorar la ejecucion.\n\n"
                    . "Plan: " . json_encode($planData, JSON_UNESCAPED_UNICODE) . "\n"
                    . "Actividades: " . json_encode($actividades, JSON_UNESCAPED_UNICODE);
                break;

            case 'riesgos':
                $stmt = $pdo->query("SELECT * FROM riesgos WHERE estado = 'abierto'");
                $riesgos = $stmt->fetchAll();
                $prompt = "Eres un consultor experto en gestion de riesgos estrategicos para un laboratorio farmaceutico. "
                    . "Analiza los siguientes riesgos abiertos y proporciona recomendaciones de mitigacion.\n\n"
                    . "Riesgos: " . json_encode($riesgos, JSON_UNESCAPED_UNICODE);
                break;

            case 'reunion':
                $reunion = $pdo->prepare("SELECT * FROM reuniones WHERE id = ?");
                $reunion->execute([$entidadId]);
                $reunionData = $reunion->fetch();

                $comps = $pdo->prepare("SELECT * FROM compromisos WHERE reunion_id = ?");
                $comps->execute([$entidadId]);
                $compromisos = $comps->fetchAll();

                $prompt = "Eres un consultor experto en gestion estrategica. "
                    . "Analiza la siguiente minuta de reunion y sus compromisos. "
                    . "Identifica riesgos, dependencias y sugiere seguimiento.\n\n"
                    . "Reunion: " . json_encode($reunionData, JSON_UNESCAPED_UNICODE) . "\n"
                    . "Compromisos: " . json_encode($compromisos, JSON_UNESCAPED_UNICODE);
                break;

            default:
                flash('error', 'Tipo de evaluacion no valido.');
                redirect('index.php?page=evaluacion');
                return;
        }

        $respuesta = self::llamarApi($prompt);

        if ($respuesta === false) {
            flash('error', 'Error al comunicarse con la API de Claude.');
            redirect('index.php?page=evaluacion');
            return;
        }

        // Save evaluation
        $stmt = $pdo->prepare("
            INSERT INTO evaluaciones_ia (tipo, entidad_id, prompt_enviado, respuesta)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$tipo, $entidadId, $prompt, $respuesta]);

        flash('success', 'Evaluacion generada correctamente.');
        redirect('index.php?page=evaluacion&id=' . $pdo->lastInsertId());
    }

    static function recopilarDatosGenerales($pdo) {
        $datos = "=== ESTADO DEL BALANCED SCORECARD ===\n\n";

        // Perspectivas e Iniciativas
        $perspectivas = $pdo->query("SELECT * FROM perspectivas ORDER BY orden")->fetchAll();
        foreach ($perspectivas as $p) {
            $datos .= "## Perspectiva: " . $p['nombre'] . "\n";

            $stmt = $pdo->prepare("SELECT * FROM iniciativas_estrategicas WHERE perspectiva_id = ? ORDER BY orden");
            $stmt->execute([$p['id']]);
            $iniciativas = $stmt->fetchAll();

            foreach ($iniciativas as $ie) {
                $datos .= "  - " . $ie['codigo'] . ": " . $ie['nombre'] . "\n";

                // Planes de accion
                $stmt2 = $pdo->prepare("SELECT codigo, nombre, owner, avance, estado, peso FROM planes_accion WHERE iniciativa_id = ?");
                $stmt2->execute([$ie['id']]);
                $planes = $stmt2->fetchAll();
                foreach ($planes as $pa) {
                    $datos .= "    * " . $pa['codigo'] . ": " . $pa['nombre']
                        . " | Owner: " . ($pa['owner'] ?: 'Sin asignar')
                        . " | Avance: " . $pa['avance'] . "%"
                        . " | Estado: " . $pa['estado']
                        . " | Peso: " . $pa['peso'] . "%\n";
                }
            }
            $datos .= "\n";
        }

        // KPIs
        $datos .= "=== KPIs ===\n";
        $kpis = $pdo->query("
            SELECT k.*, ie.codigo AS ie_codigo
            FROM kpis k
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = k.iniciativa_id
            ORDER BY k.id
        ")->fetchAll();
        foreach ($kpis as $kpi) {
            $datos .= "- [" . ($kpi['ie_codigo'] ?? 'General') . "] " . $kpi['nombre']
                . " | Meta: " . ($kpi['meta'] ?? 'N/A')
                . " | Actual: " . ($kpi['valor_actual'] ?? 'Sin datos')
                . " | Semaforo: " . ($kpi['estado_semaforo'] ?? 'gris') . "\n";
        }

        // Riesgos abiertos
        $riesgos = $pdo->query("SELECT * FROM riesgos WHERE estado = 'abierto'")->fetchAll();
        $datos .= "\n=== RIESGOS ABIERTOS (" . count($riesgos) . ") ===\n";
        foreach ($riesgos as $r) {
            $datos .= "- " . $r['descripcion'] . " | Nivel: " . $r['nivel'] . "\n";
        }

        return $datos;
    }

    static function recopilarDatosIniciativa($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT ie.*, p.nombre AS perspectiva_nombre
            FROM iniciativas_estrategicas ie
            JOIN perspectivas p ON p.id = ie.perspectiva_id
            WHERE ie.id = ?
        ");
        $stmt->execute([$id]);
        $ie = $stmt->fetch();

        if (!$ie) return "Iniciativa no encontrada.";

        $datos = "=== INICIATIVA ESTRATEGICA: " . $ie['codigo'] . " - " . $ie['nombre'] . " ===\n";
        $datos .= "Perspectiva: " . $ie['perspectiva_nombre'] . "\n";
        $datos .= "Descripcion: " . ($ie['descripcion'] ?: 'Sin descripcion') . "\n\n";

        // Planes de accion con actividades
        $planes = $pdo->prepare("SELECT * FROM planes_accion WHERE iniciativa_id = ? ORDER BY prioridad");
        $planes->execute([$id]);
        $planesData = $planes->fetchAll();

        $datos .= "## Planes de Accion (" . count($planesData) . ")\n";
        foreach ($planesData as $pa) {
            $datos .= "\n### " . $pa['codigo'] . ": " . $pa['nombre'] . "\n";
            $datos .= "Owner: " . ($pa['owner'] ?: 'Sin asignar') . " | Estado: " . $pa['estado']
                . " | Avance: " . $pa['avance'] . "% | Peso: " . $pa['peso'] . "%\n";

            $acts = $pdo->prepare("SELECT * FROM actividades WHERE plan_accion_id = ?");
            $acts->execute([$pa['id']]);
            $actividades = $acts->fetchAll();
            foreach ($actividades as $a) {
                $datos .= "  - " . $a['codigo'] . ": " . $a['descripcion'] . " [" . $a['estado'] . "]\n";
            }
        }

        // KPIs
        $kpis = $pdo->prepare("SELECT * FROM kpis WHERE iniciativa_id = ?");
        $kpis->execute([$id]);
        $kpisData = $kpis->fetchAll();

        $datos .= "\n## KPIs (" . count($kpisData) . ")\n";
        foreach ($kpisData as $kpi) {
            $datos .= "- " . $kpi['nombre'] . " | Meta: " . ($kpi['meta'] ?? 'N/A')
                . " | Actual: " . ($kpi['valor_actual'] ?? 'Sin datos')
                . " | Semaforo: " . ($kpi['estado_semaforo'] ?? 'gris') . "\n";
        }

        // Riesgos
        $riesgos = $pdo->prepare("SELECT * FROM riesgos WHERE iniciativa_id = ? AND estado = 'abierto'");
        $riesgos->execute([$id]);
        $riesgosData = $riesgos->fetchAll();

        $datos .= "\n## Riesgos Abiertos (" . count($riesgosData) . ")\n";
        foreach ($riesgosData as $r) {
            $datos .= "- " . $r['descripcion'] . " | Nivel: " . $r['nivel'] . "\n";
        }

        return $datos;
    }

    static function llamarApi($prompt) {
        return self::llamarApiMultimodal([['type' => 'text', 'text' => $prompt]]);
    }

    static function llamarApiMultimodal($contentBlocks) {
        if (empty(CLAUDE_API_KEY)) {
            return false;
        }

        $url = 'https://api.anthropic.com/v1/messages';

        $payload = [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $contentBlocks]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . CLAUDE_API_KEY,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return false;
        }

        $data = json_decode($response, true);
        if (isset($data['content'][0]['text'])) {
            return $data['content'][0]['text'];
        }

        return false;
    }

    static function extraerKpisDeDocumento($pdo, $filePath, $mimeType) {
        // Cargar KPIs activos
        $kpis = $pdo->query("
            SELECT id, nombre, tipo, unidad, meta, valor_actual, es_entero, direccion, iniciativa_id
            FROM kpis WHERE activo = 1 ORDER BY nombre
        ")->fetchAll();

        if (empty($kpis)) return ['error' => 'No hay KPIs activos en el sistema.'];

        $kpisJson = json_encode($kpis, JSON_UNESCAPED_UNICODE);

        $promptText = "Eres un analista de datos experto. Te envio un documento y una lista de KPIs del sistema.\n"
            . "Analiza el documento y extrae los valores que correspondan a cada KPI.\n\n"
            . "KPIS DEL SISTEMA:\n" . $kpisJson . "\n\n"
            . "INSTRUCCIONES:\n"
            . "- Busca valores que correspondan a cada KPI por nombre o concepto similar\n"
            . "- Si encontras una serie temporal (varios meses/periodos), inclui todos los valores con su fecha\n"
            . "- Para cada valor encontrado indica el periodo como fecha YYYY-MM-DD (primer dia del mes si es mensual)\n"
            . "- Indica tu nivel de confianza: \"alta\" (match exacto), \"media\" (match por concepto), \"baja\" (inferido)\n"
            . "- Si no encontras un KPI en el documento, no lo incluyas\n"
            . "- Los valores numericos deben ser numeros (sin separador de miles, punto como decimal)\n\n"
            . "Responde SOLO con un JSON valido, sin markdown, sin texto adicional:\n"
            . "{\"propuestas\": [{\"kpi_id\": 5, \"kpi_nombre\": \"Nombre\", \"valores\": [{\"valor\": 17.6, \"periodo\": \"2026-02-01\", \"observaciones\": \"Fuente en el doc\", \"confianza\": \"alta\"}]}]}";

        // Construir content blocks
        $contentBlocks = [];

        if (str_starts_with($mimeType, 'text/') || $mimeType === 'application/csv') {
            // CSV/texto: leer como texto
            $texto = file_get_contents($filePath);
            $contentBlocks[] = ['type' => 'text', 'text' => "CONTENIDO DEL DOCUMENTO:\n" . $texto];
        } elseif ($mimeType === 'application/pdf') {
            // PDF: enviar como document base64
            $base64 = base64_encode(file_get_contents($filePath));
            $contentBlocks[] = [
                'type' => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64]
            ];
        } elseif (str_starts_with($mimeType, 'image/')) {
            // Imagen: enviar como image base64
            $base64 = base64_encode(file_get_contents($filePath));
            $contentBlocks[] = [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $base64]
            ];
        } else {
            return ['error' => 'Formato de archivo no soportado: ' . $mimeType];
        }

        $contentBlocks[] = ['type' => 'text', 'text' => $promptText];

        $respuesta = self::llamarApiMultimodal($contentBlocks);

        if ($respuesta === false) {
            return ['error' => 'Error al comunicarse con la API de Claude. Verifica la API key.'];
        }

        // Limpiar respuesta (a veces Claude envuelve en ```json ... ```)
        $respuesta = trim($respuesta);
        $respuesta = preg_replace('/^```json\s*/i', '', $respuesta);
        $respuesta = preg_replace('/\s*```$/', '', $respuesta);

        $parsed = json_decode($respuesta, true);
        if (!$parsed || !isset($parsed['propuestas'])) {
            return ['error' => 'No se pudo interpretar la respuesta de la IA.', 'raw' => $respuesta];
        }

        // Enriquecer con datos del KPI actual
        $kpisById = [];
        foreach ($kpis as $k) { $kpisById[$k['id']] = $k; }

        foreach ($parsed['propuestas'] as &$prop) {
            $kpiData = $kpisById[$prop['kpi_id']] ?? null;
            if ($kpiData) {
                $prop['valor_actual'] = $kpiData['valor_actual'];
                $prop['unidad'] = $kpiData['unidad'];
                $prop['meta'] = $kpiData['meta'];
                $prop['es_entero'] = $kpiData['es_entero'];
            }
        }
        unset($prop);

        return $parsed;
    }

    static function getHistorial($pdo) {
        $stmt = $pdo->query("
            SELECT e.*, ie.nombre AS entidad_nombre
            FROM evaluaciones_ia e
            LEFT JOIN iniciativas_estrategicas ie ON e.tipo = 'iniciativa' AND ie.id = e.entidad_id
            ORDER BY e.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    static function generarAgenda($pdo, $responsable) {
        $datos = "=== DATOS DE: " . $responsable . " ===\n";
        $datos .= "Fecha de hoy: " . date('d/m/Y') . "\n\n";

        // Planes de acción donde es owner
        $stmt = $pdo->prepare("
            SELECT pa.codigo, pa.nombre, pa.estado, pa.avance, pa.peso, pa.fecha_inicio, pa.fecha_fin,
                   ie.codigo AS ie_codigo, ie.nombre AS ie_nombre
            FROM planes_accion pa
            JOIN iniciativas_estrategicas ie ON ie.id = pa.iniciativa_id
            WHERE pa.owner = ?
            ORDER BY pa.prioridad DESC
        ");
        $stmt->execute([$responsable]);
        $planes = $stmt->fetchAll();

        $datos .= "## Planes de Accion a cargo (" . count($planes) . ")\n";
        foreach ($planes as $p) {
            $datos .= "- " . $p['ie_codigo'] . " > " . $p['codigo'] . ": " . $p['nombre']
                . " | Estado: " . $p['estado'] . " | Avance: " . $p['avance'] . "%"
                . " | Peso: " . $p['peso'] . "%"
                . " | Periodo: " . ($p['fecha_inicio'] ?: '?') . " a " . ($p['fecha_fin'] ?: '?') . "\n";
        }

        // Actividades pendientes de planes
        $stmt = $pdo->prepare("
            SELECT a.codigo, a.descripcion, a.estado, a.fecha_limite,
                   pa.codigo AS pa_codigo, pa.nombre AS pa_nombre
            FROM actividades a
            JOIN planes_accion pa ON pa.id = a.plan_accion_id
            WHERE a.responsable = ? AND a.estado != 'completado'
            ORDER BY a.fecha_limite ASC
        ");
        $stmt->execute([$responsable]);
        $actividades = $stmt->fetchAll();

        $datos .= "\n## Actividades pendientes (" . count($actividades) . ")\n";
        foreach ($actividades as $a) {
            $datos .= "- [" . $a['pa_codigo'] . "] " . $a['codigo'] . ": " . $a['descripcion']
                . " | Estado: " . $a['estado']
                . " | Fecha limite: " . ($a['fecha_limite'] ?: 'Sin definir') . "\n";
        }

        // Compromisos pendientes
        $stmt = $pdo->prepare("
            SELECT c.descripcion, c.estado, c.fecha_limite,
                   r.titulo AS reunion_titulo, r.fecha AS reunion_fecha
            FROM compromisos c
            JOIN reuniones r ON r.id = c.reunion_id
            WHERE c.responsable = ? AND c.estado != 'completado'
            ORDER BY c.fecha_limite ASC
        ");
        $stmt->execute([$responsable]);
        $compromisos = $stmt->fetchAll();

        $datos .= "\n## Compromisos pendientes (" . count($compromisos) . ")\n";
        foreach ($compromisos as $c) {
            $datos .= "- " . $c['descripcion']
                . " | Estado: " . $c['estado']
                . " | Fecha limite: " . ($c['fecha_limite'] ?: 'Sin definir')
                . " | Reunion origen: " . $c['reunion_titulo'] . " (" . date('d/m/Y', strtotime($c['reunion_fecha'])) . ")\n";
        }

        // Riesgos abiertos
        $stmt = $pdo->prepare("
            SELECT r.descripcion, r.nivel, r.probabilidad, r.impacto, r.plan_mitigacion,
                   ie.codigo AS ie_codigo
            FROM riesgos r
            LEFT JOIN iniciativas_estrategicas ie ON ie.id = r.iniciativa_id
            WHERE r.responsable = ? AND r.estado = 'abierto'
            ORDER BY FIELD(r.nivel, 'critico', 'alto', 'medio', 'bajo')
        ");
        $stmt->execute([$responsable]);
        $riesgos = $stmt->fetchAll();

        $datos .= "\n## Riesgos abiertos (" . count($riesgos) . ")\n";
        foreach ($riesgos as $r) {
            $datos .= "- " . ($r['ie_codigo'] ? "[" . $r['ie_codigo'] . "] " : "") . $r['descripcion']
                . " | Nivel: " . $r['nivel']
                . " | Prob: " . $r['probabilidad'] . " | Impacto: " . $r['impacto']
                . ($r['plan_mitigacion'] ? " | Mitigacion: " . mb_substr($r['plan_mitigacion'], 0, 80) : "") . "\n";
        }

        // Proyectos
        $stmt = $pdo->prepare("
            SELECT p.nombre, p.estado, p.avance, p.fecha_inicio, p.fecha_fin
            FROM proyectos p
            WHERE p.responsable = ?
            ORDER BY p.prioridad DESC
        ");
        $stmt->execute([$responsable]);
        $proyectos = $stmt->fetchAll();

        $datos .= "\n## Proyectos a cargo (" . count($proyectos) . ")\n";
        foreach ($proyectos as $p) {
            $datos .= "- " . $p['nombre']
                . " | Estado: " . $p['estado'] . " | Avance: " . $p['avance'] . "%"
                . " | Periodo: " . ($p['fecha_inicio'] ?: '?') . " a " . ($p['fecha_fin'] ?: '?') . "\n";
        }

        // Actividades de proyectos pendientes
        $stmt = $pdo->prepare("
            SELECT pa.nombre, pa.estado, pa.fecha_inicio, pa.fecha_fin, pa.grupo, pa.fase,
                   p.nombre AS proyecto_nombre
            FROM proyecto_actividades pa
            JOIN proyectos p ON p.id = pa.proyecto_id
            WHERE pa.responsable = ? AND pa.estado != 'completado'
            ORDER BY pa.fecha_inicio ASC
        ");
        $stmt->execute([$responsable]);
        $proyActiv = $stmt->fetchAll();

        $datos .= "\n## Actividades de proyectos pendientes (" . count($proyActiv) . ")\n";
        foreach ($proyActiv as $pa) {
            $datos .= "- [" . $pa['proyecto_nombre'] . "] " . $pa['nombre']
                . ($pa['grupo'] ? " (Grupo: " . $pa['grupo'] . ")" : "")
                . ($pa['fase'] ? " | Fase: " . $pa['fase'] : "")
                . " | Estado: " . $pa['estado']
                . " | " . ($pa['fecha_inicio'] ?: '?') . " a " . ($pa['fecha_fin'] ?: '?') . "\n";
        }

        $prompt = "Eres un consultor experto en Balanced Scorecard y gestion estrategica para el laboratorio farmaceutico Temis Lostalo. "
            . "A partir de los siguientes datos, genera una AGENDA DE REUNION estructurada para revisar con " . $responsable . ".\n\n"
            . "La agenda debe:\n"
            . "- Estar organizada por temas prioritarios\n"
            . "- Incluir puntos de seguimiento de compromisos anteriores\n"
            . "- Destacar items vencidos o en riesgo\n"
            . "- Sugerir preguntas clave para cada tema\n"
            . "- Ser concisa y actionable\n"
            . "- Estar en espanol\n"
            . "- Usar formato de texto plano (no markdown)\n\n"
            . $datos;

        return self::llamarApi($prompt);
    }
}
