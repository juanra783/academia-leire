<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_admin();
$id = (int)($_GET['id'] ?? 0);
$topic = ['subject'=>($_GET['subject'] ?? 'Matemáticas'),'title'=>'','description'=>'','content'=>'','course'=>'4º Primaria Andalucía'];
if ($id) { $st=db()->prepare('SELECT * FROM topics WHERE id=?'); $st->execute([$id]); $topic=$st->fetch() ?: $topic; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data=[trim($_POST['subject']), trim($_POST['title']), trim($_POST['description']), trim($_POST['content']), trim($_POST['course']) ?: '4º Primaria'];
    if ($id) { $sql='UPDATE topics SET subject=?, title=?, description=?, content=?, course=? WHERE id=?'; db()->prepare($sql)->execute([...$data,$id]); }
    else { $sql='INSERT INTO topics(subject,title,description,content,course) VALUES(?,?,?,?,?)'; db()->prepare($sql)->execute($data); }
    redirect('panel.php');
}
$subjects = ['Matemáticas','Lengua','Conocimiento del Medio','Inglés'];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tema</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap">
<header class="topbar"><div class="brand">🧩 Tema</div><nav class="nav"><a class="secondary" href="panel.php">Volver</a></nav></header>
<form class="card form" method="post"><p class="pill">V3.1 Andalucía</p><h1><?= $id?'Editar':'Añadir' ?> tema</h1><label>Asignatura<select name="subject" required><?php foreach($subjects as $s): ?><option value="<?= e($s) ?>" <?= $topic['subject']===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></label><label>Curso<input class="input" name="course" value="<?= e($topic['course']) ?>"></label><label>Título del tema<input class="input" name="title" value="<?= e($topic['title']) ?>" required></label><label>Descripción corta<input class="input" name="description" value="<?= e($topic['description']) ?>"></label><label>Contenido/apuntes<textarea name="content" placeholder="Pega aquí lo que está estudiando Leire ahora o lo que estudiará más adelante."><?= e($topic['content']) ?></textarea></label><button class="btn">Guardar tema</button></form>
</div></body></html>
