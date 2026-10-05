<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';
$user = require_admin();
$topics = db()->query('SELECT * FROM topics ORDER BY subject,title')->fetchAll();
$selected = (int)($_GET['topic_id'] ?? $_POST['topic_id'] ?? 0);
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $kind = $_POST['kind'] === 'apunte' ? 'apunte' : 'libro';
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if (!$topicId || empty($_FILES['file']['name'])) {
        $err = 'Elige un tema y un archivo.';
    } else {
        $allowed = ['jpg','jpeg','png','webp','pdf'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $max = MAX_UPLOAD_MB * 1024 * 1024;
        if (!in_array($ext, $allowed, true)) {
            $err = 'Formato no permitido. Usa JPG, PNG, WEBP o PDF.';
        } elseif ($_FILES['file']['size'] > $max) {
            $err = 'El archivo supera ' . MAX_UPLOAD_MB . ' MB.';
        } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $err = 'Error al subir el archivo.';
        } else {
            $folder = upload_kind_folder($kind);
            $targetDir = __DIR__ . '/../uploads/' . $folder;
            if (!is_dir($targetDir)) mkdir($targetDir, 0775, true);
            $safe = safe_upload_name($_FILES['file']['name']);
            $dest = $targetDir . '/' . $safe;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $path = 'uploads/' . $folder . '/' . $safe;
                db()->prepare('INSERT INTO topic_files(topic_id,kind,title,notes,file_path,original_name,mime_type,size_bytes) VALUES(?,?,?,?,?,?,?,?)')->execute([$topicId,$kind,$title,$notes,$path,$_FILES['file']['name'],$_FILES['file']['type'],(int)$_FILES['file']['size']]);
                $msg = 'Archivo subido correctamente.';
                $selected = $topicId;
            } else {
                $err = 'No se pudo guardar el archivo en la carpeta uploads.';
            }
        }
    }
}
$files = [];
if ($selected) {
    $st = db()->prepare('SELECT f.*, t.title topic_title, t.subject FROM topic_files f JOIN topics t ON t.id=f.topic_id WHERE f.topic_id=? ORDER BY f.created_at DESC');
    $st->execute([$selected]);
    $files = $st->fetchAll();
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Archivos</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap">
<header class="topbar"><div class="brand">📸 Modo libro escolar</div><nav class="nav"><a class="secondary" href="panel.php">Volver</a></nav></header>
<form class="card form" method="post" enctype="multipart/form-data"><h1>Subir foto, ficha o apunte</h1><p class="muted">En esta V2.1 se guarda el material dentro de cada tema. La generación automática con IA la dejamos preparada para una versión posterior.</p><?php if($msg): ?><div class="notice ok"><?= e($msg) ?></div><?php endif; ?><?php if($err): ?><div class="notice bad"><?= e($err) ?></div><?php endif; ?>
<label>Tema<select name="topic_id" required><?php foreach($topics as $t): ?><option value="<?= (int)$t['id'] ?>" <?= $selected===(int)$t['id']?'selected':'' ?>><?= e($t['subject'].' - '.$t['title']) ?></option><?php endforeach; ?></select></label>
<label>Tipo<select name="kind"><option value="libro">Foto del libro / ficha</option><option value="apunte">Apunte</option></select></label>
<label>Título opcional<input class="input" name="title" placeholder="Ejemplo: Página 42 - fracciones"></label>
<label>Notas para Juanra<textarea name="notes" placeholder="Qué tiene que estudiar Leire de esta foto o ficha."></textarea></label>
<label>Archivo<input class="input" type="file" name="file" accept="image/*,.pdf" required></label>
<button class="btn">Subir archivo</button></form>
<?php if($files): ?><section class="card"><h2>Archivos de este tema</h2><div class="grid"><?php foreach($files as $f): ?><a class="subject" href="../<?= e($f['file_path']) ?>" target="_blank"><strong><?= e($f['title'] ?: $f['original_name']) ?></strong><p class="muted"><?= e($f['notes']) ?></p><span class="pill"><?= e($f['kind']) ?></span></a><?php endforeach; ?></div></section><?php endif; ?>
</div></body></html>
