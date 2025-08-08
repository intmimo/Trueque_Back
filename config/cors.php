<?php

return [
    'paths' => ['api/*', 'broadcasting/auth', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:8101'],
    // O más flexible:
    // 'allowed_origins_patterns' => ['#^http://localhost(:\d+)?$#'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // no estorba aunque uses Bearer
];