<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReservaService;
use Twig\Environment;

class ReservaWebController
{
    private ReservaService $service;
    private Environment $twig;
    private string $baseUrl;

    public function __construct(ReservaService $service, Environment $twig, string $baseUrl)
    {
        $this->service = $service;
        $this->twig = $twig;
        $this->baseUrl = $baseUrl;
    }

    public function index(): void
    {
        $filtros = [
            'estado'      => $_GET['estado'] ?? '',
            'nombre'      => $_GET['nombre'] ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        ];

        $reservas = $this->service->listarReservas($filtros);

        echo $this->twig->render('reservas/index.html.twig', [
            'reservas' => $reservas,
            'filtros'  => $filtros,
            'baseUrl'  => $this->baseUrl,
        ]);
    }

    public function show(int $id): void
    {
        $reserva = $this->service->obtenerDetalle($id);

        if (!$reserva) {
            http_response_code(404);
            echo "<h1>Reserva no encontrada</h1><p><a href='{$this->baseUrl}/'>Volver al inicio</a></p>";
            return;
        }

        echo $this->twig->render('reservas/show.html.twig', [
            'reserva' => $reserva,
            'baseUrl' => $this->baseUrl,
        ]);
    }
}