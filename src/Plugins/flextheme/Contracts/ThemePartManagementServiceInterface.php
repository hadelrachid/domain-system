<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Contracts;

use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;

/**
 * Contrato do serviço de aplicação para criação, validação, persistência e atribuição
 * de partes visuais de tema no Flex Builder (SOLID - SRP / DIP / ISP).
 */
interface ThemePartManagementServiceInterface
{
    /**
     * Constrói uma nova ThemePart com o preset e blocos iniciais recomendados para o tipo.
     */
    public function createDefaultPart(string $requestedType): ThemePartInterface;

    /**
     * Valida estruturalmente o documento, salva a ThemePart e aplica atribuições verificadas
     * com compensação automática caso uma atribuição posterior falhe.
     *
     * @param array<string, mixed> $input
     */
    public function saveFromPayload(array $input): ThemePartInterface;

    /**
     * Processa uma solicitação de atribuição (tema <-> parte, global ou seção <-> template).
     *
     * @param array<string, mixed> $input
     */
    public function assignFromPayload(array $input): bool;

    /**
     * Exclui uma parte visual e limpa quaisquer referências órfãs nas associações.
     */
    public function deletePart(string $id): bool;
}
