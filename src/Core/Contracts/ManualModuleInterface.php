<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato oficial do Domain-System OS para Módulos de Instrução auto-descobertos.
 *
 * Qualquer Plugin (Ring 3) ou SystemApp (Ring 0) que possua uma pasta `manual/`
 * e retorne uma implementação desta interface em `manual/module.php` terá seu
 * guia/tutorial carregado automaticamente no Manual de Instruções do Cockpit (/admin/manual).
 */
interface ManualModuleInterface
{
    /**
     * Identificador único do módulo de manual (ex: 'academy', 'seo').
     */
    public function getId(): string;

    /**
     * Título principal exibido no índice rápido e no cabeçalho do capítulo.
     */
    public function getTitle(): string;

    /**
     * Resumo curto exibido no card do índice rápido.
     */
    public function getSubtitle(): string;

    /**
     * Etiqueta ou versão do módulo (ex: 'Plugin LMS • v2.3.0').
     */
    public function getBadge(): string;

    /**
     * Cor de destaque em hexadecimal ou variável CSS (ex: '#a855f7' ou '#45f3ff').
     */
    public function getAccentColor(): string;

    /**
     * Rota administrativa opcional para o botão de atalho (ex: '/admin/academy').
     */
    public function getAdminUrl(): ?string;

    /**
     * Renderiza o conteúdo HTML didático do manual (tutorial, arquitetura, exemplos).
     */
    public function renderContent(string $baseUrl = ''): string;

    /**
     * Lista de itens interativos de validação (QA Checklist) integrados à barra de progresso do Cockpit.
     * Formato: [['id' => 'academy-block-editor', 'label' => 'Descrição do teste...'], ...]
     *
     * @return array<int, array{id: string, label: string}>
     */
    public function getChecklistItems(): array;
}
