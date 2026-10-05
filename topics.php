<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
ensure_student_money_tables();
$subject = $_GET['subject'] ?? 'Matemáticas';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $action = $_POST['topic_action'] ?? '';
    if ($topicId > 0) {
        if ($action === 'archive') {
            set_topic_archived((int)$user['id'], $topicId, true);
            $message = 'Tema pasado a segundo plano. Así verás mejor el siguiente.';
        } elseif ($action === 'unarchive') {
            set_topic_archived((int)$user['id'], $topicId, false);
            $message = 'Tema reactivado.';
        }
    }
}
$st = db()->prepare('SELECT * FROM topics WHERE subject=? ORDER BY id');
$st->execute([$subject]);
$topics = $st->fetchAll();
$archivedMap = topic_archived_map((int)$user['id'], array_map(fn($t) => (int)$t['id'], $topics));
$topicDone = topic_official_done_map((int)$user['id'], array_map(fn($t) => (int)$t['id'], $topics));
$subjectDone = subject_official_done_map((int)$user['id']);
$subjectOfficialDone = !empty($subjectDone[$subject]);
$activeTopics = [];
$archivedTopics = [];
foreach ($topics as $topic) {
    $topic['is_archived'] = !empty($archivedMap[(int)$topic['id']]);
    $topic['official_done'] = $subjectOfficialDone || !empty($topicDone[(int)$topic['id']]);
    if ($topic['is_archived'] || $topic['official_done']) $archivedTopics[] = $topic; else $activeTopics[] = $topic;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Temas · <?= e($subject) ?></title><link rel="manifest" href="manifest.webmanifest?v=5.14"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="stylesheet" href="assets/css/style.css?v=5.14"></head><body><div class="wrap">
<header class="topbar"><div class="brand"><?= subject_icon($subject) ?> <?= e($subject) ?> <span class="pill">V5.14</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="wallet.php">Mi dinero</a><a class="secondary" href="teacher.php?back=topics.php?subject=<?= urlencode($subject) ?>">Luna IA</a></nav></header>
<section class="card"><p class="pill">Temas de <?= e($subject) ?></p><h1><?= e($subject) ?></h1><p class="muted">Cuando un tema ya esté terminado en el cole puede quedar abajo. Si Juanra marca que el examen oficial ya está hecho, no saldrá en recomendaciones.</p><?php if($subjectOfficialDone): ?><div class="notice reward">🏫 Juanra ha marcado que el examen oficial de esta asignatura ya está hecho. La app no la recomendará como pendiente.</div><?php endif; ?><?php if($message): ?><div class="notice reward"><?= e($message) ?></div><?php endif; ?></section>
<section class="card"><h2>🌟 Temas principales</h2><?php if(!$activeTopics): ?><p class="muted">No hay temas activos en esta asignatura ahora mismo.</p><?php else: ?><div class="grid"><?php foreach($activeTopics as $t): ?><div class="subject active-topic"><strong><?= e($t['title']) ?></strong><span class="muted"><?= e($t['description']) ?></span><p><a class="btn" href="topic_view.php?id=<?= (int)$t['id'] ?>">Abrir tema</a></p><div class="topic-actions"><form method="post" class="inline-form"><input type="hidden" name="topic_id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="topic_action" value="archive"><button class="btn secondary small">Pasar a segundo plano</button></form></div></div><?php endforeach; ?></div><?php endif; ?></section>
<?php if($archivedTopics): ?><section class="card archived-block"><h2>🗂️ Temas en segundo plano</h2><p class="muted">Siguen disponibles para estudiar o repasar, pero no van delante en las recomendaciones.</p><div class="grid"><?php foreach($archivedTopics as $t): ?><div class="subject archived-topic"><strong><?= e($t['title']) ?></strong><span class="muted"><?= e($t['description']) ?></span><p><?php if($t['official_done']): ?><span class="pill equipped">Examen oficial hecho</span><?php else: ?><span class="pill">Segundo plano</span><?php endif; ?></p><p><a class="btn secondary" href="topic_view.php?id=<?= (int)$t['id'] ?>">Abrir tema</a></p><?php if(!$t['official_done']): ?><div class="topic-actions"><form method="post" class="inline-form"><input type="hidden" name="topic_id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="topic_action" value="unarchive"><button class="btn secondary small">Volver a activar</button></form></div><?php endif; ?></div><?php endforeach; ?></div></section><?php endif; ?>
<?= luna_floating_button() ?>
</div></body></html>
