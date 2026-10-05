<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_admin();
ensure_admin_v524_tables();
$message='';
$selectedId=(int)($_GET['id'] ?? $_POST['student_id'] ?? admin_default_student_id());
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action'] ?? '';
    if ($action==='create_student') {
        $message=admin_create_student((string)($_POST['name'] ?? ''),(string)($_POST['username'] ?? ''),(string)($_POST['password'] ?? ''));
        $selectedId=admin_default_student_id();
    } elseif ($action==='update_student') {
        $selectedId=(int)($_POST['student_id'] ?? 0);
        $profile=['level'=>$_POST['level'] ?? 1,'xp'=>$_POST['xp'] ?? 0,'coins'=>$_POST['coins'] ?? 0,'daily_streak'=>$_POST['daily_streak'] ?? 0,'weekly_goal'=>$_POST['weekly_goal'] ?? 5];
        $message=admin_update_student($selectedId,(string)($_POST['name'] ?? ''),(string)($_POST['username'] ?? ''),(string)($_POST['password'] ?? ''),!empty($_POST['active']),$profile);
        if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $message .= ' ' . save_profile_avatar_upload($selectedId, $_FILES['avatar']);
        }
    } elseif ($action==='delete_student') {
        $selectedId=(int)($_POST['student_id'] ?? 0);
        $message=admin_delete_student($selectedId,(string)($_POST['admin_pin'] ?? ''));
        $selectedId=admin_default_student_id();
    }
}
$students=admin_students();
if (!$selectedId && $students) $selectedId=(int)$students[0]['id'];
$student=admin_student_by_id($selectedId);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Alumnos · Academia Star</title><link rel="stylesheet" href="../public/assets/css/style.css?v=5.24"></head><body class="magic-ui admin-page-v524"><div class="admin-shell">
<header class="admin-command-header"><div><span class="pill glow-pill">Panel Admin</span><h1>Alumnos</h1><p class="muted">Añade alumnos y cambia cualquier dato de su perfil: usuario, contraseña, avatar, nivel, XP, saldo, racha y objetivo semanal.</p></div><nav class="admin-top-actions"><a class="btn secondary" href="panel.php">Panel</a><a class="btn secondary" href="../public/dashboard.php">Ver app</a></nav></header>
<?php if($message): ?><div class="notice reward admin-message"><?= e($message) ?></div><?php endif; ?>
<div class="admin-two-cols wide-left">
<section class="card admin-card-blue"><h2>Lista de alumnos</h2><?php if(!$students): ?><p class="muted">Todavía no hay alumnos.</p><?php else: ?><div class="student-list-admin"><?php foreach($students as $s): ?><a class="student-row-admin <?= (int)$s['id']===$selectedId?'active':'' ?>" href="students.php?id=<?= (int)$s['id'] ?>"><span class="student-avatar-mini"><?php if(!empty($s['avatar'])): ?><img src="../public/<?= e($s['avatar']) ?>" alt="<?= e($s['name']) ?>"><?php else: ?>👧<?php endif; ?></span><b><?= e($s['name']) ?></b><small><?= e($s['username']) ?> · <?= (int)$s['results_count'] ?> actividades · <?= (int)$s['level'] ?> nivel</small></a><?php endforeach; ?></div><?php endif; ?></section>
<section class="card admin-card-green"><h2>Añadir nuevo alumno</h2><form class="form" method="post"><input type="hidden" name="action" value="create_student"><label>Nombre<input class="input" name="name" placeholder="Ejemplo: Leire" required></label><label>Usuario<input class="input" name="username" placeholder="ejemplo: leire" required></label><label>Contraseña<input class="input" type="password" name="password" placeholder="Contraseña inicial" required></label><button class="btn">Crear alumno</button></form></section>
</div>
<?php if($student): ?><section class="card admin-card-purple"><h2>Editar perfil de <?= e($student['name']) ?></h2><form class="form" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="update_student"><input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>"><div class="grid small"><label>Nombre<input class="input" name="name" value="<?= e($student['name']) ?>" required></label><label>Usuario<input class="input" name="username" value="<?= e($student['username']) ?>" required></label><label>Nueva contraseña <span class="muted">opcional</span><input class="input" type="password" name="password" placeholder="Dejar vacío para no cambiar"></label><label>Avatar<input class="input" type="file" name="avatar" accept="image/*"></label><label>Nivel<input class="input" type="number" name="level" min="1" value="<?= (int)($student['level'] ?? 1) ?>"></label><label>XP<input class="input" type="number" name="xp" min="0" value="<?= (int)($student['xp'] ?? 0) ?>"></label><label>Saldo en céntimos<input class="input" type="number" name="coins" min="0" value="<?= (int)($student['coins'] ?? 0) ?>"></label><label>Racha días<input class="input" type="number" name="daily_streak" min="0" value="<?= (int)($student['daily_streak'] ?? 0) ?>"></label><label>Objetivo semanal<input class="input" type="number" name="weekly_goal" min="1" value="<?= (int)($student['weekly_goal'] ?? 5) ?>"></label><label class="checkbox-line"><input type="checkbox" name="active" value="1" <?= (int)$student['active']===1?'checked':'' ?>> Alumno activo</label></div><button class="btn">Guardar cambios</button><a class="btn secondary" href="panel.php?student_id=<?= (int)$student['id'] ?>">Administrar este alumno</a></form></section>
<section class="card admin-card-red"><h2>Zona peligrosa</h2><p class="muted">Eliminar un alumno borra sus resultados, recompensas y perfil por cascada. Úsalo solo si es una prueba.</p><form class="inline-form" method="post" onsubmit="return confirm('¿Eliminar este alumno definitivamente?')"><input type="hidden" name="action" value="delete_student"><input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>"><input class="input pin-input" type="password" name="admin_pin" placeholder="PIN 2283" autocomplete="off"><button class="btn danger">Eliminar alumno</button></form></section><?php endif; ?>
</div></body></html>
