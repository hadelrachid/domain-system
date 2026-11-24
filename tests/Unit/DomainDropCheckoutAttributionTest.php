<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCustomerRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipDisputeRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Controllers\CustomerAccountController;
use DomainSystem\Plugins\domain_drop\DTO\DemandEvent;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDemandMetricsRepository;
use DomainSystem\Plugins\domain_drop\Services\DisputeAttachmentService;
use DomainSystem\Plugins\domain_drop\Services\FileDemandLogStorage;
use DomainSystem\Plugins\domain_drop\Services\OrderSlaProgressService;
use DomainSystem\Plugins\domain_drop\Services\SessionGuardedDemandTracker;
use PDO;
use PHPUnit\Framework\TestCase;

final class DomainDropCheckoutAttributionTest extends TestCase
{
    private PDO $pdo;
    private string $tempDir;

    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) { $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.'); return; }
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->tempDir = sys_get_temp_dir() . '/test_attribution_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
        parent::tearDown();
    }

    public function testAnonymousInteractionIsPromotedWhenCustomerRegistersOrLogsIn(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $logStorage = new FileDemandLogStorage($this->tempDir);
        $tracker = new SessionGuardedDemandTracker($repo, null, 2, $logStorage);

        $fakeSessionId = 'test_sess_' . bin2hex(random_bytes(6));

        // 1. Visitante anônimo clica em "Ver Detalhes" e "Comprar"
        $eventView = new DemandEvent(
            productId: 'AE-999',
            productTitle: 'Conjunto Lingerie Natalina',
            categorySlug: 'moda_intima',
            actionType: 'view_details',
            sessionId: $fakeSessionId,
            userType: 'externo'
        );
        $eventBuy = new DemandEvent(
            productId: 'AE-999',
            productTitle: 'Conjunto Lingerie Natalina',
            categorySlug: 'moda_intima',
            actionType: 'buy_click',
            sessionId: $fakeSessionId,
            userType: 'externo'
        );

        $this->assertTrue($tracker->recordInteraction($eventView));
        $this->assertTrue($tracker->recordInteraction($eventBuy));

        // Verifica estado inicial: ambos anônimos
        $initialLogs = $repo->getRecentActivityLogs(10);
        $this->assertCount(2, $initialLogs);
        $this->assertSame('externo', $initialLogs[0]['user_type']);
        $this->assertNull($initialLogs[0]['customer_id']);

        // 2. Simula criação de conta de Mônica Medeiros
        $sessionStore = [];
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$sessionStore) {
            return $sessionStore[$key] ?? $default;
        });
        $sessionMock->method('set')->willReturnCallback(function (string $key, mixed $value) use (&$sessionStore) {
            $sessionStore[$key] = $value;
        });
        $sessionMock->method('getCsrfToken')->willReturn('csrf_valid_token');

        $customerRepoMock = $this->createMock(DropshipCustomerRepositoryInterface::class);
        $customerRepoMock->method('findByEmail')->willReturn(null);
        $customerRepoMock->method('create')->willReturn(2);

        $controller = new CustomerAccountController(
            customerRepo: $customerRepoMock,
            orderRepo: $this->createMock(DropshipOrderRepositoryInterface::class),
            disputeRepo: $this->createMock(DropshipDisputeRepositoryInterface::class),
            session: $sessionMock,
            attachmentService: $this->createMock(DisputeAttachmentService::class),
            slaService: $this->createMock(OrderSlaProgressService::class),
            catalogRepo: $this->createMock(DropshipCatalogRepositoryInterface::class),
            demandTracker: $tracker
        );

        // 3. Promove a sessão diretamente pelo tracker (como o controller faz)
        $promoted = $tracker->promoteSession(
            $fakeSessionId,
            2,
            'Monica Medeiros',
            'monicamedeirosdaher@gmail.com'
        );

        // 2 no repositório + 2 no JSON storage = 4
        $this->assertSame(4, $promoted);

        // 4. Verifica se no banco os registros foram atualizados
        $promotedLogs = $repo->getRecentActivityLogs(10);
        $this->assertCount(2, $promotedLogs);
        foreach ($promotedLogs as $log) {
            $this->assertSame('cliente_logado', $log['user_type']);
            $this->assertSame(2, (int)$log['customer_id']);
            $this->assertSame('Monica Medeiros', $log['customer_name']);
            $this->assertSame('monicamedeirosdaher@gmail.com', $log['customer_email']);
        }

        // 5. Verifica se no arquivo JSON os registros foram atualizados
        $recentJson = $logStorage->getRecentInteractions(null, 10);
        $this->assertCount(2, $recentJson);
        foreach ($recentJson as $jsonItem) {
            $this->assertSame('cliente_logado', $jsonItem['user_type']);
            $this->assertSame(2, $jsonItem['customer_id']);
            $this->assertSame('Monica Medeiros', $jsonItem['customer_name']);
            $this->assertSame('monicamedeirosdaher@gmail.com', $jsonItem['customer_email']);
        }

        // 6. Verifica lista de e-mails de leads capturados
        $leadEmails = $logStorage->getLeadEmails();
        $this->assertContains('monicamedeirosdaher@gmail.com', $leadEmails);
    }

    public function testAnonymousInteractionIsPromotedViaIpAddressEvenIfSessionChanges(): void
    {
        $repo = new PdoDemandMetricsRepository($this->pdo);
        $logStorage = new FileDemandLogStorage($this->tempDir);
        $tracker = new SessionGuardedDemandTracker($repo, null, 2, $logStorage);

        $clientIp = '200.189.45.10';
        $oldSession = 'anon_session_111';

        // 1. Visitante anônimo navega do IP 200.189.45.10
        $event = new DemandEvent(
            productId: 'AE-777',
            productTitle: 'Produto Teste IP',
            categorySlug: 'geral',
            actionType: 'view_details',
            sessionId: $oldSession,
            userType: 'externo',
            ipAddress: $clientIp
        );
        $this->assertTrue($tracker->recordInteraction($event));

        // 2. O usuário troca de aba ou limpa cookie (nova sessão: 'new_session_222'), mas mesmo IP!
        $promoted = $tracker->promoteSession(
            'new_session_222',
            10,
            'Comprador IP',
            'comprador.ip@loja.com',
            $clientIp
        );

        // Deve promover o registro que bateu por IP
        $this->assertGreaterThan(0, $promoted);

        $logs = $repo->getRecentActivityLogs(10);
        $this->assertSame('cliente_logado', $logs[0]['user_type']);
        $this->assertSame(10, (int)$logs[0]['customer_id']);
        $this->assertSame('comprador.ip@loja.com', $logs[0]['customer_email']);
        $this->assertSame($clientIp, $logs[0]['ip_address']);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        if ($items === false) return;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($p)) {
                $this->deleteDir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }
}
