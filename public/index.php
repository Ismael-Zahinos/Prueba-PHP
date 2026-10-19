<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\database;
use App\Repositories\ReservaRepository;
use App\Services\ReservaService;
use App\Controllers\ReservaApiController;

// Cabeceras comunes para respuestas JSON de la API
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Si es una petición OPTIONS (preflight CORS), responder 200 y salir
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inicialización de dependencias
$db = Database::getConnection();
$reservaRepo = new ReservaRepository($db);
$reservaService = new ReservaService($reservaRepo, $db);
$apiController = new ReservaApiController($reservaService);

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

    // Listar reservas (con filtros)
    if ($uri === '/api/reservas' && $method === 'GET') {
        $apiController->index();
        exit;
    }

    // Crear reserva
    if ($uri === '/api/reservas' && $method === 'POST') {
        $apiController->store();
        exit;
    }

    // Detalle de una reserva
    if (preg_match('#^/api/reservas/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $apiController->show((int)$matches[1]);
        exit;
    }

    // Cambiar estado (confirmar o cancelar)
    if (preg_match('#^/api/reservas/(\d+)/estado$#', $uri, $matches) && in_array($method, ['PATCH', 'PUT', 'POST'], true)) {
        $apiController->updateEstado((int)$matches[1]);
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