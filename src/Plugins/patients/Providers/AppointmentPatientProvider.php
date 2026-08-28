<?php

namespace DomainSystem\Plugins\patients\Providers;

use DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface;
use DomainSystem\Plugins\patients\Contracts\PatientRepositoryInterface;

class AppointmentPatientProvider implements PatientReaderInterface
{
    private PatientRepositoryInterface $repository;

    public function __construct(PatientRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllPatients(): array
    {
        return $this->repository->findAll();
    }

    public function getPatientsMap(): array
    {
        $patients = $this->repository->findAll();
        $map = [];
        foreach ($patients as $p) {
            $map[$p['id']] = $p;
        }
        return $map;
    }

    public function getPatientData(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function updatePatientData(int $id, array $data): void
    {
        $this->repository->update($id, $data);
    }

    public function findPatientByPhone(string $phone): ?array
    {
        $patients = $this->repository->findAll();
        foreach ($patients as $p) {
            if (($p['phone'] ?? '') === $phone) {
                return $p;
            }
        }
        return null;
    }

    public function createPatient(string $name, string $phone): int
    {
        return $this->repository->save([
            'name' => $name,
            'phone' => $phone,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
