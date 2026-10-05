<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM topics WHERE id=?');
$st->execute([$id]);
$topic = $st->fetch();
if (!$topic) die('Tema no encontrado');
record_topic_activity((int)$user['id'], (int)$topic['id'], 'ver_tema');
$f = db()->prepare('SELECT * FROM topic_files WHERE topic_id=? ORDER BY created_at DESC');
$f->execute([$id]);
$files = $f->fetchAll();
$q = db()->prepare('SELECT COUNT(*) total FROM questions WHERE topic_id=?');
$q->execute([$id]);
$totalQ = (int)$q->fetch()['total'];
$last = db()->prepare('SELECT score, created_at, mode FROM results WHERE user_id=? AND topic_id=? ORDER BY created_at DESC LIMIT 1');
$last->execute([(int)$user['id'], $id]);
$lastResult = $last->fetch();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($topic['title']) ?></title><link rel="manifest" href="manifest.webmanifest?v=5.12"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.12"></head><body><div class="wrap topic-page">
<header class="topbar"><div class="brand"><?= subject_icon($topic['subject']) ?> <?= e($topic['title']) ?></div><nav class="nav"><a class="secondary" href="topics.php?subject=<?= urlencode($topic['subject']) ?>">Volver</a><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="teacher.php?back=<?= urlencode('topic_view.php?id='.(int)$id) ?>">Luna IA</a></nav></header>
<section class="card topic-hero"><p class="pill"><?= e($topic['subject']) ?> · <?= e($topic['course']) ?></p><h1><?= e($topic['title']) ?></h1><p class="muted"><?= e($topic['description']) ?></p><div class="topic-stats"><div><b><?= (int)$totalQ ?></b><span>preguntas</span></div><div><b>15</b><span>deberes</span></div><div><b>10</b><span>examen</span></div><?php if($lastResult): ?><div><b><?= e((string)$lastResult['score']) ?></b><span>última nota</span></div><?php endif; ?></div></section>
<section class="card"><h2>Elige qué hacer</h2><div class="grid topic-action-grid"><a class="subject visual-action" href="study.php?id=<?= (int)$id ?>"><strong>📚 Estudiar</strong><span class="muted">Explicación clara, resumen y trucos para memorizar.</span><span class="pill">Primero</span></a><?php if($totalQ>0): ?><a class="subject visual-action" href="exam.php?topic_id=<?= (int)$id ?>&mode=deberes"><strong>✏️ Deberes</strong><span class="muted">15 preguntas para practicar sin presión.</span><span class="pill">Practicar</span></a><a class="subject visual-action" href="exam.php?topic_id=<?= (int)$id ?>&mode=examen"><strong>📝 Examen</strong><span class="muted">10 preguntas para comprobar si está preparado.</span><span class="pill">Comprobar</span></a><a class="subject visual-action" href="exam.php?topic_id=<?= (int)$id ?>&mode=batalla"><strong>✨ Reto con Emilia</strong><span class="muted">5 preguntas rápidas sobre este tema.</span><span class="pill">Motivación</span></a><?php endif; ?></div></section>
<?php if($topic['content']): ?><section class="card preview-card"><h2>Vista rápida</h2><p class="muted">Un vistazo al comienzo del tema. Para verlo bonito y completo, pulsa Estudiar.</p><details><summary>Ver apuntes rápidos</summary><p><?= nl2br(e(mb_substr($topic['content'], 0, 1600, 'UTF-8'))) ?><?= mb_strlen($topic['content'], 'UTF-8') > 1600 ? '...' : '' ?></p></details></section><?php endif; ?>
<?php if($files): ?><section class="card"><h2>Fotos, fichas y apuntes</h2><div class="grid"><?php foreach($files as $file): ?><a class="subject" href="../<?= e($file['file_path']) ?>" target="_blank"><strong><?= e($file['title'] ?: $file['original_name']) ?></strong><p class="muted"><?= e($file['notes']) ?></p><span class="pill"><?= e($file['kind']) ?></span></a><?php endforeach; ?></div></section><?php endif; ?>
<?= luna_floating_button() ?>
</div></body></html>
