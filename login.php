<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login_user(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        redirect((($_SESSION['user']['role'] ?? '') === 'admin') ? '../admin/panel.php' : 'dashboard.php');
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= APP_NAME ?></title><link rel="manifest" href="manifest.webmanifest?v=5.5"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=55"><link rel="icon" href="favicon.ico?v=55"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=55"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.5?v=5.5"></head><body>
<div class="wrap">
  <div class="hero">
    <section class="card">
      <span class="pill">4º de Primaria</span>
      <h1>Deberes y Exámenes de Leire</h1>
      <p class="muted">Ejercicios, exámenes, autocorrección y explicaciones sencillas cuando algo salga mal.</p>
    </section>
    <section class="card">
      <h2>Entrar</h2>
      <?php if($error): ?><div class="notice bad"><?= e($error) ?></div><?php endif; ?>
      <form class="form" method="post">
        <input class="input" name="username" placeholder="Usuario" required>
        <input class="input" type="password" name="password" placeholder="Contraseña" required>
        <button class="btn">Entrar</button>
      </form>
      <p class="muted">Usuarios iniciales: <b>admin/admin123</b> y <b>leire/leire123</b>.</p>
    </section>
  </div>
</div></body></html>
