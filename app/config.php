<?php
// Configuración básica - cambia estos datos al subirlo al hosting
define('APP_NAME', 'Academia Star');
define('APP_VERSION', '6.1 Nuevo Curso');
define('DB_HOST', 'database-5020120127.webspace-host.com');
define('DB_NAME', 'dbs15496785');
define('DB_USER', 'dbu4041869');
define('DB_PASS', 'juanra1234');
define('DB_CHARSET', 'utf8mb4');

// En producción, pon esto en true si usas HTTPS
define('COOKIE_SECURE', true);

// Subidas para fotos del libro, fichas y apuntes
define('MAX_UPLOAD_MB', 8);

// IA V4.7: por seguridad la clave NO debe ir escrita en este archivo.
// Opciones:
// 1) Variable de entorno OPENAI_API_KEY
// 2) Crear app/ai_secret.php usando app/ai_secret.example.php como plantilla
$openaiSecret = getenv('OPENAI_API_KEY') ?: '';
$openaiModel = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
$openaiVisionModel = getenv('OPENAI_VISION_MODEL') ?: $openaiModel;
$openaiVisionDetail = getenv('OPENAI_VISION_DETAIL') ?: 'high';
$aiDailyRequestLimit = getenv('AI_DAILY_REQUEST_LIMIT') ?: 30;
$aiMonthlyLimitEur = getenv('AI_MONTHLY_LIMIT_EUR') ?: 5;
$aiVisionEnabled = getenv('AI_VISION_ENABLED');
if ($aiVisionEnabled === false || $aiVisionEnabled === '') $aiVisionEnabled = '1';
$secretFile = __DIR__ . '/ai_secret.php';
if (is_file($secretFile)) {
    $secretData = require $secretFile;
    if (is_array($secretData)) {
        $secretKey = trim((string)($secretData['OPENAI_API_KEY'] ?? ''));
        if ($openaiSecret === '') $openaiSecret = $secretKey;
        $openaiModel = trim((string)($secretData['OPENAI_MODEL'] ?? $openaiModel)) ?: $openaiModel;
        $openaiVisionModel = trim((string)($secretData['OPENAI_VISION_MODEL'] ?? $openaiVisionModel)) ?: $openaiVisionModel;
        $openaiVisionDetail = trim((string)($secretData['OPENAI_VISION_DETAIL'] ?? $openaiVisionDetail)) ?: $openaiVisionDetail;
        $aiDailyRequestLimit = (int)($secretData['AI_DAILY_REQUEST_LIMIT'] ?? $aiDailyRequestLimit);
        $aiMonthlyLimitEur = (float)($secretData['AI_MONTHLY_LIMIT_EUR'] ?? $aiMonthlyLimitEur);
        $aiVisionEnabled = (string)($secretData['AI_VISION_ENABLED'] ?? $aiVisionEnabled);
    }
}
define('OPENAI_API_KEY', $openaiSecret);
define('OPENAI_MODEL', $openaiModel ?: 'gpt-4o-mini');
define('OPENAI_VISION_MODEL', $openaiVisionModel ?: OPENAI_MODEL);
define('OPENAI_VISION_DETAIL', in_array($openaiVisionDetail, ['low','high','auto'], true) ? $openaiVisionDetail : 'high');
define('AI_DAILY_REQUEST_LIMIT', max(0, (int)$aiDailyRequestLimit));
define('AI_MONTHLY_LIMIT_EUR', max(0, (float)$aiMonthlyLimitEur));
define('AI_VISION_ENABLED', !in_array(strtolower((string)$aiVisionEnabled), ['0','false','no','off'], true));

// V4.2/V4.7 OCR opcional: instala Tesseract en el servidor si quieres lectura local de fotos.
// En Windows/XAMPP normalmente sería algo como: C:\Program Files\Tesseract-OCR\tesseract.exe
define('OCR_TESSERACT_PATH', 'tesseract');
define('OCR_LANGS', 'spa+eng');
