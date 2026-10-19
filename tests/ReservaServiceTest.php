<?php

declare(strict_types=1);

namespace Tests;

use App\Repositories\ReservaRepository;
use App\Services\ReservaService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class ReservaServiceTest extends TestCase
{
    private $repositoryMock;
    private $pdoMock;
    private ReservaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = $this->createMock(ReservaRepository::class);
        $this->pdoMock = $this->createMock(PDO::class);

        $this->service = new ReservaService($this->repositoryMock, $this->pdoMock);
    }

    /**
     * Comprueba que rechaza la creación si la fecha de salida es anterior o igual a la de entrada
     */
    public function testLanzaExcepcionSiFechaSalidaEsAnteriorAEntrada(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La fecha de salida debe ser posterior a la fecha de entrada.');

        $datosInvalidos = [
            'nombre_huesped'     => 'Carlos García',
            'email_huesped'      => 'carlos@example.com',
            'nombre_alojamiento' => 'Casa Rural',
            'fecha_entrada'      => '10-12-2026',
            'fecha_salida'       => '05-12-2026', // Formato DD-MM-YYYY con salida anterior a entrada
            'importe'            => 150.00
        ];

        $this->service->crearReserva($datosInvalidos);
    }

    /**
     * Comprueba que rechaza emails con sintaxis no válida
     */
    public function testLanzaExcepcionSiEmailEsInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El email proporcionado no es válido.');

        $datosInvalidos = [
            'nombre_huesped'     => 'Carlos García',
            'email_huesped'      => 'correo-no-valido',
            'nombre_alojamiento' => 'Casa Rural',
            'fecha_entrada'      => '01-12-2026',
            'fecha_salida'       => '05-12-2026',
            'importe'            => 150.00
        ];

        $this->service->crearReserva($datosInvalidos);
    }

    /**
     * Comprueba que rechaza importes menores o iguales a cero
     */
    public function testLanzaExcepcionSiImporteEsCeroONegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El importe debe ser un número positivo.');

        $datosInvalidos = [
            'nombre_huesped'     => 'Carlos García',
            'email_huesped'      => 'carlos@example.com',
            'nombre_alojamiento' => 'Casa Rural',
            'fecha_entrada'      => '01-12-2026',
            'fecha_salida'       => '05-12-2026',
            'importe'            => -20.00
        ];

        $this->service->crearReserva($datosInvalidos);
    }

    /**
     * Comprueba que una reserva cancelada no permite volver a cambiar de estado
     */
    public function testLanzaExcepcionSiSeIntentaCambiarEstadoDeUnaReservaCancelada(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Una reserva cancelada no puede cambiar de estado.');

        $this->repositoryMock->method('findById')->willReturn([
            'id' => 5,
            'estado' => 'cancelada'
        ]);

        $this->service->cambiarEstado(5, 'confirmada');
    }

    /**
     * Comprueba que solo se admiten los estados válidos (confirmada o cancelada)
     */
    public function testLanzaExcepcionSiEstadoNoEstaPermitido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Estado no permitido. Debe ser 'confirmada' o 'cancelada'.");

        $this->repositoryMock->method('findById')->willReturn([
            'id' => 2,
            'estado' => 'pendiente'
        ]);

        $this->service->cambiarEstado(2, 'estado_inventado');
    }
}