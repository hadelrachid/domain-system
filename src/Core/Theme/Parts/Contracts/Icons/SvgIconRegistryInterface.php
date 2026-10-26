<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Icons;

/**
 * Contrato para o Catálogo e Registro de Ícones Vetoriais SVG (SOLID - OCP / Ring 0).
 * 
 * Permite renderizar vetores SVG puros inline com personalização de cores,
 * atributos e estilos, sem dependência de bibliotecas externas de fontes (FontAwesome, etc.).
 */
interface SvgIconRegistryInterface
{
    /**
     * Registra uma nova definição vetorial no catálogo.
     *
     * @param string $name Identificador do ícone (ex: 'whatsapp', 'instagram')
     * @param string $viewBox Dimensões do viewBox (ex: '0 0 24 24')
     * @param string $pathData Conteúdo interno do SVG (<path d="..."/>)
     * @param string|null $brandColor Cor hexadecimal oficial da marca (ex: '#25D366')
     * @param string|null $label Rótulo legível (ex: 'WhatsApp')
     */
    public function register(
        string $name,
        string $viewBox,
        string $pathData,
        ?string $brandColor = null,
        ?string $label = null
    ): void;

    /**
     * Renderiza o markup completo da tag <svg> para o ícone solicitado.
     *
     * @param string $name Nome do ícone registrado
     * @param array<string, string> $attributes Atributos HTML extras (width, height, fill, class, style)
     * @return string|null Retorna a tag <svg> compilada ou null se o ícone não existir
     */
    public function getSvg(string $name, array $attributes = []): ?string;

    /**
     * Retorna a cor oficial da marca associada ao ícone (se houver).
     */
    public function getBrandColor(string $name): ?string;

    /**
     * Retorna o rótulo legível do ícone.
     */
    public function getLabel(string $name): ?string;

    /**
     * Verifica se o ícone está registrado.
     */
    public function has(string $name): bool;

    /**
     * Retorna todos os dados brutos registrados.
     * @return array<string, array<string, mixed>>
     */
    public function all(): array;

    /**
     * Retorna lista associativa simplificada [ 'name' => 'Label' ] para seletores de UI.
     * @return array<string, string>
     */
    public function getAvailableIcons(): array;
}
