<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_admin();
$topic_id = (int)($_GET['topic_id'] ?? 0);
$st = db()->prepare('SELECT * FROM topics WHERE id=?');
$st->execute([$topic_id]);
$topic = $st->fetch();
if (!$topic) die('Tema no encontrado');
$q = db()->prepare('SELECT * FROM questions WHERE topic_id=? ORDER BY RAND() LIMIT 20');
$q->execute([$topic_id]);
$questions = $q->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Examen imprimible</title><link rel="stylesheet" href="../public/assets/css/style.css"><style>body{background:#fff}.print-page{max-width:900px;margin:25px auto}.answer-line{border-bottom:1px solid #222;height:34px;margin-top:14px}@media print{.no-print{display:none}.card{box-shadow:none;border:0}.wrap{width:100%}}</style></head><body><div class="print-page">
<div class="no-print"><button class="btn" onclick="window.print()">Imprimir / Guardar PDF</button> <a class="btn secondary" href="panel.php">Volver</a></div>
<section class="card"><h1>Examen</h1><p><b>Nombre:</b> Leire &nbsp;&nbsp;&nbsp; <b>Fecha:</b> ____ / ____ / ______</p><p><b>Asignatura:</b> <?= e($topic['subject']) ?> &nbsp;&nbsp; <b>Tema:</b> <?= e($topic['title']) ?></p></section>
<section class="card"><?php if(!$questions): ?><p>No hay preguntas para imprimir.</p><?php endif; ?><?php foreach($questions as $i=>$row): ?><div class="question"><h2><?= ($i+1) ?>. <?= e($row['question']) ?></h2><?php if($row['type']==='multiple'): ?><?php $opts=json_decode($row['options_json'] ?: '[]', true) ?: []; foreach($opts as $op): ?><p>☐ <?= e($op) ?></p><?php endforeach; ?><?php else: ?><div class="answer-line"></div><?php endif; ?></div><?php endforeach; ?></section>
</div></body></html>
