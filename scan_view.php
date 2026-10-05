<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/ai.php';
$user = require_login();
ensure_v42_tables();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$material = scan_get_material($id, (int)$user['id'], $user['role']==='admin');
if (!$material) die('Material no encontrado');
$message = '';
$answerResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'regenerate') {
        $text = trim($_POST['corrected_text'] ?? '');
        $subject = scan_detect_subject($text, $_POST['subject'] ?? ($material['subject'] ?? 'Auto'));
        $mode = $_POST['mode'] ?? 'todo';
        if (!in_array($mode, ['todo','ejercicios','examen','explicacion','comprension'], true)) $mode = 'todo';
        $difficulty = max(1, min(3, (int)($_POST['difficulty'] ?? 1)));
        $activity = generate_scan_activity($text, $subject, $mode, $difficulty, (int)$user['id']);
        db()->prepare('UPDATE scanned_materials SET subject=?, corrected_text=?, generated_json=?, status=? WHERE id=?')
            ->execute([$activity['subject'] ?? $subject, $text, json_encode($activity, JSON_UNESCAPED_UNICODE), 'regenerado', $id]);
        redirect('scan_view.php?id='.$id.'&regen=1');
    }
    if ($action === 'answer') {
        $answerResult = scan_score_answers($id, (int)$user['id'], $_POST['answer'] ?? []);
    }
}
$material = scan_get_material($id, (int)$user['id'], $user['role']==='admin');
$activity = json_decode($material['generated_json'] ?: '{}', true) ?: [];
$questions = $activity['questions'] ?? [];
$fileUrl = '../'.($material['file_path'] ?? '');
$isImage = str_starts_with((string)$material['mime_type'], 'image/');
$attempts = [];
try {
    $st = db()->prepare('SELECT * FROM scan_attempts WHERE material_id=? AND user_id=? ORDER BY created_at DESC LIMIT 5');
    $st->execute([$id, (int)$user['id']]);
    $attempts = $st->fetchAll();
} catch (Throwable $e) {}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($material['title']) ?></title><link rel="manifest" href="manifest.webmanifest?v=5.13"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=55"><link rel="icon" href="favicon.ico?v=55"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=55"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.13"></head><body><div class="wrap">
<header class="topbar"><div class="brand">📸 <?= e($material['title']) ?> <span class="pill">V5.13</span></div><nav class="nav"><a class="secondary" href="scan.php">Otro escaneo</a><a class="secondary" href="dashboard.php">Inicio</a><?php if($user['role']==='admin'): ?><a class="secondary" href="../admin/scans.php">Panel escaneos</a><?php endif; ?></nav></header>
<?php if(isset($_GET['regen'])): ?><div class="notice ok"><b>Listo:</b> actividades regeneradas con el texto corregido.</div><?php endif; ?>
<?php if($answerResult && $answerResult['ok']): ?><section class="card result-card"><h1>Resultado</h1><p class="score"><?= e((string)$answerResult['score']) ?>/10</p><p><b><?= (int)$answerResult['correct'] ?></b> aciertos de <b><?= (int)$answerResult['total'] ?></b>.</p><div class="grid small"><?php foreach($answerResult['details'] as $i=>$d): ?><div class="subject <?= $d['ok']?'done':'bad-soft' ?>"><strong><?= $d['ok']?'✅':'❌' ?> <?= ($i+1) ?>. <?= e($d['question']) ?></strong><span class="muted">Tu respuesta: <?= e($d['answer'] ?: 'Sin responder') ?></span><p><b>Correcta:</b> <?= e($d['correct']) ?></p><p><?= e($d['explanation']) ?></p></div><?php endforeach; ?></div></section><?php elseif($answerResult && !$answerResult['ok']): ?><div class="notice bad"><?= e($answerResult['error']) ?></div><?php endif; ?>
<section class="split">
  <div class="card"><h1><?= e($activity['topic'] ?? $material['title']) ?></h1><p class="pill"><?= e($activity['subject'] ?? $material['subject'] ?? 'Asignatura') ?></p><p class="muted">Creado desde foto/ficha el <?= e($material['created_at']) ?>.</p><p class="pill">Motor: <?= e($material['source_type'] ?: ($activity['engine'] ?? 'local')) ?></p><?php if($isImage): ?><img class="scan-preview" src="<?= e($fileUrl) ?>" alt="Foto escaneada"><?php else: ?><p><a class="btn secondary" href="<?= e($fileUrl) ?>" target="_blank">Abrir archivo</a></p><?php endif; ?></div>
  <div class="card"><h2>Explicación de Luna</h2><p><?= nl2br(e($activity['explanation'] ?? 'No hay explicación generada.')) ?></p><h3>Pasos de estudio</h3><?php if(!empty($activity['study_steps'])): ?><ol><?php foreach($activity['study_steps'] as $step): ?><li><?= e($step) ?></li><?php endforeach; ?></ol><?php else: ?><p class="muted">Lee, subraya, practica y repite los fallos.</p><?php endif; ?></div>
</section>
<section class="card"><h2>Resumen detectado</h2><p><?= nl2br(e($activity['summary'] ?? 'Sin resumen.')) ?></p><?php if(!empty($activity['detected_text'])): ?><details class="notice"><summary>Ver texto detectado por IA Vision</summary><p><?= nl2br(e($activity['detected_text'])) ?></p></details><?php endif; ?></section>
<section class="card"><h2>✍️ Actividades generadas</h2><?php if(!$questions): ?><p class="muted">No hay preguntas generadas. Corrige el texto y regenera.</p><?php else: ?><form method="post"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="answer"><?php foreach($questions as $i=>$q): ?><div class="question"><h3><?= ($i+1) ?>. <?= e($q['question'] ?? '') ?></h3><?php if(($q['type'] ?? 'text') === 'multiple'): ?><?php foreach(($q['options'] ?? []) as $opt): ?><label class="option"><input type="radio" name="answer[<?= (int)$i ?>]" value="<?= e($opt) ?>" required> <?= e($opt) ?></label><?php endforeach; ?><?php else: ?><input class="input" name="answer[<?= (int)$i ?>]" placeholder="Escribe tu respuesta" required><?php endif; ?></div><?php endforeach; ?><button class="btn">Terminar y corregir</button></form><?php endif; ?></section>
<section class="card"><h2>🛠️ Corregir texto y regenerar</h2><p class="muted">Si la foto se leyó mal o quieres añadir más texto del libro, corrígelo aquí y vuelve a generar.</p><form class="form" method="post"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="regenerate"><div class="grid small"><label>Asignatura<select name="subject"><option><?= e($material['subject'] ?: 'Auto') ?></option><option>Auto</option><option>Matemáticas</option><option>Lengua</option><option>Conocimiento del Medio</option><option>Inglés</option></select></label><label>Modo<select name="mode"><option value="todo">Todo</option><option value="ejercicios">Ejercicios</option><option value="examen">Mini examen</option><option value="explicacion">Explicación</option><option value="comprension">Comprensión</option></select></label><label>Dificultad<select name="difficulty"><option value="1">Fácil</option><option value="2">Media</option><option value="3">Difícil</option></select></label></div><textarea name="corrected_text" rows="12"><?= e($material['corrected_text'] ?: $material['extracted_text'] ?: '') ?></textarea><button class="btn secondary">Regenerar actividades</button></form></section>
<?php if($attempts): ?><section class="card"><h2>Intentos anteriores</h2><table class="table"><tr><th>Fecha</th><th>Modo</th><th>Nota</th><th>Aciertos</th></tr><?php foreach($attempts as $a): ?><tr><td><?= e($a['created_at']) ?></td><td><?= e($a['mode']) ?></td><td><b><?= e((string)$a['score']) ?>/10</b></td><td><?= (int)$a['correct_questions'] ?>/<?= (int)$a['total_questions'] ?></td></tr><?php endforeach; ?></table></section><?php endif; ?>
<footer class="footer">V5.13 · Foto a tareas, exámenes y explicaciones con IA Vision.</footer>
<?= luna_floating_button() ?>
</div></body></html>
