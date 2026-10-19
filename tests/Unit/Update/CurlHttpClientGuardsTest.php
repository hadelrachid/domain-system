<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Exception\TransportException;
use DomainSystem\Core\Update\Infrastructure\CurlHttpClient;
use DomainSystem\Core\Update\Infrastructure\HostAllowlist;
use DomainSystem\Core\Update\Value\HttpResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurlHttpClientGuardsTest extends TestCase
{
    #[DataProvider('blockedUrlsProvider')]
    public function testValidateUrlRejectsDisallowedUrlsWithoutMakingNetworkRequests(string $url): void
    {
        $client = new CurlHttpClient(new HostAllowlist());

        $this->expectException(TransportException::class);
        $client->get($url);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function blockedUrlsProvider(): array
    {
        return [
            [''],
            ['http://api.github.com/repos/hadelrachid/domain-system/releases/latest'],
            ['ftp://api.github.com/file.zip'],
            ['https://evil.example.com/releases/latest'],
            ['https://api.github.com@evil.example.com/releases/latest'],
            ['https://user:pass@api.github.com/repos/hadelrachid/domain-system'],
            ['https://api.github.com:8443/repos/hadelrachid/domain-system'],
            ['https://githubusercontent.com/file.zip'],
        ];
    }

    public function testRedirectToDisallowedHostIsBlockedBeforeSecondHop(): void
    {
        $calls = [];
        $client = new CurlHttpClient(
            new HostAllowlist(),
            static function (string $url) use (&$calls): HttpResponse {
                $calls[] = $url;
                return new HttpResponse(
                    statusCode: 302,
                    headers: ['location' => 'https://attacker.example.com/malicious.zip'],
                    body: '',
                );
            },
        );

        try {
            $client->get('https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/pkg.zip');
            $this->fail('Deveria ter bloqueado o redirecionamento para host fora da allowlist.');
        } catch (TransportException $e) {
            $this->assertStringContainsString('allowlist', $e->getMessage());
            $this->assertCount(1, $calls, 'O segundo salto jamais deve ser executado.');
        }
    }

    public function testRedirectFollowsUpToThreeAllowedHopsAndBlocksFourthHop(): void
    {
        $hopCount = 0;
        $client = new CurlHttpClient(
            new HostAllowlist(),
            static function (string $url) use (&$hopCount): HttpResponse {
                $hopCount++;
                return new HttpResponse(
                    statusCode: 302,
                    headers: ['location' => "https://objects.githubusercontent.com/hop-{$hopCount}"],
                    body: '',
                );
            },
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('redirecionamentos');
        $client->get('https://github.com/hadelrachid/domain-system/releases/download/v2.4.0/pkg.zip');
    }

    public function testDownloadDeletesPartialFileWhenMaxBytesExceeded(): void
    {
        $tempDir = sys_get_temp_dir() . '/ds_curl_guard_' . uniqid('', true);
        mkdir($tempDir, 0777, true);
        $destFile = $tempDir . '/oversized.zip';

        $client = new CurlHttpClient(
            new HostAllowlist(),
            static function (string $url, array $headers, int $timeout, ?string $dest): HttpResponse {
                if ($dest !== null) {
                    file_put_contents($dest, str_repeat('A', 200));
                }
                return new HttpResponse(200, [], '');
            },
        );

        try {
            $client->download(
                'https://objects.githubusercontent.com/pkg.zip',
                $destFile,
                maxBytes: 50,
            );
            $this->fail('Deveria ter abortado por exceder maxBytes.');
        } catch (TransportException $e) {
            $this->assertFileDoesNotExist($destFile, 'O arquivo parcial deve ser removido imediatamente.');
        } finally {
            @unlink($destFile);
            @rmdir($tempDir);
        }
    }
}
