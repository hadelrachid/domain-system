<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato de Verificação e Atualização Remota de Plugins Agregados (SOLID / DIP).
 * Permite que o Cockpit consulte releases no GitHub, emita alertas no sininho
 * e instale novas versões de plugins individuais de forma segura.
 */
interface PluginUpdateCheckerInterface
{
    /**
     * Consulta o catálogo remoto de plugins e compara com os plugins locais instalados.
     * 
     * @param bool $forceRefresh Se verdadeiro, ignora o cache local temporário.
     * @return array<string, array{
     *     slug: string,
     *     name: string,
     *     installed_version: string,
     *     latest_version: string,
     *     has_update: bool,
     *     download_url: string,
     *     sha256: string,
     *     size_bytes: int,
     *     description: string
     * }>
     */
    public function checkForUpdates(bool $forceRefresh = false): array;

    /**
     * Retorna a quantidade de plugins agregados com atualização pendente.
     */
    public function getPendingUpdatesCount(bool $forceRefresh = false): int;

    /**
     * Notifica o sininho de notificações do Cockpit caso haja atualizações disponíveis.
     */
    public function notifyIfUpdatesAvailable(bool $forceRefresh = false): void;

    /**
     * Baixa o pacote .zip do plugin a partir da release remota no GitHub, valida o hash SHA-256 e instala com segurança.
     * 
     * @param string $pluginSlug Slug do plugin (ex: 'domain_drop')
     * @return array{success: bool, message: string, new_version?: string}
     */
    public function updatePluginFromRemote(string $pluginSlug): array;
}
