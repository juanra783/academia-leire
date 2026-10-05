<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
ensure_student_money_tables();
$message = '';
$profile = ensure_student_profile((int)$user['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'request_withdrawal') {
        $amount = parse_money_to_cents((string)($_POST['amount'] ?? '0'));
        $message = create_withdrawal_request((int)$user['id'], $amount, (string)($_POST['note'] ?? ''));
        $profile = ensure_student_profile((int)$user['id']);
    }
}
$requests = withdrawal_requests_for_user((int)$user['id'], 20);
$movements = wallet_transactions_for_user((int)$user['id'], 20);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mi dinero</title><link rel="manifest" href="manifest.webmanifest?v=5.14"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="stylesheet" href="assets/css/style.css?v=5.14"></head><body><div class="wrap">
<header class="topbar"><div class="brand">💶 Mi dinero <span class="pill">V5.14</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="profile.php">Perfil</a><a class="secondary" href="shop.php">Tienda</a><a class="secondary" href="teacher.php?back=wallet.php">Luna IA</a></nav></header>
<section class="kid-hero"><div class="avatar-card"><img class="avatar" src="<?= e($profile['avatar'] ?: 'assets/img/avatar-leire-main.jpg') ?>" alt="Perfil"><div class="level-badge">Saldo</div><p class="score wallet-score"><?= e(format_cents((int)$profile['coins'])) ?></p></div><div class="card hero-main"><p class="pill">Recompensas reales</p><h1>Retirar dinero</h1><p class="muted">Aquí Leire puede pedir retirar parte de su saldo. Juanra lo verá en el panel y podrá pagarlo, rechazarlo o ajustar el saldo.</p><?php if($message): ?><div class="notice reward"><?= e($message) ?></div><?php endif; ?><form class="form" method="post"><input type="hidden" name="action" value="request_withdrawal"><input class="input" name="amount" placeholder="Cantidad, ejemplo 0,05" required><input class="input" name="note" placeholder="Nota opcional, ejemplo: quiero retirarlo para chuches"><button class="btn">Pedir retirada a Juanra</button></form></div></section>
<section class="split"><div class="card"><h2>Solicitudes</h2><?php if(!$requests): ?><p class="muted">Todavía no has pedido retirar dinero.</p><?php else: ?><table class="table"><tr><th>Fecha</th><th>Cantidad</th><th>Estado</th></tr><?php foreach($requests as $r): ?><tr><td><?= e(date('d/m H:i', strtotime($r['created_at']))) ?></td><td><b><?= e(format_cents((int)$r['amount_cents'])) ?></b></td><td><span class="pill status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td></tr><?php endforeach; ?></table><?php endif; ?></div><div class="card"><h2>Movimientos</h2><?php if(!$movements): ?><p class="muted">Aún no hay movimientos manuales.</p><?php else: ?><table class="table"><tr><th>Fecha</th><th>Motivo</th><th>Cantidad</th></tr><?php foreach($movements as $m): ?><tr><td><?= e(date('d/m H:i', strtotime($m['created_at']))) ?></td><td><?= e($m['reason']) ?></td><td><b><?= e(format_signed_cents((int)$m['amount_cents'])) ?></b></td></tr><?php endforeach; ?></table><?php endif; ?></div></section>
<?= luna_floating_button() ?>
</div></body></html>
