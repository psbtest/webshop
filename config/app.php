<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'PHP Webshop',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'Europe/Amsterdam',
    'locale' => 'nl',
    'fallback_locale' => 'en',
    
    'uploads' => [
        'max_size' => (int) ($_ENV['MAX_FILE_SIZE'] ?? 5242880), // 5MB
        'allowed_types' => explode(',', $_ENV['ALLOWED_FILE_TYPES'] ?? 'image/jpeg,image/png,image/gif,image/webp'),
        'path' => __DIR__ . '/../storage/uploads/',
        'url' => '/uploads/',
    ],
    
    'pagination' => [
        'per_page' => 20,
        'products_per_page' => 12,
    ],
    
    'cache' => [
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'ttl' => (int) ($_ENV['CACHE_TTL'] ?? 3600),
        'path' => __DIR__ . '/../storage/cache/',
    ],
    
    'session' => [
        'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),
        'path' => '/',
        'domain' => null,
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'http_only' => filter_var($_ENV['SESSION_HTTP_ONLY'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ],
    
    'security' => [
        'bcrypt_rounds' => 12,
        'allowed_hosts' => [],
        'trusted_proxies' => [],
    ],
];
