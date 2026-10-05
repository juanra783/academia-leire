<?php
require_once __DIR__ . '/../app/auth.php';
$user = require_admin();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM topics WHERE id=?');
$st->execute([$id]);
$t = $st->fetch();
if (!$t) { header('Location: panel.php'); exit; }
db()->prepare('INSERT INTO topics(subject,course,title,description,content) VALUES(?,?,?,?,?)')->execute([$t['subject'],$t['course'],$t['title'].' (copia)',$t['description'],$t['content']]);
$newId = (int)db()->lastInsertId();
$q = db()->prepare('SELECT * FROM questions WHERE topic_id=?');
$q->execute([$id]);
foreach ($q->fetchAll() as $row) {
    db()->prepare('INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(?,?,?,?,?,?,?)')->execute([$newId,$row['type'],$row['question'],$row['options_json'],$row['correct_answer'],$row['explanation'],$row['difficulty']]);
}
header('Location: topic_edit.php?id=' . $newId);
