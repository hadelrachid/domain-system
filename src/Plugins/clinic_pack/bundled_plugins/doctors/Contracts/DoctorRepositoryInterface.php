<?php

namespace DomainSystem\Plugins\doctors\Contracts;

interface DoctorRepositoryInterface
{
    /**
     * Retorna todos os médicos cadastrados.
     */
    public function findAll(): array;

    /**
     * Busca um médico pelo seu ID único.
     */
    public function findById(int $id): ?array;

    /**
     * Salva um novo médico.
     */
    public function save(array $data): void;

    /**
     * Atualiza os dados de um médico existente.
     */
    public function update(int $id, array $data): void;

    /**
     * Exclui um médico pelo ID.
     */
    public function delete(int $id): void;
}
