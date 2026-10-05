<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/ai.php';
$user = require_admin();
ensure_v42_tables();
$message = '';
if (isset($_GET['convert'])) {
    $message = convert_scan_to_topic((int)$_GET['convert']);
}
$materials = scan_recent_materials((int)$user['id'], true, 50);
$attempts = [];
try {
    $attempts = db()->query('SELECT sa.*, sm.title material_title, u.name user_name FROM scan_attempts sa JOIN scanned_materials sm ON sm.id=sa.material_id JOIN users u ON u.id=sa.user_id ORDER BY sa.created_at DESC LIMIT 20')->fetchAll();
} catch (Throwable $e) {}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Panel escaneos</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap">
<header class="topbar"><div class="brand">📸 Escaneos y fichas <span class="pill">V4.7</span></div><nav class="nav"><a class="secondary" href="panel.php">Panel Juanra</a><a class="secondary" href="../public/scan.php">Subir foto</a><a class="secondary" href="../public/dashboard.php">App Leire</a></nav></header>
<section class="card"><h1>Material escaneado</h1><p class="muted">Aquí ves las fotos/fichas subidas, puedes abrirlas, revisar actividades y convertirlas en temas permanentes del banco de preguntas.</p><a class="btn" href="../public/scan.php">Escanear nueva ficha</a></section>
<?php if($message): ?><div class="notice"><b>Resultado:</b> <?= e($message) ?></div><?php endif; ?>
<section class="card"><h2>Últimos materiales</h2><?php if(!$materials): ?><p class="muted">Aún no hay materiales escaneados.</p><?php else: ?><table class="table"><tr><th>Fecha</th><th>Alumno</th><th>Título</th><th>Asignatura</th><th>Estado</th><th>Acciones</th></tr><?php foreach($materials as $m): ?><tr><td><?= e($m['created_at']) ?></td><td><?= e($m['user_name'] ?? '') ?></td><td><?= e($m['title']) ?></td><td><?= e($m['subject'] ?: '-') ?></td><td><span class="pill"><?= e($m['status']) ?></span></td><td><a href="../public/scan_view.php?id=<?= (int)$m['id'] ?>">Ver</a> · <a href="?convert=<?= (int)$m['id'] ?>" onclick="return confirm('¿Crear un tema nuevo con las preguntas generadas?')">Convertir en tema</a></td></tr><?php endforeach; ?></table><?php endif; ?></section>
<section class="card"><h2>Resultados desde fichas escaneadas</h2><?php if(!$attempts): ?><p class="muted">Todavía no hay intentos.</p><?php else: ?><table class="table"><tr><th>Fecha</th><th>Alumno</th><th>Ficha</th><th>Nota</th><th>Aciertos</th></tr><?php foreach($attempts as $a): ?><tr><td><?= e($a['created_at']) ?></td><td><?= e($a['user_name']) ?></td><td><?= e($a['material_title']) ?></td><td><b><?= e((string)$a['score']) ?>/10</b></td><td><?= (int)$a['correct_questions'] ?>/<?= (int)$a['total_questions'] ?></td></tr><?php endforeach; ?></table><?php endif; ?></section>
</div></body></html>
