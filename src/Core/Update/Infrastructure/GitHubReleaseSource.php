<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\Update\HttpClientInterface;
use DomainSystem\Core\Contracts\Update\ReleaseSourceInterface;
use DomainSystem\Core\Contracts\Update\VersionComparatorInterface;
use DomainSystem\Core\Update\Exception\SourceUnavailableException;
use DomainSystem\Core\Update\Exception\TransportException;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use DomainSystem\Core\Update\Value\UpdateTarget;
use InvalidArgumentException;

final class GitHubReleaseSource implements ReleaseSourceInterface
{
    private const MAX_PACKAGE_BYTES = 157_286_400; // 150 MB
    private const MAX_SHA256_BYTES = 4_096;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly VersionComparatorInterface $versionComparator,
        private readonly int $maxPackageBytes = self::MAX_PACKAGE_BYTES,
    ) {
    }

    public function latest(UpdateTarget $target, ?string $knownEtag = null): ?ReleaseInfo
    {
        $url = 'https://api.github.com/repos/' . $target->repository . '/releases/latest';
        $headers = [];
        if ($knownEtag !== null && trim($knownEtag) !== '') {
            $headers['If-None-Match'] = $knownEtag;
        }

        $response = $this->httpClient->get($url, $headers, 15);
        if ($response->isNotModified()) {
            return null;
        }

        if ($response->statusCode !== 200) {
            throw new SourceUnavailableException(
                "GitHub API retornou código HTTP {$response->statusCode} para {$target->repository}."
            );
        }

        $payload = json_decode($response->body, true);
        if (!is_array($payload)) {
            throw new SourceUnavailableException('Resposta JSON da GitHub API malformada.');
        }

        if (!empty($payload['draft']) || !empty($payload['prerelease'])) {
            throw new SourceUnavailableException('A Release retornada pelo GitHub é rascunho ou pré-lançamento.');
        }

        $tag = trim((string)($payload['tag_name'] ?? ''));
        if ($tag === '') {
            throw new SourceUnavailableException('Campo tag_name ausente na Release do GitHub.');
        }

        try {
            $version = $this->versionComparator->normalize($tag);
        } catch (InvalidArgumentException $e) {
            throw new SourceUnavailableException("Tag da Release não é SemVer válida ({$tag}): " . $e->getMessage(), 0, $e);
        }

        $publishedAt = trim((string)($payload['published_at'] ?? ''));
        if ($publishedAt === '') {
            throw new SourceUnavailableException('Campo published_at ausente na Release do GitHub.');
        }

        $changelog = (string)($payload['body'] ?? '');
        $assets = is_array($payload['assets'] ?? null) ? $payload['assets'] : [];

        $expectedZipName = 'domain-system-' . $version . '.zip';
        $expectedShaName = $expectedZipName . '.sha256';

        $zipAsset = null;
        $shaAsset = null;
        $fallbackZipAsset = null;

        foreach ($assets as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $name = (string)($asset['name'] ?? '');
            if ($name === $expectedZipName) {
                $zipAsset = $asset;
            } elseif ($name === $expectedShaName) {
                $shaAsset = $asset;
            } elseif ($fallbackZipAsset === null && str_ends_with(strtolower($name), '.zip')) {
                $fallbackZipAsset = $asset;
            }
        }

        $isNewerThanInstalled = $this->versionComparator->isNewer($version, $target->installedVersion);

        if (!$isNewerThanInstalled && ($zipAsset === null || $shaAsset === null)) {
            $chosenAsset = $zipAsset ?? $fallbackZipAsset;
            $legacyAssetName = is_array($chosenAsset) && !empty($chosenAsset['name'])
                ? (string)$chosenAsset['name']
                : $expectedZipName;
            $legacyUrl = is_array($chosenAsset) && !empty($chosenAsset['browser_download_url'])
                ? (string)$chosenAsset['browser_download_url']
                : ('https://github.com/' . $target->repository . '/releases/tag/' . $tag);
            $legacySize = is_array($chosenAsset) && (int)($chosenAsset['size'] ?? 0) > 0
                ? (int)$chosenAsset['size']
                : 1;

            return new ReleaseInfo(
                version: $version,
                tag: $tag,
                publishedAt: $publishedAt,
                changelog: $changelog,
                assetUrl: $legacyUrl,
                assetName: $legacyAssetName,
                sizeBytes: $legacySize,
                sha256: str_repeat('0', 64),
                etag: $response->etag,
            );
        }

        if ($zipAsset === null) {
            throw new SourceUnavailableException("Asset obrigatório '{$expectedZipName}' ausente na Release {$tag}.");
        }
        if ($shaAsset === null) {
            throw new SourceUnavailableException("Asset de integridade '{$expectedShaName}' ausente na Release {$tag}.");
        }

        $zipUrl = trim((string)($zipAsset['browser_download_url'] ?? ''));
        $shaUrl = trim((string)($shaAsset['browser_download_url'] ?? ''));
        $sizeBytes = (int)($zipAsset['size'] ?? 0);

        if ($zipUrl === '' || $shaUrl === '' || $sizeBytes <= 0) {
            throw new SourceUnavailableException("Metadados de download incompletos na Release {$tag}.");
        }

        $shaResponse = $this->httpClient->get($shaUrl, [], 10);
        if ($shaResponse->statusCode !== 200 || strlen($shaResponse->body) > self::MAX_SHA256_BYTES) {
            throw new SourceUnavailableException("Não foi possível obter o arquivo de hash '{$expectedShaName}'.");
        }

        $sha256 = $this->extractSha256FromContent($shaResponse->body);

        return new ReleaseInfo(
            version: $version,
            tag: $tag,
            publishedAt: $publishedAt,
            changelog: $changelog,
            assetUrl: $zipUrl,
            assetName: $expectedZipName,
            sizeBytes: $sizeBytes,
            sha256: $sha256,
            etag: $response->etag,
        );
    }

    public function fetchPackage(ReleaseInfo $release, string $destinationDir): DownloadedPackage
    {
        $normalizedDir = rtrim(str_replace('\\', '/', $destinationDir), '/');
        if (!is_dir($normalizedDir) && !@mkdir($normalizedDir, 0755, true) && !is_dir($normalizedDir)) {
            throw new TransportException("Não foi possível criar o diretório de download: {$normalizedDir}");
        }

        $archivePath = $normalizedDir . '/' . $release->assetName;
        $response = $this->httpClient->download(
            url: $release->assetUrl,
            destinationFile: $archivePath,
            maxBytes: $this->maxPackageBytes,
            headers: [],
            timeoutSeconds: 120,
        );

        if ($response->statusCode !== 200 || !is_file($archivePath)) {
            if (is_file($archivePath)) {
                @unlink($archivePath);
            }
            throw new TransportException("Falha ao baixar o pacote {$release->assetName} (HTTP {$response->statusCode}).");
        }

        $actualSize = (int)filesize($archivePath);
        if ($actualSize <= 0) {
            @unlink($archivePath);
            throw new TransportException("O pacote baixado {$release->assetName} está vazio.");
        }

        return new DownloadedPackage(
            release: $release,
            archivePath: $archivePath,
            sizeBytes: $actualSize,
        );
    }

    private function extractSha256FromContent(string $raw): string
    {
        $trimmed = trim($raw);
        if (preg_match('/^([a-fA-F0-9]{64})(?:\s+.*)?$/', $trimmed, $matches) !== 1) {
            throw new SourceUnavailableException('Conteúdo do asset .sha256 não contém um hash SHA-256 hexadecimal válido de 64 caracteres.');
        }

        return strtolower($matches[1]);
    }
}
