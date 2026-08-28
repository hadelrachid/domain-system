<?php

namespace DomainSystem\Plugins\doctors\Providers;

use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;

class AppointmentDoctorProvider implements DoctorReaderInterface
{
    private DoctorRepositoryInterface $repository;

    public function __construct(DoctorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllDoctors(): array
    {
        return $this->repository->findAll();
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
        $doctor = $this->repository->findById($id);
        return $doctor ? $doctor['name'] : 'Desconhecido';
    }
}
