<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
$profile = ensure_student_profile((int)$user['id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#7558e8">
  <title>Academia Star 6.3</title>
  <link rel="manifest" href="manifest.webmanifest?v=6">
  <link rel="stylesheet" href="assets/nuevo_curso/app.css?v=6.1">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand"><div class="brand-mark">L</div><div><strong>Academia</strong><span>Leire 6.0</span></div></div>
    <nav id="nav">
      <button class="nav-btn active" data-page="inicio"><span>🏠</span> Inicio</button>
      <button class="nav-btn" data-page="aprender"><span>📚</span> Aprender</button>
      <a class="nav-btn" href="ingles.php"><span>🇬🇧</span> Inglés</a>
      <button class="nav-btn" data-page="repasar"><span>🧠</span> Repasar</button>
      <button class="nav-btn" data-page="aventuras"><span>🏆</span> Aventuras</button>
      <button class="nav-btn" data-page="perfil"><span>👧</span> Perfil</button>
    </nav>
    <a class="parent-btn" href="nuevo_curso.php">⌂ Curso principal</a><button class="parent-btn" id="parentBtn">🔒 Panel de Juanra</button>
  </aside>

  <main class="main">
    <header class="topbar">
      <button class="menu-btn" id="menuBtn">☰</button>
      <div class="greeting"><span class="eyebrow">Viernes, 31 de julio</span><h1 id="pageTitle">Hola, <?= e($user['name']) ?></h1></div>
      <div class="stats-mini">
        <span>🔥 <b id="streakTop"><?= (int)$profile['daily_streak'] ?></b></span><span>⭐ <b id="xpTop"><?= (int)$profile['xp'] ?></b></span><span>€ <b id="moneyTop"><?= e(number_format(((int)$profile['coins'])/100,2,',','.')) ?></b></span>
      </div>
    </header>

    <section class="page active" id="page-inicio">
      <div class="hero-card">
        <div class="hero-copy"><span class="pill">NUEVO CURSO</span><h2>Tu nueva aventura empieza aquí</h2><p>Repasa lo aprendido, descubre el nuevo curso y avanza a tu ritmo.</p><button class="primary" data-go="aprender">Comenzar el curso</button></div>
        <div class="mascot"><div class="mascot-face">😊</div><div class="speech">¡Hoy tenemos una misión genial!</div></div>
      </div>

      <div class="section-head"><div><span class="eyebrow">PLAN INTELIGENTE</span><h2>Tu misión de hoy</h2></div><span class="time-chip">⏱ 15 min</span></div>
      <div class="mission-card">
        <div class="mission-progress"><div class="ring" style="--p:34"><span>1/3</span></div></div>
        <div class="mission-list">
          <label class="mission done"><input type="checkbox" checked><span class="check">✓</span><span><b>Repaso rápido</b><small>Matemáticas · Multiplicaciones</small></span><em>+20 XP</em></label>
          <label class="mission"><input type="checkbox"><span class="check">✓</span><span><b>Deberes de Lengua</b><small>Comprensión lectora</small></span><em>+40 XP</em></label>
          <label class="mission"><input type="checkbox"><span class="check">✓</span><span><b>Descubrir tema nuevo</b><small>Conocimiento · El cuerpo humano</small></span><em>+30 XP</em></label>
        </div>
      </div>

      <div class="section-head"><div><span class="eyebrow">TODO EL CURSO EN UN SOLO LUGAR</span><h2>Tu centro de aprendizaje</h2></div></div>
      <div class="subjects-grid central-tools">
        <a class="subject-card" href="adventure.php"><div class="subject-icon">👧</div><h3>Emilia</h3><p>Aprende y practica con Emilia</p></a>
        <a class="subject-card" href="crazy_exam.php"><div class="subject-icon">🤯</div><h3>Examen Loco</h3><p>Retos y rachas sin fallar</p></a>
        <a class="subject-card" href="tasks.php"><div class="subject-icon">📝</div><h3>Mis tareas</h3><p>Deberes y actividades asignadas</p></a>
        <a class="subject-card" href="repaso.php"><div class="subject-icon">🧠</div><h3>Repasar</h3><p>Refuerza lo que ya has aprendido</p></a>
      </div>

      <div class="section-head"><div><span class="eyebrow">TODO EN SU SITIO</span><h2>Mis asignaturas</h2></div><button class="link-btn" data-go="aprender">Ver todas →</button></div>
      <a class="subject-card" href="ingles.php" style="display:block;text-decoration:none;color:inherit;margin:12px 0"><div class="subject-icon">🇬🇧</div><h3>Inglés</h3><p>Days of the week · Numbers 1–100 · Escribir y pronunciar</p><span class="pill">Preparar el examen →</span></a>
      <div class="subjects-grid" id="homeSubjects"></div>

      <div class="two-col">
        <div class="panel"><div class="section-head compact"><h2>Seguir donde lo dejaste</h2></div><div class="continue-card"><div class="subject-icon math">📐</div><div><span>Matemáticas</span><b>Divisiones por dos cifras</b><div class="progress"><i style="width:68%"></i></div></div><button class="round">▶</button></div></div>
        <div class="panel weekly"><div><span class="eyebrow">OBJETIVO SEMANAL</span><h2>4 de 5 días</h2><p>Te falta solo un día para conseguir el cofre.</p></div><div class="week"><span class="on">L</span><span class="on">M</span><span class="on">X</span><span class="on">J</span><span>V</span><span>S</span><span>D</span></div></div>
      </div>
    </section>

    <section class="page" id="page-aprender">
      <div class="page-intro"><div><span class="pill">CURSO 2026/27</span><h2>Aprender</h2><p>Cada asignatura tiene sus temas, repasos, deberes y exámenes.</p></div><button class="secondary" id="reviewCourseBtn">✨ Repaso de inicio de curso</button></div>
      <a class="subject-card" href="ingles.php" style="display:block;text-decoration:none;color:inherit;margin:12px 0"><div class="subject-icon">🇬🇧</div><h3>Inglés · Preparación del examen</h3><p>Días de la semana, números del 1 al 100, escritura y pronunciación.</p></a>
      <div class="subjects-large" id="subjectsLarge"></div>
      <div id="subjectDetail" class="subject-detail hidden"></div>
    </section>

    <section class="page" id="page-repasar">
      <div class="page-intro"><div><span class="pill orange">CENTRO DE ENTRENAMIENTO</span><h2>¿Cómo quieres repasar?</h2><p>Elige un modo o deja que Luna prepare el mejor repaso para ti.</p></div></div>
      <div class="review-feature"><div><span>✨ RECOMENDADO POR LUNA</span><h2>Repaso inteligente</h2><p>He preparado 10 preguntas con tus fallos recientes y temas que llevas tiempo sin practicar.</p><button class="primary" data-start="Repaso inteligente">Empezar ahora</button></div><div class="brain">🧠</div></div>
      <div class="modes-grid">
        <button class="mode" data-start="Repaso exprés"><span>⚡</span><b>Exprés</b><small>5 preguntas · 3 min</small></button>
        <button class="mode" data-start="Solo fallos"><span>🎯</span><b>Solo fallos</b><small>Practica lo que cuesta</small></button>
        <button class="mode" data-start="Contra reloj"><span>⏱️</span><b>Contra reloj</b><small>Responde en 60 segundos</small></button>
        <button class="mode" data-start="Supervivencia"><span>❤️</span><b>Supervivencia</b><small>5 vidas · sin límite</small></button>
        <button class="mode" data-start="Ruleta del saber"><span>🎡</span><b>Ruleta</b><small>Asignatura sorpresa</small></button>
        <button class="mode" data-start="Repaso del curso anterior"><span>🎒</span><b>Curso anterior</b><small>Lo esencial de 4º</small></button>
        <button class="mode crazy-mode" data-start="Examen Loco"><span>🤯</span><b>Examen Loco</b><small>10/20/30/40 seguidas sin fallar</small></button>
      </div>
    </section>

    <section class="page" id="page-aventuras">
      <div class="page-intro"><div><span class="pill green">MUNDO DE LEIRE</span><h2>Isla del Saber</h2><p>Cada tema completado transforma tu mundo.</p></div></div>
      <div class="island">
        <div class="island-title"><b>Progreso de la isla</b><span>43%</span></div><div class="progress big"><i style="width:43%"></i></div>
        <button class="isle math-isle" data-subject="matematicas">📐<b>Planeta Matemático</b><small>7/12</small></button>
        <button class="isle lang-isle" data-subject="lengua">📖<b>Biblioteca Mágica</b><small>5/12</small></button>
        <button class="isle science-isle" data-subject="conocimiento">🌍<b>Selva Exploradora</b><small>4/10</small></button>
        <button class="isle english-isle" data-subject="ingles">🇬🇧<b>English City</b><small>3/10</small></button>
        <div class="castle">🏰<small>Castillo final<br>bloqueado</small></div>
      </div>
    </section>

    <section class="page" id="page-perfil">
      <div class="profile-head"><div class="avatar-xl">👧</div><div><span class="eyebrow">PERFIL DE ALUMNA</span><h2>Leire</h2><p>Nivel 28 · Exploradora del Saber</p><div class="progress"><i style="width:78%"></i></div><small>760 / 1.000 XP para nivel 29</small></div></div>
      <div class="profile-stats"><div><span>🔥</span><b>6 días</b><small>Racha</small></div><div><span>⭐</span><b>1.240</b><small>XP total</small></div><div><span>🏅</span><b>18</b><small>Logros</small></div><div><span>💶</span><b>3,82 €</b><small>Saldo</small></div></div>
      <div class="two-col"><div class="panel"><h2>Mi habitación</h2><div class="room"><span class="window">☀️</span><span class="bed">🛏️</span><span class="plant">🪴</span><span class="desk">🪑</span><span class="pet">🐱</span></div><button class="secondary full">Decorar habitación</button></div><div class="panel"><h2>Logros recientes</h2><div class="achievements"><div>🏆<span><b>Semana perfecta</b><small>Estudiaste 5 días</small></span></div><div>🧠<span><b>Mente brillante</b><small>50 respuestas correctas</small></span></div><div>📐<span><b>Reina de las mates</b><small>Un 10 en Matemáticas</small></span></div></div></div></div>
    </section>
  </main>

  <div class="modal" id="activityModal"><div class="modal-card"><button class="close" data-close>×</button><div class="modal-icon">🧠</div><h2 id="activityTitle">Repaso inteligente</h2><p>Este prototipo ya tiene preparada la navegación. Las actividades principales ya están conectadas con la app real. Este cuadro sirve como demostración para los modos pendientes.</p><div class="question-demo"><span>Pregunta de ejemplo</span><b>¿Cuánto es 8 × 7?</b><div class="answers"><button>54</button><button class="correct">56</button><button>64</button></div></div><button class="primary full" data-close>Terminar demostración</button></div></div>
  <div class="modal" id="parentModal"><div class="modal-card small"><button class="close" data-close>×</button><div class="modal-icon">🔒</div><h2>Panel de Juanra</h2><p>Introduce el PIN para acceder al centro de control.</p><input id="pinInput" type="password" inputmode="numeric" maxlength="4" placeholder="••••"><button class="primary full" id="pinSubmit">Entrar</button><small id="pinError" class="error"></small></div></div>
  <div class="toast" id="toast"></div>
</div>
<script src="assets/nuevo_curso/app.js?v=6.1"></script>
</body>
</html>
