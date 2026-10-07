<?php
namespace DomainSystem\Plugins\flex_builder\src\Contracts;

/**
 * Interface obrigatória para qualquer componente visual (Widget/Bloco) do sistema.
 * Segue o Princípio de Inversão de Dependência (SOLID - DIP).
 */
interface VisualComponentInterface
{
    /**
     * Retorna o ID único do componente (ex: 'button', 'radio_player')
     */
    public function getComponentId(): string;

    /**
     * Retorna o nome amigável para exibição na Paleta Delphi
     */
    public function getName(): string;

    /**
     * Retorna o Schema (JSON/Array) definindo quais propriedades este componente aceita.
     * Ex: cores, tamanhos, urls.
     */
    public function getPropertiesSchema(): array;

    /**
     * Renderiza o HTML final baseando-se nas propriedades fornecidas pelo usuário/banco de dados.
     */
    public function render(array $properties = []): string;
}
