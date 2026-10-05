<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
ensure_v44_tables();
$profile = ensure_student_profile((int)$user['id']);
$equipped = equipped_rewards((int)$user['id']);
$emiliaEquipment = emilia_equipped_for_user((int)$user['id']);
$db = db();
$message = '';
try {
    $db->prepare('INSERT IGNORE INTO mascots(user_id,name,level,xp,mood,skin) VALUES(?,?,?,?,?,?)')->execute([(int)$user['id'],'Emilia',1,0,'lista para estudiar','emilia_base']);
    $db->prepare("UPDATE mascots SET name='Emilia', skin='emilia_base' WHERE user_id=?")->execute([(int)$user['id']]);
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='practice') {
        if ((int)$profile['coins'] >= 10) {
            $db->prepare('UPDATE student_profiles SET coins=coins-10 WHERE user_id=?')->execute([(int)$user['id']]);
            $db->prepare('UPDATE mascots SET xp=xp+20, mood=?, last_fed=CURDATE() WHERE user_id=?')->execute(['feliz y motivada',(int)$user['id']]);
            award_progress((int)$user['id'], 'Practicar con Emilia', 5, 0, null);
            $message = 'Emilia ha preparado un reto nuevo. +20 XP de compañera.';
        } else {
            $message = 'Aún no hay saldo suficiente para preparar un reto especial con Emilia.';
        }
    }
    $st=$db->prepare('SELECT * FROM mascots WHERE user_id=?');
    $st->execute([(int)$user['id']]);
    $mascot=$st->fetch();
    $level=(int)$mascot['level'];
    $xp=(int)$mascot['xp'];
    while($xp>=100){$xp-=100;$level++;}
    if($level!=(int)$mascot['level'] || $xp!=(int)$mascot['xp']) {
        $db->prepare('UPDATE mascots SET level=?, xp=? WHERE user_id=?')->execute([$level,$xp,(int)$user['id']]);
    }
    $mascot['level']=$level;
    $mascot['xp']=$xp;
} catch(Throwable $e) {
    $mascot=['name'=>'Emilia','level'=>1,'xp'=>0,'mood'=>'lista para estudiar','last_fed'=>null];
}
$profile=ensure_student_profile((int)$user['id']);
$emiliaItems = emilia_items_for_user((int)$user['id'], true);
function emiliaLayer(array $equipment, string $category, string $class): string {
    if (empty($equipment[$category])) return '';
    return '<div class="emilia-layer '.$class.'" title="'.e($equipment[$category]['title']).'">'.e($equipment[$category]['icon']).'</div>';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Emilia</title><link rel="manifest" href="manifest.webmanifest?v=5.13"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=5.13"></head><body><div class="wrap">
<header class="topbar"><div class="brand">✨ Emilia <span class="pill">V5.13</span></div><nav class="nav"><a class="secondary" href="dashboard.php">Inicio</a><a class="secondary" href="adventure.php">Aventura</a><a class="secondary" href="battle.php">Reto</a><a class="secondary" href="shop.php">Tienda</a></nav></header>
<section class="kid-hero">
  <div class="avatar-card emilia-card">
    <div class="emilia-preview">
      <img src="assets/img/emilia-main.png" alt="Emilia, compañera de Academia Leire">
      <?= emiliaLayer($emiliaEquipment, 'emilia_cabeza', 'head') ?>
      <?= emiliaLayer($emiliaEquipment, 'emilia_gafas', 'glasses') ?>
      <?= emiliaLayer($emiliaEquipment, 'emilia_ropa', 'outfit') ?>
      <?= emiliaLayer($emiliaEquipment, 'emilia_extra', 'extra') ?>
    </div>
    <div class="level-badge">Nivel <?= (int)$mascot['level'] ?></div>
    <div class="xpbar"><span style="width:<?= (int)$mascot['xp'] ?>%"></span></div>
    <p class="muted">XP <?= (int)$mascot['xp'] ?>/100 · <?= e($mascot['mood']) ?></p>
  </div>
  <div class="card hero-main emilia-bubble"><p class="pill">Compañera de aprendizaje</p><h1><?= e($mascot['name']) ?> acompaña a Leire</h1><p class="muted">Emilia sustituye a Draco como personaje de recompensas. Leire gana céntimos sacando buenas notas y puede comprarle gafas, sombreros, ropa, accesorios, fondos y objetos especiales.</p><?php if($message): ?><div class="notice reward"><?= e($message) ?></div><?php endif; ?><p>💶 Saldo disponible: <b><?= e(format_cents((int)$profile['coins'])) ?></b></p><p class="emilia-status"><b>Equipado:</b> <?= e(emilia_equipped_text($emiliaEquipment)) ?></p><form method="post"><input type="hidden" name="action" value="practice"><button class="btn" <?= (int)$profile['coins']<10?'disabled':'' ?>>Preparar reto especial · 0,10 €</button> <a class="btn secondary" href="shop.php">Personalizar Emilia</a></form></div>
</section>
<section class="card"><h2>Armario de Emilia</h2><?php if(!$emiliaItems): ?><p class="muted">Todavía no hay objetos comprados para Emilia. Entra en la tienda cuando Leire tenga céntimos.</p><p><a class="btn" href="shop.php">Ir a la tienda</a></p><?php else: ?><div class="grid small"><?php foreach($emiliaItems as $it): ?><div class="subject"><strong><?= e($it['icon']) ?> <?= e($it['title']) ?></strong><span class="muted"><?= e($it['description']) ?></span><?= (int)$it['equipped']===1?'<p><span class="pill equipped">Equipado</span></p>':'<p><span class="pill">Comprado</span></p>' ?></div><?php endforeach; ?></div><?php endif; ?></section>
<section class="card"><h2>Retos</h2><p class="muted">El modo reto convierte un repaso corto en un juego de 5 preguntas. Es ideal para practicar temas flojos y ganar céntimos.</p><p><a class="btn secondary" href="battle.php">Elegir reto</a></p></section>
<?= luna_floating_button() ?>
</div></body></html>
