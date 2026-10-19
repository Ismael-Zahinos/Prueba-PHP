<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReservaService;
use InvalidArgumentException;
use Throwable;

class ReservaApiController
{
    private ReservaService $service;

    public function __construct(ReservaService $service)
    {
        $this->service = $service;
    }

    public function index(): void
    {
        try {
            $filtros = [
                'estado'      => $_GET['estado'] ?? null,
                'nombre'      => $_GET['nombre'] ?? null,
                'fecha_desde' => $_GET['fecha_desde'] ?? null,
                'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
            ];

            $reservas = $this->service->listarReservas($filtros);
            $this->jsonResponse(['status' => 'success', 'data' => $reservas], 200);
        } catch (Throwable $e) {
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): void
    {
        try {
            $reserva = $this->service->obtenerDetalle($id);

            if (!$reserva) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Reserva no encontrada.'], 404);
                return;
            }

            $this->jsonResponse(['status' => 'success', 'data' => $reserva], 200);
        } catch (Throwable $e) {
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);

            if (!is_array($body)) {
                $body = $_POST;
            }

            $nuevaReserva = $this->service->crearReserva($body);
            $this->jsonResponse(['status' => 'success', 'data' => $nuevaReserva], 201);
        } catch (InvalidArgumentException $e) {
            $codigo = $e->getCode() ?: 400;
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $codigo);
        } catch (Throwable $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function updateEstado(int $id): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);
            $nuevoEstado = $body['estado'] ?? null;

            if (!$nuevoEstado) {
                $this->jsonResponse(['status' => 'error', 'message' => "El campo 'estado' es obligatorio."], 400);
                return;
            }

            $reservaActualizada = $this->service->cambiarEstado($id, (string)$nuevoEstado);
            $this->jsonResponse(['status' => 'success', 'data' => $reservaActualizada], 200);
        } catch (InvalidArgumentException $e) {
            $codigo = $e->getCode() === 404 ? 404 : 400;
            $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], $codigo);
        } catch (Throwable $e) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}