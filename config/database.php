<?php

return [
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_NAME'] ?? 'webshop',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ],
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../storage/database.sqlite',
            'prefix' => '',
        ],
    ],
    
    'migrations' => [
        'table' => 'migrations',
        'path' => __DIR__ . '/../database/migrations',
    ],
    
    'seeds' => [
        'path' => __DIR__ . '/../database/seeders',
    ],
];
