<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
ensure_v44_tables();
$profile = ensure_student_profile((int)$user['id']);
$tasks = ensure_daily_tasks((int)$user['id']);
$percent = daily_plan_percent($tasks);
$weak = weak_topics_for_user((int)$user['id'], 5);
$errors = frequent_error_topics((int)$user['id'], 5);
$summary = weekly_summary_for_user((int)$user['id']);
$focusTopics = current_focus_topics((int)$user['id'], 4);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Entrenador inteligente</title><link rel="manifest" href="manifest.webmanifest?v=5.13"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=55"><link rel="icon" href="favicon.ico?v=55"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=55"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.13"></head><body><div class="wrap">
<header class="topbar"><div class="brand">🧠 Entrenador Inteligente <span class="pill">V5.13</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="adventure.php">Aventura</a><a class="secondary" href="shop.php">Tienda</a></nav></header>
<section class="kid-hero">
  <div class="avatar-card"><img class="avatar" src="<?= e($profile['avatar'] ?: 'assets/img/avatar-leire-main.jpg') ?>" alt="Leire"><div class="level-badge">Plan diario</div><div class="xpbar"><span style="width:<?= $percent ?>%"></span></div><p class="muted"><?= $percent ?>% completado</p></div>
  <div class="card hero-main"><p class="pill">Recomendación automática</p><h1>Hoy entrenamos con cabeza</h1><p class="muted"><?= e(trainer_message((int)$user['id'])) ?></p><div class="stats compact"><div class="mini"><b><?= (int)$summary['total'] ?></b><span>Actividades esta semana</span></div><div class="mini"><b><?= e((string)$summary['avg_score']) ?>/10</b><span>Media semanal</span></div><div class="mini"><b>💶 <?= e(format_cents((int)$profile['coins'])) ?></b><span>Saldo</span></div></div></div>
</section>
<section class="card"><h2>🎯 Temas que mandan hoy</h2><p class="muted">Estos son los últimos temas trabajados por Leire y por eso el entrenador los pone primero en el plan y en los retos con Emilia.</p><?php if(!$focusTopics): ?><p class="muted">Aún no hay temas recientes suficientes.</p><?php else: ?><div class="grid small"><?php foreach($focusTopics as $ft): ?><div class="subject"><strong><?= subject_icon($ft['subject']) ?> <?= e($ft['title']) ?></strong><span class="muted"><?= e($ft['subject']) ?></span><p class="quick-actions"><a class="btn secondary" href="study.php?id=<?= (int)$ft['id'] ?>">Estudiar</a><a class="btn secondary" href="exam.php?topic_id=<?= (int)$ft['id'] ?>&mode=deberes">Deberes</a><a class="btn secondary" href="exam.php?topic_id=<?= (int)$ft['id'] ?>&mode=batalla">Reto</a></p></div><?php endforeach; ?></div><?php endif; ?></section>
<section class="card"><h2>✅ Plan de hoy</h2><div class="grid">
<?php foreach($tasks as $task): ?>
  <div class="subject task-card <?= !empty($task['completed'])?'done':'' ?>"><strong><?= !empty($task['completed'])?'✅':'🎯' ?> <?= e($task['title']) ?></strong><p class="muted"><?= e($task['subject'] ?: 'General') ?> · recompensa: +<?= (int)$task['xp_reward'] ?> XP y € según nota</p><?php if(!empty($task['completed'])): ?><span class="pill equipped">Completado</span><?php else: ?><a class="btn" href="<?= e($task['action_url']) ?>">Empezar</a><?php endif; ?></div>
<?php endforeach; ?>
</div></section>
<section class="split">
  <div class="card"><h2>⚠️ Temas a reforzar</h2><?php if(!$weak): ?><p class="muted">Todavía no hay datos suficientes.</p><?php else: ?><div class="grid small"><?php foreach($weak as $w): ?><a class="subject" href="exam.php?topic_id=<?= (int)$w['id'] ?>&mode=repaso"><strong><?= e($w['title']) ?></strong><span class="muted"><?= e($w['subject']) ?><?= $w['avg_score']!==null?' · media '.e((string)$w['avg_score']).'/10':'' ?></span><span class="pill">Repasar</span></a><?php endforeach; ?></div><?php endif; ?></div>
  <div class="card"><h2>❌ Errores frecuentes</h2><?php if(!$errors): ?><p class="muted">Cuando haya fallos, aparecerán aquí ordenados.</p><?php else: ?><table class="table"><tr><th>Tema</th><th>Fallos</th><th></th></tr><?php foreach($errors as $er): ?><tr><td><?= e($er['subject'].' · '.$er['title']) ?></td><td><b><?= (int)$er['errors'] ?></b></td><td><a href="exam.php?topic_id=<?= (int)$er['topic_id'] ?>&mode=repaso">Practicar</a></td></tr><?php endforeach; ?></table><?php endif; ?></div>
</section>
<section class="card"><h2>✨ Reto rápido con Emilia</h2><p class="muted">Un reto usa 5 preguntas y ahora sale sobre todo de los últimos temas que Leire está viendo. Así el reto acompaña lo que está dando en clase y no un tema viejo cualquiera.</p><p><a class="btn" href="battle.php">Elegir reto</a> <a class="btn secondary" href="shop.php">Ir a la tienda</a></p></section>
<?= luna_floating_button() ?>
</div></body></html>
