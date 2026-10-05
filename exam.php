<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$topic_id = (int)($_GET['topic_id'] ?? $_POST['topic_id'] ?? 0);
$mode = $_GET['mode'] ?? $_POST['mode'] ?? 'deberes';
$validModes = ['deberes','examen','repaso','batalla'];
if (!in_array($mode, $validModes, true)) $mode = 'deberes';
$topicStmt = db()->prepare('SELECT * FROM topics WHERE id=?');
$topicStmt->execute([$topic_id]);
$topic = $topicStmt->fetch();
if (!$topic) die('Tema no encontrado');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    record_topic_activity((int)$user['id'], (int)$topic_id, $mode);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionIds = array_map('intval', $_POST['question_ids'] ?? []);
    if (!$questionIds) die('No hay preguntas');
    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    $stmt = db()->prepare("SELECT * FROM questions WHERE id IN ($placeholders)");
    $stmt->execute($questionIds);
    $questions = $stmt->fetchAll();
    $byId = [];
    foreach ($questions as $q) $byId[(int)$q['id']] = $q;
    $correct = 0; $details = [];
    foreach ($questionIds as $qid) {
        $q = $byId[$qid];
        $answer = trim($_POST['answer'][$qid] ?? '');
        $isCorrect = is_correct_answer($answer, $q['correct_answer']);
        if ($isCorrect) $correct++;
        $details[] = ['question'=>$q['question'],'answer'=>$answer,'correct'=>$q['correct_answer'],'ok'=>$isCorrect,'explanation'=>$q['explanation']];
    }
    $score = round(($correct / count($questionIds)) * 10, 2);
    $ins = db()->prepare('INSERT INTO results(user_id, topic_id, mode, total_questions, correct_questions, score, details_json) VALUES(?,?,?,?,?,?,?)');
    $ins->execute([$user['id'], $topic_id, $mode, count($questionIds), $correct, $score, json_encode($details, JSON_UNESCAPED_UNICODE)]);
    // IMPORTANTE V5.8: guardar el ID del resultado justo después de insertar en results.
    // Antes se llamaba a record_topic_activity() primero y lastInsertId() podía devolver el ID de topic_activity,
    // por eso a veces la corrección mostraba otro ejercicio/examen anterior.
    $resultId = db()->lastInsertId();
    record_topic_activity((int)$user['id'], (int)$topic_id, $mode, 'result.php?id='.(int)$resultId);
    $xp = 10 + ($mode === 'examen' ? 15 : 5) + ($mode === 'repaso' ? 5 : 0) + ($mode === 'batalla' ? 15 : 0) + ($score >= 7 ? 10 : 0) + ($score >= 9 ? 15 : 0);
    $coins = reward_cents_for_activity($mode, (float)$score);
    award_progress((int)$user['id'], ucfirst($mode).' de '.$topic['title'], $xp, $coins, (int)$resultId);
    if ($score >= 9) {
        add_unique_achievement((int)$user['id'],'Nota excelente','Has sacado 9 o más en ' . $topic['title'],'⭐');
    }
    if ($correct === count($questionIds)) {
        add_unique_achievement((int)$user['id'],'Todo perfecto','Has contestado todas bien en ' . $topic['title'],'🏆');
    }
    if ($mode === 'batalla' && $score >= 7) {
        add_unique_achievement((int)$user['id'],'Reto superado con Emilia','Has superado un reto de repaso con Emilia.','✨');
    }
    unlock_basic_achievements((int)$user['id']);
    redirect('result.php?id=' . $resultId);
}

$limit = $mode === 'examen' ? 10 : ($mode === 'batalla' ? 5 : ($mode === 'deberes' ? 15 : 10));
$avgScore = user_topic_avg_score((int)$user['id'], (int)$topic_id);
$difficultySql = '';
if ($avgScore !== null && $avgScore >= 9) {
    $difficultySql = ' AND difficulty >= 2 ';
} elseif ($avgScore !== null && $avgScore >= 8) {
    $difficultySql = ' AND difficulty >= 1 ';
}
$textTarget = (int)floor($limit / 2);
$multipleTarget = $limit - $textTarget;
$questions = [];
$ids = [];
$pick = function(string $type, int $target) use ($topic_id, $difficultySql, &$ids) {
    if ($target <= 0) return [];
    $sql = 'SELECT * FROM questions WHERE topic_id=? AND type=? ' . $difficultySql . ' ORDER BY RAND() LIMIT ' . (int)$target;
    $st = db()->prepare($sql);
    $st->execute([$topic_id, $type]);
    $rows = $st->fetchAll();
    foreach ($rows as $row) $ids[(int)$row['id']] = true;
    return $rows;
};
// V5.13: 50/50 aproximado entre escribir y tipo test para que no memorice solo opciones.
$questions = array_merge($pick('text', $textTarget), $pick('multiple', $multipleTarget));
if (count($questions) < $limit) {
    $missing = $limit - count($questions);
    $exclude = $ids ? (' AND id NOT IN (' . implode(',', array_map('intval', array_keys($ids))) . ') ') : '';
    $sql = 'SELECT * FROM questions WHERE topic_id=? ' . $exclude . ' ORDER BY RAND() LIMIT ' . (int)$missing;
    $st = db()->prepare($sql);
    $st->execute([$topic_id]);
    $questions = array_merge($questions, $st->fetchAll());
}
shuffle($questions);
if (!$questions) die('Este tema todavía no tiene preguntas. Entra en el panel y añade algunas.');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($topic['title']) ?></title><link rel="manifest" href="manifest.webmanifest?v=5.8"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.8"></head><body><div class="wrap">
<header class="topbar"><div class="brand"><?= $mode==='batalla' ? '✨ Reto' : '✏️ '.e(ucfirst($mode)) ?> <span class="pill">V5.5</span></div><nav class="nav"><a class="secondary" href="topics.php?subject=<?= urlencode($topic['subject']) ?>">Volver</a><a class="secondary" href="trainer.php">Entrenador</a><a class="secondary" href="teacher.php?back=<?= urlencode(luna_current_back_url()) ?>" target="_blank">Luna IA</a></nav></header>
<?php if($mode==='batalla'): ?><section class="card battle-intro"><h1>✨ Reto con Emilia</h1><p class="muted">5 preguntas · cada acierto suma energía. Al final verás la explicación de cada fallo.</p><div class="hearts big">❤️❤️❤️❤️❤️</div></section><?php endif; ?><section class="card"><h1><?= e($topic['title']) ?></h1><p class="muted"><?= e($topic['subject']) ?> · Responde y al final se corrige solo. Ahora mezcla preguntas de escribir y tipo test, y si vas sacando buenas notas sube un poco la dificultad.</p><?php if($topic['content']): ?><details><summary>Ver apuntes del tema</summary><p><?= nl2br(e($topic['content'])) ?></p></details><?php endif; ?></section>
<form class="card" method="post"><input type="hidden" name="topic_id" value="<?= (int)$topic_id ?>"><input type="hidden" name="mode" value="<?= e($mode) ?>">
<?php foreach($questions as $i=>$q): ?>
  <div class="question">
    <input type="hidden" name="question_ids[]" value="<?= (int)$q['id'] ?>">
    <h2><?= ($i+1) ?>. <?= e($q['question']) ?></h2>
    <?php if($q['type']==='multiple'): ?>
      <?php $opts=json_decode($q['options_json'] ?: '[]', true) ?: []; ?>
      <?php foreach($opts as $opt): ?>
        <label class="option"><input type="radio" name="answer[<?= (int)$q['id'] ?>]" value="<?= e($opt) ?>" required> <?= e($opt) ?></label>
      <?php endforeach; ?>
    <?php else: ?>
      <input class="input" name="answer[<?= (int)$q['id'] ?>]" placeholder="Escribe tu respuesta" required>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<button class="btn">Terminar y corregir</button></form>
<?= luna_floating_button() ?>
</div></body></html>
