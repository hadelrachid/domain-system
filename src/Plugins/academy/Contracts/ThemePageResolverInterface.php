<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

interface ThemePageResolverInterface
{
    /**
     * Resolve o caminho absoluto no disco do tema público ativo.
     */
    public function resolveActiveThemePath(): string;

    /**
     * Lista todas as páginas (.php / .html) disponíveis dentro da pasta pages/ do tema ativo.
     *
     * @return array<int, string>
     */
    public function resolvePublicThemePages(): array;
}
