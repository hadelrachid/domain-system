<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Parte Estrutural de Tema (Header, Footer, Seção ou Template Part).
 * Compõe LayoutContainerInterface, SectionedPartInterface e MobileToggleAwareInterface (SOLID - ISP).
 */
interface ThemePartInterface extends LayoutContainerInterface, SectionedPartInterface, MobileToggleAwareInterface
{
    public const TYPE_HEADER  = 'header';
    public const TYPE_FOOTER  = 'footer';
    public const TYPE_SECTION = 'section';

    /** Identificador único (slug ou hash) */
    public function getId(): string;

    /** Tipo da parte: 'header', 'footer' ou 'section' */
    public function getType(): string;

    /** Título ou nome de exibição da parte */
    public function getTitle(): string;

    /** Se é a parte padrão de fallback global */
    public function isDefault(): bool;

    public function getCreatedAt(): int;

    public function getUpdatedAt(): int;

    /** Serializa a parte para array compatível com JSON */
    public function toArray(): array;
}
