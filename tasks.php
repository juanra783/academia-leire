<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$db = db();
ensure_v44_tables();

$daily = ensure_daily_tasks((int)$user['id']);
$bookTasks = [];
$assigned = [];
try {
    $q = $db->query("SELECT id,title,subject_key,topic_key,description,duration_minutes,xp,active FROM study_tasks WHERE active=1 AND subject_key='matematicas' ORDER BY id");
    $bookTasks = $q->fetchAll();
    $a = $db->prepare("SELECT task_id,status,assigned_date,completed_at FROM study_task_assignments WHERE user_id=? ORDER BY id DESC");
    $a->execute([(int)$user['id']]);
    foreach ($a->fetchAll() as $row) $assigned[(int)$row['task_id']] = $row;
} catch (Throwable $e) {}

function task_state(array $task, array $assigned): string {
    $a = $assigned[(int)$task['id']] ?? null;
    if (!$a) return 'Pendiente';
    if (($a['status'] ?? '') === 'completed') return 'Completada';
    return 'En progreso';
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tareas · Academia Star</title>
<link rel="stylesheet" href="assets/css/style.css?v=615">
<style>
.curriculum-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:16px}
.curriculum-card{position:relative;padding:20px;border-radius:20px;background:rgba(20,24,55,.72);border:1px solid rgba(180,150,255,.2);box-shadow:0 10px 30px rgba(0,0,0,.12)}
.curriculum-card h3{margin:0 0 8px}.curriculum-card p{line-height:1.5}.task-meta{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}.task-chip{padding:5px 9px;border-radius:999px;background:rgba(255,255,255,.08);font-size:.82rem}.task-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.state-done{color:#75e0a0}.state-progress{color:#7dd3fc}.state-pending{color:#ffd166}
.section-intro{margin-bottom:8px}.daily-grid{display:grid;gap:10px}.daily-item{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:14px;border-radius:16px;background:rgba(255,255,255,.05)}
</style>
</head>
<body>
<div class="wrap">
<header class="topbar"><div class="brand">✦ Academia Star</div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="topics.php">Aprender</a><a class="secondary" href="tasks.php">Tareas</a><a class="secondary" href="profile.php">Perfil</a></nav></header>
<main class="card">
<h1>📝 Tareas</h1>
<p class="muted">Aquí están las actividades de hoy y el bloque real de Matemáticas que estamos preparando a partir del libro.</p>

<h2>Plan diario</h2>
<div class="daily-grid">
<?php foreach($daily as $t): ?>
<div class="daily-item"><div><strong><?=e($t['title'])?></strong><div class="muted"><?=e($t['subject'] ?? 'Repaso')?> · <?= (int)($t['xp_reward'] ?? 0) ?> XP</div></div><a class="btn" href="<?=e($t['action_url'] ?? 'dashboard.php')?>">Empezar</a></div>
<?php endforeach; ?>
</div>

<h2 style="margin-top:28px">📚 Matemáticas · bloque del libro</h2>
<p class="muted section-intro">Multiplicación, expresiones con varias operaciones y potencias. Las actividades están pensadas con ejercicios del mismo tipo que aparecen en las páginas fotografiadas.</p>
<div class="curriculum-grid">
<?php foreach($bookTasks as $t): $state=task_state($t,$assigned); $cls=$state==='Completada'?'state-done':($state==='En progreso'?'state-progress':'state-pending'); ?>
<article class="curriculum-card">
<h3><?=e($t['title'])?></h3>
<p class="muted"><?=e($t['description'])?></p>
<div class="task-meta"><span class="task-chip">⏱ <?= (int)$t['duration_minutes'] ?> min</span><span class="task-chip">⭐ <?= (int)$t['xp'] ?> XP</span><span class="task-chip <?= $cls ?>">● <?=e($state)?></span></div>
<div class="task-actions"><a class="btn" href="task_activity.php?task_id=<?= (int)$t['id'] ?>">Empezar</a></div>
</article>
<?php endforeach; ?>
</div>
</main></div>
</body></html>
