<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: text/plain; charset=utf-8');
try {
    $pdo = db();
    $row = $pdo->query('SELECT DATABASE() AS db')->fetch();
    echo "OK - conexión MySQL correcta\n";
    echo "Base de datos: " . ($row['db'] ?? 'desconocida') . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR - no se pudo conectar a MySQL\n";
}
