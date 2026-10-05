<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$profile = ensure_student_profile((int)$user['id']);

$subjects = [
 'lengua' => ['name'=>'Lengua','icon'=>'📖','description'=>'Lenguaje, lengua, dialectos, acentuación y comprensión lectora.','color'=>'#7558e8'],
 'matematicas' => ['name'=>'Matemáticas','icon'=>'🔢','description'=>'Multiplicación, expresiones, potencias, cuadrados, cubos y problemas.','color'=>'#2f8fe5']
];
$subject = $_GET['subject'] ?? '';
if (!isset($subjects[$subject])) { header('Location: nuevo_curso.php'); exit; }
$s = $subjects[$subject];
$topics=[];
try {
 $db=db();
 $q=$db->prepare("SELECT topic_key, MIN(id) first_id, COUNT(*) task_count, SUM(duration_minutes) total_minutes, SUM(xp) total_xp
                  FROM study_tasks WHERE active=1 AND subject_key=? GROUP BY topic_key ORDER BY first_id ASC");
 $q->execute([$subject]);
 $topics=$q->fetchAll();
 foreach($topics as &$t){
   $q2=$db->prepare("SELECT title,description FROM study_tasks WHERE id=? LIMIT 1"); $q2->execute([(int)$t['first_id']]); $meta=$q2->fetch();
   $t['title']=$meta['title']??'Tema'; $t['description']=$meta['description']??''; $t['study_task_id']=(int)$t['first_id'];
 }
 unset($t);
} catch(Throwable $e){$topics=[];}
function topic_num($i){return $i+1;}
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#7558e8">
<title><?= e($s['name']) ?> · Academia Star</title><link rel="stylesheet" href="assets/nuevo_curso/app.css?v=619a">
<style>
:root{--ink:#25243a;--muted:#77758c;--line:#ebe8f5;--shadow:0 14px 35px rgba(45,35,95,.08)}.course-only .sidebar{display:flex}.course-only .main{margin-left:260px}.page-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:26px 0 20px}.back{display:inline-flex;text-decoration:none;color:#5d5872;font-weight:800;background:#fff;border:1px solid var(--line);padding:10px 14px;border-radius:12px}.subject-banner{background:linear-gradient(135deg,<?= e($s['color']) ?>,#a15de8);color:#fff;border-radius:28px;padding:30px;display:flex;align-items:center;justify-content:space-between;gap:20px;box-shadow:0 18px 40px rgba(60,40,150,.16)}.subject-banner .big-icon{width:72px;height:72px;background:rgba(255,255,255,.18);border-radius:22px;display:grid;place-items:center;font-size:38px}.subject-banner h2{margin:0;font-size:32px}.subject-banner p{margin:8px 0 0;opacity:.9;max-width:720px}.topic-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:20px}.topic-card{position:relative;background:#fff;border:1px solid var(--line);border-radius:24px;padding:22px;text-decoration:none;color:var(--ink);box-shadow:var(--shadow);display:flex;gap:16px;align-items:flex-start}.topic-number{width:52px;height:52px;border-radius:16px;background:<?= e($s['color']) ?>;color:#fff;display:grid;place-items:center;font-weight:900;font-size:18px;flex:none}.topic-card h3{margin:0;font-size:19px}.study-hub{margin-top:22px;padding:25px;border-radius:24px;background:linear-gradient(135deg,#122448,#253f73);color:#fff;box-shadow:0 18px 40px #10254b22}.study-hub h2{margin:0 0 8px;font-size:25px}.study-hub p{margin:0;color:#dbeafe;line-height:1.6}.study-hub .study-start{display:inline-flex;margin-top:16px;padding:13px 18px;border-radius:13px;background:#facc15;color:#172554;text-decoration:none;font-weight:900}.math-learning-guide{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:16px}.guide-card{background:#fff;border:1px solid #dbe7f7;border-radius:18px;padding:16px}.guide-card strong{display:block;margin-bottom:7px;color:#1d4ed8}.guide-card p{margin:0;color:#475569;font-size:13px;line-height:1.55}.topic-card .study-topic{display:inline-flex;margin-top:10px;padding:8px 12px;background:#2563eb;color:#fff;border-radius:10px;font-weight:900;font-size:12px}.topic-card p{margin:6px 0;color:var(--muted);font-size:13px;line-height:1.45}.topic-meta{display:flex;gap:7px;flex-wrap:wrap}.tag{display:inline-flex;padding:6px 9px;border-radius:999px;background:#f4f1fb;color:#5d5872;font-size:11px;font-weight:800}.topic-arrow{margin-left:auto;font-size:22px;color:<?= e($s['color']) ?>;align-self:center}.empty{background:#fff;border:1px solid var(--line);border-radius:22px;padding:25px;margin-top:20px}@media(max-width:760px){.math-learning-guide{grid-template-columns:1fr}.course-only .main{margin-left:0}.course-only .sidebar{transform:translateX(-100%)}.course-only .sidebar.open{transform:translateX(0)}.topic-grid{grid-template-columns:1fr}.subject-banner{padding:23px}.subject-banner h2{font-size:26px}.page-head{align-items:flex-start;flex-direction:column}}
</style></head><body class="course-only"><div class="app-shell">
<aside class="sidebar"><div class="brand"><div class="brand-mark">L</div><div><strong>Academia</strong><span>Nuevo curso</span></div></div><nav>
<a class="nav-btn" href="nuevo_curso.php"><span>🏠</span> Inicio</a><a class="nav-btn <?= $subject==='lengua'?'active':'' ?>" href="asignatura.php?subject=lengua"><span>📖</span> Lengua</a><a class="nav-btn <?= $subject==='matematicas'?'active':'' ?>" href="asignatura.php?subject=matematicas"><span>🔢</span> Matemáticas</a><a class="nav-btn" href="tasks.php"><span>📝</span> Tareas</a><a class="nav-btn" href="profile.php"><span>👧</span> Perfil</a></nav>
<a class="parent-btn" href="nuevo_curso.php">⌂ Nuevo curso</a><?php if(($user['role']??'')==='admin'): ?><a class="parent-btn" href="../admin/panel.php">🔒 Panel de Juanra</a><?php endif; ?></aside>
<main class="main"><header class="topbar"><button class="menu-btn" id="menuBtn" type="button">☰</button><div class="greeting"><span class="eyebrow">CURSO 2026/27 · ASIGNATURA</span><h1><?= e($s['icon']) ?> <?= e($s['name']) ?></h1></div><div class="stats-mini"><span>🔥 <b><?= (int)$profile['daily_streak'] ?></b></span><span>⭐ <b><?= (int)$profile['xp'] ?></b></span></div></header>
<div class="page-head"><div><span class="eyebrow">TEMARIO</span><h2 style="margin:4px 0 0"><?= $subject==='matematicas' ? 'Estudiar Matemáticas' : 'Elige un tema' ?></h2></div><a class="back" href="nuevo_curso.php">← Asignaturas</a></div>
<section class="subject-banner"><div><h2><?= e($s['name']) ?></h2><p><?= e($s['description']) ?> Aquí tienes los contenidos ordenados por temas.</p></div><div class="big-icon"><?= $s['icon'] ?></div></section>
<?php if($subject==='matematicas'): ?>
<section class="study-hub"><h2>🧠 Estudiar · Todas las unidades</h2><p>Empieza por la primera unidad y avanza en orden. En cada tema encontrarás explicaciones paso a paso, ejemplos resueltos y ejercicios. Distingue siempre la pregunta, la respuesta y el motivo de la solución.</p><?php if($topics): ?><a class="study-start" href="tema.php?subject=matematicas&topic=<?= urlencode($topics[0]['topic_key']) ?>">▶ Empezar a estudiar</a><?php endif; ?></section>
<div class="math-learning-guide"><article class="guide-card"><strong>📘 1. EXPLICACIÓN</strong><p>Lee la regla y comprende qué significa antes de memorizarla.</p></article><article class="guide-card"><strong>🟦 2. EJEMPLO RESUELTO</strong><p>Observa cada paso y por qué se hace en ese orden.</p></article><article class="guide-card"><strong>✏️ 3. PRACTICA</strong><p>Resuelve sin mirar y revisa la explicación si te equivocas.</p></article></div>
<?php endif; ?>
<?php if(!$topics): ?><div class="empty"><h3>Aún no hay temas</h3><p>No hay contenidos activos instalados para esta asignatura.</p></div><?php else: ?><div class="topic-grid">
<?php foreach($topics as $i=>$t): ?><a class="topic-card" href="tema.php?subject=<?= e($subject) ?>&topic=<?= urlencode($t['topic_key']) ?>"><div class="topic-number">T<?= topic_num($i) ?></div><div style="min-width:0"><h3>Tema <?= topic_num($i) ?> · <?= e($t['title']) ?></h3><p><?= e($t['description']) ?></p><?php if($subject==='matematicas'): ?><a class="study-topic" href="tema.php?subject=matematicas&topic=<?= urlencode($t['topic_key']) ?>">Estudiar esta unidad →</a><?php endif; ?><div class="topic-meta"><span class="tag">📚 <?= (int)$t['task_count'] ?> contenido<?= (int)$t['task_count']===1?'':'s' ?></span><span class="tag">⏱ <?= (int)$t['total_minutes'] ?> min</span><span class="tag">⭐ +<?= (int)$t['total_xp'] ?> XP</span></div></div><span class="topic-arrow">→</span></a><?php endforeach; ?></div><?php endif; ?>
</main></div><script>const m=document.getElementById('menuBtn'),sbar=document.querySelector('.sidebar');if(m)m.onclick=()=>sbar.classList.toggle('open');document.querySelectorAll('.sidebar a').forEach(x=>x.addEventListener('click',()=>sbar.classList.remove('open')));</script></body></html>
