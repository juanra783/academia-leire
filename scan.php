<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/ai.php';
$user = require_login();
ensure_v42_tables();
$error = '';
$aiStats = ai_usage_stats((int)$user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manualText = trim($_POST['manual_text'] ?? '');
    $hasFile = isset($_FILES['scan_file']) && (($_FILES['scan_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
    $subjectInput = $_POST['subject'] ?? 'Auto';
    $mode = $_POST['mode'] ?? 'todo';
    if (!in_array($mode, ['todo','ejercicios','examen','explicacion','comprension'], true)) $mode = 'todo';
    $difficulty = max(1, min(3, (int)($_POST['difficulty'] ?? 1)));
    $title = trim($_POST['title'] ?? '');
    if ($title === '') $title = ($hasFile ? 'Ficha escaneada ' : 'Texto pegado ') . date('d/m/Y H:i');

    if (!$hasFile && $manualText === '') {
        $error = 'Sube una foto/PDF o escribe/pega el texto de la ficha para generar actividades.';
    } elseif ($hasFile) {
        $upload = save_scan_upload($_FILES['scan_file'] ?? []);
        if (!$upload['ok']) {
            $error = $upload['error'];
        } else {
            $ocrText = extract_text_from_scan($upload['abs'], $upload['mime']);
            $baseText = trim($manualText !== '' ? $manualText."\n\n".$ocrText : $ocrText);
            $subject = scan_detect_subject($baseText, $subjectInput);
            $generated = generate_scan_activity_from_upload($upload['abs'], $upload['mime'], $ocrText, $manualText, $subject, $mode, $difficulty, (int)$user['id']);
            $activity = $generated['activity'];
            $baseText = $generated['base_text'];
            $sourceType = $generated['engine'];
            $status = $generated['status'];
            try {
                $st = db()->prepare('INSERT INTO scanned_materials(user_id,title,subject,source_type,file_path,original_name,mime_type,size_bytes,extracted_text,corrected_text,generated_json,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
                $st->execute([
                    (int)$user['id'], $title, $activity['subject'] ?? $subject, $sourceType, $upload['path'], $upload['original'], $upload['mime'], $upload['size'],
                    $ocrText, $baseText, json_encode($activity, JSON_UNESCAPED_UNICODE), $status
                ]);
                redirect('scan_view.php?id='.(int)db()->lastInsertId());
            } catch (Throwable $e) {
                $error = 'No se pudo guardar el escaneo: '.$e->getMessage();
            }
        }
    } else {
        // V5.13: modo texto/manual sin archivo. Sirve para copiar preguntas del libro o de una ficha.
        $subject = scan_detect_subject($manualText, $subjectInput);
        $activity = generate_scan_activity($manualText, $subject, $mode, $difficulty, (int)$user['id']);
        $sourceType = (ai_enabled() && $manualText !== '') ? 'ia_texto_manual' : 'texto_manual_local';
        $status = $sourceType === 'ia_texto_manual' ? 'generado_ia_texto' : 'generado_texto_local';
        try {
            $st = db()->prepare('INSERT INTO scanned_materials(user_id,title,subject,source_type,file_path,original_name,mime_type,size_bytes,extracted_text,corrected_text,generated_json,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                (int)$user['id'], $title, $activity['subject'] ?? $subject, $sourceType, null, null, null, 0,
                $manualText, $manualText, json_encode($activity, JSON_UNESCAPED_UNICODE), $status
            ]);
            redirect('scan_view.php?id='.(int)db()->lastInsertId());
        } catch (Throwable $e) {
            $error = 'No se pudo guardar el texto/ficha: '.$e->getMessage();
        }
    }
}
$recent = scan_recent_materials((int)$user['id'], false, 8);
$phpMaxUpload = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Escanear y practicar</title><link rel="manifest" href="manifest.webmanifest?v=5.13"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=55"><link rel="icon" href="favicon.ico?v=55"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=55"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.13"></head><body><div class="wrap">
<header class="topbar"><div class="brand">📸 Escanear y practicar <span class="pill">V5.13</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="trainer.php">Entrenador</a><a class="secondary" href="teacher.php">Luna IA</a><?php if($user['role']==='admin'): ?><a class="secondary" href="../admin/scans.php">Panel escaneos</a><?php endif; ?></nav></header>
<section class="scan-hero">
  <div class="card hero-main"><p class="pill">Foto, PDF o texto</p><h1>Foto o ficha → deberes, examen y explicación</h1><p class="muted">Puedes subir una foto del libro/ficha, elegir un PDF o pegar directamente preguntas escritas. La app generará explicación, ejercicios y mini examen.</p><div class="notice"><b>Consejo:</b> una sola página, buena luz y sin sombras. En iPhone, si una foto HEIC da problema, haz una captura o envíala como JPG/PNG.</div><p class="pill"><?= ai_enabled() && (!defined('AI_VISION_ENABLED') || AI_VISION_ENABLED) ? 'IA Vision lista' : 'Modo local / Vision desactivada' ?></p><p class="muted">Uso IA hoy: <?= (int)$aiStats['daily_count'] ?>/<?= (int)$aiStats['daily_limit'] ?: '∞' ?> · Límite PHP: <?= e($phpMaxUpload) ?> / POST <?= e($postMax) ?></p></div>
  <div class="card"><div class="scan-phone">📷</div><h2>También vale texto escrito</h2><p class="muted">Si la foto no sube o tienes las preguntas copiadas, pega el texto abajo y genera tareas sin archivo.</p></div>
</section>
<?php if($error): ?><div class="notice bad"><b>Error:</b> <?= e($error) ?></div><?php endif; ?>
<form class="card form" method="post" enctype="multipart/form-data">
  <h2>Crear tareas desde foto, PDF o texto</h2>
  <label>Título opcional<input class="input" name="title" placeholder="Ejemplo: Medidas de longitud, ficha de ecosistemas..."></label>
  <label>Foto, imagen o PDF opcional<input id="scan_file" class="input" type="file" name="scan_file" accept="image/*,application/pdf,.pdf"></label>
  <p class="file-hint">En móvil debería abrir cámara/galería. Si no eliges archivo, puedes pegar el texto en el cuadro inferior.</p>
  <div class="grid small">
    <label>Asignatura<select name="subject"><option>Auto</option><option>Matemáticas</option><option>Lengua</option><option>Conocimiento del Medio</option><option>Inglés</option></select></label>
    <label>Qué quieres crear<select name="mode"><option value="todo">Todo: explicación + ejercicios + examen</option><option value="ejercicios">Ejercicios de práctica</option><option value="examen">Mini examen</option><option value="explicacion">Explicación sencilla</option><option value="comprension">Comprensión lectora</option></select></label>
    <label>Dificultad<select name="difficulty"><option value="1">Fácil</option><option value="2">Media</option><option value="3">Difícil</option></select></label>
  </div>
  <label>Preguntas escritas o texto de la ficha<textarea name="manual_text" placeholder="Pega aquí preguntas, enunciados o un tema del libro. Ejemplo: Medidas de longitud: km, m, cm, mm. 1 m = 100 cm... La app generará deberes y examen aunque no subas foto."></textarea></label>
  <button class="btn">Generar actividades</button>
</form>
<section class="card"><h2>Últimos escaneos/textos</h2><?php if(!$recent): ?><p class="muted">Todavía no hay fichas ni textos generados.</p><?php else: ?><div class="grid small"><?php foreach($recent as $r): ?><a class="subject scan-mini" href="scan_view.php?id=<?= (int)$r['id'] ?>"><strong>📄 <?= e($r['title']) ?></strong><span class="muted"><?= e($r['subject'] ?: 'Sin asignatura') ?> · <?= e($r['source_type'] ?: 'material') ?> · <?= e($r['created_at']) ?></span></a><?php endforeach; ?></div><?php endif; ?></section>
<footer class="footer">V5.13 · Foto/PDF/texto a deberes, exámenes y explicaciones.</footer>
<?= luna_floating_button() ?>
</div></body></html>
