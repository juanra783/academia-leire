<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_admin();
$curriculum = [
 'Matemáticas'=>['Numeración','Operaciones','Problemas','Fracciones','Decimales','Medidas','Geometría','Estadística'],
 'Lengua'=>['Comprensión lectora','Expresión escrita','Ortografía','Gramática','Vocabulario','Lectura'],
 'Conocimiento del Medio'=>['Seres vivos','Cuerpo humano','Ecosistemas','Materia y energía','Máquinas','España','Andalucía'],
 'Inglés'=>['Vocabulary','Grammar','Reading','Writing','Listening futuro']
];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Temario Andalucía</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap">
<header class="topbar"><div class="brand">📚 Temario Andalucía</div><nav class="nav"><a class="secondary" href="panel.php">Panel</a></nav></header>
<section class="card"><p class="pill">4º Primaria Andalucía</p><h1>Bloques de trabajo</h1><p class="muted">Guía interna para crear temas. No sustituye al libro del colegio: sirve para organizar los repasos de Leire.</p></section>
<div class="grid"><?php foreach($curriculum as $sub=>$items): ?><div class="subject"><strong><?= subject_icon($sub) ?> <?= e($sub) ?></strong><ul><?php foreach($items as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul><a class="btn secondary" href="topic_edit.php?subject=<?= urlencode($sub) ?>">Crear tema</a></div><?php endforeach; ?></div>
</div></body></html>
