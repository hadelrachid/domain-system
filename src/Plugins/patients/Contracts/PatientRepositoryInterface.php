<?php

namespace DomainSystem\Plugins\patients\Contracts;

interface PatientRepositoryInterface
{
    /**
     * Retorna todos os pacientes cadastrados.
     * Pode receber limit/offset no futuro.
     */
    public function findAll(): array;

    /**
     * Retorna os últimos X pacientes cadastrados.
     */
    public function findLatest(int $limit): array;

    /**
     * Busca um paciente pelo seu ID único.
     */
    public function findById(int $id): ?array;

    /**
     * Salva um novo paciente. Lança exceção em caso de CPF duplicado.
     */
    public function save(array $data): void;

    /**
     * Atualiza os dados de um paciente existente.
     */
    public function update(int $id, array $data): void;

    /**
     * Exclui um paciente pelo ID.
     */
    public function delete(int $id): void;
}
