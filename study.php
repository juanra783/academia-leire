<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM topics WHERE id=?');
$st->execute([$id]);
$topic = $st->fetch();
if (!$topic) die('Tema no encontrado');
record_topic_activity((int)$user['id'], (int)$topic['id'], 'estudiar');

function study_icon_for_title(string $title): string {
    $t = mb_strtolower($title, 'UTF-8');
    if (str_contains($t, 'resumen') || str_contains($t, 'examen')) return '🧠';
    if (str_contains($t, 'fecha') || str_contains($t, 'línea') || str_contains($t, 'linea') || str_contains($t, 'siglo')) return '⏳';
    if (str_contains($t, 'fuente')) return '🔎';
    if (str_contains($t, 'vocabulario') || str_contains($t, 'palabras')) return '🗂️';
    if (str_contains($t, 'truco') || str_contains($t, 'memorizar')) return '💡';
    if (str_contains($t, 'romano') || str_contains($t, 'edad')) return '🏛️';
    return '📌';
}

function render_study_content(?string $content): string {
    $content = trim((string)$content);
    if ($content === '') return '<section class="study-section"><p class="muted">Este tema todavía no tiene apuntes de estudio.</p></section>';
    $lines = preg_split('/\R/u', $content);
    $html = '';
    $openList = false;
    $openSection = false;
    $sectionIndex = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            if ($openList) { $html .= '</ul>'; $openList = false; }
            continue;
        }
        if (str_starts_with($line, '# ')) {
            if ($openList) { $html .= '</ul>'; $openList = false; }
            if ($openSection) { $html .= '</section>'; }
            $title = trim(substr($line, 2));
            $sectionIndex++;
            $html .= '<section class="study-section visual-section"><div class="study-section-head"><span class="study-icon">'.study_icon_for_title($title).'</span><h2>'.e($title).'</h2></div>';
            $openSection = true;
        } elseif (str_starts_with($line, '## ')) {
            if ($openList) { $html .= '</ul>'; $openList = false; }
            $title = trim(substr($line, 3));
            $html .= '<div class="study-subbox"><h3>'.e($title).'</h3>';
        } elseif (str_starts_with($line, '### ')) {
            if ($openList) { $html .= '</ul>'; $openList = false; }
            $html .= '<h4>'.e(trim(substr($line, 4))).'</h4>';
        } elseif (preg_match('/^[-•]\s+/u', $line)) {
            if (!$openList) { $html .= '<ul class="study-list">'; $openList = true; }
            $html .= '<li>'.e(preg_replace('/^[-•]\s+/u', '', $line)).'</li>';
        } else {
            if ($openList) { $html .= '</ul>'; $openList = false; }
            $html .= '<p>'.e($line).'</p>';
        }
    }
    if ($openList) { $html .= '</ul>'; }
    if ($openSection) { $html .= '</section>'; }
    return $html;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Estudiar · <?= e($topic['title']) ?></title><link rel="manifest" href="manifest.webmanifest?v=5.12"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.12"></head><body><div class="wrap study-page">
<header class="topbar"><div class="brand">📚 Estudiar <span class="pill">V5.12</span></div><nav class="nav"><a class="secondary" href="topic_view.php?id=<?= (int)$id ?>">Volver al tema</a><a class="secondary" href="topics.php?subject=<?= urlencode($topic['subject']) ?>">Temas</a><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="teacher.php?back=<?= urlencode('study.php?id='.(int)$id) ?>">Luna IA</a></nav></header>
<section class="card study-hero visual-study-hero"><p class="pill"><?= e($topic['subject']) ?> · <?= e($topic['course']) ?></p><h1><?= e($topic['title']) ?></h1><p class="muted"><?= e($topic['description']) ?></p><div class="study-route"><div><b>1</b><span>Leer</span></div><div><b>2</b><span>Memorizar</span></div><div><b>3</b><span>Deberes</span></div><div><b>4</b><span>Examen</span></div></div><p><a class="btn" href="exam.php?topic_id=<?= (int)$id ?>&mode=deberes">Hacer deberes</a> <a class="btn secondary" href="exam.php?topic_id=<?= (int)$id ?>&mode=repaso">Repaso rápido</a> <a class="btn secondary" href="exam.php?topic_id=<?= (int)$id ?>&mode=examen">Hacer examen</a></p></section>
<?= render_study_content($topic['content'] ?? '') ?>
<section class="card exam-tips"><h2>🧠 Antes del examen</h2><div class="grid small"><div class="subject"><strong>Lee y tapa</strong><span class="muted">Lee un apartado, tapa la pantalla y explícalo con tus palabras.</span></div><div class="subject"><strong>Haz 15 deberes</strong><span class="muted">Practica sin prisa y revisa las explicaciones.</span></div><div class="subject"><strong>Termina con examen</strong><span class="muted">Haz 10 preguntas para comprobar si ya lo domina.</span></div></div><p><a class="btn" href="exam.php?topic_id=<?= (int)$id ?>&mode=repaso">Empezar repaso</a></p></section>
<?= luna_floating_button() ?>
</div></body></html>
