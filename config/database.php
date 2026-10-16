<?php

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306', // Si en tu MAMP es 8889, cámbialo aquí
    'dbname'   => getenv('DB_NAME') ?: 'reservas_db',
    'user'     => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: 'root', // Contraseña por defecto de MAMP suele ser 'root'
    'charset'  => 'utf8mb4'
];
