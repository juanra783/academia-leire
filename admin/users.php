<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$admin = require_admin();
$db = db();
$message = '';
$error = '';

function admin_user_csrf(): string {
    if (empty($_SESSION['admin_users_csrf'])) $_SESSION['admin_users_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['admin_users_csrf'];
}
function admin_users_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf'] ?? '');
    if (!hash_equals(admin_user_csrf(), $token)) {
        $error = 'La sesión del formulario ha caducado. Recarga la página e inténtalo de nuevo.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'create') {
                $name = trim((string)($_POST['name'] ?? ''));
                $username = trim((string)($_POST['username'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                $role = (string)($_POST['role'] ?? 'student');
                if ($name === '' || !preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username) || strlen($password) < 8) {
                    throw new RuntimeException('Completa el nombre, usa un usuario de 3 a 80 caracteres (letras, números, punto, guion o guion bajo) y una contraseña de al menos 8 caracteres.');
                }
                if (!in_array($role, ['student','admin'], true)) $role='student';
                $st=$db->prepare('INSERT INTO users(name,username,password_hash,role,active) VALUES(?,?,?,?,1)');
                $st->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role]);
                $message='Usuario creado correctamente.';
            } elseif ($action === 'update') {
                $id=(int)($_POST['user_id'] ?? 0);
                $name=trim((string)($_POST['name'] ?? ''));
                $username=trim((string)($_POST['username'] ?? ''));
                $password=(string)($_POST['password'] ?? '');
                $role=(string)($_POST['role'] ?? 'student');
                $active=!empty($_POST['active'])?1:0;
                if($id<1 || $name==='' || !preg_match('/^[a-zA-Z0-9._-]{3,80}$/',$username)) throw new RuntimeException('Revisa el nombre y el nombre de usuario.');
                if(!in_array($role,['student','admin'],true)) $role='student';
                if($id===(int)$admin['id'] && ($active!==1 || $role!=='admin')) throw new RuntimeException('No puedes desactivar tu propia cuenta ni quitarte el rol de administrador.');
                $st=$db->prepare('SELECT id FROM users WHERE username=? AND id<>? LIMIT 1');$st->execute([$username,$id]);
                if($st->fetch()) throw new RuntimeException('Ese nombre de usuario ya está en uso.');
                if($password!=='') {
                    if(strlen($password)<8) throw new RuntimeException('La nueva contraseña debe tener al menos 8 caracteres.');
                    $st=$db->prepare('UPDATE users SET name=?,username=?,password_hash=?,role=?,active=? WHERE id=?');
                    $st->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role,$active,$id]);
                } else {
                    $st=$db->prepare('UPDATE users SET name=?,username=?,role=?,active=? WHERE id=?');
                    $st->execute([$name,$username,$role,$active,$id]);
                }
                $message='Cambios guardados. La contraseña solo cambia si has escrito una nueva.';
            } else {
                throw new RuntimeException('Acción no reconocida.');
            }
        } catch (PDOException $e) {
            $error = ((string)$e->getCode()==='23000') ? 'Ese nombre de usuario ya existe.' : 'No se pudo guardar el cambio. Revisa los datos y la base de datos.';
        } catch (Throwable $e) { $error=$e->getMessage(); }
    }
}
$users=$db->query('SELECT id,name,username,role,active,created_at FROM users ORDER BY FIELD(role,"admin","student"), name ASC')->fetchAll();
$selectedId=(int)($_GET['id'] ?? 0);
$selected=null;
foreach($users as $u) if((int)$u['id']===$selectedId){$selected=$u;break;}
if(!$selected && $users) $selected=$users[0];
$csrf=admin_user_csrf();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ajustes y usuarios · Academia Star</title><link rel="stylesheet" href="../public/assets/css/style.css?v=6.22">
<style>
body{background:#0c1028}.users-wrap{max-width:1250px;margin:0 auto;padding:26px 18px 60px;color:#f6f4ff}.users-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px}.users-head h1{margin:6px 0}.users-grid{display:grid;grid-template-columns:minmax(270px,.8fr) minmax(320px,1.2fr);gap:18px}.users-card{background:rgba(25,29,65,.94);border:1px solid rgba(180,168,255,.2);border-radius:20px;padding:22px;box-shadow:0 14px 35px rgba(0,0,0,.18)}.users-card h2{margin-top:0}.user-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 10px;border-bottom:1px solid rgba(255,255,255,.08);color:inherit;text-decoration:none;border-radius:10px}.user-row.active{background:rgba(145,112,255,.18)}.user-row small{display:block;color:#aaaacb;margin-top:4px}.status{font-size:11px;border-radius:99px;padding:5px 8px;background:#194a3b;color:#a8f2d1}.status.off{background:#5a2835;color:#ffc3ce}.field-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.field{display:flex;flex-direction:column;gap:7px;font-weight:700;font-size:13px}.field input,.field select{background:#10142e;color:#fff;border:1px solid #45456c;border-radius:11px;padding:12px;font:inherit}.field.full{grid-column:1/-1}.help{font-size:12px;color:#aaaacb;line-height:1.5}.btn-main{background:linear-gradient(135deg,#7c5cff,#bf5bff);color:white;border:0;border-radius:12px;padding:12px 17px;font-weight:800;cursor:pointer;text-decoration:none;display:inline-block}.btn-sub{background:#24294d;color:#fff;border:1px solid #494b78;border-radius:12px;padding:11px 14px;text-decoration:none;font-weight:700}.notice{padding:13px 15px;border-radius:12px;margin:12px 0;background:#173f36;color:#b4f5d6}.notice.error{background:#512a36;color:#ffd1d8}.form-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:17px}@media(max-width:760px){.users-grid{grid-template-columns:1fr}.field-grid{grid-template-columns:1fr}.field.full{grid-column:auto}}
</style></head><body><main class="users-wrap">
<header class="users-head"><div><span class="pill glow-pill">Panel de administración</span><h1>⚙️ Ajustes y usuarios</h1><p style="color:#b5b4d2;margin:0">Crea cuentas, modifica nombres de usuario y restablece contraseñas.</p></div><div style="display:flex;gap:9px;flex-wrap:wrap"><a class="btn-sub" href="panel.php">← Panel</a><a class="btn-sub" href="../public/dashboard.php">Ver app</a></div></header>
<?php if($message): ?><div class="notice"><?= admin_users_h($message) ?></div><?php endif; ?><?php if($error): ?><div class="notice error"><?= admin_users_h($error) ?></div><?php endif; ?>
<div class="users-grid"><section class="users-card"><h2>Usuarios existentes</h2><?php foreach($users as $u): ?><a class="user-row <?= $selected && (int)$selected['id']===(int)$u['id']?'active':'' ?>" href="users.php?id=<?= (int)$u['id'] ?>"><span><strong><?= admin_users_h($u['name']) ?></strong><small>@<?= admin_users_h($u['username']) ?> · <?= $u['role']==='admin'?'Administrador':'Alumno' ?></small></span><span class="status <?= empty($u['active'])?'off':'' ?>"><?= empty($u['active'])?'Inactivo':'Activo' ?></span></a><?php endforeach; ?></section>
<div style="display:grid;gap:18px"><section class="users-card"><h2>Editar cuenta</h2><?php if($selected): ?><form method="post"><input type="hidden" name="csrf" value="<?= admin_users_h($csrf) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="user_id" value="<?= (int)$selected['id'] ?>"><div class="field-grid"><label class="field">Nombre visible<input name="name" required maxlength="120" value="<?= admin_users_h($selected['name']) ?>"></label><label class="field">Nombre de usuario<input name="username" required maxlength="80" pattern="[a-zA-Z0-9._-]{3,80}" value="<?= admin_users_h($selected['username']) ?>"></label><label class="field">Nueva contraseña<input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Dejar vacío para mantenerla"></label><label class="field">Tipo de cuenta<select name="role"><option value="student" <?= $selected['role']==='student'?'selected':'' ?>>Alumno</option><option value="admin" <?= $selected['role']==='admin'?'selected':'' ?>>Administrador</option></select></label><label class="field full"><span><input type="checkbox" name="active" value="1" <?= !empty($selected['active'])?'checked':'' ?>> Cuenta activa (puede iniciar sesión)</span></label></div><p class="help">Por seguridad, las contraseñas nunca se muestran. Para cambiarla, escribe una nueva; se guardará cifrada.</p><div class="form-actions"><button class="btn-main" type="submit">Guardar cambios</button></div></form><?php else: ?><p>No hay usuarios registrados.</p><?php endif; ?></section>
<section class="users-card"><h2>➕ Crear cuenta</h2><form method="post"><input type="hidden" name="csrf" value="<?= admin_users_h($csrf) ?>"><input type="hidden" name="action" value="create"><div class="field-grid"><label class="field">Nombre visible<input name="name" required maxlength="120" placeholder="Ej. Leire"></label><label class="field">Nombre de usuario<input name="username" required minlength="3" maxlength="80" pattern="[a-zA-Z0-9._-]{3,80}" placeholder="ej. leire2"></label><label class="field">Contraseña inicial<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><label class="field">Tipo de cuenta<select name="role"><option value="student">Alumno</option><option value="admin">Administrador</option></select></label></div><p class="help">Usa una contraseña de al menos 8 caracteres. El sistema guarda un hash seguro, no la contraseña en texto claro.</p><div class="form-actions"><button class="btn-main" type="submit">Crear usuario</button></div></form></section></div></div>
</main></body></html>
