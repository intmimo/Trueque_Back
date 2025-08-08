<?php

return [

    // Endpoints donde aplica CORS (API, Sanctum y broadcasting privado)
    'paths' => [
        'api/*',
        'broadcasting/auth',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'register',
    ],

    // Métodos permitidos (comodín para evitar bloqueos raros)
    'allowed_methods' => ['*'],

    // Orígenes permitidos (Ionic/Angular local + variantes)
    'allowed_origins' => [
        'http://localhost:8100',
        'http://127.0.0.1:8100',
        'http://localhost:8101',
        'http://127.0.0.1:8101',
        // Si usas Vite/Angular en 5173/4200, destapa:
        // 'http://localhost:5173',
        // 'http://127.0.0.1:5173',
        // 'http://localhost:4200',
        // 'http://127.0.0.1:4200',
        // Producción: agrega tu dominio
        // 'https://tu-dominio.com',
    ],

    'allowed_origins_patterns' => [],

    // Headers abiertos (menos fricción en dev)
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // True por si usas cookies/Sanctum; no estorba con Bearer
    'supports_credentials' => true,
];
