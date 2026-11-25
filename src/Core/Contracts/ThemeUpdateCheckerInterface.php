<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface ThemeUpdateCheckerInterface
{
    /**
     * Verifica atualizações remotas para os temas instalados.
     *
     * @param bool $forceRefresh Se true, ignora o cache local de marketplace
     * @return array<string, array{
     *     folder: string,
     *     name: string,
     *     installed_version: string,
     *     latest_version: string,
     *     new_version: string,
     *     has_update: bool,
     *     download_url: string,
     *     sha256: string,
     *     size_bytes: int,
     *     description: string
     * }>
     */
    public function checkForUpdates(bool $forceRefresh = false): array;

    /**
     * Retorna a quantidade de temas com atualizações pendentes.
     */
    public function getPendingUpdatesCount(bool $forceRefresh = false): int;

    /**
     * Notifica a central do Cockpit se houver temas com versão mais recente.
     */
    public function notifyIfUpdatesAvailable(bool $forceRefresh = false): void;

    /**
     * Executa o download, validação de integridade e extração atômica do tema.
     *
     * @return array{success: bool, message?: string, version?: string}
     */
    public function updateThemeFromRemote(string $themeFolder): array;

    /**
     * Limpa o cache local de temas remotos.
     */
    public function clearCache(): void;
}
