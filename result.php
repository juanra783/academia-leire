<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$manualMessage = '';
$id = (int)($_GET['id'] ?? $_POST['result_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'manual_correct_question') {
    $manualMessage = manual_correct_result_question(
        $id,
        (int)$user['id'],
        (int)($_POST['question_index'] ?? -1),
        (string)($_POST['admin_pin'] ?? ''),
        (string)($_POST['manual_status'] ?? 'correct') === 'correct',
        (string)($_POST['manual_note'] ?? '')
    );
}

$stmt = db()->prepare('SELECT r.*, t.title topic_title, t.subject, t.id topic_id FROM results r JOIN topics t ON t.id=r.topic_id WHERE r.id=? AND r.user_id=?');
$stmt->execute([$id, $user['id']]);
$r = $stmt->fetch();
if (!$r) die('Resultado no encontrado');
$details = json_decode($r['details_json'], true) ?: [];
$isBattle = ($r['mode'] ?? '') === 'batalla';
$hearts = max(0, (int)$r['correct_questions']);
$scoreValue = (float)$r['score'];
$modeLabel = [
    'deberes' => 'deberes',
    'examen' => 'examen',
    'repaso' => 'repaso',
    'batalla' => 'reto',
][$r['mode'] ?? 'deberes'] ?? 'actividad';
$rewardCents = reward_cents_for_activity((string)($r['mode'] ?? ''), $scoreValue);
if ($scoreValue >= 9) {
    $emiliaMood = 'excelente';
    $emiliaTitle = '¡Increíble, Leire!';
    $emiliaText = 'Emilia está súper orgullosa de ti. Has terminado este '.$modeLabel.' con una nota fantástica. ¡Sigue así, campeona!';
    $emiliaBadge = '🌟 Nota excelente';
} elseif ($scoreValue >= 7) {
    $emiliaMood = 'muybien';
    $emiliaTitle = '¡Muy bien hecho!';
    $emiliaText = 'Lo has hecho muy bien en este '.$modeLabel.'. Emilia te anima a seguir practicando para subir todavía más.';
    $emiliaBadge = '😊 Buen trabajo';
} elseif ($scoreValue >= 5) {
    $emiliaMood = 'regular';
    $emiliaTitle = '¡Buen esfuerzo!';
    $emiliaText = 'Has aprobado, pero Emilia cree que con un poquito más de práctica te saldrá aún mejor. ¿Repetimos luego?';
    $emiliaBadge = '💪 Sigue practicando';
} else {
    $emiliaMood = 'apoyo';
    $emiliaTitle = 'No pasa nada';
    $emiliaText = 'Emilia te dice que equivocarse también es aprender. Descansa un poco, revisa la corrección y vuelve a intentarlo.';
    $emiliaBadge = '💜 Te ayudo a mejorar';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate"><meta http-equiv="Pragma" content="no-cache"><title>Resultado #<?= (int)$r['id'] ?></title><link rel="manifest" href="manifest.webmanifest?v=5.15"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.15"></head><body><div class="wrap">
<header class="topbar"><div class="brand"><?= $isBattle ? '✨ Resultado reto' : '✅ Resultado' ?> <span class="pill">V5.15</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="trainer.php">Entrenador</a><a class="secondary" href="shop.php">Tienda</a></nav></header>
<?php if($manualMessage): ?><section class="notice reward"><b>Corrección manual:</b> <?= e($manualMessage) ?></section><?php endif; ?>
<section class="card"><p class="pill"><?= e($r['subject']) ?> · <?= e($r['topic_title']) ?> · Resultado #<?= (int)$r['id'] ?></p><p class="score"><?= e((string)$r['score']) ?>/10</p><h1><?= e(score_label((float)$r['score'])) ?></h1><p class="muted">Has acertado <?= (int)$r['correct_questions'] ?> de <?= (int)$r['total_questions'] ?>.</p><div class="notice reward"><b>Recompensa en €:</b> <?= e(reward_text((int)$rewardCents)) ?><?= $rewardCents > 0 ? " · Saldo añadido: ".e(format_cents((int)$rewardCents)) : "" ?></div><?php if($isBattle): ?><div class="notice reward"><b>Reto Emilia:</b> <?= $hearts >= 4 ? 'Victoria clara 🏆' : ($hearts >= 3 ? 'Reto conseguido ✨' : 'Toca repetir un poco con Emilia') ?> <div class="hearts big"><?= str_repeat('💥', $hearts) ?><?= str_repeat('❤️', max(0, 5-$hearts)) ?></div></div><?php endif; ?><p><a class="btn" href="exam.php?topic_id=<?= (int)$r['topic_id'] ?>&mode=repaso">Repetir repaso</a> <a class="btn secondary" href="exam.php?topic_id=<?= (int)$r['topic_id'] ?>&mode=batalla">Nuevo reto</a></p></section>
<section class="card emilia-result emilia-<?= e($emiliaMood) ?>">
  <div class="emilia-result-grid">
    <div class="emilia-result-figure">
      <div class="emilia-preview emilia-result-preview">
        <img src="assets/img/emilia-main.png" alt="Emilia">
      </div>
    </div>
    <div class="emilia-result-copy">
      <p class="pill"><?= e($emiliaBadge) ?></p>
      <h2><?= e($emiliaTitle) ?></h2>
      <p><?= e($emiliaText) ?></p>
      <div class="notice">
        <?php if($scoreValue >= 9): ?>Emilia dice: "¡Sigue así, que vas genial!"<?php elseif($scoreValue >= 7): ?>Emilia dice: "¡Muy bien! Un poquito más y te sale perfecto."<?php elseif($scoreValue >= 5): ?>Emilia dice: "Buen trabajo. Vamos a repasar y la próxima sale aún mejor."<?php else: ?>Emilia dice: "No te preocupes, te acompaño y lo intentamos otra vez."<?php endif; ?>
      </div>
      <p><a class="btn" href="mascot.php">Ver a Emilia</a> <a class="btn secondary" href="dashboard.php">Volver al inicio</a></p>
    </div>
  </div>
</section>
<section class="card"><h2>Corrección explicada</h2><p class="muted">Si la app corrige mal una respuesta, Juanra puede pulsar “Corregir pregunta” y usar el PIN de admin.</p><?php foreach($details as $i=>$d): ?><div class="question"><h3><?= ($i+1) ?>. <?= e($d['question']) ?></h3><?php if($d['ok']): ?><p class="ok">Bien: <?= e($d['answer']) ?></p><?php else: ?><p class="bad">Tu respuesta: <?= e($d['answer'] ?: 'sin responder') ?></p><p><b>Respuesta correcta:</b> <?= e($d['correct']) ?></p><div class="notice"><b>Explicación:</b> <?= e($d['explanation']) ?></div><?php endif; ?><?php if(!empty($d['manual_corrected'])): ?><p class="pill equipped">Corregida manualmente por Juanra</p><?php endif; ?><details class="manual-correction"><summary>🔧 Corregir pregunta</summary><form class="form compact-form" method="post"><input type="hidden" name="action" value="manual_correct_question"><input type="hidden" name="result_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="question_index" value="<?= (int)$i ?>"><label>PIN admin<input class="input" name="admin_pin" inputmode="numeric" placeholder="PIN"></label><label>Nuevo estado<select class="input" name="manual_status"><option value="correct">Marcar como correcta</option><option value="wrong">Marcar como incorrecta</option></select></label><input class="input" name="manual_note" placeholder="Nota opcional, ejemplo: estaba bien escrito"><button class="btn secondary small">Aplicar corrección</button></form></details></div><?php endforeach; ?></section>
<?= luna_floating_button() ?>
</div></body></html>
