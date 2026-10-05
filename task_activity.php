<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';

$user = require_login();
$db = db();
$taskId = (int)($_GET['task_id'] ?? $_GET['id'] ?? 0);
$mode = strtolower((string)($_GET['mode'] ?? 'practice'));
if (!in_array($mode, ['read','study','practice','review'], true)) $mode = 'practice';
$message = '';
$task = null;
$questions = [];

if ($taskId > 0) {
    try {
        $q = $db->prepare("SELECT * FROM study_tasks WHERE id=? LIMIT 1");
        $q->execute([$taskId]);
        $task = $q->fetch();

        if ($task) {
            $q = $db->prepare("SELECT * FROM study_task_content WHERE task_id=? ORDER BY sort_order,id");
            $q->execute([$taskId]);
            $questions = $q->fetchAll();
            // Cada modo usa una dinámica distinta: repaso breve aleatorio y estudio guiado.
            if ($mode === 'review' && count($questions) > 6) { shuffle($questions); $questions = array_slice($questions, 0, 6); }
            if ($mode === 'study' && count($questions) > 4) { shuffle($questions); $questions = array_slice($questions, 0, 4); }
        }
    } catch (Throwable $e) {
        $message = 'El contenido de esta tarea todavía no está instalado.';
    }
}

if (!$task) {
    $message = 'No se ha encontrado la tarea. Puedes volver a Tareas y elegir otra actividad.';
}

if ($task && $taskId > 0) {
    try {
        $assign = $db->prepare("INSERT IGNORE INTO study_task_assignments(task_id,user_id,status,assigned_date) VALUES(?,?, 'pending', CURDATE())");
        $assign->execute([$taskId, (int)$user['id']]);
    } catch (Throwable $ignored) {}
}

$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$results = [];
$score = 0;

if ($submitted && $task && $questions) {
    foreach ($questions as $index => $question) {
        $given = trim((string)($_POST['answer'][$question['id']] ?? ''));
        $expected = trim((string)($question['answer'] ?? ''));
        $correct = mb_strtolower($given, 'UTF-8') === mb_strtolower($expected, 'UTF-8');
        if ($correct) $score++;
        $results[$question['id']] = [
            'given' => $given,
            'correct' => $correct,
            'expected' => $expected
        ];
    }
    $message = 'Has terminado la actividad. Revisa tus respuestas.';
    $xpEarned = $score === count($questions) ? (int)($task['xp'] ?? 10) : (int)floor(((int)($task['xp'] ?? 10)) * ($score / count($questions)));
    try {
        $save = $db->prepare("INSERT INTO study_task_attempts
            (task_id, user_id, total_questions, correct_answers, xp_earned, completed_at, status)
            VALUES (?, ?, ?, ?, ?, NOW(), 'completed')");
        $save->execute([$taskId, (int)$user['id'], count($questions), $score, $xpEarned]);
        try {
            $assign = $db->prepare("UPDATE study_task_assignments
                SET status='completed', completed_at=NOW()
                WHERE task_id=? AND user_id=?");
            $assign->execute([$taskId, (int)$user['id']]);
        } catch (Throwable $ignored) {}
    } catch (Throwable $ignored) {}
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($task['title'] ?? 'Actividad') ?> · <?= e(['read'=>'Leer tema','study'=>'Estudiar','practice'=>'Practicar','review'=>'Repasar'][$mode]) ?> · Academia Star</title>
<link rel="stylesheet" href="assets/css/style.css?v=612">
<link rel="stylesheet" href="assets/css/task-study-new.css?v=613">
<style>.lesson-panel{margin-top:18px;padding:20px;border:1px solid #dcd5ff;border-radius:18px;background:#f8f6ff;color:#28243c}.lesson-panel h2{margin-top:0}.lesson-example{padding:12px 0;border-top:1px solid #e6e0fa}.lesson-example p{margin:7px 0}.study-tip{padding:12px 15px;border-radius:12px;background:#eaf5ff;color:#17446c;font-weight:600;margin:14px 0}.task-study-hero .lesson-panel{box-shadow:0 8px 24px #33206a12}.lesson-example{margin-top:12px;padding:16px;border:1px solid #ddd6fe;border-radius:15px;background:#faf5ff}.lesson-example>b{display:block;color:#4338ca;background:#eef2ff;padding:10px 12px;border-radius:10px}.lesson-example .answer-label{display:block;margin-top:10px;padding:10px 12px;border-radius:10px;background:#ecfdf5;border-left:4px solid #16a34a;color:#166534}.lesson-example .explanation-label{display:block;margin-top:10px;padding:10px 12px;border-radius:10px;background:#f5f3ff;border-left:4px solid #8b5cf6;color:#5b21b6}.study-question h2{padding:14px;background:#eff6ff;border-left:5px solid #2563eb;border-radius:12px}.study-question .explanation{padding:14px;background:#f5f3ff;border-left:5px solid #8b5cf6;border-radius:12px;color:#4c1d95}.result-question p strong{color:#1d4ed8}</style>
</head>
<body>
<div class="wrap academy-task-study">
<header class="topbar">
  <div class="brand">✦ Academia Star <span class="pill"><?= e(['read'=>'Leer tema','study'=>'Estudiar','practice'=>'Practicar','review'=>'Repasar'][$mode]) ?></span></div>
  <nav class="nav">
    <a class="secondary" href="tasks.php">Tareas</a>
    <a class="secondary" href="dashboard.php">Inicio</a>
  </nav>
</header>

<?php if ($task): ?>
<section class="card task-study-hero">
  <div class="task-kicker"><?= e($task['subject_key'] ?? 'Repaso') ?> · <?= e($task['topic_key'] ?? 'Actividad') ?></div>
  <h1><?= e($task['title']) ?></h1>
  <p><?= e($task['description'] ?? 'Responde con calma y comprueba lo aprendido.') ?></p>
  <?php if ($mode === 'read'): ?><div class="lesson-panel"><h2>📖 Lee y comprende</h2><p><?= e($task['description'] ?? 'Lee despacio y piensa en la idea principal.') ?></p><h3>Ejemplos y pistas</h3><?php foreach ($questions as $lessonQ): ?><article class="lesson-example"><b>❓ PREGUNTA / EJERCICIO</b><p><?= e($lessonQ['prompt']) ?></p><div class="answer-label"><strong>✅ RESPUESTA:</strong> <?= e($lessonQ['answer'] ?? '') ?></div><?php if (!empty($lessonQ['explanation'])): ?><div class="explanation-label"><strong>💡 EXPLICACIÓN:</strong> <?= e($lessonQ['explanation']) ?></div><?php endif; ?></article><?php endforeach; ?><p class="study-tip">Truco: lee un ejemplo, tapa la respuesta y explícalo con tus propias palabras.</p></div><?php elseif ($mode === 'study'): ?><div class="lesson-panel"><h2>🧠 Estudia paso a paso</h2><p>Primero lee cada pregunta y su pista. Después responde sin mirar la solución. Al corregir, revisa especialmente las que falles.</p><?php foreach ($questions as $studyQ): ?><article class="lesson-example"><b><?= e($studyQ['prompt']) ?></b><?php if (!empty($studyQ['explanation'])): ?><p><strong>Pista:</strong> <?= e($studyQ['explanation']) ?></p><?php endif; ?></article><?php endforeach; ?></div><?php elseif ($mode === 'review'): ?><div class="study-tip">🎯 Repaso rápido: <?= count($questions) ?> preguntas seleccionadas al azar. Intenta responder sin ayuda.</div><?php else: ?><div class="study-tip">✏️ Practica por tu cuenta y comprueba las respuestas al terminar.</div><?php endif; ?>
  <?php if ($questions && $mode !== 'read'): ?>
  <div class="task-progress"><?= $mode === 'review' ? 'Repaso de ' : ($mode === 'study' ? 'Práctica guiada de ' : 'Actividad de ') ?><?= count($questions) ?> preguntas</div>
  <?php endif; ?>
</section>

<?php if ($message): ?>
<div class="study-notice"><?= e($message) ?></div>
<?php endif; ?>

<?php if (!$questions): ?>
<section class="card empty-study">
  <h2>Contenido pendiente</h2>
  <p>Esta tarea todavía no tiene ejercicios asociados. Cuando se añada contenido aparecerá aquí.</p>
  <a class="btn" href="tasks.php">Volver a tareas</a>
</section>
<?php elseif ($mode === 'read'): ?>
<section class="card study-result"><h2>¿Listo para comprobar lo aprendido?</h2><p>Ya has leído la explicación y los ejemplos. Ahora elige practicar o hacer un repaso corto.</p><a class="btn" href="task_activity.php?task_id=<?= (int)$taskId ?>&mode=practice">Practicar ejercicios</a> <a class="btn secondary" href="task_activity.php?task_id=<?= (int)$taskId ?>&mode=review">Repasar rápido</a></section>
<?php elseif (!$submitted): ?>
<form method="post" class="study-form">
<?php foreach ($questions as $index => $question): ?>
<section class="card study-question">
  <div class="question-number"><?= $index + 1 ?></div>
  <h2><?= e($question['prompt']) ?></h2>
  <?php
    $options = [];
    if (!empty($question['options_json'])) {
        $decoded = json_decode($question['options_json'], true);
        if (is_array($decoded)) $options = $decoded;
    }
  ?>
  <?php if ($options): ?>
  <div class="answer-options">
    <?php foreach ($options as $option): ?>
    <label class="answer-option">
      <input type="radio" name="answer[<?= (int)$question['id'] ?>]" value="<?= e((string)$option) ?>" required>
      <span><?= e((string)$option) ?></span>
    </label>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <input class="written-answer" type="text" name="answer[<?= (int)$question['id'] ?>]" placeholder="Escribe tu respuesta" required>
  <?php endif; ?>
</section>
<?php endforeach; ?>
<button class="btn study-submit" type="submit">Comprobar respuestas</button>
</form>
<?php else: ?>
<section class="card study-result">
  <h2>Resultado</h2>
  <div class="score"><?= $score ?> / <?= count($questions) ?></div>
  <p>Has conseguido <strong><?= (int)($xpEarned ?? 0) ?> XP</strong>. Revisa cada respuesta y vuelve a intentarlo para mejorar.</p>
</section>
<?php foreach ($questions as $index => $question): $result = $results[$question['id']] ?? null; ?>
<section class="card study-question result-question <?= ($result && $result['correct']) ? 'is-correct' : 'is-wrong' ?>">
  <h2><?= $index + 1 ?>. <?= e($question['prompt']) ?></h2>
  <p><strong>Tu respuesta:</strong> <?= e($result['given'] ?? '') ?></p>
  <?php if (!$result['correct']): ?><p><strong>Respuesta correcta:</strong> <?= e($result['expected']) ?></p><?php endif; ?>
  <?php if (!empty($question['explanation'])): ?><p class="explanation"><?= e($question['explanation']) ?></p><?php endif; ?>
</section>
<?php endforeach; ?>
<a class="btn" href="task_activity.php?task_id=<?= (int)$taskId ?>&mode=<?= e($mode) ?>">Repetir <?= e(['read'=>'lectura','study'=>'estudio','practice'=>'práctica','review'=>'repaso'][$mode]) ?></a>
<a class="btn secondary" href="tasks.php">Volver a tareas</a>
<?php endif; ?>
<?php else: ?>
<section class="card empty-study">
  <h1>Actividad no disponible</h1>
  <p><?= e($message) ?></p>
  <a class="btn" href="tasks.php">Volver a tareas</a>
</section>
<?php endif; ?>
</div>
</body>
</html>
