<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_admin();
$topics = db()->query('SELECT * FROM topics ORDER BY subject,title')->fetchAll();
$selected = (int)($_GET['topic_id'] ?? 0);
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $topic_id=(int)$_POST['topic_id']; $type=$_POST['type']; $question=trim($_POST['question']); $correct=trim($_POST['correct_answer']); $explanation=trim($_POST['explanation']);
    $options = [];
    if ($type === 'multiple') {
        foreach (explode("\n", $_POST['options'] ?? '') as $line) { $line=trim($line); if($line!=='') $options[]=$line; }
    }
    db()->prepare('INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(?,?,?,?,?,?,?)')->execute([$topic_id,$type,$question,json_encode($options,JSON_UNESCAPED_UNICODE),$correct,$explanation,(int)$_POST['difficulty']]);
    $msg='Pregunta guardada correctamente.'; $selected=$topic_id;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pregunta</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap">
<header class="topbar"><div class="brand">❓ Preguntas</div><nav class="nav"><a class="secondary" href="panel.php">Volver</a></nav></header>
<form class="card form" method="post"><h1>Añadir pregunta</h1><?php if($msg): ?><div class="notice ok"><?= e($msg) ?></div><?php endif; ?><label>Tema<select name="topic_id" required><?php foreach($topics as $t): ?><option value="<?= (int)$t['id'] ?>" <?= $selected===(int)$t['id']?'selected':'' ?>><?= e($t['subject'].' - '.$t['title']) ?></option><?php endforeach; ?></select></label><label>Tipo<select name="type"><option value="text">Respuesta escrita</option><option value="multiple">Tipo test</option></select></label><label>Pregunta<textarea name="question" required></textarea></label><label>Opciones tipo test <span class="muted">una por línea</span><textarea name="options"></textarea></label><label>Respuesta correcta<input class="input" name="correct_answer" required></label><label>Explicación si falla<textarea name="explanation" required></textarea></label><label>Dificultad<select name="difficulty"><option value="1">Fácil</option><option value="2">Media</option><option value="3">Difícil</option></select></label><button class="btn">Guardar pregunta</button></form>
</div></body></html>
