<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
require_admin();
ensure_service_accounts_table();
$db = db();
$message = '';
$messageType = 'ok';

function account_status_label(string $s): string {
    return ['empty'=>'Disponible','sold'=>'Vendida','pending'=>'Pendiente'][$s] ?? $s;
}
function account_status_icon(string $s): string {
    return ['empty'=>'●','sold'=>'✓','pending'=>'◷'][$s] ?? '•';
}
function cents_from_input(string $v): ?int {
    $v = trim(str_replace(',', '.', $v));
    if ($v === '') return null;
    return (int) round(((float)$v) * 100);
}
function custom_fields_decode($v): array {
    if (is_array($v)) return $v;
    $x = json_decode((string)$v, true);
    return is_array($x) ? $x : [];
}
function service_url(string $service): string {
    $s = strtolower($service);
    if (str_contains($s, 'dazn')) return 'https://www.dazn.com/';
    if (str_contains($s, 'hbo')) return 'https://www.hbomax.com/';
    if (str_contains($s, 'movistar')) return 'https://www.movistarplus.es/';
    if (str_contains($s, 'netflix')) return 'https://www.netflix.com/';
    if (str_contains($s, 'prime')) return 'https://www.primevideo.com/';
    if (str_contains($s, 'disney')) return 'https://www.disneyplus.com/';
    return '';
}
function service_icon(string $service): string {
    $s = strtolower($service);
    if (str_contains($s,'dazn')) return 'dazn';
    if (str_contains($s,'netflix')) return 'netflix';
    if (str_contains($s,'hbo') || str_contains($s,'max')) return 'max';
    if (str_contains($s,'movistar')) return 'movistar';
    if (str_contains($s,'youtube')) return 'youtube';
    if (str_contains($s,'prime')) return 'prime';
    if (str_contains($s,'disney')) return 'disney';
    return 'generic';
}
function account_icon_key(array $a): string {
    $custom = custom_fields_decode($a['custom_fields'] ?? null);
    $key = $custom['_icon'] ?? 'auto';
    return $key === 'auto' || $key === '' ? service_icon((string)($a['service'] ?? '')) : $key;
}
function nice_date(?string $date): string {
    if (!$date) return 'Sin fecha';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $email = trim((string)($_POST['email'] ?? ''));
            $password = trim((string)($_POST['password_value'] ?? ''));
            $buyer = trim((string)($_POST['buyer'] ?? ''));
            $service = trim((string)($_POST['service'] ?? 'DAZN')) ?: 'DAZN';
            $status = (string)($_POST['status'] ?? 'empty');
            $paid = !empty($_POST['paid']) ? 1 : 0;
            $price = cents_from_input((string)($_POST['price'] ?? ''));
            $renewal = trim((string)($_POST['renewal_date'] ?? ''));
            $renewal = $renewal !== '' ? $renewal : null;
            $notes = trim((string)($_POST['notes'] ?? ''));
            $labels = $_POST['custom_label'] ?? [];
            $values = $_POST['custom_value'] ?? [];
            $custom = [];
            $selectedIcon = trim((string)($_POST['icon_key'] ?? 'auto'));
            if ($selectedIcon !== 'auto') $custom['_icon'] = $selectedIcon;
            foreach ($labels as $i => $label) {
                $label = trim((string)$label);
                $value = trim((string)($values[$i] ?? ''));
                if ($label !== '') $custom[$label] = $value;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Introduce un email válido.');
            if (!in_array($status, ['empty','sold','pending'], true)) $status = 'empty';
            $json = $custom ? json_encode($custom, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null;
            if ($id) {
                $st = $db->prepare('UPDATE service_accounts SET email=?,password_value=?,buyer=?,service=?,status=?,paid=?,price_cents=?,renewal_date=?,notes=?,custom_fields=? WHERE id=?');
                $st->execute([$email,$password ?: null,$buyer ?: null,$service,$status,$paid,$price,$renewal,$notes ?: null,$json,$id]);
                $message = 'Cuenta actualizada correctamente.';
            } else {
                $st = $db->prepare('INSERT INTO service_accounts(email,password_value,buyer,service,status,paid,price_cents,renewal_date,notes,custom_fields) VALUES(?,?,?,?,?,?,?,?,?,?)');
                $st->execute([$email,$password ?: null,$buyer ?: null,$service,$status,$paid,$price,$renewal,$notes ?: null,$json]);
                $message = 'Cuenta creada correctamente.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) $db->prepare('DELETE FROM service_accounts WHERE id=?')->execute([$id]);
            $message = 'Cuenta eliminada.';
        }
    } catch (Throwable $e) {
        $message = 'No se pudo guardar: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare('SELECT * FROM service_accounts WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$accounts = service_accounts_for_admin();
$total = count($accounts);
$empty = count(array_filter($accounts, fn($a) => ($a['status'] ?? '') === 'empty'));
$sold = count(array_filter($accounts, fn($a) => ($a['status'] ?? '') === 'sold'));
$pending = count(array_filter($accounts, fn($a) => ($a['status'] ?? '') === 'pending'));
$paidCount = count(array_filter($accounts, fn($a) => !empty($a['paid'])));
$custom = custom_fields_decode($edit['custom_fields'] ?? null);
$editIcon = $custom['_icon'] ?? 'auto';
unset($custom['_icon']);
$editing = (bool)$edit;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#08071a">
<title>Cuentas · Academia Star</title>
<link rel="stylesheet" href="../public/assets/css/style.css?v=64">
<style>
:root{--bg:#070719;--panel:rgba(15,14,39,.88);--panel2:rgba(23,21,55,.72);--line:rgba(255,255,255,.085);--text:#f7f5ff;--muted:#9c96b9;--purple:#855cff;--pink:#c45dff;--green:#45ddb0;--yellow:#ffc65b;--red:#ff6f8c;--blue:#55cfff;--shadow:0 20px 60px rgba(0,0,0,.28)}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0!important;color:var(--text)!important;background:radial-gradient(circle at 15% 0%,rgba(105,73,255,.18),transparent 32%),radial-gradient(circle at 90% 25%,rgba(211,70,255,.13),transparent 30%),linear-gradient(135deg,#070719,#0b0922 55%,#160b2d)!important;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.manager{width:min(1380px,96%);margin:auto;display:grid;grid-template-columns:218px minmax(0,1fr);gap:18px;padding:18px 0 34px;min-height:100vh}.side{position:sticky;top:18px;height:calc(100vh - 36px);background:rgba(10,9,28,.82);border:1px solid var(--line);border-radius:22px;padding:12px;backdrop-filter:blur(18px);box-shadow:var(--shadow);display:flex;flex-direction:column}.brand{display:flex;align-items:center;gap:10px;padding:8px 7px 16px}.brand-logo{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,var(--purple),var(--pink));font-weight:1000;font-size:1.05rem;box-shadow:0 8px 25px rgba(133,92,255,.3)}.brand b{display:block;font-size:.92rem}.brand small{display:block;color:var(--muted);font-size:.66rem;margin-top:2px}.nav{display:grid;gap:4px}.nav a{color:#a8a2bd;text-decoration:none;display:flex;align-items:center;gap:9px;padding:10px 10px;border-radius:12px;font-size:.8rem;font-weight:850}.nav a:hover,.nav a.active{background:rgba(133,92,255,.13);color:#e7e0ff}.nav .nicon{width:22px;text-align:center}.nav-group{margin-top:4px;padding:7px 5px 4px;color:#ddd7f3;font-size:.8rem;font-weight:950}.nav-sub{display:grid;gap:2px;padding-left:8px}.nav-sub a{font-size:.74rem;padding:8px 9px}.nav-sub a.active{background:rgba(133,92,255,.1)}.side-bottom{margin-top:auto;display:grid;gap:6px}.side-bottom a{color:#9992b0;text-decoration:none;background:rgba(255,255,255,.035);border:1px solid transparent;padding:9px;border-radius:11px;font-size:.7rem}.main{min-width:0}.top{display:flex;justify-content:space-between;align-items:center;gap:14px;margin:2px 0 14px}.top h1{margin:0;font-size:clamp(1.7rem,3vw,2.25rem);letter-spacing:-.045em}.top p{display:none}.top-actions{display:flex;gap:7px}.btn{appearance:none;border:0;border-radius:11px;padding:10px 13px;font-weight:900;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:7px;white-space:nowrap}.btn-primary{background:linear-gradient(135deg,var(--purple),var(--pink));color:white;box-shadow:0 8px 24px rgba(133,92,255,.22)}.btn-soft{background:rgba(255,255,255,.055);border:1px solid var(--line);color:#e9e4fb}.btn-danger{background:rgba(255,111,140,.08);border:1px solid rgba(255,111,140,.14);color:#ff9aae}.btn-small{padding:7px 10px;font-size:.72rem}.panel{border:1px solid var(--line);background:linear-gradient(145deg,rgba(19,17,47,.9),rgba(10,9,28,.86));border-radius:20px;box-shadow:var(--shadow);overflow:hidden}.toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px;border-bottom:1px solid var(--line)}.filters{display:flex;gap:5px;flex-wrap:wrap}.chip{border:1px solid var(--line);background:rgba(255,255,255,.035);color:#aaa3bd;border-radius:999px;padding:7px 10px;font-size:.67rem;font-weight:900;cursor:pointer}.chip.active{background:rgba(133,92,255,.17);border-color:rgba(133,92,255,.34);color:#ded6ff}.search{width:min(330px,38vw)}.input,.select,.textarea{width:100%;border:1px solid var(--line);background:rgba(4,4,16,.4);color:var(--text);border-radius:10px;padding:9px 10px;outline:none;font:inherit;font-size:.78rem}.input:focus,.select:focus,.textarea:focus{border-color:rgba(133,92,255,.6);box-shadow:0 0 0 3px rgba(133,92,255,.09)}.list-head,.account-row{display:grid;grid-template-columns:minmax(150px,1fr) minmax(220px,1.5fr) minmax(130px,.9fr) 112px 110px 82px minmax(170px,1.2fr);align-items:center;gap:12px}.list-head{padding:9px 14px;color:#716a89;text-transform:uppercase;letter-spacing:.08em;font-size:.56rem;font-weight:950;border-bottom:1px solid var(--line)}.account-row{padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.055);transition:.15s ease}.account-row:last-child{border-bottom:0}.account-row:hover{background:rgba(133,92,255,.045)}.service-cell{display:flex;align-items:center;gap:10px;min-width:0}.service-cell strong{font-size:.72rem;white-space:nowrap}.service-logo{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;overflow:hidden;background:#080812;border:1px solid rgba(255,255,255,.1)}.service-logo img{width:100%;height:100%;object-fit:cover}.email{font-weight:900;font-size:.82rem;overflow-wrap:anywhere}.client{font-size:.75rem;color:#d8d2e7}.service-name{font-size:.72rem;color:#aaa3bd}.status i{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px;background:currentColor}.status{font-size:.62rem;font-weight:950;padding:5px 7px;border-radius:999px;display:inline-flex;width:max-content}.status.empty{background:rgba(69,221,176,.1);color:#73e8c1}.status.sold{background:rgba(85,207,255,.1);color:#78dcff}.status.pending{background:rgba(255,198,91,.1);color:#ffd879}.date,.price{font-size:.7rem;color:#c3bdd4}.date-empty{color:#45ddb0}.date-sold{color:#55cfff}.date-pending{color:#ffc65b}.row-actions form{display:inline}.row-actions{display:flex;justify-content:flex-end;gap:5px}.notice{margin:12px 14px 0;padding:9px 11px;border-radius:10px;font-size:.72rem;background:rgba(69,221,176,.08);border:1px solid rgba(69,221,176,.14);color:#9cf2d0}.notice.error{background:rgba(255,111,140,.08);border-color:rgba(255,111,140,.14);color:#ffacbb}.editor{margin-top:14px}.editor-head{padding:15px 16px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:10px}.editor-head h2{margin:0;font-size:1rem}.editor-body{padding:15px 16px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.field{display:grid;gap:5px}.field.full{grid-column:1/-1}.field label{font-size:.63rem;font-weight:900;color:#aaa3bd}.pass{display:grid;grid-template-columns:1fr auto;gap:5px}.checkline{display:flex;align-items:center;gap:8px;min-height:38px;border:1px solid var(--line);background:rgba(255,255,255,.025);padding:0 10px;border-radius:10px;font-size:.7rem;color:#d4cee4}.checkline input{accent-color:var(--purple)}.advanced{border-top:1px solid var(--line);margin-top:12px;padding-top:10px}.advanced summary{cursor:pointer;list-style:none;color:#c9c2df;font-size:.7rem;font-weight:900}.advanced summary::-webkit-details-marker{display:none}.advanced-body{margin-top:10px}.custom-list{display:grid;gap:6px}.custom-row{display:grid;grid-template-columns:1fr 1.4fr auto;gap:6px}.form-actions{display:flex;gap:6px;margin-top:12px;flex-wrap:wrap}.icon-picker{display:flex;align-items:center;gap:8px}.icon-preview{width:38px;height:38px;border-radius:10px;overflow:hidden;border:1px solid var(--line);background:#09090f;flex:none}.icon-preview img{width:100%;height:100%;object-fit:cover}.empty-state{padding:40px 15px;text-align:center;color:#817a99;font-size:.76rem}.footer{display:none}
@media(max-width:1000px){.manager{grid-template-columns:1fr}.side{position:relative;top:auto;height:auto}.nav{grid-template-columns:repeat(3,1fr)}.side-bottom{display:flex;margin-top:9px}.list-head{display:none}.account-row{grid-template-columns:42px minmax(0,1fr) auto;gap:10px;padding:12px}.row-actions{justify-content:flex-end}.search{width:min(300px,48vw)}}
@media(max-width:650px){.manager{width:100%;padding:8px}.side{border-radius:17px}.nav{grid-template-columns:repeat(2,1fr)}.top{align-items:stretch;flex-direction:column}.top-actions .btn{width:100%}.toolbar{align-items:stretch;flex-direction:column}.search{width:100%}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.editor-body,.editor-head{padding-left:12px;padding-right:12px}.account-row{grid-template-columns:38px minmax(0,1fr) auto;gap:8px;padding:12px 10px;min-width:0}.service-cell{grid-column:1 / 3;grid-row:1;gap:8px}.service-cell strong{font-size:.68rem}.service-logo{width:34px;height:34px;border-radius:9px}.email{grid-column:2;grid-row:2;font-size:.76rem;line-height:1.25;min-width:0}.client{grid-column:2;grid-row:3;font-size:.68rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.account-row>div:nth-child(4){grid-column:1 / 3;grid-row:4}.status{font-size:.58rem;padding:4px 7px}.date{grid-column:3;grid-row:2;align-self:center;text-align:right;font-size:.64rem;white-space:nowrap}.price{grid-column:3;grid-row:3;align-self:center;text-align:right;font-size:.66rem;white-space:nowrap}.row-actions{grid-column:3;grid-row:1;display:flex;justify-content:flex-end;align-items:start}.row-actions .btn-soft{display:inline-flex;padding:6px 8px;font-size:.64rem}.row-actions .btn-danger,.row-actions form{display:none}.account-row:after{content:'Vencimiento · Precio';grid-column:3;grid-row:4;color:#77708f;font-size:.48rem;text-align:right;white-space:nowrap}.panel{border-radius:16px}.toolbar{padding:11px}.chip{font-size:.62rem;padding:6px 8px}}</style>
</head>
<body>
<div class="manager">
<aside class="side">
  <div class="brand"><div class="brand-logo">★</div><div><b>Academia Star</b><small>Panel de administración</small></div></div>
  <nav class="nav">
    <a href="panel.php"><span class="nicon">⌂</span>Resumen</a>
    <a href="dazn.php" class="active"><span class="nicon">▣</span>Cuentas</a>
    <div class="nav-sub">
      <a class="active" href="dazn.php">Todas <span><?= $total ?></span></a>
      <a href="dazn.php?filter=empty">Disponibles <span><?= $empty ?></span></a>
      <a href="dazn.php?filter=sold">Vendidas <span><?= $sold ?></span></a>
      <a href="dazn.php?filter=pending">Pendientes <span><?= $pending ?></span></a>
    </div>
    <a href="dazn.php#editor"><span class="nicon">＋</span>Nueva cuenta</a>
  </nav>
  <div class="side-bottom"><a href="../public/dashboard.php">← Volver a la app</a><a href="../public/logout.php">Salir</a></div>
</aside>
<main class="main">
  <div class="top"><div><h1>Cuentas</h1></div><div class="top-actions"><a class="btn btn-primary" href="#editor">＋ Nueva cuenta</a></div></div>
  <?php if($message): ?><div class="notice <?= $messageType==='error'?'error':'' ?>"><?= e($message) ?></div><?php endif; ?>
  <section class="panel">
    <div class="toolbar">
      <div class="filters"><button class="chip active" data-filter="all">Todas · <?= $total ?></button><button class="chip" data-filter="empty">Disponibles · <?= $empty ?></button><button class="chip" data-filter="sold">Vendidas · <?= $sold ?></button><button class="chip" data-filter="pending">Pendientes · <?= $pending ?></button></div>
      <div class="search"><input id="saSearch" class="input" placeholder="Buscar email o cliente…"></div>
    </div>
    <div class="list-head"><span>Servicio</span><span>Email</span><span>Cliente</span><span>Estado</span><span>Vencimiento</span><span>Precio</span><span>Acciones</span></div>
    <div id="accountList">
    <?php foreach($accounts as $a): $status=$a['status']??'empty'; $service=$a['service']??'DAZN'; $customA=custom_fields_decode($a['custom_fields']??null); $iconKey=account_icon_key($a); $search=strtolower(($a['email']??'').' '.($a['buyer']??'').' '.($service)); ?>
      <article class="account-row" data-status="<?= e($status) ?>" data-search="<?= e($search) ?>">
        <div class="service-cell"><div class="service-logo"><img src="../public/assets/services/<?= e($iconKey) ?>.svg" alt="<?= e($service) ?>"></div><strong><?= e($service) ?></strong></div>
        <div class="email"><?= e($a['email']) ?></div>
        <div class="client"><?= e($a['buyer'] ?: 'Sin cliente') ?></div>
        <div><span class="status <?= e($status) ?>"><i></i><?= e(account_status_label($status)) ?></span></div>
        <div class="date <?= $status==='pending'?'date-pending':($status==='sold'?'date-sold':'date-empty') ?>"><?= e(nice_date($a['renewal_date'] ?? null)) ?></div>
        <div class="price"><?= $a['price_cents'] !== null ? e(number_format(((int)$a['price_cents'])/100,2,',','.').' €') : '—' ?></div>
        <div class="row-actions"><a class="btn btn-soft btn-small" href="dazn.php?edit=<?= (int)$a['id'] ?>#editor">✎ Editar</a><form method="post" onsubmit="return confirm('¿Eliminar esta cuenta? Esta acción no se puede deshacer.');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-danger btn-small" type="submit">▣ Eliminar</button></form></div>
      </article>
    <?php endforeach; ?>
    </div>
    <div id="emptyState" class="empty-state" style="display:none">No hay cuentas que coincidan.</div>
  </section>

  <section id="editor" class="panel editor">
    <div class="editor-head"><h2><?= $editing ? 'Editar cuenta' : 'Nueva cuenta' ?></h2><?php if($editing): ?><a class="btn btn-soft btn-small" href="dazn.php">Cancelar</a><?php endif; ?></div>
    <div class="editor-body">
      <form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
        <div class="form-grid">
          <div class="field"><label>EMAIL</label><input class="input" type="email" name="email" required value="<?= e($edit['email']??'') ?>" placeholder="cuenta@correo.com"></div>
          <div class="field"><label>CONTRASEÑA</label><div class="pass"><input class="input" id="saPass" type="password" name="password_value" value="<?= e($edit['password_value']??'') ?>" autocomplete="off"><button class="btn btn-soft btn-small" type="button" onclick="togglePass(this)">Mostrar</button></div></div>
          <div class="field"><label>CLIENTE</label><input class="input" name="buyer" value="<?= e($edit['buyer']??'') ?>" placeholder="Nombre del cliente"></div>
          <div class="field"><label>SERVICIO</label><select class="select" name="service" id="serviceSelect"><option value="DAZN" <?= (($edit['service']??'DAZN')==='DAZN')?'selected':'' ?>>DAZN</option><option value="Movistar Plus+" <?= (($edit['service']??'')==='Movistar Plus+')?'selected':'' ?>>Movistar Plus+</option><option value="Netflix" <?= (($edit['service']??'')==='Netflix')?'selected':'' ?>>Netflix</option><option value="HBO Max" <?= (($edit['service']??'')==='HBO Max')?'selected':'' ?>>HBO Max</option><option value="YouTube" <?= (($edit['service']??'')==='YouTube')?'selected':'' ?>>YouTube</option><option value="Prime Video" <?= (($edit['service']??'')==='Prime Video')?'selected':'' ?>>Prime Video</option><option value="Disney+" <?= (($edit['service']??'')==='Disney+')?'selected':'' ?>>Disney+</option><option value="Otro" <?= (($edit['service']??'')==='Otro')?'selected':'' ?>>Otro</option></select></div>
          <div class="field"><label>ICONO</label><div class="icon-picker"><div class="icon-preview"><img id="iconPreview" src="../public/assets/services/<?= e($editIcon==='auto'?service_icon((string)($edit['service']??'DAZN')):$editIcon) ?>.svg" alt=""></div><select class="select" name="icon_key" id="iconSelect"><option value="auto" <?= $editIcon==='auto'?'selected':'' ?>>Automático según servicio</option><option value="dazn" <?= $editIcon==='dazn'?'selected':'' ?>>DAZN</option><option value="movistar" <?= $editIcon==='movistar'?'selected':'' ?>>Movistar</option><option value="netflix" <?= $editIcon==='netflix'?'selected':'' ?>>Netflix</option><option value="max" <?= $editIcon==='max'?'selected':'' ?>>HBO Max</option><option value="youtube" <?= $editIcon==='youtube'?'selected':'' ?>>YouTube</option><option value="prime" <?= $editIcon==='prime'?'selected':'' ?>>Prime Video</option><option value="disney" <?= $editIcon==='disney'?'selected':'' ?>>Disney+</option></select></div></div>
          <div class="field"><label>ESTADO</label><select class="select" name="status"><option value="empty" <?= (($edit['status']??'empty')==='empty')?'selected':'' ?>>Disponible</option><option value="sold" <?= (($edit['status']??'')==='sold')?'selected':'' ?>>Vendida</option><option value="pending" <?= (($edit['status']??'')==='pending')?'selected':'' ?>>Pendiente</option></select></div>
        </div>
        <details class="advanced" <?= $editing?'open':'' ?>><summary>Más datos</summary><div class="advanced-body"><div class="form-grid">
          <div class="field"><label>PRECIO (€)</label><input class="input" type="number" step="0.01" min="0" name="price" value="<?= isset($edit['price_cents'])&&$edit['price_cents']!==null?e(number_format(((int)$edit['price_cents'])/100,2,'.','')):'' ?>"></div>
          <div class="field"><label>RENOVACIÓN</label><input class="input" type="date" name="renewal_date" value="<?= e($edit['renewal_date']??'') ?>"></div>
          <div class="field"><label>COBRO</label><label class="checkline"><input type="checkbox" name="paid" value="1" <?= !empty($edit['paid'])?'checked':'' ?>> Pagada</label></div>
          <div class="field"><label>NOTAS</label><input class="input" name="notes" value="<?= e($edit['notes']??'') ?>"></div>
          <div class="field full"><label>DATOS ADICIONALES</label><div id="customFields" class="custom-list"><?php foreach($custom as $k=>$v): ?><div class="custom-row"><input class="input" name="custom_label[]" value="<?= e($k) ?>" placeholder="Campo"><input class="input" name="custom_value[]" value="<?= e($v) ?>" placeholder="Valor"><button class="btn btn-danger btn-small" type="button" onclick="this.parentElement.remove()">Eliminar</button></div><?php endforeach; ?></div><button class="btn btn-soft btn-small" type="button" onclick="addCustom()">＋ Añadir</button></div>
        </div></div></details>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $editing?'Guardar cambios':'Crear cuenta' ?></button><?php if($editing): ?><a class="btn btn-soft" href="dazn.php">Cancelar</a><?php endif; ?></div>
      </form>
    </div>
  </section>
</main></div>
<script>
function togglePass(btn){const i=document.getElementById('saPass');if(!i)return;i.type=i.type==='password'?'text':'password';btn.textContent=i.type==='password'?'Mostrar':'Ocultar'}
function addCustom(){const d=document.createElement('div');d.className='custom-row';d.innerHTML='<input class="input" name="custom_label[]" placeholder="Campo"><input class="input" name="custom_value[]" placeholder="Valor"><button class="btn btn-danger btn-small" type="button" onclick="this.parentElement.remove()">Eliminar</button>';document.getElementById('customFields').appendChild(d)}
const iconBase='../public/assets/services/';const serviceIcons={'DAZN':'dazn','Movistar Plus+':'movistar','Netflix':'netflix','HBO Max':'max','YouTube':'youtube','Prime Video':'prime','Disney+':'disney','Otro':'generic'};const serviceSelect=document.getElementById('serviceSelect'),iconSelect=document.getElementById('iconSelect'),iconPreview=document.getElementById('iconPreview');
function updateIconPreview(){let key=iconSelect.value==='auto'?(serviceIcons[serviceSelect.value]||'generic'):iconSelect.value;iconPreview.src=iconBase+key+'.svg'}
serviceSelect.addEventListener('change',()=>{if(iconSelect.value==='auto')updateIconPreview()});iconSelect.addEventListener('change',updateIconPreview);updateIconPreview();
async function copyText(text,btn){try{await navigator.clipboard.writeText(text);const old=btn.textContent;btn.textContent='✓';setTimeout(()=>btn.textContent=old,900)}catch(e){}}
const search=document.getElementById('saSearch'),rows=[...document.querySelectorAll('.account-row')],empty=document.getElementById('emptyState');let filter='all';
function applyFilter(){const q=(search.value||'').toLowerCase().trim();let visible=0;rows.forEach(r=>{const ok=filter==='all'||r.dataset.status===filter;const text=!q||r.dataset.search.includes(q);const show=ok&&text;r.style.display=show?'':'none';if(show)visible++});empty.style.display=visible?'none':''}
search.addEventListener('input',applyFilter);document.querySelectorAll('.chip').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('.chip').forEach(x=>x.classList.remove('active'));b.classList.add('active');filter=b.dataset.filter;applyFilter()}));
const urlFilter=new URLSearchParams(location.search).get('filter');if(urlFilter&&['empty','sold','pending'].includes(urlFilter)){const b=document.querySelector('.chip[data-filter="'+urlFilter+'"]');if(b)b.click()}
</script>
</body></html>
