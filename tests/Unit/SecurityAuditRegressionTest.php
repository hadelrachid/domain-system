<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Contracts\CourseImageStorageInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Services\CourseService;
use DomainSystem\Plugins\academy\Services\LessonBlockRenderer;
use DomainSystem\Plugins\academy\Services\ThemeLessonFileScaffolder;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Controllers\CheckoutController;
use DomainSystem\Plugins\commerce\Controllers\WebhookController;
use DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipFrontController;
use DomainSystem\Plugins\domain_drop\DTO\DemandEvent;
use DomainSystem\Plugins\domain_drop\Services\AliExpressLinkScraperService;
use DomainSystem\Plugins\domain_drop\Services\CategoryTaxonomyService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SecurityAuditRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/academy')) { $this->markTestSkipped('Plugin agregado externo [academy] não está presente no checkout.'); return; }
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/commerce')) { $this->markTestSkipped('Plugin agregado externo [commerce] não está presente no checkout.'); return; }
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) { $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.'); return; }
    }

    public function testCheckoutCanAccessOrderRejectsUnauthenticatedAndZeroUserId(): void
    {
        $store = [];
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$store): mixed {
            return $store[$key] ?? $default;
        });
        $session->method('set')->willReturnCallback(function (string $key, mixed $value) use (&$store): void {
            $store[$key] = $value;
        });

        $controller = new CheckoutController(
            $session,
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(PaymentGatewayRegistryInterface::class)
        );

        $method = new \ReflectionMethod(CheckoutController::class, 'canAccessOrder');
        $method->setAccessible(true);

        // Sem sessão e user_id=0 -> deve negar (evita IDOR)
        $this->assertFalse($method->invoke($controller, ['order_ref' => 'ORD_OTHER', 'user_id' => 0, 'item_type' => 'dropship_product']));

        // Sessão com academy_logged_out=true não pode usar academy_student_id
        $store['academy_student_id'] = 42;
        $store['academy_logged_out'] = true;
        $this->assertFalse($method->invoke($controller, ['order_ref' => 'ORD_42', 'user_id' => 42, 'item_type' => 'course']));

        // Sessão que criou o pedido (guest checkout) pode acessar apenas o seu order_ref
        $store['checkout_owned_order_refs'] = ['ORD_MINE'];
        $this->assertTrue($method->invoke($controller, ['order_ref' => 'ORD_MINE', 'user_id' => 0, 'item_type' => 'dropship_product']));
        $this->assertFalse($method->invoke($controller, ['order_ref' => 'ORD_OTHER', 'user_id' => 0, 'item_type' => 'dropship_product']));
    }

    public function testThemeLessonFileScaffolderPreventsDocblockBreakoutRce(): void
    {
        $scaffolder = new ThemeLessonFileScaffolder();
        $method = new \ReflectionMethod(ThemeLessonFileScaffolder::class, 'buildPreconfiguredPhpTemplate');
        $method->setAccessible(true);

        $phpCode = $method->invoke(
            $scaffolder,
            'Aula */ system("id"); /*',
            'aula-teste',
            'pages/aula-teste.php',
            null,
            'drawer'
        );

        $docblockEndPos = strpos($phpCode, '?>');
        $docblockPart = $docblockEndPos !== false ? substr($phpCode, 0, $docblockEndPos) : $phpCode;
        // O único fechamento '*/' no bloco PHP inicial deve ser o do próprio docblock no final
        $this->assertSame(1, substr_count($docblockPart, '*/'));
    }

    public function testCourseServiceRejectsJavascriptSchemeLinks(): void
    {
        $repo = $this->createMock(CourseRepositoryInterface::class);
        $storage = $this->createMock(CourseImageStorageInterface::class);
        $service = new CourseService($repo, $storage);

        $this->expectException(InvalidArgumentException::class);
        $service->createCourse([
            'title'  => 'Curso Teste',
            'status' => 'published',
            'link'   => 'javascript:alert(1)',
        ]);
    }

    public function testLessonBlockRendererBlocksControlCharJavascriptPdfAndInvalidCalloutType(): void
    {
        $renderer = new LessonBlockRenderer();

        $pdfHtml = $renderer->renderPdf(['url' => "java\tscript:alert(1)", 'title' => 'Evil']);
        $this->assertSame('', $pdfHtml);

        $calloutHtml = $renderer->renderCallout(['type' => 'info" onclick="alert(1)', 'text' => 'Oi']);
        $this->assertStringContainsString('ds-callout-info', $calloutHtml);
        $this->assertStringNotContainsString('onclick="alert(1)', $calloutHtml);
    }

    public function testAliExpressScraperBlocksSsrfUrls(): void
    {
        $scraper = new AliExpressLinkScraperService(new CategoryTaxonomyService());
        $method = new \ReflectionMethod(AliExpressLinkScraperService::class, 'isAllowedAliExpressUrl');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($scraper, 'http://127.0.0.1/admin'));
        $this->assertFalse($method->invoke($scraper, 'http://169.254.169.254/latest/meta-data/'));
        $this->assertFalse($method->invoke($scraper, 'https://evil.example.com/item/123.html'));
        $this->assertFalse($method->invoke($scraper, 'file:///etc/passwd'));
    }

    public function testTrackDemandStripsIdentitySpoofingAndConversionAction(): void
    {
        $capturedEvent = null;
        $trackerMock = $this->createMock(DemandTrackerInterface::class);
        $trackerMock->method('recordInteraction')->willReturnCallback(function (DemandEvent $e) use (&$capturedEvent): bool {
            $capturedEvent = $e;
            return true;
        });

        $controller = new DropshipFrontController(
            catalogRepo: $this->createMock(DropshipCatalogRepositoryInterface::class),
            demandTracker: $trackerMock
        );
        $postData = [
            'product_id'     => '1005010179002980',
            'action'         => 'conversion',
            'customer_id'    => 999,
            'customer_email' => 'victim@example.com',
        ];
        $req = new Request([], $postData, [], [], ['REQUEST_METHOD' => 'POST'], $postData);

        $res = $controller->trackDemand($req);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertNotNull($capturedEvent);
        $this->assertSame('view_details', $capturedEvent->actionType);
        $this->assertNull($capturedEvent->customerId);
        $this->assertNull($capturedEvent->customerEmail);
        $this->assertSame('externo', $capturedEvent->userType);
    }

    public function testWebhookControllerRejectsZeroPaidAmountWhenExpectedIsPositive(): void
    {
        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->method('findByOrderRef')->willReturn([
            'id'        => 10,
            'order_ref' => 'ORD_ZERO_CHECK',
            'status'    => 'pending',
            'total'     => 199.90,
            'user_id'   => 1,
            'item_id'   => 2,
        ]);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('handleWebhook')->willReturn([
            'order_ref' => 'ORD_ZERO_CHECK',
            'status'    => 'approved',
            'total'     => 0.0,
        ]);

        $registry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $registry->method('get')->with('stripe')->willReturn($gateway);

        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('all')->willReturn(['stripe_enabled' => true]);

        $controller = new WebhookController($orderRepo, $registry, null, null, null, null, $settings);
        $res = $controller->handle(new Request([], [], ['REQUEST_METHOD' => 'POST']), 'stripe');
        $body = json_decode($res->getContent(), true);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('amount_mismatch', $body['action'] ?? '');
    }
}
