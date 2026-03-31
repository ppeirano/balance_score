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

    static function generarPlanAccionEstrategico($pdo) {
        // Recopilar datos completos del BSC
        $datos = "=== ESTADO DEL BALANCED SCORECARD - TEMIS LOSTALO ===\n";
        $datos .= "Fecha: " . date('d/m/Y') . "\n\n";

        $perspectivas = $pdo->query("SELECT * FROM perspectivas ORDER BY orden")->fetchAll();
        foreach ($perspectivas as $p) {
            $datos .= "## Perspectiva: " . $p['nombre'] . "\n";

            $stmt = $pdo->prepare("SELECT * FROM iniciativas_estrategicas WHERE perspectiva_id = ? ORDER BY orden");
            $stmt->execute([$p['id']]);
            $iniciativas = $stmt->fetchAll();

            foreach ($iniciativas as $ie) {
                // Avance ponderado
                $stmtPda = $pdo->prepare("SELECT avance, peso FROM planes_accion WHERE iniciativa_id = ?");
                $stmtPda->execute([$ie['id']]);
                $pdas = $stmtPda->fetchAll();
                $pesoTotal = array_sum(array_column($pdas, 'peso'));
                $avIE = 0;
                if ($pesoTotal > 0) {
                    foreach ($pdas as $pd) { $avIE += ($pd['avance'] * $pd['peso'] / $pesoTotal); }
                }
                $datos .= "\n### " . $ie['codigo'] . ": " . $ie['nombre'] . " (Avance: " . round($avIE) . "%)\n";

                // PDAs
                $stmtPda2 = $pdo->prepare("SELECT codigo, nombre, owner, avance, estado, peso FROM planes_accion WHERE iniciativa_id = ? ORDER BY prioridad");
                $stmtPda2->execute([$ie['id']]);
                foreach ($stmtPda2->fetchAll() as $pa) {
                    $datos .= "  PDA " . $pa['codigo'] . ": " . $pa['nombre']
                        . " | Owner: " . ($pa['owner'] ?: 'Sin asignar')
                        . " | Avance: " . $pa['avance'] . "% | Estado: " . $pa['estado']
                        . " | Peso: " . $pa['peso'] . "%\n";
                }

                // KPIs con semáforo recalculado
                $stmtK = $pdo->prepare("SELECT * FROM kpis WHERE iniciativa_id = ? AND activo = 1");
                $stmtK->execute([$ie['id']]);
                foreach ($stmtK->fetchAll() as $kpi) {
                    $sem = $kpi['tipo'] === 'cuantitativo'
                        ? calcularSemaforo($kpi['valor_actual'], $kpi['meta'], $kpi['umbral_verde'], $kpi['umbral_amarillo'], $kpi['direccion'])
                        : ($kpi['estado_semaforo'] ?? 'gris');
                    $datos .= "  KPI: " . $kpi['nombre']
                        . " | Actual: " . ($kpi['valor_actual'] ?? 'N/A')
                        . " | Meta: " . ($kpi['meta'] ?? 'N/A')
                        . " | Unidad: " . ($kpi['unidad'] ?? '')
                        . " | Semaforo: " . $sem
                        . " | Direccion: " . ($kpi['direccion'] ?? '') . "\n";
                }

                // Riesgos
                $stmtR = $pdo->prepare("SELECT descripcion, nivel, probabilidad, impacto, plan_mitigacion, responsable FROM riesgos WHERE iniciativa_id = ? AND estado = 'abierto'");
                $stmtR->execute([$ie['id']]);
                foreach ($stmtR->fetchAll() as $r) {
                    $datos .= "  RIESGO [" . $r['nivel'] . "]: " . $r['descripcion']
                        . " | Prob: " . $r['probabilidad'] . " | Impacto: " . $r['impacto']
                        . " | Resp: " . ($r['responsable'] ?: 'Sin asignar')
                        . ($r['plan_mitigacion'] ? " | Mitigacion: " . mb_substr($r['plan_mitigacion'], 0, 100) : "") . "\n";
                }
            }
            $datos .= "\n";
        }

        // Relaciones causa-efecto
        $relaciones = $pdo->query("
            SELECT r.descripcion, io.codigo AS origen, id2.codigo AS destino
            FROM relaciones_causa_efecto r
            JOIN iniciativas_estrategicas io ON r.iniciativa_origen_id = io.id
            JOIN iniciativas_estrategicas id2 ON r.iniciativa_destino_id = id2.id
        ")->fetchAll();
        $datos .= "=== RELACIONES CAUSA-EFECTO ===\n";
        foreach ($relaciones as $rel) {
            $datos .= $rel['origen'] . " → " . $rel['destino'] . ($rel['descripcion'] ? ": " . $rel['descripcion'] : '') . "\n";
        }

        // Proyectos
        $proyectos = $pdo->query("SELECT nombre, responsable, estado, avance, prioridad FROM proyectos ORDER BY prioridad")->fetchAll();
        $datos .= "\n=== PROYECTOS (" . count($proyectos) . ") ===\n";
        foreach ($proyectos as $pr) {
            $datos .= "- " . $pr['nombre'] . " | Resp: " . ($pr['responsable'] ?: '-')
                . " | Estado: " . $pr['estado'] . " | Avance: " . $pr['avance'] . "% | Prioridad: " . $pr['prioridad'] . "\n";
        }

        // Compromisos pendientes
        $compromisos = $pdo->query("
            SELECT c.descripcion, c.responsable, c.fecha_limite, c.estado
            FROM compromisos c WHERE c.estado != 'completado' ORDER BY c.fecha_limite
        ")->fetchAll();
        $datos .= "\n=== COMPROMISOS PENDIENTES (" . count($compromisos) . ") ===\n";
        foreach ($compromisos as $c) {
            $datos .= "- " . $c['descripcion'] . " | Resp: " . ($c['responsable'] ?: '-')
                . " | Fecha: " . ($c['fecha_limite'] ?: 'Sin definir') . " | Estado: " . $c['estado'] . "\n";
        }

        $prompt = "Eres un consultor senior de estrategia y Balanced Scorecard para el laboratorio "
            . "farmacéutico Temis Lostalo.\n\n"
            . "A partir de los datos del BSC, generá un ANÁLISIS ESTRATÉGICO y PLAN DE ACCIÓN.\n\n"
            . "El análisis debe incluir:\n\n"
            . "1. **DIAGNÓSTICO**: Estado actual en 4-5 líneas (avance global, qué funciona bien, "
            . "qué preocupa, principales cuellos de botella)\n\n"
            . "2. **ACCIONES PRIORITARIAS**: Las 5-8 acciones más importantes ordenadas por "
            . "impacto × urgencia. Para cada una:\n"
            . "   - **Acción concreta** y palanca estratégica (IE/PDA con código)\n"
            . "   - **KPI que impacta**: nombre, valor actual → meta, y qué se espera lograr\n"
            . "   - **Riesgos a considerar**: restricciones que pueden frenar esta acción\n"
            . "   - **Prioridad**: 🔴 Crítica / 🟡 Alta / 🟢 Media\n\n"
            . "3. **FOCOS DEL PRÓXIMO PERÍODO**: 3-4 recomendaciones claras de dónde "
            . "concentrar esfuerzos y recursos\n\n"
            . "4. **ALERTAS**: Situaciones que requieren atención inmediata (KPIs en rojo, "
            . "riesgos críticos sin mitigar, planes estancados)\n\n"
            . "Sé concreto y usá los códigos de IE/PDA/KPI. No seas genérico.\n"
            . "Formato: Markdown en español.\n\n"
            . $datos;

        $respuesta = self::llamarApi($prompt);

        if ($respuesta === false) {
            return false;
        }

        // Guardar en evaluaciones_ia
        $stmt = $pdo->prepare("INSERT INTO evaluaciones_ia (tipo, entidad_id, prompt_enviado, respuesta) VALUES (?, NULL, ?, ?)");
        $stmt->execute(['general', $prompt, $respuesta]);

        return $respuesta;
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
