<?php
// Copia este archivo como app/ai_secret.php y pega tu clave NUEVA aquí.
// No compartas este archivo ni lo subas a repositorios públicos.
return [
    'OPENAI_API_KEY' => 'PEGA_AQUI_TU_API_KEY_NUEVA',
    'OPENAI_MODEL' => 'gpt-4o-mini',
    'OPENAI_VISION_MODEL' => 'gpt-4o-mini',
    // high lee mejor fichas y libros; low gasta menos; auto deja decidir al modelo.
    'OPENAI_VISION_DETAIL' => 'high',
    // V4.7 límites de gasto y uso. 0 significa sin límite.
    'AI_DAILY_REQUEST_LIMIT' => 30,
    'AI_MONTHLY_LIMIT_EUR' => 5.0,
    'AI_VISION_ENABLED' => '1',
];
