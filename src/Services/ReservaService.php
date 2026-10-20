<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReservaRepository;
use InvalidArgumentException;
use PDO;

class ReservaService
{
    private ReservaRepository $repository;
    private PDO $db;

    public function __construct(ReservaRepository $repository, PDO $db)
    {
        $this->repository = $repository;
        $this->db = $db;
    }

    

    public function listarReservas(array $filtros): array
    {
        return $this->repository->findAll($filtros);
    }

    public function obtenerDetalle(int $id): ?array
    {
        $reserva = $this->repository->findById($id);
        if (!$reserva) {
            return null;
        }

        $reserva['eventos'] = $this->repository->findEventosByReservaId($id);
        return $reserva;
    }

    private function normalizarFecha(string $fecha): string
    {
        // 1. Si viene en formato YYYY-MM-DD (navegador HTML / MySQL)
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if ($d && $d->format('Y-m-d') === $fecha) {
            return $fecha;
        }

        // 2. Si viene en formato DD-MM-YYYY (tests unitarios)
        $d = \DateTime::createFromFormat('d-m-Y', $fecha);
        if ($d && $d->format('d-m-Y') === $fecha) {
            return $d->format('Y-m-d');
        }

        throw new InvalidArgumentException("Las fechas deben tener el formato DD-MM-YYYY o YYYY-MM-DD.", 400);
    }

    public function crearReserva(array $data): array
    {
        // Normaliza y valida datos antes de comenzar la transacción
        $data = $this->validarDatosCreacion($data);

        $this->db->beginTransaction();

        try {
            $nuevoId = $this->repository->create($data);

            $this->repository->addEvento(
                $nuevoId,
                'creada',
                'Reserva registrada con estado pendiente para ' . $data['nombre_huesped'] . '.'
            );

            $this->db->commit();

            return $this->obtenerDetalle($nuevoId);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        $reserva = $this->repository->findById($id);
        if (!$reserva) {
            throw new InvalidArgumentException("La reserva no existe.", 404);
        }

        $estadosPermitidos = ['confirmada', 'cancelada'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException("Estado no permitido. Debe ser 'confirmada' o 'cancelada'.", 400);
        }

        if ($reserva['estado'] === $nuevoEstado) {
            throw new InvalidArgumentException("La reserva ya se encuentra en estado '{$nuevoEstado}'.", 400);
        }

        if ($reserva['estado'] === 'cancelada') {
            throw new InvalidArgumentException("Una reserva cancelada no puede cambiar de estado.", 400);
        }

        $this->db->beginTransaction();

        try {
            $this->repository->updateEstado($id, $nuevoEstado);

            $descripcion = $nuevoEstado === 'confirmada'
                ? 'Reserva confirmada tras validación de disponibilidad/pago.'
                : 'Reserva cancelada por el usuario o administrador.';

            $this->repository->addEvento($id, $nuevoEstado, $descripcion);

            $this->db->commit();

            return $this->obtenerDetalle($id);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validarDatosCreacion(array $data): array
    {
        $camposObligatorios = [
            'nombre_huesped',
            'email_huesped',
            'nombre_alojamiento',
            'fecha_entrada',
            'fecha_salida',
            'importe'
        ];

        foreach ($camposObligatorios as $campo) {
            if (empty($data[$campo])) {
                throw new InvalidArgumentException("El campo '{$campo}' es obligatorio.", 400);
            }
        }

        if (!filter_var($data['email_huesped'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("El email proporcionado no es válido.", 400);
        }

        if (!is_numeric($data['importe']) || (float)$data['importe'] <= 0) {
            throw new InvalidArgumentException("El importe debe ser un número positivo.", 400);
        }

        // Normalizar fechas aceptando tanto Y-m-d como d-m-Y
        $fechaEntrada = $this->normalizarFecha((string)$data['fecha_entrada']);
        $fechaSalida  = $this->normalizarFecha((string)$data['fecha_salida']);

        if ($fechaSalida <= $fechaEntrada) {
            throw new InvalidArgumentException("La fecha de salida debe ser posterior a la fecha de entrada.", 400);
        }

        // Devolvemos los datos con las fechas listas para MySQL (YYYY-MM-DD)
        $data['fecha_entrada'] = $fechaEntrada;
        $data['fecha_salida']  = $fechaSalida;

        return $data;
    }
}