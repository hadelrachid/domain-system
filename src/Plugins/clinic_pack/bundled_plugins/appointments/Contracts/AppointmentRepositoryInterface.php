<?php

namespace DomainSystem\Plugins\appointments\Contracts;

interface AppointmentRepositoryInterface
{
    public function getPendingQueue(?string $doctorId = null): array;
    public function getHistory(?string $doctorId = null, string $searchQuery = ''): array;
    public function createAppointment(array $data): void;
    public function updateStatus(int $id, string $status): void;
    public function isSlotOccupied(int $doctorId, string $date, string $time): bool;
    public function getBookedSlots(int $doctorId, string $date, array $candidateSlots): array;
    public function getConfirmedAppointmentsByDoctor(int $doctorId): array;
    public function getAllActiveAppointments(): array;
}
