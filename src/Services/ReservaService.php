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

    public function crearReserva(array $data): array
    {
        $this->validarDatosCreacion($data);

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

    private function validarDatosCreacion(array $data): void
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

        $entrada = \DateTime::createFromFormat('d-m-Y', $data['fecha_entrada']);
        $salida = \DateTime::createFromFormat('d-m-Y', $data['fecha_salida']);

        if (!$entrada || !$salida) {
            throw new InvalidArgumentException("Las fechas deben tener el formato DD-MM-YYYY.", 400);
        }

        if ($salida <= $entrada) {
            throw new InvalidArgumentException("La fecha de salida debe ser posterior a la fecha de entrada.", 400);
        }
    }
}