<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$profile = ensure_student_profile((int)$user['id']);
// V5.9: Emilia reta primero sobre los últimos temas que Leire está estudiando o practicando.
$topics = [];
$seenTopics = [];
foreach (recent_topics_for_user((int)$user['id'], 8) as $rt) {
    $tid = (int)($rt['topic_id'] ?? $rt['id'] ?? 0);
    if ($tid <= 0 || isset($seenTopics[$tid])) continue;
    $seenTopics[$tid] = true;
    $topics[] = [
        'id' => $tid,
        'subject' => $rt['subject'],
        'title' => $rt['title'],
        'avg_score' => $rt['avg_score'] ?? null,
        'tries' => $rt['tries'] ?? 0,
        'source_label' => $rt['action_label'] ?? 'Último tema'
    ];
}
// Si faltan retos, completa con temas flojos para reforzar.
if (count($topics) < 8) {
    foreach (weak_topics_for_user((int)$user['id'], 8) as $wt) {
        $tid = (int)$wt['id'];
        if (isset($seenTopics[$tid])) continue;
        $seenTopics[$tid] = true;
        $wt['source_label'] = 'A reforzar';
        $topics[] = $wt;
        if (count($topics) >= 8) break;
    }
}
// Si aún no hay historial, muestra temas base.
if (!$topics) {
    $topics = db()->query('SELECT id,subject,title,NULL avg_score,0 tries, "Para empezar" source_label FROM topics ORDER BY FIELD(subject,"Matemáticas","Lengua","Conocimiento del Medio","Inglés"), id LIMIT 8')->fetchAll();
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reto con Emilia</title><link rel="manifest" href="manifest.webmanifest?v=5.9"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.9"></head><body><div class="wrap">
<header class="topbar"><div class="brand">✨ Reto con Emilia <span class="pill">V5.9</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="trainer.php">Entrenador</a><a class="secondary" href="mascot.php">Emilia</a></nav></header>
<section class="kid-hero"><div class="avatar-card emilia-card"><div class="emilia-preview"><img src="assets/img/emilia-avatar.png" alt="Emilia"></div><div class="level-badge">Emilia te reta</div><p class="muted">5 corazones · 5 preguntas</p></div><div class="card hero-main"><p class="pill">Modo juego</p><h1>Elige un reto</h1><p class="muted">Emilia prioriza los últimos temas que Leire está estudiando. Si ha hecho Conocimiento y Matemáticas, los retos saldrán de esos temas antes que de otros.</p></div></section>
<section class="card"><h2>Retos recomendados por lo último estudiado</h2><div class="grid">
<?php foreach($topics as $t): ?>
<a class="subject battle-card" href="exam.php?topic_id=<?= (int)$t['id'] ?>&mode=batalla"><strong><?= subject_icon($t['subject']) ?> <?= e($t['title']) ?></strong><p class="muted"><?= e($t['subject']) ?> · <?= e($t['source_label'] ?? 'Recomendado') ?><?= $t['avg_score']!==null?' · media '.e((string)$t['avg_score']).'/10':'' ?></p><div class="hearts">❤️❤️❤️❤️❤️</div><span class="pill">Empezar reto</span></a>
<?php endforeach; ?>
</div></section>
<?= luna_floating_button() ?>
</div></body></html>
