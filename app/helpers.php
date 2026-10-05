<?php
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function score_label(float $score): string {
    if ($score >= 9) return 'Excelente';
    if ($score >= 7) return 'Muy bien';
    if ($score >= 5) return 'Aprobado';
    return 'Hay que repasarlo';
}

function normalize_answer(string $value): string {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = strtr($value, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u']);
    $value = preg_replace('/[^a-z0-9ñ\s\+\-\*\/\.,]/u', '', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return $value ?? '';
}

function is_correct_answer(string $answer, string $correct): bool {
    $a = normalize_answer($answer);
    $c = normalize_answer($correct);
    if ($c === 'respuesta libre') return mb_strlen(trim($answer), 'UTF-8') >= 6;
    if ($a === $c) return true;
    $valid = array_map('trim', explode('|', $correct));
    foreach ($valid as $v) {
        if ($a === normalize_answer($v)) return true;
    }
    return false;
}


function reward_rule_defaults(): array {
    return [
        ['examen', 10.0, 5, 1],
        ['examen', 8.0, 2, 1],
        ['deberes', 10.0, 2, 1],
        ['deberes', 8.0, 1, 1],
        ['repaso', 10.0, 2, 1],
        ['repaso', 8.0, 1, 1],
        ['batalla', 10.0, 1, 1],
    ];
}

function ensure_reward_rule_tables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $db = db();
        $db->exec("CREATE TABLE IF NOT EXISTS reward_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mode VARCHAR(40) NOT NULL,
            min_score DECIMAL(4,2) NOT NULL DEFAULT 10.00,
            amount_cents INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_reward_rules_mode(mode, active, min_score)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $count = (int)$db->query('SELECT COUNT(*) c FROM reward_rules')->fetch()['c'];
        // Seed/update the accounts supplied by the administrator without overwriting later edits.
        $seedPasswords = [
            'francisco.ibanez10@gmail.com' => 'Juanra09',
            'normankaminski@gmx.de' => 'Sokani3912',
            'ernestfrancisco7@gmail.com' => 'Juanra10',
            'florian.flasbeck@gmx.de' => 'Maikelsi234',
            'perokdices88+89@gmail.com' => 'Juanra12',
            'melanie.pracht@gmx.net' => 'Juanra12',
            'califfo78@gmail.com' => 'Giulia15@4!',
        ];
        foreach ($seedPasswords as $email => $pass) {
            $st = $db->prepare('UPDATE service_accounts SET password_value=? WHERE email=? AND (password_value IS NULL OR password_value="")');
            $st->execute([$pass, $email]);
        }
        if ($count === 0) {
            $ins = $db->prepare('INSERT INTO reward_rules(mode,min_score,amount_cents,active) VALUES(?,?,?,?)');
            foreach (reward_rule_defaults() as $r) $ins->execute($r);
        }
        $db->exec("CREATE TABLE IF NOT EXISTS result_corrections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            result_id INT NOT NULL,
            user_id INT NOT NULL,
            question_index INT NOT NULL,
            old_ok TINYINT(1) NOT NULL DEFAULT 0,
            new_ok TINYINT(1) NOT NULL DEFAULT 0,
            old_score DECIMAL(4,2) NOT NULL DEFAULT 0,
            new_score DECIMAL(4,2) NOT NULL DEFAULT 0,
            reward_diff_cents INT NOT NULL DEFAULT 0,
            note VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_result_corrections_result(result_id),
            CONSTRAINT fk_result_corrections_result FOREIGN KEY (result_id) REFERENCES results(id) ON DELETE CASCADE,
            CONSTRAINT fk_result_corrections_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

function reward_rules_for_admin(): array {
    ensure_reward_rule_tables();
    try {
        $st = db()->query('SELECT * FROM reward_rules ORDER BY FIELD(mode,"examen","deberes","repaso","batalla"), min_score DESC, amount_cents DESC, id');
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

function save_reward_rule(int $id, string $mode, float $minScore, int $amountCents, bool $active): string {
    ensure_reward_rule_tables();
    $mode = trim(mb_strtolower($mode, 'UTF-8'));
    if (!in_array($mode, ['examen','deberes','repaso','batalla'], true)) return 'Modo de recompensa no válido.';
    $minScore = max(0, min(10, $minScore));
    $amountCents = max(0, $amountCents);
    try {
        if ($id > 0) {
            db()->prepare('UPDATE reward_rules SET mode=?, min_score=?, amount_cents=?, active=? WHERE id=?')->execute([$mode, $minScore, $amountCents, $active ? 1 : 0, $id]);
            return 'Regla de recompensa actualizada.';
        }
        db()->prepare('INSERT INTO reward_rules(mode,min_score,amount_cents,active) VALUES(?,?,?,?)')->execute([$mode, $minScore, $amountCents, $active ? 1 : 0]);
        return 'Regla de recompensa añadida.';
    } catch (Throwable $e) { return 'No se pudo guardar la regla.'; }
}

function reward_cents_for_activity(string $mode, float $score): int {
    $mode = mb_strtolower(trim($mode), 'UTF-8');
    ensure_reward_rule_tables();
    try {
        $st = db()->prepare('SELECT amount_cents FROM reward_rules WHERE active=1 AND mode=? AND ? >= min_score ORDER BY min_score DESC, amount_cents DESC LIMIT 1');
        $st->execute([$mode, $score]);
        $row = $st->fetch();
        if ($row) return (int)$row['amount_cents'];
        return 0;
    } catch (Throwable $e) {
        // Fallback si la tabla aún no existe.
        if ($mode === 'examen') {
            if ($score >= 9.95) return 5;
            if ($score >= 8) return 2;
            return 0;
        }
        if ($mode === 'batalla') return $score >= 9.95 ? 1 : 0;
        if ($score >= 9.95) return 2;
        if ($score >= 8) return 1;
        return 0;
    }
}

function format_cents(int $cents): string {
    $euros = intdiv(max(0, $cents), 100);
    $rest = max(0, $cents) % 100;
    return number_format($euros + ($rest / 100), 2, ',', '.') . ' €';
}

function reward_text(int $cents): string {
    if ($cents <= 0) return 'Sin recompensa económica esta vez';
    if ($cents === 1) return '+1 céntimo';
    return '+' . $cents . ' céntimos';
}

function format_signed_cents(int $cents): string {
    $sign = $cents > 0 ? '+' : ($cents < 0 ? '-' : '');
    return $sign . format_cents(abs($cents));
}

function parse_money_to_cents(string $value): int {
    $value = trim(str_replace(['€',' '], '', $value));
    $value = str_replace(',', '.', $value);
    if ($value === '' || !is_numeric($value)) return 0;
    return (int)round(((float)$value) * 100);
}


function user_topic_avg_score(int $userId, int $topicId): ?float {
    try {
        $st = db()->prepare('SELECT AVG(score) avg_score FROM results WHERE user_id=? AND topic_id=?');
        $st->execute([$userId, $topicId]);
        $v = $st->fetch()['avg_score'] ?? null;
        return $v === null ? null : (float)$v;
    } catch (Throwable $e) { return null; }
}

function subject_icon(string $subject): string {
    return match ($subject) {
        'Matemáticas' => '➗',
        'Lengua' => '📖',
        'Conocimiento del Medio' => '🌍',
        'Inglés' => '🇬🇧',
        default => '📚',
    };
}

function safe_upload_name(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($name, PATHINFO_FILENAME));
    $base = trim($base, '-') ?: 'archivo';
    return $base . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
}

function upload_kind_folder(string $kind): string {
    return $kind === 'apunte' ? 'apuntes' : 'libros';
}

function v31_subject_order_sql(string $alias = 'subject'): string {
    return 'FIELD('.$alias.',"Matemáticas","Lengua","Conocimiento del Medio","Inglés")';
}

function level_from_score(?float $score): string {
    if ($score === null) return 'Sin datos';
    if ($score >= 8.5) return 'Dominado';
    if ($score >= 7) return 'Bien';
    if ($score >= 5) return 'En progreso';
    return 'Necesita refuerzo';
}

function level_class(?float $score): string {
    if ($score === null) return 'neutral';
    if ($score >= 8.5) return 'ok';
    if ($score >= 7) return 'good';
    if ($score >= 5) return 'warn';
    return 'bad';
}

function teacher_answer(string $question, string $subject = ''): string {
    $q = normalize_answer($question);
    $subject = normalize_answer($subject);

    if (str_contains($q, 'fraccion') || str_contains($q, 'fracciones')) {
        return 'Una fracción representa una parte de un total. El número de arriba se llama numerador y dice cuántas partes cogemos. El de abajo se llama denominador y dice en cuántas partes iguales se divide el total. Truco: dibuja una pizza o una tableta dividida en partes iguales.';
    }
    if (str_contains($q, 'division') || str_contains($q, 'dividir')) {
        return 'Dividir es repartir en partes iguales. Para comprobar una división, multiplica el resultado por el divisor. Por ejemplo: 48 ÷ 6 = 8 porque 8 × 6 = 48.';
    }
    if (str_contains($q, 'multiplic')) {
        return 'Multiplicar es sumar varias veces el mismo número. Si tienes 4 bolsas con 6 caramelos, haces 4 × 6 porque hay 4 grupos de 6.';
    }
    if (str_contains($q, 'ecosistema')) {
        return 'Un ecosistema está formado por los seres vivos, el lugar donde viven y las relaciones entre ellos. Por ejemplo, en un bosque hay plantas, animales, suelo, agua, luz y todos dependen de alguna forma unos de otros.';
    }
    if (str_contains($q, 'andalucia')) {
        return 'Andalucía es una comunidad autónoma de España. Tiene ocho provincias: Almería, Cádiz, Córdoba, Granada, Huelva, Jaén, Málaga y Sevilla. En 4º conviene situarlas en el mapa y conocer algunos ríos, sierras y costas.';
    }
    if (str_contains($q, 'sustantivo')) {
        return 'Un sustantivo es una palabra que nombra personas, animales, cosas, lugares o ideas. Ejemplos: niña, perro, mesa, Cádiz, alegría.';
    }
    if (str_contains($q, 'adjetivo')) {
        return 'Un adjetivo acompaña al sustantivo y dice cómo es. En “casa grande”, casa es el sustantivo y grande es el adjetivo.';
    }
    if (str_contains($q, 'verbo')) {
        return 'Un verbo expresa una acción o un estado. Ejemplos: correr, estudiar, ser, estar. Para localizarlo, pregunta: ¿qué hace?';
    }
    if (str_contains($q, 'english') || str_contains($q, 'ingles') || $subject === 'ingles') {
        return 'En inglés, estudia poco a poco: primero vocabulario, luego frases cortas. Una buena forma es escribir una frase con cada palabra nueva.';
    }
    return 'Vamos por partes: lee el enunciado despacio, subraya las palabras importantes y piensa qué te están preguntando exactamente. Después intenta explicarlo con tus palabras. Si es un problema, escribe los datos, la operación y la respuesta final.';
}

function reading_texts(): array {
    return [
        'gota_agua' => [
            'title' => 'El viaje de una gota de agua',
            'subject' => 'Lengua',
            'text' => 'Nara era una pequeña gota de agua que vivía en el mar de Alborán. Una mañana, el sol la calentó tanto que subió al cielo convertida en vapor. Allí se juntó con otras gotas y formó una nube blanca. El viento empujó la nube hasta las montañas. Cuando hizo frío, Nara cayó en forma de lluvia sobre un río. Después viajó entre piedras, plantas y peces hasta regresar otra vez al mar.',
            'questions' => [
                ['q'=>'¿Dónde vivía Nara al principio?', 'a'=>'En el mar de Alborán|mar de Alborán|en el mar', 'e'=>'El texto dice que Nara vivía en el mar de Alborán.'],
                ['q'=>'¿Qué hizo que Nara subiera al cielo?', 'a'=>'El sol|el calor del sol|calor', 'e'=>'El sol la calentó y por eso subió convertida en vapor.'],
                ['q'=>'¿En qué cayó Nara cuando hizo frío?', 'a'=>'En forma de lluvia|lluvia', 'e'=>'Al enfriarse, la nube soltó agua en forma de lluvia.'],
                ['q'=>'¿A dónde regresó al final?', 'a'=>'Al mar|mar', 'e'=>'Al final del viaje volvió al mar.'],
            ],
        ],
        'olivo_andaluz' => [
            'title' => 'El olivo del abuelo',
            'subject' => 'Lengua',
            'text' => 'En un pueblo de Jaén, Leire visitó el campo de su abuelo. Allí había muchos olivos ordenados en largas filas. El abuelo le explicó que de las aceitunas se obtiene aceite de oliva, un alimento muy importante en Andalucía. Leire tocó el tronco rugoso de un olivo antiguo y pensó que aquel árbol había visto pasar muchas primaveras.',
            'questions' => [
                ['q'=>'¿En qué provincia estaba el campo del abuelo?', 'a'=>'Jaén|Jaen', 'e'=>'El texto empieza diciendo que estaban en un pueblo de Jaén.'],
                ['q'=>'¿Qué se obtiene de las aceitunas?', 'a'=>'Aceite de oliva|aceite', 'e'=>'De las aceitunas se obtiene aceite de oliva.'],
                ['q'=>'¿Cómo era el tronco del olivo antiguo?', 'a'=>'Rugoso|tronco rugoso', 'e'=>'El texto dice que Leire tocó el tronco rugoso.'],
                ['q'=>'¿Por qué el aceite es importante?', 'a'=>'Porque es un alimento importante en Andalucía|alimento importante', 'e'=>'El abuelo explica que es un alimento muy importante en Andalucía.'],
            ],
        ],
    ];
}

function unlock_basic_achievements(int $userId): void {
    $db = db();
    $total = (int)$db->query('SELECT COUNT(*) c FROM results WHERE user_id='.(int)$userId)->fetch()['c'];
    if ($total >= 1) {
        $st = $db->prepare('SELECT COUNT(*) c FROM achievements WHERE user_id=? AND title=?');
        $st->execute([$userId, 'Primer ejercicio terminado']);
        if ((int)$st->fetch()['c'] === 0) {
            $db->prepare('INSERT INTO achievements(user_id,title,description,icon) VALUES(?,?,?,?)')->execute([$userId,'Primer ejercicio terminado','Ya has terminado tu primera actividad.','🌟']);
        }
    }
    if ($total >= 5) {
        $st = $db->prepare('SELECT COUNT(*) c FROM achievements WHERE user_id=? AND title=?');
        $st->execute([$userId, 'Constancia']);
        if ((int)$st->fetch()['c'] === 0) {
            $db->prepare('INSERT INTO achievements(user_id,title,description,icon) VALUES(?,?,?,?)')->execute([$userId,'Constancia','Has completado 5 actividades.','🔥']);
        }
    }
}

/* =========================
   V3.3 Gamificación Leire
   ========================= */
function xp_for_next_level(int $level): int {
    return 100 + max(0, $level - 1) * 75;
}

function ensure_student_profile(int $userId): array {
    $db = db();
    try {
        $st = $db->prepare('SELECT * FROM student_profiles WHERE user_id=? LIMIT 1');
        $st->execute([$userId]);
        $profile = $st->fetch();
        if ($profile) return $profile;
        $avatar = 'assets/img/avatar-leire.svg';
        $db->prepare('INSERT INTO student_profiles(user_id, avatar, level, xp, coins, daily_streak, weekly_goal, last_activity) VALUES(?,?,?,?,?,?,?,NULL)')
           ->execute([$userId, $avatar, 1, 0, 0, 0, 5]);
        $st->execute([$userId]);
        return $st->fetch();
    } catch (Throwable $e) {
        return ['user_id'=>$userId,'avatar'=>'assets/img/avatar-leire.svg','level'=>1,'xp'=>0,'coins'=>0,'daily_streak'=>0,'weekly_goal'=>5,'last_activity'=>null];
    }
}

function award_progress(int $userId, string $reason, int $xp, int $coins, ?int $resultId = null): array {
    $db = db();
    $profile = ensure_student_profile($userId);
    try {
        $today = date('Y-m-d');
        $last = $profile['last_activity'] ?? null;
        $streak = (int)($profile['daily_streak'] ?? 0);
        if ($last !== $today) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $streak = ($last === $yesterday) ? $streak + 1 : 1;
        }
        $level = (int)$profile['level'];
        $newXp = (int)$profile['xp'] + $xp;
        while ($newXp >= xp_for_next_level($level)) {
            $newXp -= xp_for_next_level($level);
            $level++;
            // En V5.13 subir de nivel ya no añade dinero automático: el saldo en € se gana por notas.
            add_unique_achievement($userId, 'Subida de nivel', 'Has subido al nivel '.$level.'.', '🚀');
        }
        $newCoins = (int)$profile['coins'] + $coins;
        $up = $db->prepare('UPDATE student_profiles SET level=?, xp=?, coins=?, daily_streak=?, last_activity=? WHERE user_id=?');
        $up->execute([$level, $newXp, $newCoins, $streak, $today, $userId]);
        $db->prepare('INSERT INTO xp_history(user_id,result_id,reason,xp,coins) VALUES(?,?,?,?,?)')->execute([$userId,$resultId,$reason,$xp,$coins]);
        if ($streak >= 3) add_unique_achievement($userId, 'Racha de 3 días', 'Tres días seguidos estudiando.', '🔥');
        if ($streak >= 7) add_unique_achievement($userId, 'Semana completa', 'Siete días seguidos sin perder la racha.', '🏅');
    } catch (Throwable $e) {}
    return ensure_student_profile($userId);
}

function add_unique_achievement(int $userId, string $title, string $description, string $icon='⭐'): void {
    try {
        $db = db();
        $st = $db->prepare('SELECT COUNT(*) c FROM achievements WHERE user_id=? AND title=?');
        $st->execute([$userId, $title]);
        if ((int)$st->fetch()['c'] === 0) {
            $db->prepare('INSERT INTO achievements(user_id,title,description,icon) VALUES(?,?,?,?)')->execute([$userId,$title,$description,$icon]);
        }
    } catch (Throwable $e) {}
}

function progress_percent(array $profile): int {
    $need = xp_for_next_level((int)$profile['level']);
    if ($need <= 0) return 0;
    return min(100, (int)round(((int)$profile['xp'] / $need) * 100));
}

function topic_mastery_for_user(int $userId, string $operator): array {
    $sql = 'SELECT t.subject,t.title, ROUND(AVG(r.score),2) avg_score, COUNT(r.id) tries
            FROM results r JOIN topics t ON t.id=r.topic_id
            WHERE r.user_id=? GROUP BY t.id HAVING avg_score '.$operator.' 8
            ORDER BY avg_score '.($operator === '>=' ? 'DESC' : 'ASC').' LIMIT 6';
    $st = db()->prepare($sql);
    $st->execute([$userId]);
    return $st->fetchAll();
}

/* =========================
   V4.1 Aventura + recompensas
   ========================= */
function claim_daily_reward(int $userId): ?array {
    try {
        $db = db();
        $today = date('Y-m-d');
        $st = $db->prepare('SELECT * FROM daily_rewards WHERE user_id=? AND reward_date=?');
        $st->execute([$userId, $today]);
        if ($st->fetch()) return null;
        $xp = 10; $coins = 0;
        $db->prepare('INSERT INTO daily_rewards(user_id,reward_date,xp,coins) VALUES(?,?,?,?)')->execute([$userId,$today,$xp,$coins]);
        award_progress($userId, 'Recompensa diaria', $xp, $coins, null);
        return ['xp'=>$xp,'coins'=>$coins];
    } catch (Throwable $e) { return null; }
}

/* =========================
   V4.7 Entrenador inteligente, tienda Emilia y retos
   ========================= */
function v41_reward_catalog(): array {
    return [
        // Marcos y fondos generales
        ['marco_dorado','marco','Marco dorado','Un marco brillante para el perfil principal.','🟡',50,1,'normal'],
        ['marco_morado','marco','Marco morado mágico','Marco violeta suave para el avatar.','💜',70,2,'normal'],
        ['marco_arcoiris','marco','Marco arcoíris','Marco alegre para días especiales.','🌈',120,4,'raro'],
        ['marco_galaxia','marco','Marco galaxia','Fondo estrellado alrededor de Leire.','🌌',180,7,'epico'],
        ['fondo_castillo','fondo','Fondo castillo','Un castillo de cuento para la pantalla.','🏰',100,3,'normal'],
        ['fondo_bosque','fondo','Bosque matemático','Fondo del mundo de matemáticas.','🌳',80,2,'normal'],
        ['fondo_isla','fondo','Isla del Inglés','Fondo de isla para practicar inglés.','🏝️',90,2,'normal'],
        ['fondo_estrellas','fondo','Cielo de estrellas','Fondo nocturno tranquilo y bonito.','✨',130,5,'raro'],
        ['fondo_arcoiris','fondo','Parque arcoíris','Un fondo alegre con colores suaves.','🎡',160,6,'raro'],

        // V4.7 Emilia: categorías equipables por capas
        ['emilia_gafas_redondas','emilia_gafas','Gafas redondas','Gafas redondas doradas para que Emilia parezca una profe divertida.','👓',40,1,'normal'],
        ['emilia_gafas_corazon','emilia_gafas','Gafas corazón','Gafas rosas con forma de corazón.','💗',70,2,'normal'],
        ['emilia_gafas_sol','emilia_gafas','Gafas de sol','Unas gafas modernas para recompensas especiales.','🕶️',95,4,'raro'],
        ['emilia_gafas_estrella','emilia_gafas','Gafas estrella','Gafas brillantes para días de nota alta.','⭐',140,7,'epico'],

        ['emilia_lazo_rosa','emilia_cabeza','Lazo rosa','Un lazo grande y alegre para Emilia.','🎀',35,1,'normal'],
        ['emilia_gorra_estrella','emilia_cabeza','Gorra estrella','Gorra rosa y azul con una estrella dorada.','🧢',60,2,'normal'],
        ['emilia_flor_amarilla','emilia_cabeza','Flor amarilla','Flor tropical para el pelo.','🌼',75,3,'normal'],
        ['emilia_sombrero_lila','emilia_cabeza','Sombrero lila','Sombrero elegante de color lila.','👒',115,5,'raro'],
        ['emilia_corona_suave','emilia_cabeza','Corona de logro','Corona dorada para los grandes objetivos.','👑',200,9,'epico'],

        ['emilia_look_colegio','emilia_ropa','Look colegio','Uniforme bonito para modo estudio.','🎒',90,2,'normal'],
        ['emilia_look_deportivo','emilia_ropa','Look deportivo','Chándal turquesa para retos rápidos.','🏃‍♀️',110,3,'normal'],
        ['emilia_sudadera_pastel','emilia_ropa','Sudadera pastel','Sudadera rosa y morada para el día a día.','🧥',125,4,'raro'],
        ['emilia_look_fiesta','emilia_ropa','Look fiesta brilli','Vestido morado brillante para celebraciones.','👗',180,7,'epico'],
        ['emilia_chaqueta_morada','emilia_ropa','Chaqueta morada','Chaqueta estilo app educativa con detalles dorados.','💜',150,6,'raro'],
        ['emilia_vestido_rojo','emilia_ropa','Vestido rojo Emilia','Vestido rojo brillante al estilo de Emilia para celebrar buenas notas.','❤️',160,5,'raro'],
        ['emilia_falda_roja','emilia_ropa','Falda roja pop','Falda roja con estilo divertido para retos y exámenes.','💃',135,4,'raro'],
        ['emilia_camisa_brilli','emilia_ropa','Camisa brilli','Camisa brillante y moderna para días especiales.','✨',145,5,'raro'],
        ['emilia_top_estrella','emilia_ropa','Camiseta estrella','Camiseta con estrella para cuando Leire complete deberes.','⭐',95,2,'normal'],
        ['emilia_conjunto_cielo','emilia_ropa','Conjunto cielo','Conjunto azul cielo con detalles blancos y dorados.','☁️',125,3,'normal'],
        ['emilia_vestido_lila','emilia_ropa','Vestido lila','Vestido lila suave, elegante y alegre para estudiar.','👗',155,5,'raro'],
        ['emilia_falda_dorada','emilia_ropa','Falda dorada','Falda dorada de recompensa para objetivos semanales.','🌟',190,8,'epico'],
        ['emilia_sudadera_roja','emilia_ropa','Sudadera roja','Sudadera roja cómoda para practicar sin presión.','🧣',115,3,'normal'],
        ['emilia_blusa_rosa','emilia_ropa','Blusa rosa','Blusa rosa dulce con estilo de app infantil.','🌸',120,3,'normal'],
        ['emilia_look_premium','emilia_ropa','Look premium Emilia','Conjunto especial brillante para notas de sobresaliente.','💎',260,10,'epico'],

        ['emilia_auriculares','emilia_extra','Auriculares pastel','Auriculares para escuchar lecturas y practicar inglés.','🎧',85,2,'normal'],
        ['emilia_mochila','emilia_extra','Mochila estrella','Mochila con estrellas para la aventura.','🎒',100,3,'normal'],
        ['emilia_collar_estrella','emilia_extra','Collar estrella','Collar dorado con una estrella.','📿',70,2,'normal'],
        ['emilia_pulsera','emilia_extra','Pulsera morada','Pulsera con cuentas moradas y doradas.','🟣',60,1,'normal'],
        ['emilia_microfono','emilia_extra','Micrófono pop','Micrófono para celebrar exámenes aprobados.','🎤',170,7,'epico'],

        // Avatar jugable de Leire y colección
        ['ropa_vestido_azul','ropa','Vestido azul aventura','Ropa azul para el personaje Roblox de Leire.','👗',90,2,'normal'],
        ['ropa_zapatillas','ropa','Zapatillas doradas','Zapatillas para correr por los mundos.','👟',85,2,'normal'],
        ['ropa_mochila','ropa','Mochila estrella','Mochila de exploradora para Leire.','🎒',120,4,'raro'],
        ['ropa_varita','ropa','Varita de Luna','Accesorio mágico para estudiar con Luna.','🪄',160,6,'raro'],
        ['ropa_diadema','ropa','Diadema brillante','Diadema bonita para el avatar jugable.','🎀',75,1,'normal'],
        ['ropa_capa','ropa','Capa aventurera','Capa para misiones difíciles.','🦸‍♀️',210,8,'epico'],
        ['pegatina_estrella','pegatina','Pegatina estrella','Una estrella para el álbum.','⭐',30,1,'normal'],
        ['pegatina_corazon','pegatina','Pegatina corazón','Pegatina tierna para recompensas.','💖',30,1,'normal'],
        ['pegatina_trofeo','pegatina','Pegatina trofeo','Para recordar una victoria.','🏆',60,3,'normal'],
        ['pegatina_llama','pegatina','Pegatina llama','Para rachas y días potentes.','🔥',60,3,'normal'],
        ['pegatina_libro','pegatina','Pegatina libro','Para lecturas completadas.','📚',45,2,'normal'],
        ['pegatina_luna','pegatina','Pegatina Luna','La profesora Luna en el álbum.','🌙',75,4,'raro'],
        ['bonus_pista','bonus','Pista extra','Recompensa simbólica para pedir una pista.','💡',40,1,'normal'],
        ['bonus_doble_saldo','bonus','Bonus especial','Premio especial de motivación.','💶',140,5,'raro'],
        ['bonus_salvar_racha','bonus','Salva racha','Objeto especial para no perder motivación.','🛟',200,8,'epico'],
    ];
}

function ensure_v41_tables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $db = db();
        $db->exec("CREATE TABLE IF NOT EXISTS reward_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_key VARCHAR(80) NOT NULL UNIQUE,
            category VARCHAR(60) NOT NULL,
            title VARCHAR(160) NOT NULL,
            description TEXT NULL,
            icon VARCHAR(16) NOT NULL DEFAULT '⭐',
            price INT NOT NULL DEFAULT 0,
            unlock_level INT NOT NULL DEFAULT 1,
            rarity VARCHAR(40) NOT NULL DEFAULT 'normal',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS user_rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            item_id INT NOT NULL,
            equipped TINYINT(1) NOT NULL DEFAULT 0,
            purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_item(user_id,item_id),
            CONSTRAINT fk_user_rewards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_user_rewards_item FOREIGN KEY (item_id) REFERENCES reward_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS daily_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task_date DATE NOT NULL,
            task_key VARCHAR(80) NOT NULL,
            title VARCHAR(180) NOT NULL,
            subject VARCHAR(120) NULL,
            topic_id INT NULL,
            action_url VARCHAR(255) NOT NULL,
            xp_reward INT NOT NULL DEFAULT 0,
            coins_reward INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            UNIQUE KEY uniq_daily_task(user_id,task_date,task_key),
            CONSTRAINT fk_daily_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_daily_tasks_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $ins = $db->prepare('INSERT IGNORE INTO reward_items(item_key,category,title,description,icon,price,unlock_level,rarity) VALUES(?,?,?,?,?,?,?,?)');
        foreach (v41_reward_catalog() as $item) {
            $ins->execute($item);
        }
    } catch (Throwable $e) {}
}



function ensure_student_money_tables(): void {
    ensure_topic_status_table();
    ensure_reward_rule_tables();
    try {
        $db = db();
        $db->exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            admin_id INT NULL,
            request_id INT NULL,
            amount_cents INT NOT NULL,
            type VARCHAR(40) NOT NULL DEFAULT 'manual',
            reason VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_wallet_user_date(user_id, created_at),
            CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS withdrawal_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount_cents INT NOT NULL,
            note VARCHAR(255) NULL,
            status ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending',
            admin_note VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            INDEX idx_withdrawal_status(status, created_at),
            CONSTRAINT fk_withdrawal_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS user_subject_status (
            user_id INT NOT NULL,
            subject VARCHAR(120) NOT NULL,
            official_exam_done TINYINT(1) NOT NULL DEFAULT 0,
            official_exam_at DATE NULL,
            note VARCHAR(255) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(user_id, subject),
            INDEX idx_subject_status(user_id, official_exam_done),
            CONSTRAINT fk_subject_status_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        foreach ([
            "ALTER TABLE user_topic_status ADD COLUMN official_exam_done TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE user_topic_status ADD COLUMN official_exam_at DATE NULL",
            "ALTER TABLE user_topic_status ADD COLUMN note VARCHAR(255) NULL"
        ] as $sql) {
            try { $db->exec($sql); } catch (Throwable $e) {}
        }
    } catch (Throwable $e) {}
}

function wallet_adjust_balance(int $userId, int $amountCents, string $reason, ?int $adminId = null, string $type = 'manual', ?int $requestId = null): bool {
    if ($userId <= 0 || $amountCents === 0) return false;
    ensure_student_money_tables();
    try {
        $db = db();
        $profile = ensure_student_profile($userId);
        $current = (int)($profile['coins'] ?? 0);
        if ($current + $amountCents < 0) return false;
        $db->beginTransaction();
        $db->prepare('UPDATE student_profiles SET coins=coins+? WHERE user_id=?')->execute([$amountCents, $userId]);
        $db->prepare('INSERT INTO wallet_transactions(user_id,admin_id,request_id,amount_cents,type,reason) VALUES(?,?,?,?,?,?)')
           ->execute([$userId, $adminId, $requestId, $amountCents, $type, $reason]);
        $db->commit();
        return true;
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        return false;
    }
}

function create_withdrawal_request(int $userId, int $amountCents, string $note = ''): string {
    ensure_student_money_tables();
    $amountCents = max(0, $amountCents);
    if ($amountCents <= 0) return 'Elige una cantidad válida.';
    $profile = ensure_student_profile($userId);
    if ($amountCents > (int)($profile['coins'] ?? 0)) return 'No hay saldo suficiente para pedir esa retirada.';
    try {
        $st = db()->prepare("SELECT COUNT(*) c FROM withdrawal_requests WHERE user_id=? AND status='pending'");
        $st->execute([$userId]);
        if ((int)$st->fetch()['c'] >= 3) return 'Ya hay varias solicitudes pendientes. Espera a que Juanra las revise.';
        db()->prepare('INSERT INTO withdrawal_requests(user_id,amount_cents,note) VALUES(?,?,?)')
            ->execute([$userId, $amountCents, mb_substr(trim($note), 0, 255, 'UTF-8')]);
        return 'Solicitud enviada a Juanra.';
    } catch (Throwable $e) { return 'No se pudo enviar la solicitud ahora.'; }
}

function withdrawal_requests_for_admin(int $limit = 50): array {
    ensure_student_money_tables();
    try {
        $st = db()->prepare('SELECT wr.*, u.name user_name, u.username FROM withdrawal_requests wr JOIN users u ON u.id=wr.user_id ORDER BY FIELD(wr.status,"pending","paid","rejected"), wr.created_at DESC LIMIT '.(int)$limit);
        $st->execute();
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

function withdrawal_requests_for_user(int $userId, int $limit = 20): array {
    ensure_student_money_tables();
    try {
        $st = db()->prepare('SELECT * FROM withdrawal_requests WHERE user_id=? ORDER BY created_at DESC LIMIT '.(int)$limit);
        $st->execute([$userId]);
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

function wallet_transactions_for_user(int $userId, int $limit = 20): array {
    ensure_student_money_tables();
    try {
        $st = db()->prepare('SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT '.(int)$limit);
        $st->execute([$userId]);
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

function handle_withdrawal_admin_action(int $requestId, string $action, int $adminId, string $note = ''): string {
    ensure_student_money_tables();
    try {
        $db = db();
        $st = $db->prepare('SELECT * FROM withdrawal_requests WHERE id=? LIMIT 1');
        $st->execute([$requestId]);
        $req = $st->fetch();
        if (!$req) return 'Solicitud no encontrada.';
        if ($req['status'] !== 'pending') return 'Esa solicitud ya estaba resuelta.';
        if ($action === 'reject') {
            $db->prepare("UPDATE withdrawal_requests SET status='rejected', admin_note=?, resolved_at=NOW() WHERE id=?")->execute([mb_substr($note,0,255,'UTF-8'), $requestId]);
            return 'Solicitud rechazada.';
        }
        if ($action === 'pay') {
            $ok = wallet_adjust_balance((int)$req['user_id'], -((int)$req['amount_cents']), 'Retirada aprobada por Juanra', $adminId, 'withdrawal_paid', $requestId);
            if (!$ok) return 'No se pudo pagar: revisa que Leire tenga saldo suficiente.';
            $db->prepare("UPDATE withdrawal_requests SET status='paid', admin_note=?, resolved_at=NOW() WHERE id=?")->execute([mb_substr($note,0,255,'UTF-8'), $requestId]);
            return 'Solicitud pagada y retirada del saldo.';
        }
        return 'Acción no válida.';
    } catch (Throwable $e) { return 'No se pudo resolver la solicitud.'; }
}


function manual_correct_result_question(int $resultId, int $userId, int $questionIndex, string $pin, bool $markCorrect, string $note = ''): string {
    if (trim($pin) !== '2283') return 'PIN incorrecto. No se ha cambiado la corrección.';
    if ($resultId <= 0 || $userId <= 0 || $questionIndex < 0) return 'Datos de corrección no válidos.';
    ensure_student_money_tables();
    try {
        $db = db();
        $st = $db->prepare('SELECT * FROM results WHERE id=? AND user_id=? LIMIT 1');
        $st->execute([$resultId, $userId]);
        $r = $st->fetch();
        if (!$r) return 'Resultado no encontrado.';
        $details = json_decode($r['details_json'] ?? '[]', true) ?: [];
        if (!isset($details[$questionIndex])) return 'Pregunta no encontrada en este resultado.';
        $oldOk = !empty($details[$questionIndex]['ok']);
        $newOk = $markCorrect;
        if ($oldOk === $newOk) return 'Esa pregunta ya estaba marcada así.';
        $oldScore = (float)$r['score'];
        $oldReward = reward_cents_for_activity((string)$r['mode'], $oldScore);
        $details[$questionIndex]['ok'] = $newOk;
        $details[$questionIndex]['manual_corrected'] = true;
        $details[$questionIndex]['manual_note'] = mb_substr(trim($note), 0, 180, 'UTF-8');
        $correct = 0;
        foreach ($details as $d) if (!empty($d['ok'])) $correct++;
        $total = max(1, count($details));
        $newScore = round(($correct / $total) * 10, 2);
        $newReward = reward_cents_for_activity((string)$r['mode'], $newScore);
        $diff = $newReward - $oldReward;
        $db->beginTransaction();
        $db->prepare('UPDATE results SET correct_questions=?, score=?, details_json=? WHERE id=? AND user_id=?')
           ->execute([$correct, $newScore, json_encode($details, JSON_UNESCAPED_UNICODE), $resultId, $userId]);
        if ($diff !== 0) {
            if ($diff > 0) {
                $db->prepare('UPDATE student_profiles SET coins=coins+? WHERE user_id=?')->execute([$diff, $userId]);
            } else {
                $db->prepare('UPDATE student_profiles SET coins=GREATEST(coins+?,0) WHERE user_id=?')->execute([$diff, $userId]);
            }
            $db->prepare('INSERT INTO wallet_transactions(user_id,admin_id,request_id,amount_cents,type,reason) VALUES(?,?,?,?,?,?)')
               ->execute([$userId, null, null, $diff, 'manual_result_correction', 'Ajuste por corrección manual del resultado #'.$resultId]);
            $db->prepare('UPDATE xp_history SET coins=GREATEST(coins+?,0) WHERE result_id=? AND user_id=?')->execute([$diff, $resultId, $userId]);
        }
        $db->prepare('INSERT INTO result_corrections(result_id,user_id,question_index,old_ok,new_ok,old_score,new_score,reward_diff_cents,note) VALUES(?,?,?,?,?,?,?,?,?)')
           ->execute([$resultId, $userId, $questionIndex, $oldOk ? 1 : 0, $newOk ? 1 : 0, $oldScore, $newScore, $diff, mb_substr(trim($note),0,255,'UTF-8')]);
        $db->commit();
        $msg = $newOk ? 'Pregunta marcada como correcta.' : 'Pregunta marcada como incorrecta.';
        if ($diff !== 0) $msg .= ' Ajuste de saldo: '.format_signed_cents($diff).'.';
        return $msg;
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        return 'No se pudo aplicar la corrección manual.';
    }
}

function set_topic_official_exam_done(int $userId, int $topicId, bool $done, string $note = ''): void {
    if ($userId <= 0 || $topicId <= 0) return;
    ensure_student_money_tables();
    try {
        $dt = $done ? date('Y-m-d') : null;
        db()->prepare('INSERT INTO user_topic_status(user_id,topic_id,is_archived,archived_at,official_exam_done,official_exam_at,note) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE official_exam_done=VALUES(official_exam_done), official_exam_at=VALUES(official_exam_at), note=VALUES(note), is_archived=IF(VALUES(official_exam_done)=1,1,is_archived), archived_at=IF(VALUES(official_exam_done)=1,NOW(),archived_at)')
            ->execute([$userId, $topicId, $done ? 1 : 0, $done ? date('Y-m-d H:i:s') : null, $done ? 1 : 0, $dt, mb_substr($note,0,255,'UTF-8')]);
    } catch (Throwable $e) {}
}

function set_subject_official_exam_done(int $userId, string $subject, bool $done, string $note = ''): void {
    $subject = trim($subject);
    if ($userId <= 0 || $subject === '') return;
    ensure_student_money_tables();
    try {
        db()->prepare('INSERT INTO user_subject_status(user_id,subject,official_exam_done,official_exam_at,note) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE official_exam_done=VALUES(official_exam_done), official_exam_at=VALUES(official_exam_at), note=VALUES(note)')
            ->execute([$userId, $subject, $done ? 1 : 0, $done ? date('Y-m-d') : null, mb_substr($note,0,255,'UTF-8')]);
    } catch (Throwable $e) {}
}

function subject_official_done_map(int $userId): array {
    ensure_student_money_tables();
    try {
        $st = db()->prepare('SELECT subject, official_exam_done FROM user_subject_status WHERE user_id=?');
        $st->execute([$userId]);
        $out = [];
        foreach ($st->fetchAll() as $r) $out[$r['subject']] = (int)$r['official_exam_done'] === 1;
        return $out;
    } catch (Throwable $e) { return []; }
}

function topic_official_done_map(int $userId, array $topicIds = []): array {
    ensure_student_money_tables();
    try {
        $sql = 'SELECT topic_id, official_exam_done FROM user_topic_status WHERE user_id=?';
        $params = [$userId];
        $topicIds = array_values(array_filter(array_map('intval', $topicIds)));
        if ($topicIds) {
            $sql .= ' AND topic_id IN (' . implode(',', array_fill(0, count($topicIds), '?')) . ')';
            $params = array_merge($params, $topicIds);
        }
        $st = db()->prepare($sql);
        $st->execute($params);
        $out = [];
        foreach ($st->fetchAll() as $r) $out[(int)$r['topic_id']] = (int)$r['official_exam_done'] === 1;
        return $out;
    } catch (Throwable $e) { return []; }
}

function profile_upload_folder_abs(): string {
    $base = realpath(__DIR__ . '/../public');
    if ($base === false) $base = dirname(__DIR__) . '/public';
    return $base . '/uploads/profile';
}

function save_profile_avatar_upload(int $userId, array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return 'No se recibió bien la foto.';
    $ext = strtolower(pathinfo((string)($file['name'] ?? 'foto.jpg'), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) return 'Formato no permitido. Usa JPG, PNG o WEBP.';
    $max = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $max) return 'La foto pesa demasiado. Máximo 5 MB.';
    $mime = @mime_content_type($file['tmp_name']) ?: '';
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) return 'El archivo no parece una imagen válida.';
    $folder = profile_upload_folder_abs();
    if (!is_dir($folder)) mkdir($folder, 0775, true);
    $name = 'leire-profile-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    $abs = $folder . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $abs)) return 'No se pudo guardar la foto.';
    $rel = 'uploads/profile/' . $name;
    try {
        ensure_student_profile($userId);
        db()->prepare('UPDATE student_profiles SET avatar=? WHERE user_id=?')->execute([$rel, $userId]);
        return 'Foto de perfil actualizada.';
    } catch (Throwable $e) { return 'Foto guardada, pero no se pudo actualizar el perfil.'; }
}

function ensure_topic_status_table(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    ensure_topic_activity_table();
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS user_topic_status (
            user_id INT NOT NULL,
            topic_id INT NOT NULL,
            is_archived TINYINT(1) NOT NULL DEFAULT 0,
            archived_at DATETIME NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(user_id, topic_id),
            INDEX idx_topic_status_archived (user_id, is_archived),
            CONSTRAINT fk_topic_status_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_topic_status_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

function set_topic_archived(int $userId, int $topicId, bool $archived): void {
    if ($userId <= 0 || $topicId <= 0) return;
    ensure_topic_status_table();
    try {
        $dt = $archived ? date('Y-m-d H:i:s') : null;
        $st = db()->prepare('INSERT INTO user_topic_status(user_id,topic_id,is_archived,archived_at) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE is_archived=VALUES(is_archived), archived_at=VALUES(archived_at)');
        $st->execute([$userId, $topicId, $archived ? 1 : 0, $dt]);
    } catch (Throwable $e) {}
}

function topic_archived_map(int $userId, array $topicIds = []): array {
    ensure_topic_status_table();
    try {
        $sql = 'SELECT topic_id, is_archived FROM user_topic_status WHERE user_id=?';
        $params = [$userId];
        if ($topicIds) {
            $topicIds = array_values(array_filter(array_map('intval', $topicIds)));
            if ($topicIds) {
                $sql .= ' AND topic_id IN (' . implode(',', array_fill(0, count($topicIds), '?')) . ')';
                $params = array_merge($params, $topicIds);
            }
        }
        $st = db()->prepare($sql);
        $st->execute($params);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[(int)$row['topic_id']] = (int)$row['is_archived'] === 1;
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function active_recent_topics_for_user(int $userId, int $limit = 4): array {
    ensure_student_money_tables();
    try {
        $sql = 'SELECT t.id, t.subject, t.title, MAX(a.created_at) last_used
                FROM topic_activity a
                JOIN topics t ON t.id=a.topic_id
                LEFT JOIN user_topic_status uts ON uts.user_id=? AND uts.topic_id=t.id
                LEFT JOIN user_subject_status uss ON uss.user_id=? AND uss.subject=t.subject
                WHERE a.user_id=?
                  AND COALESCE(uts.is_archived,0)=0
                  AND COALESCE(uts.official_exam_done,0)=0
                  AND COALESCE(uss.official_exam_done,0)=0
                GROUP BY t.id, t.subject, t.title
                ORDER BY MAX(a.created_at) DESC
                LIMIT ' . (int)$limit;
        $st = db()->prepare($sql);
        $st->execute([$userId, $userId, $userId]);
        return $st->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function current_focus_topics(int $userId, int $limit = 4): array {
    ensure_student_money_tables();
    $items = [];
    $seen = [];
    foreach (active_recent_topics_for_user($userId, $limit) as $row) {
        $id = (int)$row['id'];
        $items[] = $row;
        $seen[$id] = true;
    }
    if (count($items) < $limit) {
        try {
            $sql = 'SELECT t.id, t.subject, t.title,
                           GREATEST(COALESCE(UNIX_TIMESTAMP(MAX(a.created_at)),0), COALESCE(UNIX_TIMESTAMP(MAX(r.created_at)),0)) sort_time
                    FROM topics t
                    LEFT JOIN topic_activity a ON a.topic_id=t.id AND a.user_id=?
                    LEFT JOIN results r ON r.topic_id=t.id AND r.user_id=?
                    LEFT JOIN user_topic_status uts ON uts.user_id=? AND uts.topic_id=t.id
                    LEFT JOIN user_subject_status uss ON uss.user_id=? AND uss.subject=t.subject
                    WHERE COALESCE(uts.is_archived,0)=0
                      AND COALESCE(uts.official_exam_done,0)=0
                      AND COALESCE(uss.official_exam_done,0)=0
                    GROUP BY t.id, t.subject, t.title
                    ORDER BY sort_time DESC, FIELD(t.subject,"Matemáticas","Lengua","Conocimiento del Medio","Inglés"), t.id DESC';
            $st = db()->prepare($sql);
            $st->execute([$userId, $userId, $userId, $userId]);
            foreach ($st->fetchAll() as $row) {
                $id = (int)$row['id'];
                if (isset($seen[$id])) continue;
                $items[] = $row;
                $seen[$id] = true;
                if (count($items) >= $limit) break;
            }
        } catch (Throwable $e) {}
    }
    return array_slice($items, 0, $limit);
}

function battle_topics_for_user(int $userId, int $limit = 8): array {
    $focus = current_focus_topics($userId, $limit);
    if ($focus) return $focus;
    return weak_topics_for_user($userId, $limit);
}

function weak_topics_for_user(int $userId, int $limit = 5): array {
    ensure_student_money_tables();
    try {
        $st = db()->prepare('SELECT t.id,t.subject,t.title, ROUND(AVG(r.score),2) avg_score, COUNT(r.id) tries
            FROM results r
            JOIN topics t ON t.id=r.topic_id
            LEFT JOIN user_topic_status uts ON uts.user_id=? AND uts.topic_id=t.id
            LEFT JOIN user_subject_status uss ON uss.user_id=? AND uss.subject=t.subject
            WHERE r.user_id=?
              AND COALESCE(uts.is_archived,0)=0
              AND COALESCE(uts.official_exam_done,0)=0
              AND COALESCE(uss.official_exam_done,0)=0
            GROUP BY t.id,t.subject,t.title
            HAVING avg_score < 7
            ORDER BY avg_score ASC, tries DESC LIMIT '.(int)$limit);
        $st->execute([$userId, $userId, $userId]);
        $rows = $st->fetchAll();
        if ($rows) return $rows;
        $st = db()->prepare('SELECT t.id,t.subject,t.title, NULL avg_score, 0 tries
            FROM topics t
            LEFT JOIN results r ON r.topic_id=t.id AND r.user_id=?
            LEFT JOIN user_topic_status uts ON uts.user_id=? AND uts.topic_id=t.id
            LEFT JOIN user_subject_status uss ON uss.user_id=? AND uss.subject=t.subject
            WHERE COALESCE(uts.is_archived,0)=0
              AND COALESCE(uts.official_exam_done,0)=0
              AND COALESCE(uss.official_exam_done,0)=0
            GROUP BY t.id,t.subject,t.title
            ORDER BY COUNT(r.id) ASC, FIELD(t.subject,"Matemáticas","Lengua","Conocimiento del Medio","Inglés"), t.id DESC
            LIMIT '.(int)$limit);
        $st->execute([$userId, $userId, $userId]);
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

function frequent_error_topics(int $userId, int $limit = 8): array {
    ensure_student_money_tables();
    try {
        $st = db()->prepare('SELECT r.details_json,t.id topic_id,t.subject,t.title,COALESCE(uts.is_archived,0) archived,COALESCE(uts.official_exam_done,0) topic_done,COALESCE(uss.official_exam_done,0) subject_done
            FROM results r
            JOIN topics t ON t.id=r.topic_id
            LEFT JOIN user_topic_status uts ON uts.user_id=? AND uts.topic_id=t.id
            LEFT JOIN user_subject_status uss ON uss.user_id=? AND uss.subject=t.subject
            WHERE r.user_id=?
            ORDER BY r.created_at DESC LIMIT 80');
        $st->execute([$userId, $userId, $userId]);
        $map = [];
        foreach ($st->fetchAll() as $row) {
            if ((int)($row['archived'] ?? 0) === 1 || (int)($row['topic_done'] ?? 0) === 1 || (int)($row['subject_done'] ?? 0) === 1) continue;
            $details = json_decode($row['details_json'] ?? '[]', true) ?: [];
            foreach ($details as $d) {
                if (empty($d['ok'])) {
                    $id = (int)$row['topic_id'];
                    if (!isset($map[$id])) {
                        $map[$id] = ['topic_id'=>$id,'subject'=>$row['subject'],'title'=>$row['title'],'errors'=>0];
                    }
                    $map[$id]['errors']++;
                }
            }
        }
        usort($map, fn($a,$b) => $b['errors'] <=> $a['errors']);
        return array_slice(array_values($map), 0, $limit);
    } catch (Throwable $e) { return []; }
}

function ensure_daily_tasks(int $userId): array {
    ensure_v41_tables();
    ensure_topic_status_table();
    try {
        $db = db();
        $today = date('Y-m-d');
        $st = $db->prepare('SELECT * FROM daily_tasks WHERE user_id=? AND task_date=? ORDER BY id');
        $st->execute([$userId, $today]);
        $tasks = $st->fetchAll();
        if (!$tasks) {
            $focus = current_focus_topics($userId, 3);
            $insert = $db->prepare('INSERT IGNORE INTO daily_tasks(user_id,task_date,task_key,title,subject,topic_id,action_url,xp_reward,coins_reward) VALUES(?,?,?,?,?,?,?,?,?)');
            if (!empty($focus[0])) {
                $t = $focus[0];
                $insert->execute([$userId,$today,'focus_deberes','Deberes del tema: '.$t['title'],$t['subject'],(int)$t['id'],'exam.php?topic_id='.(int)$t['id'].'&mode=deberes',20,0]);
            }
            if (!empty($focus[1])) {
                $t = $focus[1];
                $insert->execute([$userId,$today,'focus_reto','Reto con Emilia: '.$t['title'],$t['subject'],(int)$t['id'],'exam.php?topic_id='.(int)$t['id'].'&mode=batalla',30,0]);
            } elseif (!empty($focus[0])) {
                $t = $focus[0];
                $insert->execute([$userId,$today,'focus_reto','Reto con Emilia: '.$t['title'],$t['subject'],(int)$t['id'],'exam.php?topic_id='.(int)$t['id'].'&mode=batalla',30,0]);
            }
            if (!empty($focus[2])) {
                $t = $focus[2];
                $insert->execute([$userId,$today,'focus_examen','Mini examen: '.$t['title'],$t['subject'],(int)$t['id'],'exam.php?topic_id='.(int)$t['id'].'&mode=examen',25,0]);
            } elseif (!empty($focus[0])) {
                $t = $focus[0];
                $insert->execute([$userId,$today,'focus_examen','Mini examen: '.$t['title'],$t['subject'],(int)$t['id'],'exam.php?topic_id='.(int)$t['id'].'&mode=examen',25,0]);
            }
            $insert->execute([$userId,$today,'scan','Escanear una ficha o repasar una foto',null,null,'scan.php',10,0]);
            $insert->execute([$userId,$today,'luna','Preguntar una duda a Luna IA',null,null,'teacher.php',5,0]);
            $st->execute([$userId, $today]);
            $tasks = $st->fetchAll();
        }
        return mark_daily_tasks_status($userId, $tasks);
    } catch (Throwable $e) { return []; }
}

function mark_daily_tasks_status(int $userId, array $tasks): array {
    try {
        $db = db();
        $today = date('Y-m-d');
        foreach ($tasks as &$task) {
            $completed = false;
            if (!empty($task['completed_at'])) $completed = true;
            if (!$completed && !empty($task['topic_id'])) {
                $st = $db->prepare('SELECT COUNT(*) c FROM results WHERE user_id=? AND topic_id=? AND DATE(created_at)=?');
                $st->execute([$userId, (int)$task['topic_id'], $today]);
                $completed = (int)$st->fetch()['c'] > 0;
            }
            if (!$completed && $task['task_key'] === 'scan') {
                try {
                    ensure_v42_tables();
                    $st = $db->prepare('SELECT COUNT(*) c FROM scanned_materials WHERE user_id=? AND DATE(created_at)=?');
                    $st->execute([$userId, $today]);
                    $completed = (int)$st->fetch()['c'] > 0;
                } catch (Throwable $e) {}
            }
            if (!$completed && $task['task_key'] === 'luna') {
                $st = $db->prepare('SELECT COUNT(*) c FROM ai_logs WHERE user_id=? AND DATE(created_at)=?');
                $st->execute([$userId, $today]);
                $completed = (int)$st->fetch()['c'] > 0;
            }
            if ($completed && empty($task['completed_at'])) {
                $db->prepare('UPDATE daily_tasks SET completed_at=NOW() WHERE id=?')->execute([(int)$task['id']]);
                $task['completed_at'] = date('Y-m-d H:i:s');
            }
            $task['completed'] = $completed;
        }
        unset($task);
    } catch (Throwable $e) {}
    return $tasks;
}

function daily_plan_percent(array $tasks): int {
    if (!$tasks) return 0;
    $done = 0;
    foreach ($tasks as $task) if (!empty($task['completed'])) $done++;
    return min(100, (int)round($done / count($tasks) * 100));
}

function weekly_summary_for_user(int $userId): array {
    try {
        $db = db();
        $st = $db->prepare('SELECT COUNT(*) total, COALESCE(ROUND(AVG(score),2),0) avg_score, COALESCE(SUM(correct_questions),0) corrects, COALESCE(SUM(total_questions-correct_questions),0) errors FROM results WHERE user_id=? AND YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)');
        $st->execute([$userId]);
        $summary = $st->fetch() ?: ['total'=>0,'avg_score'=>0,'corrects'=>0,'errors'=>0];
        $best = $db->prepare('SELECT t.subject, ROUND(AVG(r.score),2) avg_score FROM results r JOIN topics t ON t.id=r.topic_id WHERE r.user_id=? AND YEARWEEK(r.created_at,1)=YEARWEEK(CURDATE(),1) GROUP BY t.subject ORDER BY avg_score DESC LIMIT 1');
        $best->execute([$userId]);
        $summary['best'] = $best->fetch();
        $weak = weak_topics_for_user($userId, 1);
        $summary['weak'] = $weak[0] ?? null;
        return $summary;
    } catch (Throwable $e) { return ['total'=>0,'avg_score'=>0,'corrects'=>0,'errors'=>0,'best'=>null,'weak'=>null]; }
}

function trainer_message(int $userId): string {
    $focus = current_focus_topics($userId, 3);
    if ($focus) {
        $parts = array_map(fn($t) => '“'.$t['title'].'” ('.$t['subject'].')', $focus);
        return 'Hoy el plan se centra en los últimos temas que Leire ha estado trabajando: '.implode(', ', $parts).'. Primero deberes o repaso, después reto con Emilia y al final un mini examen.';
    }
    $weak = weak_topics_for_user($userId, 1);
    if (!empty($weak[0])) {
        return 'Hoy conviene reforzar “'.$weak[0]['title'].'” en '.$weak[0]['subject'].'. Mejor poco y bien: 10-15 minutos, corregir errores y repetir un reto con Emilia.';
    }
    return 'Hoy no hay fallos claros todavía. Lo ideal es hacer una lectura corta, un repaso de matemáticas y preguntar una duda a Luna.';
}

function shop_items_for_user(int $userId): array {
    ensure_v41_tables();
    try {
        $st = db()->prepare('SELECT ri.*, ur.id owned_id, COALESCE(ur.equipped,0) equipped
            FROM reward_items ri
            LEFT JOIN user_rewards ur ON ur.item_id=ri.id AND ur.user_id=?
            WHERE ri.active=1
            ORDER BY FIELD(ri.category,"marco","fondo","emilia_cabeza","emilia_gafas","emilia_ropa","emilia_extra","ropa","pegatina","bonus"), ri.unlock_level, ri.price, ri.id');
        $st->execute([$userId]);
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

function purchase_reward(int $userId, int $itemId): string {
    ensure_v41_tables();
    try {
        $db = db();
        $profile = ensure_student_profile($userId);
        $st = $db->prepare('SELECT * FROM reward_items WHERE id=? AND active=1');
        $st->execute([$itemId]);
        $item = $st->fetch();
        if (!$item) return 'Objeto no encontrado.';
        if ((int)$profile['level'] < (int)$item['unlock_level']) return 'Aún necesita nivel '.$item['unlock_level'].' para desbloquearlo.';
        if ((int)$profile['coins'] < (int)$item['price']) return 'No tiene saldo suficiente todavía.';
        $owned = $db->prepare('SELECT id FROM user_rewards WHERE user_id=? AND item_id=?');
        $owned->execute([$userId,$itemId]);
        if ($owned->fetch()) return 'Ese objeto ya está comprado.';
        $db->beginTransaction();
        $db->prepare('UPDATE student_profiles SET coins=coins-? WHERE user_id=?')->execute([(int)$item['price'],$userId]);
        $db->prepare('INSERT INTO user_rewards(user_id,item_id,equipped) VALUES(?,?,0)')->execute([$userId,$itemId]);
        $db->commit();
        add_unique_achievement($userId, 'Primera compra', 'Has comprado una recompensa en la tienda.', '🛍️');
        return 'Comprado: '.$item['title'].'.';
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        return 'No se pudo comprar ahora.';
    }
}

function equip_reward(int $userId, int $ownedId): string {
    ensure_v41_tables();
    try {
        $db = db();
        $st = $db->prepare('SELECT ur.*, ri.category, ri.title FROM user_rewards ur JOIN reward_items ri ON ri.id=ur.item_id WHERE ur.id=? AND ur.user_id=?');
        $st->execute([$ownedId,$userId]);
        $owned = $st->fetch();
        if (!$owned) return 'Objeto no encontrado.';
        $ids = $db->prepare('SELECT ur.id FROM user_rewards ur JOIN reward_items ri ON ri.id=ur.item_id WHERE ur.user_id=? AND ri.category=?');
        $ids->execute([$userId,$owned['category']]);
        foreach ($ids->fetchAll() as $row) {
            $db->prepare('UPDATE user_rewards SET equipped=0 WHERE id=?')->execute([(int)$row['id']]);
        }
        $db->prepare('UPDATE user_rewards SET equipped=1 WHERE id=?')->execute([$ownedId]);
        return 'Equipado: '.$owned['title'].'.';
    } catch (Throwable $e) { return 'No se pudo equipar ahora.'; }
}


function unequip_reward(int $userId, int $ownedId): string {
    try {
        $st = db()->prepare('SELECT ur.*, ri.category, ri.title FROM user_rewards ur JOIN reward_items ri ON ri.id=ur.item_id WHERE ur.id=? AND ur.user_id=?');
        $st->execute([$ownedId, $userId]);
        $row = $st->fetch();
        if (!$row) return 'No se ha encontrado ese objeto.';
        db()->prepare('UPDATE user_rewards SET equipped=0 WHERE id=? AND user_id=?')->execute([$ownedId, $userId]);
        return 'Has quitado “'.$row['title'].'” de Emilia.';
    } catch (Throwable $e) {
        return 'No se pudo quitar el objeto.';
    }
}

function unequip_reward_category(int $userId, string $category): string {
    try {
        $allowed = array_merge(emilia_categories(), ['marco','fondo','ropa','pegatina','bonus']);
        if (!in_array($category, $allowed, true)) return 'Categoría no válida.';
        $st = db()->prepare('SELECT COUNT(*) c FROM user_rewards ur JOIN reward_items ri ON ri.id=ur.item_id WHERE ur.user_id=? AND ri.category=? AND ur.equipped=1');
        $st->execute([$userId, $category]);
        $count = (int)($st->fetch()['c'] ?? 0);
        $db = db();
        $ids = $db->prepare('SELECT ur.id FROM user_rewards ur JOIN reward_items ri ON ri.id=ur.item_id WHERE ur.user_id=? AND ri.category=? AND ur.equipped=1');
        $ids->execute([$userId, $category]);
        foreach ($ids->fetchAll() as $row) {
            $db->prepare('UPDATE user_rewards SET equipped=0 WHERE id=? AND user_id=?')->execute([(int)$row['id'], $userId]);
        }
        return $count > 0 ? 'Has dejado esa categoría sin equipar.' : 'No había nada equipado en esa categoría.';
    } catch (Throwable $e) {
        return 'No se pudo quitar el equipamiento.';
    }
}

function equipped_rewards(int $userId): array {
    ensure_v41_tables();
    try {
        $st = db()->prepare('SELECT ri.* FROM user_rewards ur JOIN reward_items ri ON ri.id=ur.item_id WHERE ur.user_id=? AND ur.equipped=1');
        $st->execute([$userId]);
        $out = [];
        foreach ($st->fetchAll() as $item) $out[$item['category']] = $item;
        return $out;
    } catch (Throwable $e) { return []; }
}

/* =========================
   V4.7 Emilia: compañera y tienda por capas
   ========================= */
function ensure_v44_tables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    ensure_v42_tables();
    try {
        $db = db();
        $db->exec("CREATE TABLE IF NOT EXISTS mascots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            name VARCHAR(80) NOT NULL DEFAULT 'Emilia',
            level INT NOT NULL DEFAULT 1,
            xp INT NOT NULL DEFAULT 0,
            mood VARCHAR(120) NOT NULL DEFAULT 'lista para estudiar',
            skin VARCHAR(80) NOT NULL DEFAULT 'emilia_base',
            last_fed DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_mascots_user_v44 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Oculta objetos antiguos de Draco si la instalación viene de V4.0/V4.1.
        $db->exec("UPDATE reward_items SET active=0 WHERE category='draco' OR item_key LIKE 'draco_%'");
        // Asegura que los nuevos objetos de Emilia están insertados aunque la tabla ya existiera.
        $ins = $db->prepare('INSERT IGNORE INTO reward_items(item_key,category,title,description,icon,price,unlock_level,rarity) VALUES(?,?,?,?,?,?,?,?)');
        foreach (v41_reward_catalog() as $item) {
            $ins->execute($item);
        }
        // La tabla se mantiene como mascots para compatibilidad, pero el personaje visible pasa a ser Emilia.
        $db->exec("UPDATE mascots SET name='Emilia', skin='emilia_base' WHERE name='Draco' OR skin LIKE 'dragon%'");
    } catch (Throwable $e) {}
}

function emilia_categories(): array {
    return ['emilia_cabeza','emilia_gafas','emilia_ropa','emilia_extra'];
}

function emilia_items_for_user(int $userId, bool $ownedOnly = false): array {
    ensure_v44_tables();
    $items = shop_items_for_user($userId);
    return array_values(array_filter($items, function($item) use ($ownedOnly) {
        $isEmilia = in_array($item['category'], emilia_categories(), true);
        if (!$isEmilia) return false;
        if ($ownedOnly && empty($item['owned_id'])) return false;
        return true;
    }));
}

function emilia_equipped_for_user(int $userId): array {
    ensure_v44_tables();
    $all = equipped_rewards($userId);
    return array_intersect_key($all, array_flip(emilia_categories()));
}

function emilia_equipped_text(array $equipment): string {
    $parts = [];
    foreach (['emilia_cabeza'=>'Cabeza','emilia_gafas'=>'Gafas','emilia_ropa'=>'Ropa','emilia_extra'=>'Extra'] as $cat=>$label) {
        if (!empty($equipment[$cat])) $parts[] = $label.': '.$equipment[$cat]['title'];
    }
    return $parts ? implode(' · ', $parts) : 'Sin accesorios equipados todavía';
}


/* =========================
   V4.2 Escanear y practicar
   ========================= */
function ensure_v42_tables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    ensure_v41_tables();
    try {
        $db = db();
        $db->exec("CREATE TABLE IF NOT EXISTS scanned_materials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(180) NOT NULL,
            subject VARCHAR(120) NULL,
            source_type VARCHAR(40) NOT NULL DEFAULT 'foto',
            file_path VARCHAR(255) NULL,
            original_name VARCHAR(255) NULL,
            mime_type VARCHAR(120) NULL,
            size_bytes INT NOT NULL DEFAULT 0,
            extracted_text MEDIUMTEXT NULL,
            corrected_text MEDIUMTEXT NULL,
            generated_json JSON NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'generado',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_scanned_user(user_id),
            CONSTRAINT fk_scanned_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS scan_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            material_id INT NOT NULL,
            user_id INT NOT NULL,
            mode VARCHAR(40) NOT NULL DEFAULT 'practica',
            total_questions INT NOT NULL DEFAULT 0,
            correct_questions INT NOT NULL DEFAULT 0,
            score DECIMAL(4,2) NOT NULL DEFAULT 0,
            details_json JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_scan_attempts_user(user_id),
            CONSTRAINT fk_scan_attempts_material FOREIGN KEY (material_id) REFERENCES scanned_materials(id) ON DELETE CASCADE,
            CONSTRAINT fk_scan_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

function scan_allowed_extensions(): array {
    return ['jpg','jpeg','png','webp','pdf'];
}

function scan_allowed_mimes(): array {
    return ['image/jpeg','image/png','image/webp','application/pdf'];
}

function scan_upload_folder_abs(): string {
    $folder = realpath(__DIR__ . '/..');
    if ($folder === false) $folder = dirname(__DIR__);
    return $folder . '/uploads/scans';
}

function save_scan_upload(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok'=>false,'error'=>'No se recibió bien el archivo.'];
    }
    $max = (defined('MAX_UPLOAD_MB') ? MAX_UPLOAD_MB : 8) * 1024 * 1024;
    if (($file['size'] ?? 0) > $max) {
        return ['ok'=>false,'error'=>'El archivo pesa demasiado. Máximo '.(defined('MAX_UPLOAD_MB') ? MAX_UPLOAD_MB : 8).' MB.'];
    }
    $original = (string)($file['name'] ?? 'foto');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, scan_allowed_extensions(), true)) {
        return ['ok'=>false,'error'=>'Formato no permitido. Usa JPG, PNG, WEBP o PDF.'];
    }
    $mime = mime_content_type($file['tmp_name']) ?: (string)($file['type'] ?? '');
    if (!in_array($mime, scan_allowed_mimes(), true)) {
        return ['ok'=>false,'error'=>'Tipo de archivo no permitido.'];
    }
    $folder = scan_upload_folder_abs();
    if (!is_dir($folder)) mkdir($folder, 0775, true);
    $safe = safe_upload_name($original);
    $abs = $folder . '/' . $safe;
    if (!move_uploaded_file($file['tmp_name'], $abs)) {
        return ['ok'=>false,'error'=>'No se pudo guardar la foto en uploads/scans.'];
    }
    return [
        'ok'=>true,
        'abs'=>$abs,
        'path'=>'uploads/scans/'.$safe,
        'original'=>$original,
        'mime'=>$mime,
        'size'=>(int)($file['size'] ?? 0),
    ];
}

function extract_text_from_scan(string $absPath, string $mime): string {
    if (!str_starts_with($mime, 'image/')) return '';
    if (!function_exists('shell_exec')) return '';
    $bin = defined('OCR_TESSERACT_PATH') ? OCR_TESSERACT_PATH : 'tesseract';
    $langs = defined('OCR_LANGS') ? OCR_LANGS : 'spa+eng';
    $cmd = escapeshellcmd($bin) . ' ' . escapeshellarg($absPath) . ' stdout -l ' . escapeshellarg($langs) . ' --psm 6 2>/dev/null';
    $out = shell_exec($cmd);
    if (!is_string($out)) return '';
    $out = trim(preg_replace('/\s+/u', ' ', $out) ?? $out);
    return mb_substr($out, 0, 12000, 'UTF-8');
}

function scan_detect_subject(string $text, string $selected = ''): string {
    $selected = trim($selected);
    if ($selected !== '' && $selected !== 'Auto') return $selected;
    $n = normalize_answer($text);
    if (preg_match('/\b(fraccion|division|multiplic|suma|resta|decimal|problema|calcula|area|perimetro)\b/u', $n)) return 'Matemáticas';
    if (preg_match('/\b(verb|sustantiv|adjetiv|determinante|ortografia|acentu|lectura|texto|poema|cuento)\b/u', $n)) return 'Lengua';
    if (preg_match('/\b(ecosistema|seres vivos|planta|animal|mapa|andalucia|provincia|rio|energia|materia|cuerpo humano)\b/u', $n)) return 'Conocimiento del Medio';
    if (preg_match('/\b(english|translate|choose|complete|vocabulary|grammar|school|colour|family|have got|there is)\b/u', $n)) return 'Inglés';
    return 'Lengua';
}

function scan_detect_topic(string $text, string $subject): string {
    $n = normalize_answer($text);
    $rules = [
        'Fracciones' => ['fraccion','numerador','denominador'],
        'Divisiones y problemas' => ['division','dividir','repartir'],
        'Multiplicaciones' => ['multiplic','tabla'],
        'Problemas de matemáticas' => ['problema','calcula','operacion'],
        'Ortografía y acentuación' => ['ortografia','acento','tilde','b v','h muda'],
        'Sustantivos, adjetivos y verbos' => ['sustantivo','adjetivo','verbo'],
        'Comprensión lectora' => ['lee','lectura','texto','responde'],
        'Ecosistemas y seres vivos' => ['ecosistema','seres vivos','animal','planta'],
        'Andalucía y paisaje' => ['andalucia','provincia','rio','paisaje'],
        'English vocabulary' => ['vocabulary','translate','english','colour','school','family'],
    ];
    foreach ($rules as $topic => $words) {
        foreach ($words as $w) if (str_contains($n, $w)) return $topic;
    }
    return 'Repaso desde foto de '.$subject;
}

function scan_text_excerpt(string $text, int $max = 700): string {
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mb_strlen($text, 'UTF-8') <= $max) return $text;
    return mb_substr($text, 0, $max, 'UTF-8') . '…';
}

function scan_build_fallback_activity(string $text, string $subject, string $mode = 'todo', int $difficulty = 1): array {
    $subject = scan_detect_subject($text, $subject);
    $topic = scan_detect_topic($text, $subject);
    $excerpt = scan_text_excerpt($text ?: 'La foto no ha devuelto texto suficiente. Usa el cuadro de texto para escribir o pegar el contenido de la ficha y vuelve a generar.', 900);
    $questions = [];
    $free = fn($q, $e) => ['type'=>'text','question'=>$q,'options'=>[],'correct_answer'=>'respuesta libre','explanation'=>$e,'difficulty'=>$difficulty];
    $multi = fn($q, $opts, $ans, $e) => ['type'=>'multiple','question'=>$q,'options'=>$opts,'correct_answer'=>$ans,'explanation'=>$e,'difficulty'=>$difficulty];

    if ($subject === 'Matemáticas') {
        if (str_contains(normalize_answer($text), 'fraccion')) {
            $questions[] = $multi('En una fracción, ¿cómo se llama el número de abajo?', ['Numerador','Denominador','Producto','Resto'], 'Denominador', 'El denominador indica en cuántas partes iguales se divide el total.');
            $questions[] = $multi('¿Qué fracción representa una de cuatro partes iguales?', ['1/4','4/1','2/4','1/2'], '1/4', 'Una parte de cuatro se escribe 1/4.');
            $questions[] = $multi('¿Cuál es equivalente a 1/2?', ['2/4','1/3','3/8','4/8'], '2/4|4/8', '2/4 y 4/8 representan la mitad.');
        } else {
            $questions[] = ['type'=>'text','question'=>'Calcula: 648 ÷ 6', 'options'=>[], 'correct_answer'=>'108', 'explanation'=>'Comprueba multiplicando: 108 × 6 = 648.', 'difficulty'=>$difficulty];
            $questions[] = ['type'=>'text','question'=>'Calcula: 37 × 8', 'options'=>[], 'correct_answer'=>'296', 'explanation'=>'37 × 8 = 30×8 + 7×8 = 240 + 56 = 296.', 'difficulty'=>$difficulty];
            $questions[] = $multi('Para repartir 96 caramelos entre 8 personas, ¿qué operación usamos?', ['96 + 8','96 × 8','96 ÷ 8','96 - 8'], '96 ÷ 8', 'Repartir en partes iguales es dividir.');
        }
        $questions[] = $free('Inventa un problema parecido al de la foto y resuélvelo.', 'Lo importante es plantear datos, operación y respuesta final.');
    } elseif ($subject === 'Inglés') {
        $questions[] = $multi('What does “school” mean?', ['casa','colegio','perro','mesa'], 'colegio', 'School significa colegio.');
        $questions[] = ['type'=>'text','question'=>'Translate: family', 'options'=>[], 'correct_answer'=>'familia', 'explanation'=>'Family significa familia.', 'difficulty'=>$difficulty];
        $questions[] = $multi('Choose: I ___ happy.', ['am','is','are','be'], 'am', 'Con I usamos am.');
        $questions[] = $free('Escribe una frase corta en inglés usando una palabra de la ficha.', 'Una frase corta bien escrita ayuda a recordar vocabulario.');
    } elseif ($subject === 'Conocimiento del Medio') {
        $questions[] = $multi('¿Qué conviene hacer primero al estudiar este tema?', ['Memorizar sin leer','Leer y subrayar ideas importantes','Cerrar el libro','Copiar sin entender'], 'Leer y subrayar ideas importantes', 'Primero hay que entender las ideas principales.');
        $questions[] = $free('Escribe dos ideas importantes del texto o ficha.', 'Busca palabras clave y explícalas con tus palabras.');
        $questions[] = $free('Explica un concepto del tema como si se lo contaras a una niña más pequeña.', 'Si puedes explicarlo sencillo, normalmente lo has entendido.');
        $questions[] = $free('Haz un mini resumen de 3 líneas.', 'Un resumen debe decir lo esencial, no copiar todo.');
    } else {
        $questions[] = $multi('¿Qué es la idea principal de un texto?', ['Un detalle pequeño','Lo más importante','Una palabra difícil','El nombre del autor'], 'Lo más importante', 'La idea principal resume de qué trata el texto.');
        $questions[] = $free('Escribe la idea principal de la ficha o lectura.', 'La idea principal debe explicar lo más importante con tus palabras.');
        $questions[] = $free('Busca dos palabras importantes y explica su significado.', 'El vocabulario ayuda a comprender mejor el texto.');
        $questions[] = $free('Haz tres preguntas que podrían salir en un examen sobre esta ficha.', 'Convertir el contenido en preguntas es una forma buena de estudiar.');
    }

    $questions[] = $free('Resume lo aprendido en una frase.', 'La frase debe ser corta y recoger lo esencial.');
    $questions[] = $free('¿Qué parte te parece más difícil y por qué?', 'Reconocer la dificultad ayuda a preparar el siguiente repaso.');

    return [
        'version'=>'4.2-local',
        'subject'=>$subject,
        'topic'=>$topic,
        'mode'=>$mode,
        'difficulty'=>$difficulty,
        'summary'=>'Contenido detectado: '.$excerpt,
        'explanation'=>'Lee primero la ficha despacio, subraya palabras clave y después practica con estas preguntas. Si la foto no se leyó bien, corrige el texto y pulsa “Regenerar actividades”.',
        'study_steps'=>[
            'Lee la ficha una vez sin responder nada.',
            'Subraya datos, palabras clave o ideas principales.',
            'Haz los ejercicios y mira las explicaciones de los fallos.',
            'Repite el mini examen otro día para comprobar si ya está dominado.'
        ],
        'questions'=>array_slice($questions, 0, $mode === 'examen' ? 10 : 8),
    ];
}

function scan_normalize_activity(array $data, string $text, string $subject, string $mode, int $difficulty): array {
    $subject = scan_detect_subject($text, $data['subject'] ?? $subject);
    $data['version'] = (string)($data['version'] ?? '4.3');
    $data['subject'] = $subject;
    $data['topic'] = trim((string)($data['topic'] ?? scan_detect_topic($text, $subject))) ?: scan_detect_topic($text, $subject);
    $data['mode'] = $mode;
    $data['difficulty'] = $difficulty;
    $data['summary'] = trim((string)($data['summary'] ?? '')) ?: 'Resumen generado desde la foto/ficha.';
    $data['detected_text'] = trim((string)($data['detected_text'] ?? ''));
    $data['explanation'] = trim((string)($data['explanation'] ?? '')) ?: 'Repasa el contenido y contesta a las preguntas.';
    $steps = $data['study_steps'] ?? [];
    $data['study_steps'] = is_array($steps) ? array_values(array_slice($steps, 0, 6)) : [];
    $questions = [];
    foreach (($data['questions'] ?? []) as $q) {
        if (!is_array($q)) continue;
        $question = trim((string)($q['question'] ?? ''));
        if ($question === '') continue;
        $type = (string)($q['type'] ?? 'text');
        $type = $type === 'multiple' ? 'multiple' : 'text';
        $options = $q['options'] ?? [];
        if (!is_array($options)) $options = [];
        $correct = trim((string)($q['correct_answer'] ?? 'respuesta libre')) ?: 'respuesta libre';
        $explanation = trim((string)($q['explanation'] ?? 'Revisa la explicación del tema.')) ?: 'Revisa la explicación del tema.';
        if ($type === 'multiple' && count($options) < 2) $type = 'text';
        $questions[] = [
            'type'=>$type,
            'question'=>$question,
            'options'=>array_values(array_slice(array_map('strval', $options), 0, 5)),
            'correct_answer'=>$correct,
            'explanation'=>$explanation,
            'difficulty'=>(int)($q['difficulty'] ?? $difficulty),
        ];
    }
    if (!$questions) $questions = scan_build_fallback_activity($text, $subject, $mode, $difficulty)['questions'];
    $data['questions'] = array_slice($questions, 0, 12);
    return $data;
}

function generate_scan_activity(string $text, string $subject, string $mode = 'todo', int $difficulty = 1, int $userId = 0): array {
    $text = trim($text);
    if (function_exists('luna_scan_activity') && $text !== '') {
        $ai = luna_scan_activity($text, $subject, $mode, $difficulty, $userId);
        if (is_array($ai)) return scan_normalize_activity($ai, $text, $subject, $mode, $difficulty);
    }
    return scan_normalize_activity(scan_build_fallback_activity($text, $subject, $mode, $difficulty), $text, $subject, $mode, $difficulty);
}

function generate_scan_activity_from_upload(string $absPath, string $mime, string $ocrText, string $manualText, string $subject, string $mode = 'todo', int $difficulty = 1, int $userId = 0): array {
    $manualText = trim($manualText);
    $ocrText = trim($ocrText);
    $baseText = trim($manualText !== '' ? $manualText."

".$ocrText : $ocrText);
    $engine = 'local';
    $status = 'generado_local';
    $activity = null;

    if (function_exists('luna_scan_image_activity') && str_starts_with($mime, 'image/')) {
        $vision = luna_scan_image_activity($absPath, $mime, $manualText !== '' ? $manualText : $ocrText, $subject, $mode, $difficulty, $userId);
        if (is_array($vision)) {
            $detected = trim((string)($vision['detected_text'] ?? ''));
            if ($detected !== '') {
                $baseText = trim($manualText !== '' ? $manualText."

".$detected : $detected);
            } elseif ($baseText === '') {
                $baseText = 'La IA Vision ha analizado la imagen, pero no devolvió texto literal suficiente.';
            }
            $activity = scan_normalize_activity($vision, $baseText, $subject, $mode, $difficulty);
            $activity['engine'] = 'ia_vision';
            $engine = 'ia_vision';
            $status = 'generado_vision';
        }
    }

    if (!$activity) {
        $activity = generate_scan_activity($baseText, $subject, $mode, $difficulty, $userId);
        $engine = ai_enabled() && $baseText !== '' ? 'ia_texto' : 'local';
        $activity['engine'] = $engine;
        $status = $engine === 'ia_texto' ? 'generado_ia_texto' : 'generado_local';
    }

    return [
        'activity' => $activity,
        'base_text' => $baseText,
        'engine' => $engine,
        'status' => $status,
    ];
}

function scan_get_material(int $id, int $userId, bool $admin = false): ?array {
    ensure_v42_tables();
    try {
        $sql = 'SELECT sm.*, u.name user_name FROM scanned_materials sm JOIN users u ON u.id=sm.user_id WHERE sm.id=?';
        $params = [$id];
        if (!$admin) { $sql .= ' AND sm.user_id=?'; $params[] = $userId; }
        $st = db()->prepare($sql.' LIMIT 1');
        $st->execute($params);
        $row = $st->fetch();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function scan_recent_materials(int $userId, bool $admin = false, int $limit = 8): array {
    ensure_v42_tables();
    try {
        if ($admin) {
            $st = db()->query('SELECT sm.*, u.name user_name FROM scanned_materials sm JOIN users u ON u.id=sm.user_id ORDER BY sm.created_at DESC LIMIT '.(int)$limit);
            return $st->fetchAll();
        }
        $st = db()->prepare('SELECT * FROM scanned_materials WHERE user_id=? ORDER BY created_at DESC LIMIT '.(int)$limit);
        $st->execute([$userId]);
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

function scan_score_answers(int $materialId, int $userId, array $answers): array {
    $material = scan_get_material($materialId, $userId, false);
    if (!$material) return ['ok'=>false,'error'=>'Material no encontrado.'];
    $activity = json_decode($material['generated_json'] ?: '{}', true) ?: [];
    $questions = $activity['questions'] ?? [];
    $correct = 0; $details = [];
    foreach ($questions as $idx => $q) {
        $answer = trim((string)($answers[$idx] ?? ''));
        $expected = (string)($q['correct_answer'] ?? 'respuesta libre');
        $isCorrect = is_correct_answer($answer, $expected);
        if ($isCorrect) $correct++;
        $details[] = [
            'question'=>(string)($q['question'] ?? ''),
            'answer'=>$answer,
            'correct'=>$expected,
            'ok'=>$isCorrect,
            'explanation'=>(string)($q['explanation'] ?? ''),
        ];
    }
    $total = max(1, count($questions));
    $score = round(($correct / $total) * 10, 2);
    try {
        $db = db();
        $db->prepare('INSERT INTO scan_attempts(material_id,user_id,mode,total_questions,correct_questions,score,details_json) VALUES(?,?,?,?,?,?,?)')
           ->execute([$materialId,$userId,(string)($activity['mode'] ?? 'practica'),$total,$correct,$score,json_encode($details, JSON_UNESCAPED_UNICODE)]);
        $xp = 12 + ($score >= 7 ? 10 : 0) + ($score >= 9 ? 18 : 0);
        $coins = reward_cents_for_activity('deberes', (float)$score);
        award_progress($userId, 'Escanear y practicar: '.$material['title'], $xp, $coins, null);
        if ($score >= 8) add_unique_achievement($userId, 'Ficha dominada', 'Has superado una ficha escaneada con buena nota.', '📸');
    } catch (Throwable $e) {}
    return ['ok'=>true,'score'=>$score,'correct'=>$correct,'total'=>$total,'details'=>$details];
}

function convert_scan_to_topic(int $materialId): string {
    ensure_v42_tables();
    try {
        $db = db();
        $st = $db->prepare('SELECT * FROM scanned_materials WHERE id=? LIMIT 1');
        $st->execute([$materialId]);
        $m = $st->fetch();
        if (!$m) return 'No se encontró el material escaneado.';
        $activity = json_decode($m['generated_json'] ?: '{}', true) ?: [];
        $subject = scan_detect_subject(($m['corrected_text'] ?: $m['extracted_text'] ?: ''), $m['subject'] ?: ($activity['subject'] ?? 'Lengua'));
        $title = trim((string)($activity['topic'] ?? $m['title'])) ?: $m['title'];
        $content = "Resumen generado desde foto/ficha:\n\n" . ($activity['summary'] ?? '') . "\n\nExplicación:\n" . ($activity['explanation'] ?? '') . "\n\nTexto base:\n" . ($m['corrected_text'] ?: $m['extracted_text'] ?: '');
        $db->beginTransaction();
        $db->prepare('INSERT INTO topics(subject, course, title, description, content) VALUES(?,?,?,?,?)')
           ->execute([$subject, '4º Primaria Andalucía', $title, 'Tema creado desde Escanear y practicar.', $content]);
        $topicId = (int)$db->lastInsertId();
        $qIns = $db->prepare('INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(?,?,?,?,?,?,?)');
        foreach (($activity['questions'] ?? []) as $q) {
            $type = (($q['type'] ?? 'text') === 'multiple') ? 'multiple' : 'text';
            $opts = $type === 'multiple' ? json_encode(array_values($q['options'] ?? []), JSON_UNESCAPED_UNICODE) : null;
            $qIns->execute([$topicId, $type, (string)$q['question'], $opts, (string)($q['correct_answer'] ?? 'respuesta libre'), (string)($q['explanation'] ?? ''), (int)($q['difficulty'] ?? 1)]);
        }
        $db->commit();
        return 'Tema creado correctamente desde la ficha: '.$title.'.';
    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        return 'No se pudo convertir en tema: '.$e->getMessage();
    }
}


/* V5.5 - actividad de temas y acceso flotante a Luna */
function ensure_topic_activity_table(): void {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS topic_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            topic_id INT NOT NULL,
            action VARCHAR(30) NOT NULL DEFAULT 'ver_tema',
            target_url VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_topic_activity_user_date (user_id, created_at),
            INDEX idx_topic_activity_topic (topic_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

function topic_action_url(int $topicId, string $action): string {
    return match ($action) {
        'estudiar' => 'study.php?id=' . $topicId,
        'ver_tema' => 'topic_view.php?id=' . $topicId,
        'examen' => 'exam.php?topic_id=' . $topicId . '&mode=examen',
        'repaso' => 'exam.php?topic_id=' . $topicId . '&mode=repaso',
        'batalla' => 'exam.php?topic_id=' . $topicId . '&mode=batalla',
        default => 'exam.php?topic_id=' . $topicId . '&mode=deberes',
    };
}

function topic_action_label(string $action): string {
    return match ($action) {
        'estudiar' => 'Estudio',
        'ver_tema' => 'Visto',
        'examen' => 'Examen',
        'repaso' => 'Repaso',
        'batalla' => 'Reto',
        default => 'Deberes',
    };
}

function record_topic_activity(int $userId, int $topicId, string $action, ?string $targetUrl = null): void {
    if ($userId <= 0 || $topicId <= 0) return;
    ensure_topic_activity_table();
    $allowed = ['estudiar','ver_tema','deberes','examen','repaso','batalla'];
    if (!in_array($action, $allowed, true)) $action = 'ver_tema';
    $targetUrl = $targetUrl ?: topic_action_url($topicId, $action);
    try {
        $stmt = db()->prepare('INSERT INTO topic_activity(user_id, topic_id, action, target_url) VALUES(?,?,?,?)');
        $stmt->execute([$userId, $topicId, $action, $targetUrl]);
    } catch (Throwable $e) {}
}

function recent_topics_for_user(int $userId, int $limit = 3): array {
    ensure_topic_activity_table();
    $items = [];
    $seen = [];
    try {
        $stmt = db()->prepare('SELECT a.topic_id, a.action, a.target_url, a.created_at, t.subject, t.title
            FROM topic_activity a
            JOIN topics t ON t.id=a.topic_id
            WHERE a.user_id=?
            ORDER BY a.created_at DESC
            LIMIT 40');
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            $tid = (int)$row['topic_id'];
            if (isset($seen[$tid])) continue;
            $seen[$tid] = true;
            $row['action_label'] = topic_action_label((string)$row['action']);
            $row['target_url'] = $row['target_url'] ?: topic_action_url($tid, (string)$row['action']);
            $items[] = $row;
            if (count($items) >= $limit) return $items;
        }
    } catch (Throwable $e) {}
    try {
        $stmt = db()->prepare('SELECT r.topic_id, r.mode action, r.created_at, t.subject, t.title
            FROM results r
            JOIN topics t ON t.id=r.topic_id
            WHERE r.user_id=?
            ORDER BY r.created_at DESC
            LIMIT 40');
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            $tid = (int)$row['topic_id'];
            if (isset($seen[$tid])) continue;
            $seen[$tid] = true;
            $action = (string)($row['action'] ?: 'deberes');
            $row['action_label'] = topic_action_label($action);
            $row['target_url'] = topic_action_url($tid, $action);
            $items[] = $row;
            if (count($items) >= $limit) return $items;
        }
    } catch (Throwable $e) {}
    return $items;
}

function luna_current_back_url(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
    $path = parse_url($uri, PHP_URL_PATH) ?: '';
    $query = parse_url($uri, PHP_URL_QUERY);
    $file = basename($path);
    $url = $file ?: 'dashboard.php';
    if ($query) $url .= '?' . $query;
    return $url;
}

function luna_safe_back_url(?string $url): string {
    $url = trim((string)$url);
    if ($url === '' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
        return 'dashboard.php';
    }
    $url = str_replace(["\r", "\n"], '', $url);
    if (str_starts_with($url, '../')) return 'dashboard.php';
    return $url;
}

function luna_floating_button(): string {
    $back = luna_current_back_url();
    $href = 'teacher.php?back=' . rawurlencode($back);
    return '<a class="luna-float" href="' . e($href) . '" target="_blank" rel="noopener" aria-label="Preguntar a Luna IA"><span>🌙</span><b>Luna IA</b></a>';
}


/* =========================
   Gestor de cuentas DAZN / servicios
   ========================= */
function ensure_service_accounts_table(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $db = db();
        $db->exec("CREATE TABLE IF NOT EXISTS service_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            password_value VARCHAR(255) NULL,
            buyer VARCHAR(160) NULL,
            service VARCHAR(160) NULL,
            status ENUM('empty','sold','pending') NOT NULL DEFAULT 'empty',
            paid TINYINT(1) NOT NULL DEFAULT 0,
            price_cents INT NULL,
            renewal_date DATE NULL,
            notes TEXT NULL,
            custom_fields JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_service_accounts_email(email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $cols = $db->query("SHOW COLUMNS FROM service_accounts")->fetchAll(PDO::FETCH_COLUMN);
        $needed = [
            'password_value' => "ALTER TABLE service_accounts ADD COLUMN password_value VARCHAR(255) NULL AFTER email",
            'paid' => "ALTER TABLE service_accounts ADD COLUMN paid TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
            'price_cents' => "ALTER TABLE service_accounts ADD COLUMN price_cents INT NULL AFTER paid",
            'renewal_date' => "ALTER TABLE service_accounts ADD COLUMN renewal_date DATE NULL AFTER price_cents",
            'custom_fields' => "ALTER TABLE service_accounts ADD COLUMN custom_fields JSON NULL AFTER notes",
        ];
        foreach ($needed as $col => $sql) if (!in_array($col, $cols, true)) $db->exec($sql);

        $count = (int)$db->query('SELECT COUNT(*) c FROM service_accounts')->fetch()['c'];
        // Seed/update the accounts supplied by the administrator without overwriting later edits.
        $seedPasswords = [
            'francisco.ibanez10@gmail.com' => 'Juanra09',
            'normankaminski@gmx.de' => 'Sokani3912',
            'ernestfrancisco7@gmail.com' => 'Juanra10',
            'florian.flasbeck@gmx.de' => 'Maikelsi234',
            'perokdices88+89@gmail.com' => 'Juanra12',
            'melanie.pracht@gmx.net' => 'Juanra12',
            'califfo78@gmail.com' => 'Giulia15@4!',
        ];
        foreach ($seedPasswords as $email => $pass) {
            $st = $db->prepare('UPDATE service_accounts SET password_value=? WHERE email=? AND (password_value IS NULL OR password_value="")');
            $st->execute([$pass, $email]);
        }
        if ($count === 0) {
            $rows = [
                ['I92447585+3@gmail.com', null, null, null, 'empty', 0],
                ['ikergm05@gmail.com', null, null, null, 'empty', 0],
                ['lcastrosaez@gmail.com', null, null, 'HBO + DAZN', 'sold', 0],
                ['francisco.ibanez10@gmail.com', 'Juanra09', 'Abel Ronco', 'DAZN', 'sold', 0],
                ['hakaini33+79@gmail.com', null, null, null, 'empty', 0],
                ['ferranyesther@hotmail.com', null, null, null, 'empty', 0],
                ['parknicolnuno@gmail.com', null, 'José Luis Ronco', 'DAZN', 'sold', 0],
                ['sscosta@live.com.pt', null, 'José Luis Ronco', 'DAZN', 'sold', 0],
                ['melanie.pracht@gmx.net', 'Juanra12', 'Juanjo', 'DAZN', 'sold', 0],
                ['perokdices88+89@gmail.com', 'Juanra12', 'Juanjo', 'DAZN', 'sold', 0],
                ['normankaminski@gmx.de', 'Sokani3912', 'Manu Ronco', 'DAZN', 'sold', 0],
                ['ernestfrancisco7@gmail.com', 'Juanra10', 'Manu Ronco', 'DAZN', 'sold', 0],
                ['florian.flasbeck@gmx.de', 'Maikelsi234', 'Manu Ronco', 'DAZN', 'sold', 0],
                ['arigatosense33+24@gmail.com', null, null, null, 'empty', 0],
                ['califfo78@gmail.com', 'Giulia15@4!', null, null, 'pending', 0],
            ];
            $st = $db->prepare('INSERT INTO service_accounts(email,password_value,buyer,service,status,paid) VALUES(?,?,?,?,?,?)');
            foreach ($rows as $r) $st->execute($r);
        }
    } catch (Throwable $e) {}
}

function service_accounts_for_admin(): array {
    ensure_service_accounts_table();
    try { return db()->query('SELECT * FROM service_accounts ORDER BY CASE status WHEN "empty" THEN 0 WHEN "pending" THEN 1 ELSE 2 END, email')->fetchAll(); }
    catch (Throwable $e) { return []; }
}
