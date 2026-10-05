<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$texts = reading_texts();
$key = $_GET['text'] ?? array_key_first($texts);
if (!isset($texts[$key])) $key = array_key_first($texts);
$item = $texts[$key];
$done = false; $correct = 0; $details = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $done = true;
    foreach ($item['questions'] as $i=>$q) {
        $ans = trim($_POST['answer'][$i] ?? '');
        $ok = is_correct_answer($ans, $q['a']);
        if ($ok) $correct++;
        $details[] = ['question'=>$q['q'], 'answer'=>$ans, 'correct'=>$q['a'], 'ok'=>$ok, 'explanation'=>$q['e']];
    }
    $score = round(($correct / count($item['questions'])) * 10, 2);
    $xp = 15 + ($score >= 7 ? 10 : 0) + ($score >= 9 ? 15 : 0);
    $coins = 5 + ($score >= 8 ? 5 : 0);
    award_progress((int)$user['id'], 'Comprensión lectora: '.$item['title'], $xp, $coins, null);
    try { ensure_v42_tables(); db()->prepare('UPDATE daily_tasks SET completed_at=NOW() WHERE user_id=? AND task_date=CURDATE() AND task_key=? AND completed_at IS NULL')->execute([(int)$user['id'], 'lectura']); } catch(Throwable $e) {}
}

?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Comprensión lectora</title><link rel="manifest" href="manifest.webmanifest?v=5.5"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=55"><link rel="icon" href="favicon.ico?v=55"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=55"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.5"></head><body><div class="wrap">
<header class="topbar"><div class="brand">📖 Comprensión lectora <span class="pill">V4.7</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="trainer.php">Entrenador</a></nav></header>
<section class="card"><p class="pill">Lengua · 4º Primaria</p><h1><?= e($item['title']) ?></h1><p class="reading"><?= nl2br(e($item['text'])) ?></p></section>
<section class="card"><h2>Elegir lectura</h2><div class="grid small"><?php foreach($texts as $k=>$t): ?><a class="subject" href="reading.php?text=<?= e($k) ?>"><strong><?= e($t['title']) ?></strong><span class="muted">Lectura con preguntas autocorregibles</span></a><?php endforeach; ?></div></section>
<?php if(!$done): ?>
<form class="card" method="post"><h2>Preguntas</h2><?php foreach($item['questions'] as $i=>$q): ?><div class="question"><h3><?= ($i+1) ?>. <?= e($q['q']) ?></h3><input class="input" name="answer[<?= $i ?>]" placeholder="Escribe tu respuesta" required></div><?php endforeach; ?><button class="btn">Corregir lectura</button></form>
<?php else: ?>
<section class="card"><p class="score"><?= e($score) ?>/10</p><h1><?= e(score_label((float)$score)) ?></h1><p class="muted">Has acertado <?= $correct ?> de <?= count($item['questions']) ?>.</p></section>
<section class="card"><h2>Corrección</h2><?php foreach($details as $i=>$d): ?><div class="question"><h3><?= ($i+1) ?>. <?= e($d['question']) ?></h3><?php if($d['ok']): ?><p class="ok">Bien: <?= e($d['answer']) ?></p><?php else: ?><p class="bad">Tu respuesta: <?= e($d['answer'] ?: 'sin responder') ?></p><p><b>Respuesta válida:</b> <?= e(str_replace('|', ' / ', $d['correct'])) ?></p><div class="notice"><b>Explicación:</b> <?= e($d['explanation']) ?></div><?php endif; ?></div><?php endforeach; ?></section>
<?php endif; ?>
<?= luna_floating_button() ?>
</div></body></html>
