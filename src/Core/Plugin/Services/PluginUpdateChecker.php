<?php

declare(strict_types=1);

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\PluginUpdateCheckerInterface;
use DomainSystem\Core\Utils\Archive\ExtractorFactory;
use Throwable;

/**
 * Serviço de Verificação e Atualização Automática de Plugins Agregados (SRP / DIP).
 * Consulta o catálogo de releases no GitHub, notifica o sininho do Cockpit
 * e permite atualização atômica e segura com um clique (estilo WordPress).
 */
class PluginUpdateChecker implements PluginUpdateCheckerInterface
{
    public const DEFAULT_MARKETPLACE_URL = 'https://raw.githubusercontent.com/hadelrachid/domain-system-plugins/main/marketplace.json';
    public const CACHE_TTL_SECONDS = 43200; // 12 horas

    public function __construct(
        private readonly string $basePath,
        private readonly ?NotificationManagerInterface $notifications = null,
        private readonly string $marketplaceUrl = self::DEFAULT_MARKETPLACE_URL,
        private readonly int $cacheTtlSeconds = self::CACHE_TTL_SECONDS
    ) {
    }

    public function checkForUpdates(bool $forceRefresh = false): array
    {
        $marketplace = $this->fetchMarketplace($forceRefresh);
        if ($marketplace === null || empty($marketplace['plugins']) || !is_array($marketplace['plugins'])) {
            return [];
        }

        $pluginsPath = $this->basePath . '/src/Plugins';
        if (!is_dir($pluginsPath)) {
            return [];
        }

        $results = [];
        $directories = glob($pluginsPath . '/*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $dir) {
            $manifestFile = $dir . '/plugin.json';
            if (!file_exists($manifestFile)) {
                continue;
            }

            $metadata = json_decode((string)file_get_contents($manifestFile), true);
            if (!is_array($metadata)) {
                continue;
            }

            // Ignora componentes de Composição (Core / Ring 0) - são atualizados pelo auto-updater do SO
            if (!empty($metadata['core'])) {
                continue;
            }

            $slug = basename($dir);
            $localVersion = (string)($metadata['version'] ?? '0.0.0');
            $name = (string)($metadata['name'] ?? $slug);

            $remoteInfo = $marketplace['plugins'][$slug]
                ?? ($marketplace['plugins'][$name] ?? null);

            if ($remoteInfo === null || !is_array($remoteInfo)) {
                continue;
            }

            $remoteVersion = (string)($remoteInfo['version'] ?? '0.0.0');
            $hasUpdate = version_compare($remoteVersion, $localVersion, '>');

            $results[$slug] = [
                'slug'              => $slug,
                'name'              => $name,
                'installed_version' => $localVersion,
                'latest_version'    => $remoteVersion,
                'new_version'       => $remoteVersion,
                'has_update'        => $hasUpdate,
                'download_url'      => (string)($remoteInfo['download_url'] ?? ''),
                'sha256'            => (string)($remoteInfo['sha256'] ?? ''),
                'size_bytes'        => (int)($remoteInfo['size_bytes'] ?? 0),
                'description'       => (string)($remoteInfo['description'] ?? ''),
            ];
        }

        return $results;
    }

    public function getPendingUpdatesCount(bool $forceRefresh = false): int
    {
        $all = $this->checkForUpdates($forceRefresh);
        $pending = array_filter($all, fn($item) => !empty($item['has_update']));
        return count($pending);
    }

    public function notifyIfUpdatesAvailable(bool $forceRefresh = false): void
    {
        if ($this->notifications === null) {
            return;
        }

        $all = $this->checkForUpdates($forceRefresh);
        $pending = array_filter($all, fn($item) => !empty($item['has_update']));

        if (empty($pending)) {
            return;
        }

        // Evita notificar repetidamente na mesma janela de 6 horas
        $notifLockFile = $this->basePath . '/temp/cache/last_plugin_update_notif.txt';
        if (!$forceRefresh && file_exists($notifLockFile)) {
            $lastTime = (int)file_get_contents($notifLockFile);
            if ((time() - $lastTime) < 21600) {
                return;
            }
        }

        $count = count($pending);
        $names = array_map(fn($p) => $p['name'] . ' (v' . $p['latest_version'] . ')', $pending);
        $pluginList = implode(', ', array_slice($names, 0, 3));
        if ($count > 3) {
            $pluginList .= ' e mais ' . ($count - 3);
        }

        $message = "🔔 Atualização disponível para {$count} plugin(s) ({$pluginList}). Acesse a aba Plugins para atualizar com 1 clique.";
        $this->notifications->push($message, 'info', 'Plugin Updater', ['url' => '/admin/plugins']);

        $dir = dirname($notifLockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($notifLockFile, (string)time());
    }

    public function updatePluginFromRemote(string $pluginSlug): array
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($pluginSlug));
        if ($slug === '') {
            return ['success' => false, 'message' => 'Identificador de plugin inválido.'];
        }

        $pluginsPath = $this->basePath . '/src/Plugins';
        $targetDir = $pluginsPath . '/' . $slug;

        // Validação de segurança: proibir atualização de SystemApps ou plugins Core
        if (is_dir($this->basePath . '/src/SystemApps/' . $slug)) {
            return ['success' => false, 'message' => 'Segurança do Sistema: Componentes de Núcleo (SystemApps) não podem ser atualizados via repositório de plugins.'];
        }

        if (file_exists($targetDir . '/plugin.json')) {
            $localMeta = json_decode((string)file_get_contents($targetDir . '/plugin.json'), true);
            if (!empty($localMeta['core'])) {
                return ['success' => false, 'message' => 'Segurança do Sistema: Módulos Core vitais não podem ser atualizados individualmente.'];
            }
        }

        $marketplace = $this->fetchMarketplace(true);
        $pluginInfo = $marketplace['plugins'][$slug] ?? null;

        if ($pluginInfo === null || empty($pluginInfo['download_url'])) {
            return ['success' => false, 'message' => "O pacote do plugin '{$slug}' não foi localizado no repositório de plugins do GitHub."];
        }

        $localVersion = (string)($localMeta['version'] ?? '0.0.0');
        $remoteVersion = (string)($pluginInfo['version'] ?? '0.0.0');
        $pluginName = (string)($pluginInfo['name'] ?? $slug);

        if (version_compare($remoteVersion, $localVersion, '<=')) {
            return [
                'success' => false,
                'message' => "O plugin '{$pluginName}' já está na versão mais recente (v{$localVersion}). Nenhuma atualização disponível."
            ];
        }

        $downloadUrl = (string)$pluginInfo['download_url'];
        $expectedSha = (string)($pluginInfo['sha256'] ?? '');
        $newVersion = (string)($pluginInfo['version'] ?? 'N/A');

        // Prepara diretório de download
        $downloadDir = $this->basePath . '/temp/downloads';
        if (!is_dir($downloadDir)) {
            @mkdir($downloadDir, 0755, true);
        }
        $tmpZip = $downloadDir . '/' . $slug . '-update-' . time() . '.zip';

        try {
            // 1. Download do Pacote ZIP da Release do GitHub
            $this->downloadFile($downloadUrl, $tmpZip);

            if (!file_exists($tmpZip) || filesize($tmpZip) < 100) {
                throw new \RuntimeException('O download do pacote falhou ou retornou arquivo vazio.');
            }

            // 2. Validação Criptográfica de Integridade (SHA-256)
            if ($expectedSha !== '') {
                $actualSha = hash_file('sha256', $tmpZip);
                if (!hash_equals(strtolower($expectedSha), strtolower((string)$actualSha))) {
                    throw new \RuntimeException("Falha de integridade: SHA-256 esperado ({$expectedSha}) diverge do arquivo recebido ({$actualSha}).");
                }
            }

            // 3. Extração Segura em Quarentena e Substituição Atômica
            $extractor = ExtractorFactory::create();
            $extractedFolder = $extractor->extract($tmpZip, $pluginsPath);

            // 4. Limpa caches locais
            $this->clearCache();

            // 5. Notifica o sistema e o sininho
            if ($this->notifications !== null) {
                $this->notifications->push(
                    "✅ O plugin '{$pluginName}' foi atualizado com sucesso para a versão v{$newVersion}!",
                    'success',
                    'Plugin Updater'
                );
            }

            return [
                'success'     => true,
                'message'     => "O plugin '{$pluginName}' foi atualizado com sucesso para a versão v{$newVersion}!",
                'version'     => $newVersion,
                'new_version' => $newVersion,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Falha ao atualizar plugin: ' . $e->getMessage(),
            ];
        } finally {
            if (file_exists($tmpZip)) {
                @unlink($tmpZip);
            }
        }
    }

    public function clearCache(): void
    {
        $cacheFile = $this->basePath . '/temp/cache/marketplace_plugins.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
        $notifLockFile = $this->basePath . '/temp/cache/last_plugin_update_notif.txt';
        if (file_exists($notifLockFile)) {
            @unlink($notifLockFile);
        }
    }

    private function fetchMarketplace(bool $forceRefresh = false): ?array
    {
        $cacheFile = $this->basePath . '/temp/cache/marketplace_plugins.json';

        if (!$forceRefresh && file_exists($cacheFile)) {
            $mtime = filemtime($cacheFile);
            if ((time() - $mtime) < $this->cacheTtlSeconds) {
                $cached = json_decode((string)file_get_contents($cacheFile), true);
                if (is_array($cached) && !empty($cached['plugins'])) {
                    return $cached;
                }
            }
        }

        $content = $this->httpGet($this->marketplaceUrl);
        if ($content === null) {
            // Se falhar a requisição remota, tenta usar o cache antigo mesmo expirado
            if (file_exists($cacheFile)) {
                return json_decode((string)file_get_contents($cacheFile), true);
            }
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || empty($data['plugins'])) {
            return null;
        }

        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($cacheFile, $content);

        return $data;
    }

    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_USERAGENT      => 'DomainSystem-Cockpit/2.8',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status === 200 && is_string($body) && $body !== '') {
                return $body;
            }
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 12,
                'user_agent' => 'DomainSystem-Cockpit/2.8',
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) && $body !== '' ? $body : null;
    }

    private function downloadFile(string $url, string $destPath): void
    {
        if (function_exists('curl_init')) {
            $fp = fopen($destPath, 'w+');
            if ($fp === false) {
                throw new \RuntimeException('Não foi possível criar o arquivo de destino temporário.');
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 90,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_USERAGENT      => 'DomainSystem-Cockpit/2.8',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $success = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            if ($success && ($status === 200 || $status === 302)) {
                return;
            }
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout'         => 90,
                'user_agent'      => 'DomainSystem-Cockpit/2.8',
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $ctx);
        if (!is_string($content) || $content === '') {
            throw new \RuntimeException("Falha ao baixar o arquivo da URL: {$url}");
        }

        file_put_contents($destPath, $content);
    }
}
