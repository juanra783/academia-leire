<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
if (($user['role'] ?? 'student') !== 'admin') { header('Location: nuevo_curso.php'); exit; }
ensure_v44_tables();
$dailyReward = claim_daily_reward((int)$user['id']);
$profile = ensure_student_profile((int)$user['id']);
$equipped = equipped_rewards((int)$user['id']);
$tasks = ensure_daily_tasks((int)$user['id']);
$dailyPercent = daily_plan_percent($tasks);
$xpNeed = xp_for_next_level((int)$profile['level']);
$xpPercent = progress_percent($profile);

$subjects = db()->query('
    SELECT t.subject, COUNT(DISTINCT t.id) total_topics,
           COUNT(DISTINCT q.id) total_questions,
           ROUND(AVG(r.score),2) avg_score
    FROM topics t
    LEFT JOIN questions q ON q.topic_id=t.id
    LEFT JOIN results r ON r.topic_id=t.id AND r.user_id='.(int)$user['id'].'
    GROUP BY t.subject
    ORDER BY FIELD(t.subject,"Matemáticas","Lengua","Conocimiento del Medio","Inglés")
')->fetchAll();

$stmt = db()->prepare('SELECT r.*, t.title topic_title, t.subject FROM results r JOIN topics t ON t.id=r.topic_id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 8');
$stmt->execute([$user['id']]);
$results = $stmt->fetchAll();

$weakTopics = weak_topics_for_user((int)$user['id'], 4);
$mastered = topic_mastery_for_user((int)$user['id'], '>=');

$ach = db()->prepare('SELECT * FROM achievements WHERE user_id=? ORDER BY created_at DESC LIMIT 6');
$ach->execute([$user['id']]);
$achievements = $ach->fetchAll();

$week = db()->prepare('SELECT COUNT(*) c FROM results WHERE user_id=? AND YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)');
$week->execute([$user['id']]);
$doneWeek = (int)$week->fetch()['c'];
$weekGoal = max(1, (int)($profile['weekly_goal'] ?? 5));
$weekPercent = min(100, (int)round($doneWeek / $weekGoal * 100));
$recentTopics = recent_topics_for_user((int)$user['id'], 3);
$focusTopics = current_focus_topics((int)$user['id'], 3);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Academia Star 6.3</title><link rel="manifest" href="manifest.webmanifest?v=6"><link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=58"><link rel="icon" href="favicon.ico?v=58"><link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=58"><meta name="theme-color" content="#ff4fb3"><link rel="stylesheet" href="assets/css/style.css?v=63"></head>
<body class="dashboard-pro"><div class="wrap <?= !empty($equipped['fondo']) ? 'has-fondo fondo-'.e($equipped['fondo']['item_key']) : '' ?>">
<header class="topbar"><div class="brand">✦ Academia Star <span class="pill v6-pill">6.3</span></div><nav class="nav"><?php if($user['role']==='admin'): ?><a class="secondary" href="../admin/panel.php">Panel</a><?php endif; ?><a class="secondary" href="nuevo_curso.php">Inicio</a><a class="secondary" href="nuevo_curso.php">Nuevo curso</a><a class="secondary" href="tasks.php">Tareas</a><a class="secondary" href="repaso.php">Repasar</a><a class="secondary" href="crazy_exam.php">Examen Loco</a><a class="secondary" href="trainer.php">Plan</a><a class="secondary" href="teacher.php?back=dashboard.php">Luna</a><a class="secondary" href="profile.php">Perfil</a></nav></header>

<section class="kid-hero">
  <div class="avatar-card <?= !empty($equipped['marco']) ? 'equipped-frame '.$equipped['marco']['item_key'] : '' ?>">
    <img class="avatar" src="<?= e($profile['avatar'] ?: 'assets/img/avatar-leire-main.jpg') ?>" alt="Avatar de Leire">
    <div class="level-badge">Nivel <?= (int)$profile['level'] ?></div>
    <div class="xpbar"><span style="width:<?= $xpPercent ?>%"></span></div>
    <p class="muted">XP <?= (int)$profile['xp'] ?>/<?= $xpNeed ?></p>
    <?php if(!empty($equipped['pegatina'])): ?><div class="sticker-equipped"><?= e($equipped['pegatina']['icon']) ?></div><?php endif; ?>
  </div>
  <div class="card hero-main">
    <p class="pill">4º Primaria · Andalucía</p>
    <h1>Hola, <?= e($user['name']) ?> 👋</h1>
    <p class="muted">Hoy la app centra el plan en los últimos temas que Leire ha estado trabajando. Así no salta de una cosa a otra: primero lo más reciente, luego el reto con Emilia y después el mini examen o repaso que toque.</p>
    <div class="stats compact"><div class="mini"><b>💶 <?= e(format_cents((int)$profile['coins'])) ?></b><span>Saldo</span></div><div class="mini"><b>🔥 <?= (int)$profile['daily_streak'] ?></b><span>Racha</span></div><div class="mini"><b><?= $doneWeek ?>/<?= $weekGoal ?></b><span>Objetivo semanal</span></div><div class="mini"><b><?= $dailyPercent ?>%</b><span>Plan de hoy</span></div></div>
    <div class="goal"><span style="width:<?= $weekPercent ?>%"></span></div>
    <?php if($dailyReward): ?><div class="notice reward">🎁 Recompensa diaria: +<?= (int)$dailyReward['xp'] ?> XP y <?= e(reward_text((int)$dailyReward['coins'])) ?></div><?php endif; ?>
    <p><a class="btn" href="trainer.php">Ver plan de hoy</a> <a class="btn secondary" href="wallet.php">Retirar saldo</a> <a class="btn secondary" href="profile.php">Cambiar foto</a> <a class="btn secondary" href="scan.php">Escanear ficha</a> <a class="btn secondary" href="battle.php">Reto con Emilia</a> <a class="btn secondary" href="shop.php">Tienda</a></p>
  </div>
</section>



<section class="dashboard-feature"><div><h2>⚡ Tu reto de hoy</h2><p>Continúa tu plan o entra directamente en Examen Loco para poner a prueba tu racha.</p><div class="v6-hero-actions"><a class="btn" href="trainer.php">Continuar plan</a><a class="btn secondary" href="crazy_exam.php">Examen Loco</a></div></div><div class="feature-icon">✦</div></section>

<section class="card subjects-top"><div class="section-title"><h2>📚 Asignaturas</h2><p>Elige y continúa</p></div><p class="muted">Elige primero la asignatura para ver sus temas, estudiar, hacer deberes o preparar examen.</p><div class="grid">
<?php foreach($subjects as $s): $score=$s['avg_score'] !== null ? (float)$s['avg_score'] : null; $pct=$score===null?0:min(100,(int)round($score*10)); ?>
<a class="subject progress <?= e(level_class($score)) ?>" href="topics.php?subject=<?= urlencode($s['subject']) ?>">
  <strong><?= subject_icon($s['subject']) ?> <?= e($s['subject']) ?></strong>
  <span class="muted"><?= (int)$s['total_topics'] ?> temas · <?= (int)$s['total_questions'] ?> preguntas</span>
  <span class="pill"><?= e(level_from_score($score)) ?><?= $score !== null ? ' · '.e((string)$score).'/10' : '' ?></span>
  <div class="mini-bar"><span style="width:<?= $pct ?>%"></span></div>
</a>
<?php endforeach; ?>
</div></section>


<section class="card recent-topics"><div class="section-title"><h2>⚡ Continuar</h2><p>Últimos temas</p></div><p class="muted">Acceso rápido a los 3 últimos temas estudiados, deberes, repasos o exámenes.</p>
<?php if(!$recentTopics): ?>
  <p class="muted">Aún no hay temas recientes. Cuando estudie o haga deberes aparecerán aquí.</p>
<?php else: ?>
  <div class="grid small">
  <?php foreach($recentTopics as $rt): $tid=(int)$rt['topic_id']; ?>
    <div class="subject recent-card">
      <strong><?= subject_icon($rt['subject']) ?> <?= e($rt['title']) ?></strong>
      <span class="muted"><?= e($rt['subject']) ?> · Último acceso: <?= e($rt['action_label']) ?></span>
      <p class="quick-actions">
        <a class="btn" href="<?= e($rt['target_url']) ?>">Continuar</a>
        <a class="btn secondary" href="study.php?id=<?= $tid ?>">Estudiar</a>
        <a class="btn secondary" href="exam.php?topic_id=<?= $tid ?>&mode=deberes">Deberes</a>
        <a class="btn secondary" href="exam.php?topic_id=<?= $tid ?>&mode=examen">Examen</a>
      </p>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
</section>






<section class="card"><h2>🎯 Temas en foco ahora</h2><p class="muted">El plan diario inteligente y los retos con Emilia se apoyan sobre los últimos temas que Leire ha ido tocando.</p><?php if(!$focusTopics): ?><p class="muted">Todavía no hay temas en foco. En cuanto estudie o haga deberes, aparecerán aquí.</p><?php else: ?><div class="grid small"><?php foreach($focusTopics as $ft): ?><div class="subject"><strong><?= subject_icon($ft['subject']) ?> <?= e($ft['title']) ?></strong><span class="muted"><?= e($ft['subject']) ?></span><p class="quick-actions"><a class="btn secondary" href="study.php?id=<?= (int)$ft['id'] ?>">Estudiar</a><a class="btn secondary" href="exam.php?topic_id=<?= (int)$ft['id'] ?>&mode=deberes">Deberes</a><a class="btn secondary" href="exam.php?topic_id=<?= (int)$ft['id'] ?>&mode=examen">Examen</a></p></div><?php endforeach; ?></div><?php endif; ?></section>

<section class="card scan-card"><h2>📸 Escanear y practicar</h2><p class="muted">Sube una foto del libro o de una ficha para generar explicación, ejercicios y mini examen sobre ese contenido.</p><p><a class="btn" href="scan.php">Hacer foto o subir ficha</a></p></section>
<section class="card emilia-bubble"><h2>✨ Emilia te acompaña</h2><p class="muted">Compra recompensas con saldo en € y personaliza a Emilia con gafas, sombreros, ropa y extras. Lo equipado ahora: <?= e(emilia_equipped_text(emilia_equipped_for_user((int)$user['id']))) ?>.</p><p><a class="btn secondary" href="mascot.php">Ver Emilia</a> <a class="btn secondary" href="shop.php">Abrir tienda</a></p></section>
<section class="card"><h2>🧠 Plan diario inteligente</h2><div class="grid small">
<?php foreach($tasks as $task): ?>
  <div class="subject task-card <?= !empty($task['completed'])?'done':'' ?>"><strong><?= !empty($task['completed'])?'✅':'🎯' ?> <?= e($task['title']) ?></strong><span class="muted"><?= e($task['subject'] ?: 'General') ?> · +<?= (int)$task['xp_reward'] ?> XP · recompensa en € según nota</span><?php if(empty($task['completed'])): ?><p><a class="btn secondary" href="<?= e($task['action_url']) ?>">Hacer ahora</a></p><?php else: ?><p><span class="pill equipped">Completado</span></p><?php endif; ?></div>
<?php endforeach; ?>
</div></section>

<section class="split">
  <div class="card"><h2>✅ Temas dominados</h2><?php if(!$mastered): ?><p class="muted">Aún no hay temas dominados. En cuanto saque buena media aparecerán aquí.</p><?php else: ?><div class="grid small"><?php foreach($mastered as $m): ?><div class="subject"><strong><?= e($m['title']) ?></strong><span class="muted"><?= e($m['subject']) ?> · media <?= e((string)$m['avg_score']) ?>/10</span></div><?php endforeach; ?></div><?php endif; ?></div>
  <div class="card"><h2>⚠️ A reforzar</h2><?php if(!$weakTopics): ?><p class="ok">No hay temas flojos registrados todavía.</p><?php else: ?><div class="grid small"><?php foreach($weakTopics as $w): ?><a class="subject" href="exam.php?topic_id=<?= (int)$w['id'] ?>&mode=repaso"><strong><?= e($w['title']) ?></strong><span class="muted"><?= e($w['subject']) ?><?= $w['avg_score']!==null ? ' · media '.e((string)$w['avg_score']).'/10' : '' ?></span><span class="pill">Repasar</span></a><?php endforeach; ?></div><?php endif; ?></div>
</section>

<?php if($achievements): ?><section class="card"><h2>🏆 Logros recientes</h2><div class="grid small"><?php foreach($achievements as $a): ?><div class="subject badge-card"><strong><?= e($a['icon']) ?> <?= e($a['title']) ?></strong><span class="muted"><?= e($a['description']) ?></span></div><?php endforeach; ?></div></section><?php endif; ?>

<section class="card"><h2>📊 Últimos resultados</h2><?php if(!$results): ?><p class="muted">Todavía no hay resultados.</p><?php else: ?><table class="table"><tr><th>Fecha</th><th>Tema</th><th>Nota</th><th>Resultado</th><th>Ver</th></tr><?php foreach($results as $r): ?><tr><td><?= e(date("d/m", strtotime($r['created_at']))) ?></td><td><?= e($r['subject']) ?> · <?= e($r['topic_title']) ?></td><td><b><?= e((string)$r['score']) ?>/10</b></td><td><?= e(score_label((float)$r['score'])) ?></td><td><a class="btn secondary small" href="result.php?id=<?= (int)$r['id'] ?>">Ver ejercicio</a></td></tr><?php endforeach; ?></table><?php endif; ?></section>
<footer class="footer">Academia Star 6.3 · Nuevo diseño, centro de repaso y Examen Loco profesional.</footer>
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('sw.js?v=6').catch(()=>{});}</script>
<?= luna_floating_button() ?>
</div></body></html>
