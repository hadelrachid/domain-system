<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

use DomainSystem\Core\Contracts\ResponseInterface;

/**
 * Contrato para Renderização de Telas do Portal do Aluno (DIP / SOLID).
 * Isola a camada de apresentação do aluno de qualquer acoplamento com o painel administrativo.
 */
interface StudentViewRendererInterface
{
    /**
     * Renderiza uma view pública/aluno e retorna uma Response HTTP pura,
     * garantindo ausência total de casca (shell) administrativa.
     */
    public function render(string $viewName, array $data = []): ResponseInterface;
}
