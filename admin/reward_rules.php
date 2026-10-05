<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user=require_admin();
ensure_admin_v524_tables();
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $message=save_reward_rule((int)($_POST['rule_id'] ?? 0),(string)($_POST['rule_mode'] ?? 'examen'),(float)str_replace(',','.',(string)($_POST['min_score'] ?? '10')),(int)($_POST['amount_cents'] ?? 0),!empty($_POST['active']));
}
$rules=reward_rules_for_admin();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reglas de recompensas · Academia Star</title><link rel="stylesheet" href="../public/assets/css/style.css?v=5.24"></head><body class="magic-ui admin-page-v524"><div class="admin-shell">
<header class="admin-command-header"><div><span class="pill glow-pill">Dinero y recompensas</span><h1>Reglas de recompensas</h1><p class="muted">Página separada para controlar cuánto gana cada alumno según nota y tipo de actividad.</p></div><nav class="admin-top-actions"><a class="btn secondary" href="panel.php#dinero">Volver a dinero</a><a class="btn secondary" href="panel.php">Panel</a></nav></header>
<?php if($message): ?><div class="notice reward admin-message"><?= e($message) ?></div><?php endif; ?>
<section class="card admin-card-green"><h2>Reglas actuales</h2><?php if(!$rules): ?><p class="muted">No hay reglas todavía.</p><?php else: ?><div class="reward-rule-grid"><?php foreach($rules as $rule): ?><form method="post" class="reward-rule-card"><input type="hidden" name="rule_id" value="<?= (int)$rule['id'] ?>"><input type="hidden" name="rule_mode" value="<?= e($rule['mode']) ?>"><strong><?= e(ucfirst($rule['mode'])) ?></strong><label>Nota mínima<input class="input" name="min_score" value="<?= e((string)$rule['min_score']) ?>"></label><label>Céntimos<input class="input" name="amount_cents" type="number" min="0" value="<?= (int)$rule['amount_cents'] ?>"></label><label class="checkbox-line"><input type="checkbox" name="active" value="1" <?= (int)$rule['active']===1?'checked':'' ?>> Activa</label><button class="btn secondary small">Guardar</button></form><?php endforeach; ?></div><?php endif; ?></section>
<section class="card admin-card-blue"><h2>Añadir regla nueva</h2><form class="form compact-form" method="post"><input type="hidden" name="rule_id" value="0"><select class="input" name="rule_mode"><option value="examen">Examen</option><option value="deberes">Deberes</option><option value="repaso">Repaso</option><option value="batalla">Reto Emilia</option></select><input class="input" name="min_score" placeholder="Nota mínima, ejemplo 8"><input class="input" type="number" min="0" name="amount_cents" placeholder="Céntimos"><label class="checkbox-line"><input type="checkbox" name="active" value="1" checked> Activa</label><button class="btn">Añadir regla</button></form></section>
</div></body></html>
