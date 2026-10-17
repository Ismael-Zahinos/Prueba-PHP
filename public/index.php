<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\database;

// Cabeceras comunes para respuestas JSON de la API
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Si es una petición OPTIONS (preflight CORS), responder 200 y salir
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener la ruta limpia y el método HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Quitar prefijos en caso de que MAMP ejecute dentro de subcarpeta (ej. /Prueba-PHP/public)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && str_starts_with($uri, $scriptName)) {
    $uri = substr($uri, strlen($scriptName));
}
$uri = '/' . trim($uri, '/');

// Enrutador básico inicial para verificar funcionamiento
try {
    if ($uri === '/' || $uri === '') {
        echo "<h1>Gestión de Reservas - API y Vistas activas</h1>";
        exit;
    }

    if ($uri === '/api/ping') {
        // Probamos también la conexión PDO
        database::getConnection();
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => 'API operativa y conexion a BD establecida correctamente'
        ]);
        exit;
    }

    // Ruta no encontrada
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Ruta no encontrada'
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno en el servidor: ' . $e->getMessage()
    ]);
}