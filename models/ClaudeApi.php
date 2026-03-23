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
        if (empty(CLAUDE_API_KEY)) {
            return false;
        }

        $url = 'https://api.anthropic.com/v1/messages';

        $payload = [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
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

    static function getHistorial($pdo) {
        $stmt = $pdo->query("
            SELECT e.*, ie.nombre AS entidad_nombre
            FROM evaluaciones_ia e
            LEFT JOIN iniciativas_estrategicas ie ON e.tipo = 'iniciativa' AND ie.id = e.entidad_id
            ORDER BY e.created_at DESC
        ");
        return $stmt->fetchAll();
    }
}
