<?php

namespace DomainSystem\Plugins\doctors\Providers;

use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;
use DomainSystem\Plugins\Database\QueryBuilder;

class AppointmentDoctorProvider implements DoctorReaderInterface
{
    private QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        // Nota: No futuro, Doctors também deve ter um Repository Pattern
        // Por enquanto, resolvemos o DIP lendo via QueryBuilder internamente.
        $this->db = $db;
    }

    public function getAllDoctors(): array
    {
        return $this->db->table('doctors')->get();
    }

    public function getDoctorsMap(): array
    {
        $doctors = $this->getAllDoctors();
        $map = [];
        foreach ($doctors as $d) {
            $map[$d['id']] = $d;
        }
        return $map;
    }

    public function getDoctorName(int $id): string
    {
        $result = $this->db->table('doctors')->where('id', '=', $id)->get();
        return !empty($result) ? $result[0]['name'] : 'Desconhecido';
    }
}
