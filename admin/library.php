<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user=require_admin();
ensure_admin_v524_tables();
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action'] ?? '';
    if ($action==='delete_topic') $message=delete_topic_admin((int)($_POST['topic_id'] ?? 0),(string)($_POST['admin_pin'] ?? ''));
}
$subjects=academy_subjects();
$selected=(string)($_GET['subject'] ?? ($subjects[0] ?? ''));
if ($selected && !in_array($selected,$subjects,true)) $selected=$subjects[0] ?? '';
$topics=admin_topics_by_subject($selected);
$allTopics=admin_topics_by_subject('');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Biblioteca de temas · Academia Star</title><link rel="stylesheet" href="../public/assets/css/style.css?v=5.24"></head><body class="magic-ui admin-page-v524"><div class="admin-shell">
<header class="admin-command-header"><div><span class="pill glow-pill">Biblioteca de temas</span><h1>Asignaturas y temas</h1><p class="muted">Primero elige una asignatura. Después selecciona el tema y se abre su propia página de edición.</p></div><nav class="admin-top-actions"><a class="btn secondary" href="panel.php">Panel</a><a class="btn secondary" href="topic_edit.php?subject=<?= urlencode($selected) ?>">Añadir tema</a><a class="btn secondary" href="subjects.php">Asignaturas</a></nav></header>
<?php if($message): ?><div class="notice reward admin-message"><?= e($message) ?></div><?php endif; ?>
<section class="card admin-card-purple"><form class="form compact-form library-selector" method="get"><label>1. Asignatura<select class="input" name="subject" onchange="this.form.submit()"><?php foreach($subjects as $sub): ?><option value="<?= e($sub) ?>" <?= $sub===$selected?'selected':'' ?>><?= subject_icon($sub) ?> <?= e($sub) ?></option><?php endforeach; ?></select></label><label>2. Tema<select class="input" onchange="if(this.value) location.href=this.value"><option value="">Selecciona un tema para editar</option><?php foreach($topics as $t): ?><option value="topic_edit.php?id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></option><?php endforeach; ?></select></label><a class="btn" href="topic_edit.php?subject=<?= urlencode($selected) ?>">Nuevo tema de <?= e($selected) ?></a></form></section>
<section class="subject-logo-grid big-subjects"><?php foreach($subjects as $sub): ?><a class="subject-logo-card subject-<?= e(subject_slug($sub)) ?> <?= $sub===$selected?'selected':'' ?>" href="library.php?subject=<?= urlencode($sub) ?>"><span><?= subject_icon($sub) ?></span><b><?= e($sub) ?></b><small><?= count(array_filter($allTopics, fn($t)=>$t['subject']===$sub)) ?> temas</small></a><?php endforeach; ?></section>
<section class="card table-card admin-card-silver"><h2><?= subject_icon($selected) ?> <?= e($selected) ?></h2><?php if(!$topics): ?><p class="muted">No hay temas en esta asignatura todavía.</p><a class="btn" href="topic_edit.php?subject=<?= urlencode($selected) ?>">Crear primer tema</a><?php else: ?><table class="table"><tr><th>Tema</th><th>Curso</th><th>Preguntas</th><th>Acciones</th></tr><?php foreach($topics as $t): ?><tr><td><b><?= e($t['title']) ?></b><br><span class="muted"><?= e((string)$t['description']) ?></span></td><td><?= e($t['course']) ?></td><td><?= (int)$t['questions'] ?></td><td class="admin-actions"><a href="topic_edit.php?id=<?= (int)$t['id'] ?>">Editar tema</a><a href="question_edit.php?topic_id=<?= (int)$t['id'] ?>">Preguntas</a><a href="uploads.php?topic_id=<?= (int)$t['id'] ?>">Archivos</a><a href="print_exam.php?topic_id=<?= (int)$t['id'] ?>" target="_blank">Imprimir</a><form method="post" class="inline-form" onsubmit="return confirm('¿Eliminar este tema y sus preguntas?')"><input type="hidden" name="action" value="delete_topic"><input type="hidden" name="topic_id" value="<?= (int)$t['id'] ?>"><input class="input pin-input" type="password" name="admin_pin" placeholder="PIN 2283"><button class="btn danger small">Eliminar</button></form></td></tr><?php endforeach; ?></table><?php endif; ?></section>
</div></body></html>
