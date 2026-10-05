<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
require_admin($user);
$db=db();
$logs=[];$weak=[];$xp=[];
try { $logs=$db->query('SELECT l.*, u.name FROM ai_logs l JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 30')->fetchAll(); } catch(Throwable $e) {}
try { $weak=$db->query('SELECT t.subject,t.title,ROUND(AVG(r.score),2) avg_score,COUNT(r.id) tries FROM results r JOIN topics t ON t.id=r.topic_id GROUP BY t.id HAVING avg_score<7 ORDER BY avg_score ASC LIMIT 10')->fetchAll(); } catch(Throwable $e) {}
try { $xp=$db->query('SELECT x.*,u.name FROM xp_history x JOIN users u ON u.id=x.user_id ORDER BY x.created_at DESC LIMIT 20')->fetchAll(); } catch(Throwable $e) {}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Informe IA</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap">
<header class="topbar"><div class="brand">🌙 Informe Luna IA</div><nav class="nav"><a class="secondary" href="panel.php">Panel Juanra</a><a class="secondary" href="../public/dashboard.php">App</a></nav></header>
<section class="card"><p class="pill">V4.7</p><h1>Seguimiento inteligente</h1><p class="muted">Resumen para ver qué pregunta Leire, dónde falla más y cómo evoluciona con XP.</p></section>
<section class="split"><div class="card"><h2>⚠️ Temas a reforzar</h2><?php if(!$weak): ?><p class="muted">Aún no hay datos suficientes.</p><?php else: ?><table class="table"><tr><th>Asignatura</th><th>Tema</th><th>Media</th><th>Intentos</th></tr><?php foreach($weak as $w): ?><tr><td><?= e($w['subject']) ?></td><td><?= e($w['title']) ?></td><td><?= e($w['avg_score']) ?>/10</td><td><?= (int)$w['tries'] ?></td></tr><?php endforeach; ?></table><?php endif; ?></div><div class="card"><h2>⭐ Últimos XP</h2><?php if(!$xp): ?><p class="muted">Sin movimientos.</p><?php else: ?><table class="table"><tr><th>Fecha</th><th>Motivo</th><th>XP</th><th>Monedas</th></tr><?php foreach($xp as $x): ?><tr><td><?= e($x['created_at']) ?></td><td><?= e($x['reason']) ?></td><td><?= (int)$x['xp'] ?></td><td><?= (int)$x['coins'] ?></td></tr><?php endforeach; ?></table><?php endif; ?></div></section>
<section class="card"><h2>💬 Últimas preguntas a Luna</h2><?php if(!$logs): ?><p class="muted">Todavía no hay preguntas registradas.</p><?php else: ?><table class="table"><tr><th>Fecha</th><th>Asignatura</th><th>Pregunta</th><th>Respuesta</th></tr><?php foreach($logs as $l): ?><tr><td><?= e($l['created_at']) ?></td><td><?= e($l['subject']) ?></td><td><?= e($l['question']) ?></td><td><?= e(mb_strimwidth($l['answer'],0,160,'...','UTF-8')) ?></td></tr><?php endforeach; ?></table><?php endif; ?></section>
</div></body></html>
