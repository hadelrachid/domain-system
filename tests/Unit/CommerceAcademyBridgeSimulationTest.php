<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\academy\Adapters\CoursePurchasableAdapter;
use DomainSystem\Plugins\academy\Contracts\CourseNavigationInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Controllers\CourseFrontController;
use DomainSystem\Plugins\academy\Repositories\PdoCourseEnrollmentRepository;
use DomainSystem\Plugins\academy\Services\AcademyCourseFulfillment;
use DomainSystem\Plugins\academy\Services\CourseEnrollmentService;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Controllers\CheckoutController;
use DomainSystem\Plugins\commerce\Controllers\WebhookController;
use DomainSystem\Plugins\commerce\Repositories\PdoOrderRepository;
use DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway;
use DomainSystem\Plugins\commerce\Services\PaymentGatewayRegistry;
use DomainSystem\Plugins\commerce\Services\PurchasableItemResolver;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Teste de Simulação Completa da Conexão entre o Commerce ("WooCommerce do Domain System")
 * e o LMS Academy através de Interfaces e Contratos Formais (DIP / SOLID / Padrão Adapter).
 */
final class CommerceAcademyBridgeSimulationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testCommerceCanSellAcademyCourseViaInterfaceAndFulfillEnrollmentSeamlessly(): void
    {
        // =========================================================================
        // 1. O ACADEMY DISPONIBILIZA O CURSO COMO UM ADAPTADOR (PurchasableItemInterface)
        // =========================================================================
        $rawCourseData = [
            'id'          => 404,
            'title'       => 'Curso Avançado de SOLID e Micro-Kernels',
            'slug'        => 'curso-solid-avancado',
            'price'       => 249.90,
            'status'      => 'published',
            'description' => 'Aprenda a construir CMSs ultra-rápidos e modulares.',
            'image'       => 'https://cdn.meusite.com/capa-curso.png',
        ];

        // O Academy adapta o seu curso para a interface que o Commerce entende:
        $purchasableCourse = new CoursePurchasableAdapter($rawCourseData);

        $this->assertSame(404, $purchasableCourse->getId());
        $this->assertSame('course', $purchasableCourse->getItemType());
        $this->assertSame('Curso Avançado de SOLID e Micro-Kernels', $purchasableCourse->getTitle());
        $this->assertEquals(249.90, $purchasableCourse->getPrice());
        $this->assertTrue($purchasableCourse->isAvailable());

        // =========================================================================
        // 2. O COMMERCE RESOLVE O PRODUTO VIA CONTRATO (PurchasableItemResolverInterface)
        // =========================================================================
        $itemResolver = new PurchasableItemResolver();
        $itemResolver->registerResolver('course', function (int $id) use ($purchasableCourse) {
            return $id === 404 ? $purchasableCourse : null;
        });

        // O Commerce pergunta para a interface: "Qual é o produto 'course' com ID 404?"
        $resolvedItem = $itemResolver->resolve('course', 404);
        $this->assertNotNull($resolvedItem);
        $this->assertSame('Curso Avançado de SOLID e Micro-Kernels', $resolvedItem->getTitle());
        $this->assertEquals(249.90, $resolvedItem->getPrice());

        // =========================================================================
        // 3. O ALUNO CONCLUI O PEDIDO NO CHECKOUT DO COMMERCE (PIX)
        // =========================================================================
        $orderRepo = new PdoOrderRepository($this->pdo);
        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('get')->willReturnCallback(function ($k, $def = null) {
            if ($k === 'commerce_demo_mode') {
                return true;
            }
            return $def;
        });

        $gatewayRegistry = new PaymentGatewayRegistry();
        $mercadoPago = new MercadoPagoGateway(settings: $settings);
        $gatewayRegistry->register($mercadoPago);

        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function ($k) {
            return match ($k) {
                'user_id'    => 100, // ID do Aluno comprador
                'user_name'  => 'Ana Desenvolvedora',
                'user_email' => 'ana@dev.com',
                default      => null,
            };
        });
        $session->method('getCsrfToken')->willReturn('csrf_token_seguro');

        $eventsDispatched = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($eventName, $payload = null) use (&$eventsDispatched) {
            $eventsDispatched[] = ['event' => $eventName, 'payload' => $payload];
            return null;
        });

        $checkoutController = new CheckoutController(
            $session,
            $orderRepo,
            $gatewayRegistry,
            null, // CourseRepo é null porque o Commerce usa o itemResolver desacoplado!
            $dispatcher,
            $itemResolver,
            $settings
        );

        $checkoutRequest = new Request(
            [],
            [
                'csrf_token'     => 'csrf_token_seguro',
                'course_id'      => 404,
                'gateway'        => 'mercadopago',
                'payment_method' => 'pix',
            ],
            ['REQUEST_METHOD' => 'POST']
        );

        $checkoutResponse = $checkoutController->process($checkoutRequest);
        $this->assertSame(200, $checkoutResponse->getStatusCode());
        $checkoutJson = json_decode($checkoutResponse->getContent(), true);

        $this->assertTrue($checkoutJson['success']);
        $this->assertSame('pending', $checkoutJson['status']);
        $orderRef = $checkoutJson['order_ref'];
        $this->assertStringContainsString('ORD_', $orderRef);

        // O pedido foi salvo no banco do Commerce como 'pending'
        $orderInDb = $orderRepo->findByOrderRef($orderRef);
        $this->assertSame('pending', $orderInDb['status']);
        $this->assertEquals(249.90, (float)$orderInDb['total']);

        // =========================================================================
        // 4. ANTES DO PAGAMENTO: A AULA ESTÁ BLOQUEADA NO ACADEMY
        // =========================================================================
        $enrollmentRepo = new PdoCourseEnrollmentRepository($this->pdo);
        $enrollmentService = new CourseEnrollmentService($enrollmentRepo);

        // Aluno 100 ainda NÃO tem matrícula ativa
        $this->assertFalse($enrollmentService->hasActiveEnrollment(100, 404));

        // =========================================================================
        // 5. O BANCO ENVIA O WEBHOOK: COMMERCE LIQUIDA E O FULFILLMENT ENTREGA A MATRÍCULA
        // =========================================================================
        $webhookController = new WebhookController($orderRepo, $gatewayRegistry, $dispatcher);

        $webhookRequest = new Request(
            [],
            [
                'id'        => 'MP_TX_888999',
                'type'      => 'payment',
                'order_ref' => $orderRef,
            ],
            ['REQUEST_METHOD' => 'POST']
        );

        $webhookResponse = $webhookController->handle($webhookRequest, 'mercadopago');
        $this->assertSame(200, $webhookResponse->getStatusCode());

        // Pedido atualizado no Commerce para 'paid'
        $paidOrder = $orderRepo->findByOrderRef($orderRef);
        $this->assertSame('paid', $paidOrder['status']);

        // O evento 'payment.approved' foi disparado
        $this->assertCount(1, $eventsDispatched);
        $this->assertSame('payment.approved', $eventsDispatched[0]['event']);
        $this->assertSame(100, $eventsDispatched[0]['payload']['user_id']);
        $this->assertSame(404, $eventsDispatched[0]['payload']['course_id']);

        // O despachante de entrega do Academy (AcademyCourseFulfillment) atende a matrícula via interface:
        $fulfillment = new AcademyCourseFulfillment($enrollmentService);
        $this->assertTrue($fulfillment->supports('course'));

        $delivered = $fulfillment->fulfill(100, 404, $orderRef);
        $this->assertTrue($delivered);

        // =========================================================================
        // 6. APÓS O PAGAMENTO: AULA RESTAURADA E ACESSO 100% LIBERADO PARA O ALUNO
        // =========================================================================
        $this->assertTrue($enrollmentService->hasActiveEnrollment(100, 404));
        $this->assertContains(404, $enrollmentService->getActiveCourseIds(100));

        // Teste de exibição da aula real no CourseFrontController do Academy
        $courseMock = $this->createMock(CourseRepositoryInterface::class);
        $courseMock->method('findBySlug')->with('curso-solid-avancado')->willReturn($rawCourseData);

        $structureMock = $this->createMock(CourseStructureRepositoryInterface::class);
        $structureMock->method('getPublishedLessonBySlug')->with(404, 'aula-01')->willReturn([
            'id'        => 1,
            'course_id' => 404,
            'title'     => 'Aula Magna',
            'slug'      => 'aula-01',
            'status'    => 'published',
            'content'   => json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Bem-vindo ao Curso! Conteúdo 100% Desbloqueado.']]]]),
        ]);

        $navMock = $this->createMock(CourseNavigationInterface::class);
        $navMock->method('renderCourseNav')->willReturn('<nav>sidebar</nav>');
        $navMock->method('getAdjacentLessons')->willReturn(['prev' => null, 'next' => null]);

        $frontController = new CourseFrontController(
            $courseMock,
            $structureMock,
            $navMock,
            $this->createMock(\DomainSystem\Plugins\academy\Contracts\CourseCatalogRendererInterface::class),
            null,
            null,
            new \DomainSystem\Plugins\academy\Services\LessonBlockRenderer(),
            null,
            $enrollmentService,
            $session
        );

        $lessonResponse = $frontController->showLesson('curso-solid-avancado', 'aula-01');
        $lessonHtml = $lessonResponse->getContent();

        // A aula NÃO exibe mais a tela de bloqueio com botão de compra
        $this->assertStringNotContainsString('Conteúdo Exclusivo para Alunos Matriculados', $lessonHtml);
        // O conteúdo foi renderizado com sucesso para o aluno!
        $this->assertStringContainsString('Bem-vindo ao Curso! Conteúdo 100% Desbloqueado.', $lessonHtml);

        // =========================================================================
        // 7. SIMULAÇÃO DE REEMBOLSO (T13): MATRÍCULA REVOGADA BLOQUEIA O ACESSO
        // =========================================================================
        $enrollmentRepo->updateStatus(100, 404, 'cancelled');
        $this->assertFalse($enrollmentService->hasActiveEnrollment(100, 404));

        $revokedResponse = $frontController->showLesson('curso-solid-avancado', 'aula-01');
        $this->assertStringContainsString('Conteúdo Exclusivo para Alunos Matriculados', $revokedResponse->getContent());
    }
}
