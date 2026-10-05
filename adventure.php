<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$profile = ensure_student_profile((int)$user['id']);
try { $worlds = db()->query('SELECT * FROM adventure_worlds ORDER BY sort_order, id')->fetchAll(); } catch(Throwable $e) { $worlds = []; }
if (!$worlds) {
 $worlds=[['subject'=>'Matemáticas','world_name'=>'Bosque Matemático','icon'=>'➗','description'=>'Operaciones y problemas.'],['subject'=>'Lengua','world_name'=>'Castillo de la Lengua','icon'=>'📖','description'=>'Ortografía y lectura.'],['subject'=>'Conocimiento del Medio','world_name'=>'Laboratorio del Medio','icon'=>'🌍','description'=>'Ciencia y Andalucía.'],['subject'=>'Inglés','world_name'=>'Isla del Inglés','icon'=>'🇬🇧','description'=>'Vocabulario y frases.']];
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Aventura de Leire</title><link rel="manifest" href="manifest.webmanifest?v=5.13"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=55"><link rel="icon" href="favicon.ico?v=55"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=55"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.13"></head><body><div class="wrap">
<header class="topbar"><div class="brand">🗺️ La aventura de Leire</div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="trainer.php">Entrenador</a><a class="secondary" href="teacher.php">Luna IA</a><a class="secondary" href="battle.php">Reto</a><a class="secondary" href="shop.php">Tienda</a><a class="secondary" href="mascot.php">Emilia</a></nav></header>
<section class="card hero-main"><p class="pill">Modo aventura</p><h1>Elige un mundo para avanzar</h1><p class="muted">Cada mundo lleva a sus temas. Al completar ejercicios ganas XP y saldo en € y ayudas a Emilia a subir de nivel.</p></section>
<section class="adventure-map">
<?php foreach($worlds as $w):
 $st=db()->prepare('SELECT COUNT(DISTINCT t.id) topics, COUNT(DISTINCT q.id) questions, ROUND(AVG(r.score),2) score FROM topics t LEFT JOIN questions q ON q.topic_id=t.id LEFT JOIN results r ON r.topic_id=t.id AND r.user_id=? WHERE t.subject=?');
 $st->execute([(int)$user['id'],$w['subject']]); $stats=$st->fetch(); $score=$stats['score']!==null?(float)$stats['score']:0; $pct=min(100,(int)round($score*10));
?>
<a class="world-card" href="topics.php?subject=<?= urlencode($w['subject']) ?>"><span class="world-icon"><?= e($w['icon']) ?></span><h2><?= e($w['world_name']) ?></h2><p><?= e($w['description']) ?></p><div class="mini-bar"><span style="width:<?= $pct ?>%"></span></div><small><?= (int)$stats['topics'] ?> temas · <?= (int)$stats['questions'] ?> preguntas<?= $score? ' · media '.e($score).'/10':'' ?></small></a>
<?php endforeach; ?>
</section>
<?= luna_floating_button() ?>
</div></body></html>
