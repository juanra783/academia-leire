<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
ensure_v44_tables();
$profile = ensure_student_profile((int)$user['id']);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'buy') {
        $message = purchase_reward((int)$user['id'], (int)($_POST['item_id'] ?? 0));
    } elseif ($action === 'equip') {
        $message = equip_reward((int)$user['id'], (int)($_POST['owned_id'] ?? 0));
    } elseif ($action === 'unequip') {
        $message = unequip_reward((int)$user['id'], (int)($_POST['owned_id'] ?? 0));
    } elseif ($action === 'unequip_category') {
        $message = unequip_reward_category((int)$user['id'], (string)($_POST['category'] ?? ''));
    }
    $profile = ensure_student_profile((int)$user['id']);
}
$items = shop_items_for_user((int)$user['id']);
$categories = [
    'emilia_cabeza'=>'Emilia · Cabeza',
    'emilia_gafas'=>'Emilia · Gafas',
    'emilia_ropa'=>'Emilia · Ropa',
    'emilia_extra'=>'Emilia · Extras',
    'marco'=>'Marcos de Leire',
    'fondo'=>'Fondos',
    'ropa'=>'Avatar de Leire',
    'pegatina'=>'Pegatinas',
    'bonus'=>'Bonus'
];
$grouped = [];
foreach ($items as $item) $grouped[$item['category']][] = $item;
$emiliaEquipment = emilia_equipped_for_user((int)$user['id']);
function emiliaShopLayer(array $equipment, string $category, string $class): string {
    if (empty($equipment[$category])) return '';
    return '<div class="emilia-layer '.$class.'" title="'.e($equipment[$category]['title']).'">'.e($equipment[$category]['icon']).'</div>';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tienda de recompensas</title><link rel="manifest" href="manifest.webmanifest?v=5.13"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.13"></head><body><div class="wrap">
<header class="topbar"><div class="brand">🛍️ Tienda de Emilia <span class="pill">V5.13</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="trainer.php">Entrenador</a><a class="secondary" href="mascot.php">Emilia</a></nav></header>
<section class="emilia-shop-hero">
  <div class="avatar-card emilia-card">
    <div class="emilia-preview">
      <img src="assets/img/emilia-main.png" alt="Emilia personalizable">
      <?= emiliaShopLayer($emiliaEquipment, 'emilia_cabeza', 'head') ?>
      <?= emiliaShopLayer($emiliaEquipment, 'emilia_gafas', 'glasses') ?>
      <?= emiliaShopLayer($emiliaEquipment, 'emilia_ropa', 'outfit') ?>
      <?= emiliaShopLayer($emiliaEquipment, 'emilia_extra', 'extra') ?>
    </div>
    <div class="level-badge">Nivel <?= (int)$profile['level'] ?></div>
    <p class="muted">💶 <?= e(format_cents((int)$profile['coins'])) ?> de saldo disponible</p>
  </div>
  <div class="card hero-main emilia-bubble"><p class="pill">Recompensas</p><h1>Personaliza a Emilia</h1><p class="muted">Leire gana céntimos sacando buenas notas. Aquí puede comprarle a Emilia gafas, sombreros, ropa y extras. También puede comprar fondos, marcos, pegatinas y accesorios para su avatar.</p><?php if($message): ?><div class="notice reward"><?= e($message) ?></div><?php endif; ?><p class="emilia-status"><b>Equipado ahora:</b> <?= e(emilia_equipped_text($emiliaEquipment)) ?></p></div>
</section>
<div class="category-tabs">
<?php foreach($categories as $cat=>$label): if(empty($grouped[$cat])) continue; ?><a href="#cat-<?= e($cat) ?>"><?= e($label) ?></a><?php endforeach; ?>
</div>
<section class="notice emilia-note">Consejo: en Emilia puedes equipar una cosa por categoría: cabeza, gafas, ropa y extra. Así no se mezclan demasiadas recompensas a la vez.</section>
<?php foreach($categories as $cat=>$label): if(empty($grouped[$cat])) continue; $isEmilia = str_starts_with($cat, 'emilia_'); ?>
<section class="card" id="cat-<?= e($cat) ?>"><h2><?= e($label) ?></h2><?php if($isEmilia && !empty($emiliaEquipment[$cat])): ?><form method="post" class="inline-form emilia-unequip-category"><input type="hidden" name="action" value="unequip_category"><input type="hidden" name="category" value="<?= e($cat) ?>"><button class="btn secondary small">Quitar <?= e($label) ?></button></form><?php endif; ?><div class="shop-grid">
<?php foreach($grouped[$cat] as $item): $locked=(int)$profile['level'] < (int)$item['unlock_level']; $owned=!empty($item['owned_id']); ?>
  <div class="shop-item rarity-<?= e($item['rarity']) ?> <?= $locked?'locked':'' ?> <?= $isEmilia?'emilia-focused':'' ?>">
    <?php if($owned): ?><span class="owned-badge">Comprado</span><?php endif; ?>
    <div class="shop-icon"><?= e($item['icon']) ?></div>
    <h3><?= e($item['title']) ?></h3>
    <p class="muted"><?= e($item['description']) ?></p>
    <p><span class="pill">Nivel <?= (int)$item['unlock_level'] ?></span> <span class="pill">💶 <?= e(format_cents((int)$item['price'])) ?></span></p>
    <?php if($owned): ?>
      <?php if((int)$item['equipped'] === 1): ?><span class="pill equipped">Equipado</span><form method="post" class="inline-form"><input type="hidden" name="action" value="unequip"><input type="hidden" name="owned_id" value="<?= (int)$item['owned_id'] ?>"><button class="btn secondary small">Quitar</button></form><?php else: ?><form method="post"><input type="hidden" name="action" value="equip"><input type="hidden" name="owned_id" value="<?= (int)$item['owned_id'] ?>"><button class="btn secondary">Equipar</button></form><?php endif; ?>
    <?php elseif($locked): ?>
      <button class="btn secondary" disabled>Bloqueado</button>
    <?php else: ?>
      <form method="post"><input type="hidden" name="action" value="buy"><input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>"><button class="btn" <?= (int)$profile['coins'] < (int)$item['price'] ? 'disabled' : '' ?>>Comprar</button></form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div></section>
<?php endforeach; ?>
<?= luna_floating_button() ?>
</div></body></html>
