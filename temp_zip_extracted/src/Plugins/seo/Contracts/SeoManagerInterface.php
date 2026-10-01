<?php

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
}
