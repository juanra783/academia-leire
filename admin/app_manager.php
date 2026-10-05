<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user=require_admin();
ensure_admin_v524_tables();
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    set_app_setting('app_name', trim((string)($_POST['app_name'] ?? 'Academia Star')) ?: 'Academia Star');
    set_app_setting('current_course', trim((string)($_POST['current_course'] ?? '5º Primaria Andalucía')) ?: '5º Primaria Andalucía');
    set_app_setting('current_course_short', trim((string)($_POST['current_course_short'] ?? '5º Primaria')) ?: '5º Primaria');
    set_app_setting('education_region', trim((string)($_POST['education_region'] ?? 'Andalucía')) ?: 'Andalucía');
    $message='Ajustes generales guardados.';
}
$appName=app_setting('app_name','Academia Star');
$course=app_setting('current_course','5º Primaria Andalucía');
$short=app_setting('current_course_short','5º Primaria');
$region=app_setting('education_region','Andalucía');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Funciones de la app · Academia Star</title><link rel="stylesheet" href="../public/assets/css/style.css?v=5.24"></head><body class="magic-ui admin-page-v524"><div class="admin-shell">
<header class="admin-command-header"><div><span class="pill glow-pill">Configuración</span><h1>Funciones de la app</h1><p class="muted">Accesos rápidos para modificar lo importante sin perderte por la app.</p></div><nav class="admin-top-actions"><a class="btn secondary" href="panel.php">Panel</a><a class="btn secondary" href="../public/dashboard.php">Ver app</a></nav></header>
<?php if($message): ?><div class="notice reward admin-message"><?= e($message) ?></div><?php endif; ?>
<section class="admin-hub-grid manager-grid">
<a class="admin-hub-card hub-students" href="students.php"><b>👧 Alumnos</b><span>Crear, editar perfil, avatar, nivel, XP y saldo.</span></a>
<a class="admin-hub-card hub-subjects" href="subjects.php"><b>🎨 Asignaturas</b><span>Añadir Francés u otras asignaturas, cambiar iconos y orden.</span></a>
<a class="admin-hub-card hub-library" href="library.php"><b>📚 Biblioteca</b><span>Temas por asignatura con desplegable y página individual.</span></a>
<a class="admin-hub-card hub-library" href="topic_edit.php"><b>➕ Añadir tema</b><span>Crear apuntes y contenido de estudio.</span></a>
<a class="admin-hub-card hub-dashboard" href="question_edit.php"><b>❓ Preguntas</b><span>Añadir ejercicios tipo test o escritos.</span></a>
<a class="admin-hub-card hub-money" href="reward_rules.php"><b>💶 Recompensas</b><span>Reglas de dinero por nota y actividad.</span></a>
<a class="admin-hub-card hub-emilia" href="panel.php#emilia"><b>💃 Emilia</b><span>Subir nuevas imágenes para rotación automática.</span></a>
<a class="admin-hub-card hub-app" href="ai_settings.php"><b>🌙 Luna IA</b><span>Configurar IA, límites y comportamiento.</span></a>
<a class="admin-hub-card hub-app" href="scans.php"><b>📷 Escaneos</b><span>Ver fichas y fotos subidas.</span></a>
<a class="admin-hub-card hub-results" href="panel.php#resultados"><b>📊 Seguimiento</b><span>Notas y errores frecuentes.</span></a>
</section>
<section class="card admin-card-blue"><h2>Ajustes generales</h2><form class="form" method="post"><div class="grid small"><label>Nombre de la app<input class="input" name="app_name" value="<?= e($appName) ?>"></label><label>Curso completo<input class="input" name="current_course" value="<?= e($course) ?>"></label><label>Curso corto<input class="input" name="current_course_short" value="<?= e($short) ?>"></label><label>Región educativa<input class="input" name="education_region" value="<?= e($region) ?>"></label></div><button class="btn">Guardar ajustes</button></form></section>
<section class="card admin-card-yellow"><h2>Nota clara</h2><p>Desde aquí puedes tocar casi todo lo que necesitas: alumnos, asignaturas, temas, preguntas, recompensas, imágenes de Emilia, IA, escaneos y seguimiento. Las funciones delicadas siguen separadas en páginas propias para evitar errores.</p></section>
</div></body></html>
