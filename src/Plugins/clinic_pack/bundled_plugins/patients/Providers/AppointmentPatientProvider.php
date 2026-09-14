<?php

namespace DomainSystem\Plugins\patients\Providers;

use DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface;
use DomainSystem\Plugins\appointments\Contracts\PatientWriterInterface;
use DomainSystem\Plugins\appointments\Contracts\PatientFinderInterface;
use DomainSystem\Plugins\patients\Contracts\PatientRepositoryInterface;

class AppointmentPatientProvider implements PatientReaderInterface, PatientWriterInterface, PatientFinderInterface
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
        return $this->repository->findByPhone($phone);
    }

    public function createPatient(string $name, string $phone): int
    {
        return $this->repository->save([
            'name' => $name,
            'phone' => $phone,
            'cpf' => null, // LGPD: Opcional
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function findPatientByEmailOrPhone(string $email, string $phone): ?array
    {
        return $this->repository->findByEmailOrPhone($email, $phone);
    }

    public function createPatientFull(array $data): int
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['cpf']) || empty($data['cpf'])) {
            $data['cpf'] = null; // LGPD: Opcional
        }
        return $this->repository->save($data);
    }
}
