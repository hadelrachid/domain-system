<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\ThemeUpdateCheckerInterface;
use DomainSystem\Core\Utils\Archive\ExtractorFactory;
use Throwable;

/**
 * Serviço de Verificação e Atualização Automática de Temas (SRP / DIP).
 * Consulta o catálogo de releases em domain-system-themes e direciona
 * o download diretamente para o repositório dedicado de cada tema (ex: hadelrachid/rachidd_store).
 */
class ThemeUpdateChecker implements ThemeUpdateCheckerInterface
{
    public const DEFAULT_MARKETPLACE_URL = 'https://raw.githubusercontent.com/hadelrachid/domain-system-themes/main/marketplace.json';
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
        if ($marketplace === null || empty($marketplace['themes']) || !is_array($marketplace['themes'])) {
            return [];
        }

        $directories = $this->discoverThemeDirectories();
        if (empty($directories)) {
            return [];
        }

        $results = [];

        foreach ($directories as $folder => $dir) {
            $manifestFile = $dir . '/theme.json';
            $localVersion = '1.0.0';
            $name = ucfirst($folder);
            $localMeta = null;

            if (file_exists($manifestFile)) {
                $localMeta = json_decode((string)file_get_contents($manifestFile), true);
                if (is_array($localMeta)) {
                    $localVersion = (string)($localMeta['version'] ?? '1.0.0');
                    $name = (string)($localMeta['name'] ?? $name);
                }
            }

            $remoteInfo = $marketplace['themes'][$folder]
                ?? ($marketplace['themes'][$name] ?? null);

            if ($remoteInfo === null || !is_array($remoteInfo)) {
                continue;
            }

            $remoteVersion = (string)($remoteInfo['version'] ?? '1.0.0');
            $hasUpdate = version_compare($remoteVersion, $localVersion, '>');
            $downloadUrl = $this->resolveDownloadUrl($folder, $remoteInfo, $localMeta);

            $results[$folder] = [
                'folder'            => $folder,
                'name'              => $name,
                'installed_version' => $localVersion,
                'latest_version'    => $remoteVersion,
                'new_version'       => $remoteVersion,
                'has_update'        => $hasUpdate,
                'download_url'      => $downloadUrl,
                'sha256'            => (string)($remoteInfo['sha256'] ?? ''),
                'size_bytes'        => (int)($remoteInfo['size_bytes'] ?? 0),
                'description'       => (string)($remoteInfo['description'] ?? ''),
                'repository'        => (string)($remoteInfo['repository'] ?? ($localMeta['repository'] ?? '')),
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
        $notifLockFile = $this->basePath . '/temp/cache/last_theme_update_notif.txt';
        if (!$forceRefresh && file_exists($notifLockFile)) {
            $lastTime = (int)file_get_contents($notifLockFile);
            if ((time() - $lastTime) < 21600) {
                return;
            }
        }

        $count = count($pending);
        $names = array_map(fn($t) => $t['name'] . ' (v' . $t['latest_version'] . ')', $pending);
        $themeList = implode(', ', array_slice($names, 0, 3));
        if ($count > 3) {
            $themeList .= ' e mais ' . ($count - 3);
        }

        $message = "🎨 Atualização de Tema disponível: {$themeList}. Acesse a aba Temas para atualizar com 1 clique.";
        $this->notifications->push($message, 'info', 'Theme Updater', ['url' => '/admin/themes']);

        $dir = dirname($notifLockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($notifLockFile, (string)time());
    }

    public function updateThemeFromRemote(string $themeFolder): array
    {
        $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($themeFolder));
        if ($folder === '') {
            return ['success' => false, 'message' => 'Identificador do tema inválido.'];
        }

        // Bloqueio de temas do núcleo do sistema
        if (in_array($folder, ['admin', 'manager', 'subscriber', 'user'], true)) {
            return ['success' => false, 'message' => 'Segurança do Sistema: Temas internos do sistema não podem ser modificados remotamente.'];
        }

        $themesPath = $this->basePath . '/themes';
        $targetDir = $themesPath . '/' . $folder;

        $marketplace = $this->fetchMarketplace(true);
        $themeInfo = $marketplace['themes'][$folder] ?? null;

        $localVersion = '1.0.0';
        $localMeta = null;
        if (file_exists($targetDir . '/theme.json')) {
            $localMeta = json_decode((string)file_get_contents($targetDir . '/theme.json'), true);
            $localVersion = (string)($localMeta['version'] ?? '1.0.0');
        } elseif (file_exists($this->basePath . '/src/Plugins/flextheme/themes/' . $folder . '/theme.json')) {
            $localMeta = json_decode((string)file_get_contents($this->basePath . '/src/Plugins/flextheme/themes/' . $folder . '/theme.json'), true);
            $localVersion = (string)($localMeta['version'] ?? '1.0.0');
        }

        if ($themeInfo === null && empty($localMeta['repository'])) {
            return ['success' => false, 'message' => "O tema '{$folder}' não foi localizado no catálogo oficial do GitHub."];
        }

        $remoteVersion = (string)($themeInfo['version'] ?? '1.0.0');
        $themeName = (string)($themeInfo['name'] ?? $folder);

        if ($themeInfo !== null && version_compare($remoteVersion, $localVersion, '<=')) {
            return [
                'success' => false,
                'message' => "O tema '{$themeName}' já está na versão mais recente (v{$localVersion}). Nenhuma atualização necessária."
            ];
        }

        $downloadUrl = $this->resolveDownloadUrl($folder, (array)($themeInfo ?? []), $localMeta);
        if (empty($downloadUrl)) {
            return ['success' => false, 'message' => "URL de download para o tema '{$folder}' não pôde ser resolvida."];
        }

        $expectedSha = (string)($themeInfo['sha256'] ?? '');

        // Prepara diretório de download
        $downloadDir = $this->basePath . '/temp/downloads';
        if (!is_dir($downloadDir)) {
            @mkdir($downloadDir, 0755, true);
        }
        $tmpZip = $downloadDir . '/' . $folder . '-theme-update-' . time() . '.zip';

        try {
            // 1. Download do Pacote ZIP diretamente da Release do GitHub
            $this->downloadFile($downloadUrl, $tmpZip);

            if (!file_exists($tmpZip) || filesize($tmpZip) < 100) {
                throw new \RuntimeException('O download do pacote falhou ou retornou arquivo vazio.');
            }

            // 2. Validação Criptográfica de Integridade (SHA-256) se informada
            if ($expectedSha !== '') {
                $actualSha = hash_file('sha256', $tmpZip);
                if (!hash_equals(strtolower($expectedSha), strtolower((string)$actualSha))) {
                    throw new \RuntimeException("Falha de integridade: SHA-256 esperado ({$expectedSha}) diverge do arquivo recebido ({$actualSha}).");
                }
            }

            // 3. Extração Segura em Quarentena e Normalização na pasta /themes/{folder}
            $extractor = ExtractorFactory::create();
            $extractedFolder = $extractor->extract($tmpZip, $themesPath, 'theme.json');

            $extractedPath = $themesPath . '/' . $extractedFolder;
            $targetPath = $themesPath . '/' . $folder;

            if ($extractedFolder !== $folder && is_dir($extractedPath)) {
                if (is_dir($targetPath)) {
                    $this->deleteDirectory($targetPath);
                }
                @rename($extractedPath, $targetPath);
            }

            // 4. Limpa caches
            $this->clearCache();

            return [
                'success' => true,
                'message' => "Tema '{$themeName}' atualizado com sucesso para v{$remoteVersion} diretamente do repositório oficial!",
                'version' => $remoteVersion,
                'folder'  => $folder
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Falha durante o processo de atualização do tema: ' . $e->getMessage()
            ];
        } finally {
            if (file_exists($tmpZip)) {
                @unlink($tmpZip);
            }
        }
    }

    /**
     * Resolve a URL de download priorizando o repositório dedicado do tema
     * (ex: hadelrachid/rachidd_store) em vez do intermediário.
     */
    public function resolveDownloadUrl(string $folder, array $remoteInfo, ?array $localMeta = null): string
    {
        $rawUrl = (string)($remoteInfo['download_url'] ?? '');
        $repoUrl = (string)($remoteInfo['repository'] ?? ($localMeta['repository'] ?? ''));

        // Se o tema declarar repositório próprio (ex: https://github.com/hadelrachid/rachidd_store)
        if (!empty($repoUrl)) {
            return rtrim($repoUrl, '/') . "/releases/latest/download/{$folder}.zip";
        }

        // Se o tema for rachidd_store e a URL apontar para o antigo agregador, normaliza
        if ($folder === 'rachidd_store') {
            return "https://github.com/hadelrachid/rachidd_store/releases/latest/download/rachidd_store.zip";
        }

        return $rawUrl;
    }

    /**
     * Descobre todos os temas instalados, incluindo /themes e temas integrados em plugins.
     *
     * @return array<string, string> Mapa [folder => caminho_absoluto]
     */
    private function discoverThemeDirectories(): array
    {
        $directories = [];
        $themesPath = $this->basePath . '/themes';

        // 1. Temas em /themes
        if (is_dir($themesPath)) {
            foreach (glob($themesPath . '/*', GLOB_ONLYDIR) ?: [] as $d) {
                $folder = basename($d);
                if (!in_array($folder, ['admin', 'manager', 'subscriber', 'user'], true)) {
                    $directories[$folder] = $d;
                }
            }
        }

        // 2. Temas integrados dentro de plugins (ex: src/Plugins/flextheme/themes)
        $pluginsPath = $this->basePath . '/src/Plugins';
        if (is_dir($pluginsPath)) {
            foreach (glob($pluginsPath . '/*/themes/*', GLOB_ONLYDIR) ?: [] as $bd) {
                $folder = basename($bd);
                if (!isset($directories[$folder]) && !in_array($folder, ['admin', 'manager', 'subscriber', 'user'], true)) {
                    $directories[$folder] = $bd;
                }
            }
        }

        return $directories;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return @unlink($dir);
        }

        $items = scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return @rmdir($dir);
    }

    public function clearCache(): void
    {
        $cacheFile = $this->basePath . '/temp/cache/marketplace_themes.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    private function fetchMarketplace(bool $forceRefresh = false): ?array
    {
        $cacheFile = $this->basePath . '/temp/cache/marketplace_themes.json';

        if (!$forceRefresh && file_exists($cacheFile)) {
            $age = time() - filemtime($cacheFile);
            if ($age < $this->cacheTtlSeconds) {
                $cached = json_decode((string)file_get_contents($cacheFile), true);
                if (is_array($cached)) {
                    return $cached;
                }
            }
        }

        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $jsonString = $this->fetchRemoteUrl($this->marketplaceUrl);
        if ($jsonString === null) {
            if (file_exists($cacheFile)) {
                $stale = json_decode((string)file_get_contents($cacheFile), true);
                if (is_array($stale)) {
                    return $stale;
                }
            }
            return null;
        }

        $decoded = json_decode($jsonString, true);
        if (!is_array($decoded) || empty($decoded['themes'])) {
            return null;
        }

        @file_put_contents($cacheFile, $jsonString);
        return $decoded;
    }

    private function fetchRemoteUrl(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT      => 'DomainSystem-ThemeUpdater/2.8',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && is_string($response)) {
                return $response;
            }
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 8,
                'user_agent' => 'DomainSystem-ThemeUpdater/2.8',
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $ctx);
        return is_string($content) ? $content : null;
    }

    private function downloadFile(string $url, string $destPath): void
    {
        if (function_exists('curl_init')) {
            $fp = fopen($destPath, 'wb');
            if ($fp === false) {
                throw new \RuntimeException("Não foi possível criar o arquivo de destino: {$destPath}");
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 90,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT      => 'DomainSystem-ThemeUpdater/2.8',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if (!$success || $httpCode !== 200) {
                @unlink($destPath);
                throw new \RuntimeException("Falha no download via cURL (HTTP {$httpCode}): {$err}");
            }
            return;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout'         => 90,
                'follow_location' => 1,
                'user_agent'      => 'DomainSystem-ThemeUpdater/2.8',
            ],
        ]);

        $content = @file_get_contents($url, false, $ctx);
        if ($content === false) {
            throw new \RuntimeException('Falha no download via stream_context.');
        }

        file_put_contents($destPath, $content);
    }
}
