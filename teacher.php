<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/ai.php';
$user = require_login();
$profile = ensure_student_profile((int)$user['id']);
$answer = '';
$question = '';
$backUrl = luna_safe_back_url($_POST['back'] ?? $_GET['back'] ?? ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
$aiStats = ai_usage_stats((int)$user['id']);
$subject = $_POST['subject'] ?? $_GET['subject'] ?? 'Matemáticas';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    if ($question !== '') {
        $answer = luna_answer($question, $subject, $user, $profile);
        save_ai_log((int)$user['id'], $subject, $question, $answer);
        award_progress((int)$user['id'], 'Pregunta a Luna', 5, 0, null);
    }
}
$subjects = ['Matemáticas','Lengua','Conocimiento del Medio','Inglés'];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Luna IA</title><link rel="manifest" href="manifest.webmanifest?v=5.13"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=55"><link rel="icon" href="favicon.ico?v=55"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=55"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.13"></head><body><div class="wrap">
<header class="topbar"><div class="brand">🌙 Luna · Profesora IA <span class="pill">V5.13</span></div><nav class="nav"><a class="secondary" href="<?= e($backUrl) ?>">Volver donde estaba</a><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="trainer.php">Entrenador</a><a class="secondary" href="adventure.php">Aventura</a><a class="secondary" href="shop.php">Tienda</a></nav></header>
<section class="kid-hero"><div class="avatar-card"><img class="avatar" src="assets/img/avatar-leire-main.jpg" alt="Leire"><div class="level-badge">Luna ayuda</div><p class="muted">Modo <?= ai_enabled() ? 'IA externa' : 'local sin coste' ?></p><p class="muted">IA hoy: <?= (int)$aiStats['daily_count'] ?>/<?= (int)$aiStats['daily_limit'] ?: '∞' ?></p></div><div class="card hero-main"><p class="pill">Profesora personalizada</p><h1>Pregunta lo que no entiendas</h1><p class="muted">Luna explica con palabras sencillas, pone ejemplos y propone un mini ejercicio. Se abre desde cualquier pantalla y puedes volver donde estabas sin perder el ejercicio.</p><p><a class="btn secondary" href="<?= e($backUrl) ?>">← Volver donde estaba</a></p></div></section>
<form class="card form" method="post"><input type="hidden" name="back" value="<?= e($backUrl) ?>">
<label>Asignatura<select name="subject"><?php foreach($subjects as $s): ?><option value="<?= e($s) ?>" <?= $subject===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
<label>Pregunta<textarea name="question" required placeholder="Ejemplo: Luna, no entiendo las divisiones con dos cifras"><?= e($question) ?></textarea></label>
<button class="btn">Preguntar a Luna</button>
</form>
<?php if($answer): ?><section class="card teacher"><h2>Respuesta de Luna</h2><p><?= nl2br(e($answer)) ?></p><div class="notice"><b>Recompensa:</b> +5 XP por preguntar una duda.</div><p><a class="btn" href="<?= e($backUrl) ?>">← Volver donde estaba</a></p></section><?php endif; ?>
<section class="card"><h2>Dudas rápidas</h2><div class="grid small">
<?php $quick=[['Matemáticas','No entiendo las fracciones','Fracciones'],['Matemáticas','Cómo hago una división','Divisiones'],['Lengua','Qué es un adjetivo','Adjetivos'],['Conocimiento del Medio','Qué es un ecosistema','Ecosistemas'],['Inglés','Explícame el verbo to be fácil','To be']]; foreach($quick as $q): ?>
<form method="post" class="subject"><input type="hidden" name="back" value="<?= e($backUrl) ?>"><input type="hidden" name="subject" value="<?= e($q[0]) ?>"><input type="hidden" name="question" value="<?= e($q[1]) ?>"><button class="linkbtn"><?= e($q[2]) ?></button></form>
<?php endforeach; ?>
</div></section>
<?= luna_floating_button() ?>
</div></body></html>
