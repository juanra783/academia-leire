<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$recent = recent_topics_for_user((int)$user['id'], 6);
$weak = weak_topics_for_user((int)$user['id'], 6);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Repaso mágico · Academia Star 6.3</title><link rel="manifest" href="manifest.webmanifest?v=6"><meta name="theme-color" content="#251052"><link rel="stylesheet" href="assets/css/style.css?v=63"></head>
<body class="v6-page"><div class="v6-ambient" aria-hidden="true"><i></i><i></i><i></i></div><div class="wrap">
<header class="topbar v6-topbar"><div class="brand">✨ Academia Star <span class="pill v6-pill">6.1</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="nuevo_curso.php">Nuevo Curso</a><a class="secondary" href="teacher.php?back=repaso.php">Luna IA</a></nav></header>
<section class="v6-repaso-hero v6-glass"><div><span class="v6-kicker">CENTRO DE ENTRENAMIENTO</span><h1>Repasar también puede ser una aventura</h1><p>Elige una ruta rápida, refuerza lo que cuesta o entra en el Examen Loco para intentar una racha perfecta.</p><div class="v6-hero-actions"><a class="v6-main-btn" href="crazy_exam.php">🧠 Entrar al Examen Loco</a><a class="btn secondary" href="trainer.php">Plan inteligente</a></div></div><div class="v6-orb"><span>⚡</span></div></section>
<section class="v6-mode-grid">
<a class="v6-mode-card v6-card-cyan" href="repaso_inicio_curso.php"><span class="v6-mode-icon">🎒</span><b>Inicio de curso</b><small>Repasa lo esencial antes de comenzar los temas nuevos.</small></a>
<a class="v6-mode-card v6-card-cyan" href="trainer.php"><span class="v6-mode-icon">🎯</span><b>Repaso inteligente</b><small>La app decide qué conviene practicar hoy.</small></a>
<a class="v6-mode-card v6-card-pink" href="crazy_exam.php?selection=failed"><span class="v6-mode-icon">🔁</span><b>Preguntas falladas</b><small>Volver sobre los errores hasta dominarlos.</small></a>
<a class="v6-mode-card v6-card-gold" href="crazy_exam.php?count=10"><span class="v6-mode-icon">🔥</span><b>Racha rápida</b><small>Diez respuestas perfectas sin fallar.</small></a>
<a class="v6-mode-card v6-card-violet" href="crazy_exam.php?count=40"><span class="v6-mode-icon">💎</span><b>Racha legendaria</b><small>Cuarenta seguidas. El desafío máximo.</small></a>
</section>
<section class="v6-glass v6-section"><div class="v6-section-head"><div><span class="v6-kicker">CONTINUAR</span><h2>Últimos temas trabajados</h2></div></div><div class="grid small"><?php if(!$recent): ?><p class="muted">Todavía no hay actividad reciente.</p><?php else: foreach($recent as $r): ?><article class="v6-topic-card"><span><?= subject_icon($r['subject']) ?></span><div><b><?= e($r['title']) ?></b><small><?= e($r['subject']) ?></small></div><a href="<?= e($r['target_url']) ?>">Continuar →</a></article><?php endforeach; endif; ?></div></section>
<section class="v6-glass v6-section"><div class="v6-section-head"><div><span class="v6-kicker">REFUERZO</span><h2>Lo que más conviene practicar</h2></div></div><div class="grid small"><?php if(!$weak): ?><p class="ok">No hay temas flojos registrados.</p><?php else: foreach($weak as $w): ?><article class="v6-topic-card warning"><span>🛡️</span><div><b><?= e($w['title']) ?></b><small><?= e($w['subject']) ?><?= $w['avg_score']!==null?' · '.e((string)$w['avg_score']).'/10':'' ?></small></div><a href="exam.php?topic_id=<?= (int)$w['id'] ?>&mode=repaso">Repasar →</a></article><?php endforeach; endif; ?></div></section>
<footer class="footer">Academia Star 6.3 · Repaso, fantasía y progreso real.</footer><?= luna_floating_button() ?></div></body></html>
