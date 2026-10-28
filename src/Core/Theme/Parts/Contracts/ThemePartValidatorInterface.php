<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

use InvalidArgumentException;

/**
 * Contrato para validação estrutural e de segurança de documentos visuais (ThemePart) antes da persistência.
 */
interface ThemePartValidatorInterface
{
    /**
     * Valida a estrutura completa de um payload de ThemePart.
     *
     * @param array<string, mixed> $payload
     * @throws InvalidArgumentException Quando qualquer regra estrutural, limite ou contrato de bloco é violado.
     */
    public function validate(array $payload): void;
}
