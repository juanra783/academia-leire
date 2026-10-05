<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_admin();
$subject = $_GET['subject'] ?? 'Matemáticas';
$difficulty = (int)($_GET['difficulty'] ?? 1);
$count = max(5, min(30, (int)($_GET['count'] ?? 10)));
$subjects = ['Matemáticas','Lengua','Conocimiento del Medio','Inglés'];
$stmt = db()->prepare('SELECT q.*, t.subject, t.title topic_title FROM questions q JOIN topics t ON t.id=q.topic_id WHERE t.subject=? AND q.difficulty<=? ORDER BY RAND() LIMIT '.$count);
$stmt->execute([$subject, $difficulty]);
$questions = $stmt->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Examen rápido</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap print-page">
<header class="topbar"><div class="brand">🧪 Crear examen rápido</div><nav class="nav"><a class="secondary" href="panel.php">Panel</a><button class="btn" onclick="window.print()">Imprimir</button></nav></header>
<section class="card"><h1>Examen rápido</h1><form class="form" method="get"><label>Asignatura<select name="subject"><?php foreach($subjects as $s): ?><option value="<?= e($s) ?>" <?= $s===$subject?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></label><label>Dificultad máxima<select name="difficulty"><option value="1" <?= $difficulty===1?'selected':'' ?>>Fácil</option><option value="2" <?= $difficulty===2?'selected':'' ?>>Media</option><option value="3" <?= $difficulty===3?'selected':'' ?>>Difícil</option></select></label><label>Nº preguntas<input class="input" type="number" name="count" value="<?= $count ?>" min="5" max="30"></label><button class="btn">Generar</button></form></section>
<section class="card"><p class="pill"><?= e($subject) ?> · dificultad <?= $difficulty ?> · <?= count($questions) ?> preguntas</p><?php foreach($questions as $i=>$q): ?><div class="question"><h2><?= $i+1 ?>. <?= e($q['question']) ?></h2><p class="muted"><?= e($q['topic_title']) ?></p><?php if($q['type']==='multiple'): $opts=json_decode($q['options_json']?:'[]',true)?:[]; foreach($opts as $opt): ?><p>☐ <?= e($opt) ?></p><?php endforeach; else: ?><div class="answer-line"></div><?php endif; ?></div><?php endforeach; ?></section>
<section class="card"><h2>Soluciones</h2><?php foreach($questions as $i=>$q): ?><p><b><?= $i+1 ?>.</b> <?= e(str_replace('|',' / ',$q['correct_answer'])) ?> — <?= e($q['explanation']) ?></p><?php endforeach; ?></section>
</div></body></html>
