<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_login();
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function crazy_tables(): void {
    db()->exec("CREATE TABLE IF NOT EXISTS crazy_exam_attempts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,target_questions SMALLINT UNSIGNED NOT NULL,best_streak SMALLINT UNSIGNED NOT NULL DEFAULT 0,completed TINYINT(1) NOT NULL DEFAULT 0,duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,subject_key VARCHAR(100) NOT NULL DEFAULT 'mixed',selection_mode VARCHAR(40) NOT NULL DEFAULT 'smart',difficulty_mode VARCHAR(40) NOT NULL DEFAULT 'adaptive',failed_question_id INT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_crazy_user_created(user_id,created_at),INDEX idx_crazy_user_completed(user_id,completed,target_questions)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    db()->exec("CREATE TABLE IF NOT EXISTS crazy_exam_records (user_id INT PRIMARY KEY,best_streak SMALLINT UNSIGNED NOT NULL DEFAULT 0,best_completed_target SMALLINT UNSIGNED NOT NULL DEFAULT 0,total_attempts INT UNSIGNED NOT NULL DEFAULT 0,total_completed INT UNSIGNED NOT NULL DEFAULT 0,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
crazy_tables();

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $_GET['ajax'];
    if ($action === 'start') {
        $target = (int)($payload['count'] ?? 10);
        if (!in_array($target,[10,20,30,40],true)) $target=10;
        $subject = trim((string)($payload['subject'] ?? 'mixed'));
        $selection = trim((string)($payload['selection'] ?? 'smart'));
        $difficulty = trim((string)($payload['difficulty'] ?? 'adaptive'));
        $where=[];$params=[];
        if ($subject !== 'mixed') {$where[]='t.subject=?';$params[]=$subject;}
        if ($difficulty==='hard') $where[]='q.difficulty>=2';
        elseif ($difficulty==='normal') $where[]='q.difficulty>=1';
        $sql='SELECT q.*,t.subject,t.title topic_title FROM questions q JOIN topics t ON t.id=q.topic_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY RAND() LIMIT '.$target;
        $st=db()->prepare($sql);$st->execute($params);$rows=$st->fetchAll();
        if (count($rows)<$target) { echo json_encode(['ok'=>false,'error'=>'No hay suficientes preguntas para este filtro. Prueba con todas las asignaturas.']); exit; }
        $ids=array_map(fn($r)=>(int)$r['id'],$rows);
        $_SESSION['crazy_exam']=['user_id'=>(int)$user['id'],'target'=>$target,'subject'=>$subject,'selection'=>$selection,'difficulty'=>$difficulty,'ids'=>$ids,'index'=>0,'started'=>time()];
        $q=$rows[0];
        echo json_encode(['ok'=>true,'question'=>crazy_public_question($q),'target'=>$target],JSON_UNESCAPED_UNICODE);exit;
    }
    if ($action === 'answer') {
        $s=$_SESSION['crazy_exam']??null;
        if(!$s || (int)$s['user_id']!==(int)$user['id']){echo json_encode(['ok'=>false,'error'=>'La sesión del reto ha caducado.']);exit;}
        $index=(int)$s['index'];$qid=(int)($s['ids'][$index]??0);
        $st=db()->prepare('SELECT q.*,t.subject,t.title topic_title FROM questions q JOIN topics t ON t.id=q.topic_id WHERE q.id=?');$st->execute([$qid]);$q=$st->fetch();
        if(!$q){echo json_encode(['ok'=>false,'error'=>'Pregunta no encontrada.']);exit;}
        $answer=trim((string)($payload['answer']??''));$correct=is_correct_answer($answer,$q['correct_answer']);
        if(!$correct){crazy_finish((int)$user['id'],$s,false,$index,$qid);unset($_SESSION['crazy_exam']);echo json_encode(['ok'=>true,'correct'=>false,'streak'=>$index,'correct_answer'=>$q['correct_answer'],'explanation'=>$q['explanation']?:'Revisa la respuesta correcta antes de volver a empezar.'],JSON_UNESCAPED_UNICODE);exit;}
        $index++;$_SESSION['crazy_exam']['index']=$index;
        if($index >= (int)$s['target']){crazy_finish((int)$user['id'],$_SESSION['crazy_exam'],true,$index,null);unset($_SESSION['crazy_exam']);add_unique_achievement((int)$user['id'],'Racha perfecta de '.$s['target'],'Has completado '.$s['target'].' preguntas seguidas sin fallar.',$s['target']>=40?'💎':'🏆');award_progress((int)$user['id'],'Examen Loco '.$s['target'].'/'.$s['target'],20+$s['target'],0,null);echo json_encode(['ok'=>true,'correct'=>true,'completed'=>true,'streak'=>$index]);exit;}
        $nextId=(int)$s['ids'][$index];$st=db()->prepare('SELECT q.*,t.subject,t.title topic_title FROM questions q JOIN topics t ON t.id=q.topic_id WHERE q.id=?');$st->execute([$nextId]);$next=$st->fetch();
        echo json_encode(['ok'=>true,'correct'=>true,'completed'=>false,'streak'=>$index,'question'=>crazy_public_question($next)],JSON_UNESCAPED_UNICODE);exit;
    }
    echo json_encode(['ok'=>false,'error'=>'Acción no válida']);exit;
}
function crazy_public_question(array $q): array {
    $opts=[]; if(($q['type']??'')==='multiple') $opts=json_decode($q['options_json']?:'[]',true)?:[];
    return ['id'=>(int)$q['id'],'subject'=>$q['subject'],'topic'=>$q['topic_title'],'type'=>$q['type'],'question'=>$q['question'],'options'=>$opts,'difficulty'=>(int)($q['difficulty']??1)];
}
function crazy_finish(int $uid,array $s,bool $completed,int $streak,?int $failed): void {
    $seconds=max(0,time()-(int)$s['started']);
    $st=db()->prepare('INSERT INTO crazy_exam_attempts(user_id,target_questions,best_streak,completed,duration_seconds,subject_key,selection_mode,difficulty_mode,failed_question_id) VALUES(?,?,?,?,?,?,?,?,?)');
    $st->execute([$uid,(int)$s['target'],$streak,$completed?1:0,$seconds,$s['subject'],$s['selection'],$s['difficulty'],$failed]);
    $sql='INSERT INTO crazy_exam_records(user_id,best_streak,best_completed_target,total_attempts,total_completed) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE best_streak=GREATEST(best_streak,VALUES(best_streak)),best_completed_target=GREATEST(best_completed_target,VALUES(best_completed_target)),total_attempts=total_attempts+1,total_completed=total_completed+VALUES(total_completed)';
    db()->prepare($sql)->execute([$uid,$streak,$completed?(int)$s['target']:0,1,$completed?1:0]);
}
$rec=db()->prepare('SELECT * FROM crazy_exam_records WHERE user_id=?');$rec->execute([$user['id']]);$record=$rec->fetch()?:['best_streak'=>0,'best_completed_target'=>0];
$subjects=db()->query('SELECT DISTINCT subject FROM topics ORDER BY FIELD(subject,"Matemáticas","Lengua","Conocimiento del Medio","Inglés")')->fetchAll(PDO::FETCH_COLUMN);
$prefCount=(int)($_GET['count']??10);if(!in_array($prefCount,[10,20,30,40],true))$prefCount=10;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Examen Loco · Academia Star 6.3</title><link rel="manifest" href="manifest.webmanifest?v=6"><meta name="theme-color" content="#16092f"><link rel="stylesheet" href="assets/css/style.css?v=63"></head>
<body class="crazy-body"><div class="crazy-sky" aria-hidden="true"><i></i><i></i><i></i><i></i></div><main class="crazy-shell">
<header class="crazy-top"><a href="repaso.php" class="crazy-icon-btn">←</a><div><small>ACADEMIA STAR 6.1</small><b>Examen Loco</b></div><button id="soundBtn" class="crazy-icon-btn">🔊</button></header>
<section id="setup" class="crazy-screen active">
<div class="crazy-hero crazy-glass"><div class="crazy-emblem"><span>🧠</span><i></i></div><div><span class="crazy-kicker">RACHA PERFECTA</span><h1>¿Podrás acertarlas todas sin fallar?</h1><p>Un solo error rompe la racha y te devuelve al comienzo. Las preguntas se mezclan en cada intento.</p></div></div>
<div class="crazy-panel crazy-glass"><div class="crazy-heading"><h2><span>01</span> Elige el desafío</h2><b>Sin fallos</b></div><div class="crazy-counts"><?php foreach([10=>'⚡',20=>'🔥',30=>'💫',40=>'💎'] as $n=>$ic): ?><button data-count="<?=$n?>" class="crazy-count <?=$prefCount===$n?'selected':''?>"><span><?=$ic?></span><strong><?=$n?></strong><small>preguntas</small><em><?=$n===10?'Reto rápido':($n===20?'Racha seria':($n===30?'Nivel experto':'Racha legendaria'))?></em></button><?php endforeach; ?></div></div>
<div class="crazy-panel crazy-glass"><div class="crazy-heading"><h2><span>02</span> Elige el contenido</h2></div><div class="crazy-form-grid"><label>Asignatura<select id="subject"><option value="mixed">Todas mezcladas</option><?php foreach($subjects as $s):?><option><?=e($s)?></option><?php endforeach;?></select></label><label>Selección<select id="selection"><option value="smart">Mezcla inteligente</option><option value="failed" <?=($_GET['selection']??'')==='failed'?'selected':''?>>Preguntas falladas</option><option value="new">Preguntas nuevas</option></select></label></div><div class="crazy-difficulty"><button data-difficulty="adaptive" class="selected">Inteligente</button><button data-difficulty="normal">Normal</button><button data-difficulty="hard">Difícil</button></div></div>
<div class="crazy-record crazy-glass"><div><small>MEJOR RACHA</small><strong><?= (int)$record['best_streak']?></strong></div><i></i><div><small>RETO SUPERADO</small><strong><?= (int)$record['best_completed_target'] ? (int)$record['best_completed_target'].'/40':'—'?></strong></div><span>♛</span></div>
<button id="startBtn" class="crazy-primary"><i></i><span>COMENZAR EL RETO</span><b>→</b></button><p id="startError" class="crazy-error"></p>
</section>
<section id="play" class="crazy-screen"><div class="crazy-status crazy-glass"><div>🔥 <span><small>RACHA ACTUAL</small><strong><b id="number">1</b> / <b id="target">10</b></strong></span></div><time id="timer">00:00</time></div><div class="crazy-progress"><span id="bar"></span></div><article id="questionCard" class="crazy-question crazy-glass"><div class="crazy-meta"><span id="qSubject"></span><span id="qTopic"></span></div><h2 id="qText"></h2><div id="answers" class="crazy-answers"></div></article><div class="crazy-power crazy-glass"><div><small>PODER MÁGICO</small><b id="powerText">0%</b></div><span><i id="powerBar"></i></span></div><button id="quitBtn" class="crazy-quit">Abandonar reto</button></section>
<section id="result" class="crazy-screen"><div class="crazy-result crazy-glass"><div class="crazy-portal"><i></i><i></i><span id="resultIcon">🏆</span></div><span id="resultKicker" class="crazy-kicker">RACHA PERFECTA</span><h1 id="resultTitle"></h1><p id="resultMessage"></p><div class="crazy-stats"><div><small>Racha</small><b id="resultStreak"></b></div><div><small>Tiempo</small><b id="resultTime"></b></div><div><small>Récord</small><b id="resultRecord"></b></div></div><div id="explain" class="crazy-explain" hidden><small>EXPLICACIÓN DE LUNA</small><p id="explainText"></p></div><button id="retryBtn" class="crazy-primary"><span>VOLVER A INTENTAR</span></button><a href="repaso.php" class="crazy-home">Cambiar desafío</a></div></section>
<div id="particles" class="crazy-particles"></div><div id="toast" class="crazy-toast"></div></main>
<script>window.CRAZY_INITIAL_COUNT=<?=$prefCount?>;window.CRAZY_RECORD=<?= (int)$record['best_streak']?>;</script><script src="assets/js/crazy-exam-v6.js?v=6"></script></body></html>
