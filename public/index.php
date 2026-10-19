<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Repositories\ReservaRepository;
use App\Services\ReservaService;
use App\Controllers\ReservaApiController;
use App\Controllers\ReservaWebController;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

// Cabeceras CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inicialización de base de datos y servicios
$db = Database::getConnection();
$reservaRepo = new ReservaRepository($db);
$reservaService = new ReservaService($reservaRepo, $db);
$apiController = new ReservaApiController($reservaService);

// Configuración de Twig
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader, [
    'autoescape' => 'html',
    'cache'      => false // Sin caché en desarrollo para ver cambios al instante
]);

// Normalización de URL y detección de subcarpeta en MAMP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim(str_replace('\\', '/', $scriptDir), '/');

if ($baseUrl !== '' && str_starts_with($uri, $baseUrl)) {
    $uri = substr($uri, strlen($baseUrl));
}
$uri = '/' . trim($uri, '/');

$webController = new ReservaWebController($reservaService, $twig, $baseUrl);

try {
    // ---------------- API ENDPOINTS ----------------
    if ($uri === '/api/ping') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'message' => 'API operativa y conexion a BD establecida']);
        exit;
    }

    if ($uri === '/api/reservas' && $method === 'GET') {
        $apiController->index();
        exit;
    }

    if ($uri === '/api/reservas' && $method === 'POST') {
        $apiController->store();
        exit;
    }

    if (preg_match('#^/api/reservas/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $apiController->show((int)$matches[1]);
        exit;
    }

    if (preg_match('#^/api/reservas/(\d+)/estado$#', $uri, $matches) && in_array($method, ['PATCH', 'PUT', 'POST'], true)) {
        $apiController->updateEstado((int)$matches[1]);
        exit;
    }

    // ---------------- VISTAS HTML CON TWIG ----------------
    // Listado inicial
    if (($uri === '/' || $uri === '/reservas') && $method === 'GET') {
        $webController->index();
        exit;
    }

    // Detalle de reserva
    if (preg_match('#^/reservas/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $webController->show((int)$matches[1]);
        exit;
    }

    // 404
    http_response_code(404);
    if (str_starts_with($uri, '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Endpoint de API no encontrado']);
    } else {
        echo "<h1>404 - Página no encontrada</h1><p><a href='{$baseUrl}/'>Volver al inicio</a></p>";
    }
} catch (\Throwable $e) {
    http_response_code(500);
    if (str_starts_with($uri, '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
    } else {
        echo "<h1>500 - Error en el servidor</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}