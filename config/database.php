<?php

// Detecta si se está ejecutando en local (MAMP / CLI para PHPUnit) o en producción
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000'], true)
    || php_sapi_name() === 'cli';

if ($isLocal) {
    return [
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => getenv('DB_PORT') ?: '3306', // O '8889' si en tu MAMP usas el puerto de MAMP
        'dbname'   => getenv('DB_NAME') ?: 'reservas_db',
        'user'     => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: 'root', // En MAMP Windows suele ser 'root' o vacío ''
        'charset'  => 'utf8mb4'
    ];
}

// Configuración para el hosting de producción (InfinityFree)
return [
    'host'     => getenv('DB_HOST') ?: 'sql210.infinityfree.com',
    'port'     => getenv('DB_PORT') ?: '3306', 
    'dbname'   => getenv('DB_NAME') ?: 'if0_43219679_reservas_db',
    'user'     => getenv('DB_USER') ?: 'if0_43219679',
    'password' => getenv('DB_PASS') ?: 'qxWyMzawuFPQN', 
    'charset'  => 'utf8mb4'
];