<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\seo\Controllers\SeoAdminController;
use DomainSystem\Plugins\seo\Repositories\PdoPageSpeedAuditRepository;
use DomainSystem\Plugins\seo\Services\PageSpeedAuditService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Testes Unitários e de Integração do Módulo DomainSEO & Google PageSpeed Hub.
 */
class DomainSeoPageSpeedTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testPageSpeedAuditServiceProducesValidStructureViaProfiler(): void
    {
        $service = new PageSpeedAuditService();
        $audit = $service->audit('http://localhost:8000', 'mobile');

        $this->assertIsArray($audit);
        $this->assertSame('http://localhost:8000', $audit['url']);
        $this->assertSame('mobile', $audit['strategy']);
        $this->assertGreaterThanOrEqual(0, $audit['score']);
        $this->assertLessThanOrEqual(100, $audit['score']);
        $this->assertNotEmpty($audit['lcp']);
        $this->assertNotEmpty($audit['cls']);
        $this->assertNotEmpty($audit['ttfb']);
        $this->assertArrayHasKey('ai_diagnostics', $audit);
        $this->assertArrayHasKey('opportunities', $audit);
        $this->assertStringContainsString('pagespeed.web.dev', $audit['raw_report_url']);
    }

    public function testPageSpeedAuditServiceParsesGoogleLighthouseJson(): void
    {
        // Subclasse para simular a resposta JSON oficial do Google PageSpeed API
        $fakeGoogleResponse = [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.94],
                ],
                'audits' => [
                    'largest-contentful-paint' => ['displayValue' => '1.4 s'],
                    'cumulative-layout-shift'  => ['displayValue' => '0.005'],
                    'first-contentful-paint'   => ['displayValue' => '0.8 s'],
                    'server-response-time'     => ['displayValue' => '120 ms'],
                    'max-potential-fid'        => ['displayValue' => '35 ms'],
                    'speed-index'              => ['displayValue' => '1.1 s'],
                    'total-blocking-time'      => ['displayValue' => '10 ms'],
                    'unused-css-rules'         => [
                        'score'        => 0.75,
                        'displayValue' => 'Potencial economia de 45 KB',
                        'description'  => 'Remova regras CSS não utilizadas.',
                        'details'      => [
                            'items' => [
                                ['url' => 'https://rachidd.com/assets/css/theme-rachidd.css'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $service = new class($fakeGoogleResponse) extends PageSpeedAuditService {
            public function __construct(private readonly array $stubResponse)
            {
                parent::__construct();
            }

            protected function fetchFromGoogleApi(string $url, string $strategy): array
            {
                return $this->stubResponse;
            }
        };

        $result = $service->audit('https://rachidd.com', 'mobile');

        $this->assertSame(94, $result['score']);
        $this->assertSame('google_pagespeed_api', $result['source']);
        $this->assertSame('1.4 s', $result['lcp']);
        $this->assertSame('0.005', $result['cls']);
        $this->assertCount(1, $result['opportunities']);
        $this->assertSame('CSS não utilizado', $result['opportunities'][0]['title']);
        $this->assertSame('https://rachidd.com/assets/css/theme-rachidd.css', $result['opportunities'][0]['files'][0]);
        $this->assertStringContainsString('rachidd.com', $result['ai_diagnostics']['overview']['url']);
    }

    public function testPdoPageSpeedAuditRepositoryPersistsAndHydratesHistory(): void
    {
        $repo = new PdoPageSpeedAuditRepository($this->pdo);

        $savedId = $repo->save([
            'url'            => 'https://rachidd.com',
            'strategy'       => 'mobile',
            'score'          => 88,
            'source'         => 'google_pagespeed_api',
            'lcp'            => '1.9 s',
            'cls'            => '0.01',
            'inp'            => '50 ms',
            'fcp'            => '1.1 s',
            'ttfb'           => '180 ms',
            'metrics'        => ['speed_index' => '1.6 s'],
            'opportunities'  => [
                ['title' => 'Minificação de Imagens', 'savings' => '120 KB'],
            ],
            'ai_diagnostics' => ['task' => 'Otimizar banner WebP'],
            'raw_report_url' => 'https://pagespeed.web.dev/analysis?url=https://rachidd.com',
            'audited_at'     => '2026-11-19 20:00:00',
        ]);

        $this->assertGreaterThan(0, $savedId);

        $latest = $repo->getLatestByStrategy('mobile');
        $this->assertNotNull($latest);
        $this->assertSame(88, $latest['score']);
        $this->assertSame('1.9 s', $latest['lcp']);
        $this->assertCount(1, $latest['opportunities']);
        $this->assertSame('Minificação de Imagens', $latest['opportunities'][0]['title']);

        $summary = $repo->getSummaryForAi();
        $this->assertSame('active', $summary['status']);
        $this->assertNotNull($summary['latest_audits']['mobile']);
        $this->assertNull($summary['latest_audits']['desktop']);
    }

    public function testSeoAdminControllerRendersDashboardAndExecutesAudit(): void
    {
        $service = new PageSpeedAuditService();
        $repo = new PdoPageSpeedAuditRepository($this->pdo);
        $controller = new SeoAdminController($service, $repo);

        // 1. Dashboard View
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/seo']);
        $resp = $controller->index($req);

        $this->assertInstanceOf(ViewResponse::class, $resp);
        $html = $resp->getContent();
        $this->assertStringContainsString('DomainSEO & Performance Hub', $html);
        $this->assertStringContainsString('Google PageSpeed API v5', $html);
        $this->assertStringContainsString('Executar Auditoria do Google', $html);

        // 2. Executar auditoria via POST AJAX
        $postReq = new Request(
            [],
            ['url' => 'http://localhost:8000', 'strategy' => 'mobile'],
            ['REQUEST_METHOD' => 'POST', 'HTTP_ACCEPT' => 'application/json']
        );

        $auditResp = $controller->runAudit($postReq);
        $this->assertInstanceOf(JsonResponse::class, $auditResp);
        $data = json_decode($auditResp->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('mobile', $data['results']);
        $this->assertGreaterThan(0, $data['results']['mobile']['score']);

        // 3. Feed JSON para a I.A.
        $feedReq = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/seo/ai-feed']);
        $feedResp = $controller->getAiFeed($feedReq);
        $this->assertInstanceOf(JsonResponse::class, $feedResp);
        $feedData = json_decode($feedResp->getContent(), true);

        $this->assertSame('active', $feedData['status']);
        $this->assertNotNull($feedData['latest_audits']['mobile']);
    }
}
