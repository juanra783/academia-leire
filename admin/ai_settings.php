<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/ai.php';
$user = require_admin();
$message = '';
$error = '';
$secretFile = __DIR__ . '/../app/ai_secret.php';

function mask_api_key_for_ui(string $key): string {
    $key = trim($key);
    if ($key === '') return 'No configurada';
    return substr($key, 0, 7) . '…' . substr($key, -4);
}
function money_fmt(float $n): string { return number_format($n, 4, ',', '.'); }

$currentKey = defined('OPENAI_API_KEY') ? (string)OPENAI_API_KEY : '';
$currentModel = defined('OPENAI_MODEL') ? (string)OPENAI_MODEL : 'gpt-4o-mini';
$currentVisionModel = defined('OPENAI_VISION_MODEL') ? (string)OPENAI_VISION_MODEL : $currentModel;
$currentDetail = defined('OPENAI_VISION_DETAIL') ? (string)OPENAI_VISION_DETAIL : 'high';
$currentDailyLimit = defined('AI_DAILY_REQUEST_LIMIT') ? (int)AI_DAILY_REQUEST_LIMIT : 30;
$currentMonthlyLimit = defined('AI_MONTHLY_LIMIT_EUR') ? (float)AI_MONTHLY_LIMIT_EUR : 5.0;
$currentVisionEnabled = defined('AI_VISION_ENABLED') ? (bool)AI_VISION_ENABLED : true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'clear') {
        if (is_file($secretFile)) @unlink($secretFile);
        $message = 'Clave y configuración eliminadas del servidor. La app volverá a modo local si no hay variable de entorno.';
        $currentKey = '';
    } elseif ($action === 'reset_today') {
        try {
            ai_usage_ensure_table();
            db()->exec('DELETE FROM ai_usage WHERE DATE(created_at)=CURDATE()');
            $message = 'Contador de hoy reiniciado.';
        } catch (Throwable $e) { $error = 'No se pudo reiniciar el contador diario.'; }
    } elseif ($action === 'reset_month') {
        try {
            ai_usage_ensure_table();
            db()->exec("DELETE FROM ai_usage WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
            $message = 'Contador mensual reiniciado.';
        } catch (Throwable $e) { $error = 'No se pudo reiniciar el contador mensual.'; }
    } else {
        $key = trim($_POST['api_key'] ?? '');
        if ($key === '') $key = $currentKey;
        $model = trim($_POST['model'] ?? 'gpt-4o-mini') ?: 'gpt-4o-mini';
        $visionModel = trim($_POST['vision_model'] ?? $model) ?: $model;
        $detail = $_POST['vision_detail'] ?? 'high';
        if (!in_array($detail, ['low','high','auto'], true)) $detail = 'high';
        $dailyLimit = max(0, (int)($_POST['daily_limit'] ?? 30));
        $monthlyLimit = max(0, (float)str_replace(',', '.', (string)($_POST['monthly_limit'] ?? 5)));
        $visionEnabled = isset($_POST['vision_enabled']) ? '1' : '0';

        if ($key !== '' && !preg_match('/^sk-[A-Za-z0-9_\-]{20,}$/', $key)) {
            $error = 'La clave no parece tener formato de API key de OpenAI.';
        } else {
            $content = "<?php\n// Archivo generado desde Panel Juanra > Configuración IA.\n// No compartas este archivo.\nreturn [\n";
            $content .= "    'OPENAI_API_KEY' => " . var_export($key, true) . ",\n";
            $content .= "    'OPENAI_MODEL' => " . var_export($model, true) . ",\n";
            $content .= "    'OPENAI_VISION_MODEL' => " . var_export($visionModel, true) . ",\n";
            $content .= "    'OPENAI_VISION_DETAIL' => " . var_export($detail, true) . ",\n";
            $content .= "    'AI_DAILY_REQUEST_LIMIT' => " . var_export($dailyLimit, true) . ",\n";
            $content .= "    'AI_MONTHLY_LIMIT_EUR' => " . var_export($monthlyLimit, true) . ",\n";
            $content .= "    'AI_VISION_ENABLED' => " . var_export($visionEnabled, true) . ",\n";
            $content .= "];\n";
            if (@file_put_contents($secretFile, $content, LOCK_EX) === false) {
                $error = 'No se pudo escribir app/ai_secret.php. Revisa permisos del servidor.';
            } else {
                $message = 'Configuración IA guardada. Recarga la app para aplicar los límites.';
                $currentKey = $key;
                $currentModel = $model;
                $currentVisionModel = $visionModel;
                $currentDetail = $detail;
                $currentDailyLimit = $dailyLimit;
                $currentMonthlyLimit = $monthlyLimit;
                $currentVisionEnabled = $visionEnabled === '1';
            }
        }
    }
}

$leireId = 0;
try {
    $st = db()->prepare("SELECT id FROM users WHERE username='leire' LIMIT 1");
    $st->execute();
    $leireId = (int)($st->fetchColumn() ?: 0);
} catch (Throwable $e) {}
$leireStats = ai_usage_stats($leireId);
$globalStats = ai_usage_stats(0);
$dayPercent = $currentDailyLimit > 0 ? min(100, round(($leireStats['daily_count'] / max(1, $currentDailyLimit)) * 100)) : 0;
$monthPercent = $currentMonthlyLimit > 0 ? min(100, round(($globalStats['monthly_cost'] / max(0.0001, $currentMonthlyLimit)) * 100)) : 0;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Configuración IA</title><link rel="stylesheet" href="../public/assets/css/style.css"></head><body><div class="wrap">
<header class="topbar"><div class="brand">🤖 Configuración IA <span class="pill">V4.7</span></div><nav class="nav"><a class="secondary" href="panel.php">Panel</a><a class="secondary" href="../public/teacher.php">Luna IA</a><a class="secondary" href="../public/scan.php">Escanear</a></nav></header>
<?php if($message): ?><div class="notice ok"><b>Listo:</b> <?= e($message) ?></div><?php endif; ?>
<?php if($error): ?><div class="notice bad"><b>Error:</b> <?= e($error) ?></div><?php endif; ?>

<section class="card"><h1>Control de gasto IA</h1><p class="muted">Aquí puedes poner límites para evitar sustos: consultas diarias para Leire y gasto mensual estimado. Cuando se llegue al límite, la app cambia sola a modo local.</p><div class="stats compact"><div class="mini"><b><?= ai_enabled() ? 'Activa' : 'Local' ?></b><span>Estado</span></div><div class="mini"><b><?= e(mask_api_key_for_ui($currentKey)) ?></b><span>Clave</span></div><div class="mini"><b><?= (int)$leireStats['daily_count'] ?> / <?= $currentDailyLimit ?: '∞' ?></b><span>Consultas hoy</span></div><div class="mini"><b><?= money_fmt((float)$globalStats['monthly_cost']) ?> / <?= $currentMonthlyLimit ?: '∞' ?> €</b><span>Mes estimado</span></div></div><div class="mini-bar"><span style="width:<?= (int)$dayPercent ?>%"></span></div><p class="muted">Uso diario de Leire: <?= (int)$dayPercent ?>%</p><div class="mini-bar"><span style="width:<?= (int)$monthPercent ?>%"></span></div><p class="muted">Gasto mensual estimado: <?= (int)$monthPercent ?>%</p></section>

<form class="card form" method="post"><input type="hidden" name="action" value="save"><h2>Guardar API y límites</h2><label>API key de OpenAI<input class="input" type="password" name="api_key" autocomplete="off" placeholder="Dejar vacío para mantener la actual"></label><div class="grid small"><label>Modelo texto<input class="input" name="model" value="<?= e($currentModel ?: 'gpt-4o-mini') ?>"></label><label>Modelo visión<input class="input" name="vision_model" value="<?= e($currentVisionModel ?: 'gpt-4o-mini') ?>"></label><label>Detalle imagen<select name="vision_detail"><option value="high" <?= $currentDetail==='high'?'selected':'' ?>>high - mejor para fichas</option><option value="auto" <?= $currentDetail==='auto'?'selected':'' ?>>auto</option><option value="low" <?= $currentDetail==='low'?'selected':'' ?>>low - gasta menos</option></select></label><label>Consultas IA al día<input class="input" type="number" min="0" name="daily_limit" value="<?= (int)$currentDailyLimit ?>"><small class="muted">0 = sin límite diario</small></label><label>Gasto mensual máximo (€ aprox.)<input class="input" type="number" min="0" step="0.50" name="monthly_limit" value="<?= e((string)$currentMonthlyLimit) ?>"><small class="muted">0 = sin límite mensual</small></label><label><input type="checkbox" name="vision_enabled" value="1" <?= $currentVisionEnabled?'checked':'' ?>> Permitir IA Vision para fotos</label></div><button class="btn">Guardar configuración</button></form>

<section class="card"><h2>Acciones rápidas</h2><p class="muted">Úsalo solo para pruebas. Reiniciar el contador no devuelve crédito en OpenAI; solo reinicia el control interno de la app.</p><form method="post" style="display:inline-block" onsubmit="return confirm('¿Reiniciar contador de hoy?')"><input type="hidden" name="action" value="reset_today"><button class="btn secondary">Reiniciar hoy</button></form> <form method="post" style="display:inline-block" onsubmit="return confirm('¿Reiniciar contador mensual?')"><input type="hidden" name="action" value="reset_month"><button class="btn secondary">Reiniciar mes</button></form> <form method="post" style="display:inline-block" onsubmit="return confirm('¿Eliminar la clave guardada en este servidor?')"><input type="hidden" name="action" value="clear"><button class="btn secondary">Eliminar clave guardada</button></form></section>

<section class="card"><h2>Notas importantes</h2><p class="muted">El coste es estimado usando los tokens que devuelve la API. OpenAI factura en dólares; la app lo muestra como euros aproximados para que puedas poner un límite sencillo. La parte que más puede consumir es analizar fotos con IA Vision.</p></section>
<footer class="footer">V4.7 · Luna IA con límites diarios, control mensual y modo local automático.</footer>
</div></body></html>
