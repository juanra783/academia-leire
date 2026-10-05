<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
ensure_student_money_tables();
$message = '';
$profile = ensure_student_profile((int)$user['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $message = save_profile_avatar_upload((int)$user['id'], $_FILES['avatar']);
    $profile = ensure_student_profile((int)$user['id']);
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Perfil de Leire</title><link rel="manifest" href="manifest.webmanifest?v=5.14"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="stylesheet" href="assets/css/style.css?v=5.14"></head><body><div class="wrap">
<header class="topbar"><div class="brand">👧 Perfil <span class="pill">V5.14</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="wallet.php">Mi dinero</a><a class="secondary" href="shop.php">Tienda</a></nav></header>
<section class="kid-hero"><div class="avatar-card"><img class="avatar" src="<?= e($profile['avatar'] ?: 'assets/img/avatar-leire-main.jpg') ?>" alt="Perfil de Leire"><div class="level-badge">Nivel <?= (int)$profile['level'] ?></div><div class="xpbar"><span style="width:<?= progress_percent($profile) ?>%"></span></div><p class="muted">XP <?= (int)$profile['xp'] ?>/<?= xp_for_next_level((int)$profile['level']) ?></p></div><div class="card hero-main"><p class="pill">Foto de perfil</p><h1>Cambiar foto</h1><p class="muted">Puedes poner una foto o avatar nuevo de Leire. Se verá en el inicio, panel y perfil.</p><?php if($message): ?><div class="notice reward"><?= e($message) ?></div><?php endif; ?><form class="form" method="post" enctype="multipart/form-data"><input class="input" type="file" name="avatar" accept="image/*" required><button class="btn">Guardar foto</button></form></div></section>
<?= luna_floating_button() ?>
</div></body></html>
