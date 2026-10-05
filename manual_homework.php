<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
ensure_manual_homework_tables();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'submit_homework') {
        $hid = (int)($_POST['homework_id'] ?? 0);
        $attemptId = submit_manual_homework_attempt($hid, (int)$user['id'], $_POST['answer'] ?? []);
        if ($attemptId) header('Location: manual_homework.php?attempt_id='.$attemptId); else $message = 'No se pudo corregir este deber.';
        exit;
    } elseif ($action === 'admin_correct') {
        $message = admin_correct_manual_answer((int)($_POST['answer_id'] ?? 0), ($_POST['mark'] ?? '') === 'correct', (string)($_POST['pin'] ?? ''));
    }
}

$attemptId = (int)($_GET['attempt_id'] ?? 0);
$homeworkId = (int)($_GET['id'] ?? 0);
$attempt = $attemptId ? manual_attempt_get($attemptId) : null;
$attemptAnswers = $attempt ? manual_attempt_answers($attemptId) : [];
$homework = (!$attempt && $homeworkId) ? manual_homework_get($homeworkId) : null;
$questions = $homework ? manual_homework_questions($homeworkId) : [];
$homeworks = (!$attempt && !$homework) ? manual_homeworks_for_user((int)$user['id']) : [];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Deberes de Juanra</title><link rel="manifest" href="manifest.webmanifest?v=5.24"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=5.24"><link rel="icon" href="favicon.ico?v=5.24"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=5.24"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.24"></head><body class="magic-ui"><div class="wrap">
<header class="topbar"><div class="brand">📝 Deberes de Juanra <span class="pill">V5.24</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="teacher.php?back=manual_homework.php">Luna IA</a><?php if($user['role']==='admin'): ?><a class="secondary" href="../admin/panel.php">Panel Juanra</a><?php endif; ?></nav></header>
<?php if($message): ?><section class="card"><div class="notice reward"><?= e($message) ?></div></section><?php endif; ?>

<?php if($attempt): ?>
<section class="card study-hero"><p class="pill"><?= e($attempt['subject']) ?> · corregido</p><h1><?= e($attempt['title']) ?></h1><p class="muted">Nota: <b><?= e((string)$attempt['score']) ?>/10</b> · <?= (int)$attempt['correct_questions'] ?>/<?= (int)$attempt['total_questions'] ?> correctas.</p><p><a class="btn secondary" href="manual_homework.php">Volver a deberes</a></p></section>
<section class="card"><h2>Corrección pregunta por pregunta</h2><div class="manual-answer-list">
<?php foreach($attemptAnswers as $a): $ok=(int)$a['final_correct']===1; ?>
  <div class="subject answer-card <?= $ok?'ok-card':'bad-card' ?>"><strong><?= (int)$a['order_no'] ?>. <?= e($a['question_text']) ?></strong><p><b>Respuesta de Leire:</b> <?= e($a['student_answer'] ?: 'Sin responder') ?></p><p><b>Respuesta modelo:</b> <?= e($a['expected_answer'] ?: 'Respuesta libre') ?></p><p class="muted"><?= e($a['feedback']) ?></p><?php if($a['explanation']): ?><p><b>Explicación:</b> <?= e($a['explanation']) ?></p><?php endif; ?><span class="pill <?= $ok?'equipped':'' ?>"><?= $ok?'Correcta':'Para revisar' ?></span>
    <details class="admin-correction"><summary>🔧 Corregir con PIN de admin</summary><form method="post" class="form compact-form"><input type="hidden" name="action" value="admin_correct"><input type="hidden" name="answer_id" value="<?= (int)$a['id'] ?>"><input class="input" type="password" name="pin" placeholder="PIN admin" inputmode="numeric" autocomplete="off"><select class="input" name="mark"><option value="correct">Marcar correcta</option><option value="wrong">Marcar incorrecta</option></select><button class="btn secondary small">Guardar corrección</button></form></details>
  </div>
<?php endforeach; ?>
</div></section>

<?php elseif($homework): ?>
<?php if((int)$homework['assigned_user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') die('No permitido'); ?>
<section class="card study-hero"><p class="pill"><?= e($homework['subject']) ?></p><h1><?= e($homework['title']) ?></h1><p class="muted"><?= e($homework['instructions'] ?: 'Responde con frases completas. La app corrige primero y Juanra puede revisar después.') ?></p></section>
<form method="post" class="card form"><input type="hidden" name="action" value="submit_homework"><input type="hidden" name="homework_id" value="<?= (int)$homework['id'] ?>"><h2>Responde las preguntas</h2><?php foreach($questions as $q): ?><label><b><?= (int)$q['order_no'] ?>. <?= e($q['question_text']) ?></b><textarea class="input" name="answer[<?= (int)$q['id'] ?>]" rows="3" placeholder="Escribe tu respuesta aquí"></textarea></label><?php endforeach; ?><button class="btn">Terminar y corregir</button></form>

<?php else: ?>
<section class="card study-hero"><p class="pill">Deberes personalizados</p><h1>Deberes que ha puesto Juanra</h1><p class="muted">Aquí aparecen los deberes que Juanra crea manualmente para Leire. La app los corrige automáticamente y después Juanra puede ajustar la corrección con PIN.</p></section>
<?php if(!$homeworks): ?><section class="card"><p class="muted">No hay deberes manuales pendientes todavía.</p></section><?php else: ?><section class="card"><h2>Disponibles</h2><div class="grid"><?php foreach($homeworks as $hw): ?><a class="subject" href="manual_homework.php?id=<?= (int)$hw['id'] ?>"><strong><?= e($hw['subject']) ?> · <?= e($hw['title']) ?></strong><span class="muted"><?= (int)$hw['questions'] ?> preguntas · intentos: <?= (int)$hw['attempts'] ?></span><span class="pill">Empezar</span></a><?php endforeach; ?></div></section><?php endif; ?>
<?php endif; ?>
<?= luna_floating_button() ?>
</div></body></html>
