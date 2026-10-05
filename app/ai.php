<?php
require_once __DIR__ . '/helpers.php';

function ai_enabled(): bool {
    return defined('OPENAI_API_KEY') && trim((string)OPENAI_API_KEY) !== '' && OPENAI_API_KEY !== 'pon_aqui_tu_api_key' && OPENAI_API_KEY !== 'PEGA_AQUI_TU_API_KEY_NUEVA';
}



function ai_usage_ensure_table(): void {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS ai_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            request_type VARCHAR(40) NOT NULL DEFAULT 'texto',
            model VARCHAR(120) NULL,
            prompt_tokens INT NOT NULL DEFAULT 0,
            completion_tokens INT NOT NULL DEFAULT 0,
            total_tokens INT NOT NULL DEFAULT 0,
            estimated_cost_eur DECIMAL(12,6) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ai_usage_user_date (user_id, created_at),
            INDEX idx_ai_usage_date (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

function ai_model_pricing_per_million(string $model): array {
    $m = strtolower($model);
    if (str_contains($m, 'gpt-4o-mini')) return ['in'=>0.15, 'out'=>0.60];
    if (str_contains($m, 'gpt-4o')) return ['in'=>2.50, 'out'=>10.00];
    if (str_contains($m, 'gpt-4.1-mini')) return ['in'=>0.40, 'out'=>1.60];
    if (str_contains($m, 'gpt-4.1-nano')) return ['in'=>0.10, 'out'=>0.40];
    return ['in'=>0.15, 'out'=>0.60];
}

function ai_usage_from_response(array $data): array {
    $u = $data['usage'] ?? [];
    $prompt = (int)($u['prompt_tokens'] ?? $u['input_tokens'] ?? 0);
    $completion = (int)($u['completion_tokens'] ?? $u['output_tokens'] ?? 0);
    $total = (int)($u['total_tokens'] ?? ($prompt + $completion));
    return ['prompt_tokens'=>$prompt, 'completion_tokens'=>$completion, 'total_tokens'=>$total];
}

function ai_estimated_cost_eur(string $model, array $usage): float {
    $pricing = ai_model_pricing_per_million($model);
    $prompt = max(0, (int)($usage['prompt_tokens'] ?? 0));
    $completion = max(0, (int)($usage['completion_tokens'] ?? 0));
    // OpenAI factura en dólares; aquí usamos una estimación equivalente en euros para poner límite sencillo.
    return round(($prompt / 1000000 * $pricing['in']) + ($completion / 1000000 * $pricing['out']), 6);
}

function ai_record_usage(int $userId, string $requestType, string $model, array $data): void {
    try {
        ai_usage_ensure_table();
        $usage = ai_usage_from_response($data);
        $cost = ai_estimated_cost_eur($model, $usage);
        db()->prepare('INSERT INTO ai_usage(user_id, request_type, model, prompt_tokens, completion_tokens, total_tokens, estimated_cost_eur) VALUES(?,?,?,?,?,?,?)')
            ->execute([$userId > 0 ? $userId : null, $requestType, $model, $usage['prompt_tokens'], $usage['completion_tokens'], $usage['total_tokens'], $cost]);
    } catch (Throwable $e) {}
}

function ai_usage_stats(int $userId = 0): array {
    $stats = [
        'daily_count'=>0,
        'daily_limit'=>defined('AI_DAILY_REQUEST_LIMIT') ? (int)AI_DAILY_REQUEST_LIMIT : 30,
        'monthly_cost'=>0.0,
        'monthly_limit'=>defined('AI_MONTHLY_LIMIT_EUR') ? (float)AI_MONTHLY_LIMIT_EUR : 5.0,
        'month_tokens'=>0,
        'today_tokens'=>0,
    ];
    try {
        ai_usage_ensure_table();
        $db = db();
        if ($userId > 0) {
            $st = $db->prepare('SELECT COUNT(*) c, COALESCE(SUM(total_tokens),0) t FROM ai_usage WHERE user_id=? AND DATE(created_at)=CURDATE()');
            $st->execute([$userId]);
            $row = $st->fetch() ?: [];
        } else {
            $row = $db->query('SELECT COUNT(*) c, COALESCE(SUM(total_tokens),0) t FROM ai_usage WHERE DATE(created_at)=CURDATE()')->fetch() ?: [];
        }
        $stats['daily_count'] = (int)($row['c'] ?? 0);
        $stats['today_tokens'] = (int)($row['t'] ?? 0);
        $row = $db->query("SELECT COALESCE(SUM(estimated_cost_eur),0) cost, COALESCE(SUM(total_tokens),0) tokens FROM ai_usage WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetch() ?: [];
        $stats['monthly_cost'] = (float)($row['cost'] ?? 0);
        $stats['month_tokens'] = (int)($row['tokens'] ?? 0);
    } catch (Throwable $e) {}
    return $stats;
}

function ai_limits_check(int $userId, string $requestType = 'texto'): array {
    if (!ai_enabled()) return ['ok'=>false, 'reason'=>'La IA externa no está configurada.'];
    if ($requestType === 'vision' && defined('AI_VISION_ENABLED') && !AI_VISION_ENABLED) {
        return ['ok'=>false, 'reason'=>'IA Vision está desactivada en el Panel Juanra.'];
    }
    $stats = ai_usage_stats($userId);
    if ($stats['daily_limit'] > 0 && $userId > 0 && $stats['daily_count'] >= $stats['daily_limit']) {
        return ['ok'=>false, 'reason'=>'Has llegado al límite diario de consultas IA. La app seguirá en modo local hasta mañana o hasta que Juanra suba el límite.'];
    }
    if ($stats['monthly_limit'] > 0 && $stats['monthly_cost'] >= $stats['monthly_limit']) {
        return ['ok'=>false, 'reason'=>'Has llegado al límite mensual de gasto IA. La app seguirá en modo local hasta reiniciar el contador o subir el límite.'];
    }
    return ['ok'=>true, 'reason'=>''];
}

function ai_limit_fallback_message(string $reason): string {
    return "\n\nNota para Juanra: ".$reason;
}

function ai_system_prompt(array $user, array $profile = []): string {
    $level = (int)($profile['level'] ?? 1);
    return "Eres Luna, una profesora virtual dulce, clara y paciente para Leire, alumna de 4º de Primaria de Andalucía. Responde en español sencillo, con ejemplos cortos, sin dar sermones. Adapta la explicación a una niña. Nivel de app: {$level}. Da siempre: 1) explicación breve, 2) ejemplo, 3) mini ejercicio para practicar. No inventes notas ni datos personales.";
}

function local_ai_fallback(string $question, string $subject = ''): string {
    $base = teacher_answer($question, $subject);
    return "Luna responde en modo local:\n\n".$base."\n\nMini reto: escribe otro ejemplo parecido y comprueba si puedes explicarlo con tus palabras.";
}

function luna_answer(string $question, string $subject, array $user, array $profile = []): string {
    $question = trim($question);
    if ($question === '') return '';
    if (!ai_enabled()) return local_ai_fallback($question, $subject);
    $limit = ai_limits_check((int)($user['id'] ?? 0), 'texto');
    if (!$limit['ok']) return local_ai_fallback($question, $subject).ai_limit_fallback_message($limit['reason']);

    $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini';
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => ai_system_prompt($user, $profile)],
            ['role' => 'user', 'content' => "Asignatura: {$subject}\nPregunta de Leire: {$question}"]
        ],
        'temperature' => 0.45,
        'max_tokens' => 650
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer '.OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 25
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $code < 200 || $code >= 300) {
        return local_ai_fallback($question, $subject)."\n\nNota técnica para Juanra: no se pudo conectar con la IA externa".($err ? " ({$err})" : "").".";
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) return local_ai_fallback($question, $subject);
    ai_record_usage((int)($user['id'] ?? 0), 'texto', $model, $data);
    $answer = trim($data['choices'][0]['message']['content'] ?? '');
    return $answer !== '' ? $answer : local_ai_fallback($question, $subject);
}

function save_ai_log(int $userId, string $subject, string $question, string $answer): void {
    try {
        $db = db();
        $db->prepare('INSERT INTO ai_logs(user_id, subject, question, answer) VALUES(?,?,?,?)')
           ->execute([$userId, $subject, $question, $answer]);
    } catch (Throwable $e) {}
}


function openai_extract_output_text(array $data): string {
    if (isset($data['output_text']) && is_string($data['output_text'])) {
        return trim($data['output_text']);
    }
    $chunks = [];
    foreach (($data['output'] ?? []) as $out) {
        foreach (($out['content'] ?? []) as $content) {
            if (isset($content['text']) && is_string($content['text'])) {
                $chunks[] = $content['text'];
            }
            if (isset($content['type'], $content['text']) && in_array($content['type'], ['output_text','text'], true)) {
                $chunks[] = $content['text'];
            }
        }
    }
    return trim(implode("\n", array_filter($chunks, fn($v) => trim((string)$v) !== '')));
}

function luna_scan_schema_instruction(): string {
    return 'Devuelve SOLO JSON válido con esta estructura: {"subject":"Matemáticas|Lengua|Conocimiento del Medio|Inglés", "topic":"tema corto", "detected_text":"texto leído de la foto o resumen fiel si no puedes leer todo", "summary":"resumen claro", "explanation":"explicación para niña de 4º primaria", "study_steps":["paso 1"], "questions":[{"type":"multiple|text", "question":"...", "options":["..."], "correct_answer":"...", "explanation":"...", "difficulty":1}]}. Crea entre 6 y 10 preguntas. Si la respuesta es abierta, usa correct_answer="respuesta libre". No inventes contenido que no aparezca en la imagen/texto o que no se pueda deducir con seguridad. Si la imagen es borrosa, dilo en detected_text y crea un repaso general prudente.';
}

function luna_scan_image_activity(string $absPath, string $mime, string $manualText, string $subject, string $mode = 'todo', int $difficulty = 1, int $userId = 0): ?array {
    if (!ai_enabled()) return null;
    $limit = ai_limits_check($userId, 'vision');
    if (!$limit['ok']) return null;
    if (!is_file($absPath) || !str_starts_with($mime, 'image/')) return null;
    $bytes = file_get_contents($absPath);
    if ($bytes === false || strlen($bytes) === 0) return null;
    $maxBytes = (defined('MAX_UPLOAD_MB') ? MAX_UPLOAD_MB : 8) * 1024 * 1024;
    if (strlen($bytes) > $maxBytes) return null;

    $manualText = trim($manualText);
    $subject = trim($subject) ?: 'Auto';
    $detail = defined('OPENAI_VISION_DETAIL') ? OPENAI_VISION_DETAIL : 'high';
    $model = defined('OPENAI_VISION_MODEL') ? OPENAI_VISION_MODEL : (defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini');
    $schemaInstruction = luna_scan_schema_instruction();
    $prompt = "Eres Luna, profesora virtual de 4º de Primaria de Andalucía. Analiza esta foto de un libro, ficha o ejercicio escolar y conviértela en una actividad para Leire.\n\nAsignatura indicada: {$subject}\nModo solicitado: {$mode}\nDificultad 1-3: {$difficulty}\n\n{$schemaInstruction}";
    if ($manualText !== '') {
        $prompt .= "\n\nTexto de apoyo escrito por Juanra o Leire:\n" . mb_substr($manualText, 0, 6000, 'UTF-8');
    }

    $payload = [
        'model' => $model,
        'input' => [[
            'role' => 'user',
            'content' => [
                ['type' => 'input_text', 'text' => $prompt],
                ['type' => 'input_image', 'image_url' => 'data:'.$mime.';base64,'.base64_encode($bytes), 'detail' => $detail],
            ],
        ]],
        'text' => ['format' => ['type' => 'json_object']],
        'temperature' => 0.25,
        'max_output_tokens' => 2600,
    ];

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer '.OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 55
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code < 200 || $code >= 300) return null;
    $data = json_decode($raw, true);
    if (!is_array($data)) return null;
    ai_record_usage($userId, 'vision', $model, $data);
    $content = openai_extract_output_text($data);
    if ($content === '') return null;
    $json = json_decode($content, true);
    return is_array($json) ? $json : null;
}

function generate_local_practice(string $subject, string $topic): array {
    $s = normalize_answer($subject.' '.$topic);
    if (str_contains($s, 'division')) {
        return [
            ['q'=>'648 ÷ 6 = ?', 'a'=>'108', 'e'=>'6 cabe en 648 un total de 108 veces. Comprueba: 108 × 6 = 648.'],
            ['q'=>'936 ÷ 9 = ?', 'a'=>'104', 'e'=>'104 × 9 = 936.'],
            ['q'=>'Un paquete tiene 72 cromos y se reparte entre 8 niñas. ¿Cuántos recibe cada una?', 'a'=>'9', 'e'=>'72 ÷ 8 = 9.']
        ];
    }
    if (str_contains($s, 'fraccion')) {
        return [
            ['q'=>'¿Cómo se llama el número de abajo en una fracción?', 'a'=>'denominador', 'e'=>'El denominador indica en cuántas partes iguales se divide el total.'],
            ['q'=>'Escribe la fracción: una parte de cuatro.', 'a'=>'1/4', 'e'=>'Una de cuatro partes iguales se escribe 1/4.'],
            ['q'=>'¿Qué es mayor: 1/2 o 1/4?', 'a'=>'1/2', 'e'=>'La mitad es mayor que un cuarto.']
        ];
    }
    if (str_contains($s, 'lengua') || str_contains($s, 'verbo') || str_contains($s, 'sustantivo')) {
        return [
            ['q'=>'En la frase “La niña canta”, ¿cuál es el verbo?', 'a'=>'canta', 'e'=>'El verbo dice la acción.'],
            ['q'=>'Escribe un adjetivo para “casa”.', 'a'=>'grande|bonita|pequeña|alta', 'e'=>'Un adjetivo dice cómo es el sustantivo.'],
            ['q'=>'¿“Perro” es sustantivo o adjetivo?', 'a'=>'sustantivo', 'e'=>'Nombra un animal, por eso es sustantivo.']
        ];
    }
    if (str_contains($s, 'ingles') || str_contains($s, 'english')) {
        return [
            ['q'=>'Translate: house', 'a'=>'casa', 'e'=>'House significa casa.'],
            ['q'=>'Choose: I ___ happy. (am / is)', 'a'=>'am', 'e'=>'Con I usamos am.'],
            ['q'=>'Translate: blue', 'a'=>'azul', 'e'=>'Blue significa azul.']
        ];
    }
    return [
        ['q'=>'Lee el tema y escribe una idea importante.', 'a'=>'respuesta libre', 'e'=>'Lo importante es explicar la idea con tus palabras.'],
        ['q'=>'Escribe una pregunta sobre el tema.', 'a'=>'respuesta libre', 'e'=>'Hacer preguntas ayuda a estudiar mejor.'],
        ['q'=>'Resume el tema en una frase.', 'a'=>'respuesta libre', 'e'=>'Un buen resumen usa pocas palabras y dice lo esencial.']
    ];
}

function luna_scan_activity(string $text, string $subject, string $mode = 'todo', int $difficulty = 1, int $userId = 0): ?array {
    $text = trim($text);
    if ($text === '' || !ai_enabled()) return null;
    $limit = ai_limits_check($userId, 'scan_text');
    if (!$limit['ok']) return null;
    $subject = trim($subject) ?: 'Auto';
    $text = mb_substr($text, 0, 9000, 'UTF-8');
    $schemaInstruction = luna_scan_schema_instruction();
    $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini';
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Eres Luna, profesora de 4º de Primaria de Andalucía. Creas actividades educativas desde texto extraído de una foto de libro o ficha. Sé clara, segura y adaptada a una niña.'],
            ['role' => 'user', 'content' => "Asignatura indicada: {$subject}\nModo solicitado: {$mode}\nDificultad 1-3: {$difficulty}\n\n{$schemaInstruction}\n\nTexto de la ficha/libro:\n{$text}"]
        ],
        'temperature' => 0.35,
        'max_tokens' => 1800,
        'response_format' => ['type' => 'json_object']
    ];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer '.OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 35
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code < 200 || $code >= 300) return null;
    $data = json_decode($raw, true);
    if (!is_array($data)) return null;
    ai_record_usage($userId, 'scan_text', $model, $data);
    $content = trim($data['choices'][0]['message']['content'] ?? '');
    if ($content === '') return null;
    $json = json_decode($content, true);
    return is_array($json) ? $json : null;
}
