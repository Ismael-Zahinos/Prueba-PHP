<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ReservaRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Listado con filtros combinables: estado, rango de fechas y búsqueda por nombre de huésped
     */
    public function findAll(array $filtros = []): array
    {
        $sql = "SELECT id, nombre_huesped, email_huesped, nombre_alojamiento, 
                       fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion 
                FROM reserva WHERE 1=1";
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND estado = :estado";
            $params['estado'] = $filtros['estado'];
        }

        if (!empty($filtros['nombre'])) {
            $sql .= " AND nombre_huesped LIKE :nombre";
            $params['nombre'] = '%' . $filtros['nombre'] . '%';
        }

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND fecha_entrada >= :fecha_desde";
            $params['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND fecha_entrada <= :fecha_hasta";
            $params['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $sql .= " ORDER BY fecha_creacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Obtener reserva por su ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reserva WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $reserva = $stmt->fetch();

        return $reserva ?: null;
    }

    /**
     * Obtener historial de eventos de una reserva
     */
    public function findEventosByReservaId(int $reservaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, reserva_id, tipo, descripcion, fecha_creacion 
             FROM reserva_evento 
             WHERE reserva_id = :reserva_id 
             ORDER BY fecha_creacion ASC"
        );
        $stmt->execute(['reserva_id' => $reservaId]);

        return $stmt->fetchAll();
    }

    /**
     * Insertar nueva reserva
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO reserva 
                (nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas) 
             VALUES 
                (:nombre_huesped, :email_huesped, :nombre_alojamiento, :fecha_entrada, :fecha_salida, :estado, :importe, :notas)"
        );

        $stmt->execute([
            'nombre_huesped'     => $data['nombre_huesped'],
            'email_huesped'      => $data['email_huesped'],
            'nombre_alojamiento' => $data['nombre_alojamiento'],
            'fecha_entrada'      => $data['fecha_entrada'],
            'fecha_salida'       => $data['fecha_salida'],
            'estado'             => $data['estado'] ?? 'pendiente',
            'importe'            => $data['importe'],
            'notas'              => $data['notas'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar estado de una reserva
     */
    public function updateEstado(int $id, string $nuevoEstado): bool
    {
        $stmt = $this->db->prepare("UPDATE reserva SET estado = :estado WHERE id = :id");
        return $stmt->execute([
            'estado' => $nuevoEstado,
            'id'     => $id
        ]);
    }

    /**
     * Insertar evento en el historial
     */
    public function addEvento(int $reservaId, string $tipo, string $descripcion): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO reserva_evento (reserva_id, tipo, descripcion) 
             VALUES (:reserva_id, :tipo, :descripcion)"
        );

        return $stmt->execute([
            'reserva_id'  => $reservaId,
            'tipo'        => $tipo,
            'descripcion' => $descripcion
        ]);
    }
}