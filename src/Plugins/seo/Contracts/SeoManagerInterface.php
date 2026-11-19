<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo\Contracts;

interface SeoManagerInterface
{
    /**
     * Define o título principal da página.
     */
    public function setTitle(string $title): self;

    /**
     * Retorna o título atual.
     */
    public function getTitle(): string;

    /**
     * Define a descrição meta da página.
     */
    public function setDescription(string $description): self;

    /**
     * Define a URL canônica para evitar conteúdo duplicado.
     */
    public function setCanonical(string $url): self;

    /**
     * Define o ícone (favicon) do site.
     */
    public function setFavicon(string $url): self;

    /**
     * Adiciona tags meta customizadas (OpenGraph, Twitter Cards, etc).
     */
    public function addMeta(string $name, string $content, string $type = 'name'): self;

    /**
     * Gera o bloco de HTML de todas as tags <meta> configuradas.
     */
    public function generateTags(): string;

    /**
     * Enfileira um arquivo CSS para minificação e bundle unificado pelo AssetPipeline.
     */
    public function enqueueCss(string $absolutePath): self;

    /**
     * Enfileira um arquivo JavaScript para minificação e bundle unificado com <script defer>.
     */
    public function enqueueJs(string $absolutePath): self;

    /**
     * Retorna a tag <link rel="stylesheet"> com o bundle minificado e hash de cache busting.
     */
    public function renderCssTags(): string;

    /**
     * Retorna a tag <script defer> com o bundle minificado e hash de cache busting.
     */
    public function renderJsTags(): string;
}
