<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\Parts\Blocks\UserAuthBlock;
use DomainSystem\Plugins\academy\Controllers\StudentAuthController;
use DomainSystem\Plugins\academy\Controllers\StudentDashboardController;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressInterface;
use DomainSystem\Plugins\academy\Contracts\StudentStorageInterface;
use PDO;
use PHPUnit\Framework\TestCase;

class StudentAuthAndHeaderBlockTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                username TEXT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function testUserAuthBlockRendersLoginAndRegisterButtonsWhenLoggedOut(): void
    {
        $block = new UserAuthBlock();
        $this->assertSame('user_auth', $block->getType());
        $this->assertSame('Área de Login / Aluno', $block->getName());

        $html = $block->render([], ['is_logged_in' => false]);

        $this->assertStringContainsString('ds-block-user-auth', $html);
        $this->assertStringContainsString('Entrar', $html);
        $this->assertStringContainsString('/aluno/login', $html);
        $this->assertStringContainsString('Cadastrar', $html);
        $this->assertStringContainsString('/aluno/cadastro', $html);
    }

    public function testUserAuthBlockRendersDashboardAndLogoutWhenLoggedIn(): void
    {
        $block = new UserAuthBlock();

        $html = $block->render([], [
            'is_logged_in' => true,
            'user_name'    => 'Carlos Dev',
        ]);

        $this->assertStringContainsString('ds-block-user-auth', $html);
        $this->assertStringContainsString('Área do Aluno', $html);
        $this->assertStringContainsString('/aluno/painel', $html);
        $this->assertStringNotContainsString('Carlos Dev', $html);
        $this->assertStringContainsString('Sair', $html);
        $this->assertStringContainsString('/aluno/logout', $html);
    }

    public function testUserAuthBlockDoesNotTreatAdminSessionAsStudent(): void
    {
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('has')->willReturnCallback(function ($key) {
            // Admin logado no painel geral, mas NÃO na academia
            return $key === 'user_id' || $key === 'user_role';
        });
        $sessionMock->method('get')->willReturnCallback(function ($key, $default = null) {
            if ($key === 'user_id') return 1;
            if ($key === 'user_role') return 'admin';
            return $default;
        });

        $block = new UserAuthBlock(null, null, null, $sessionMock);
        $html = $block->render([], []);

        // Deve exibir Entrar e Cadastrar, protegendo o isolamento de privilégios
        $this->assertStringContainsString('Entrar', $html);
        $this->assertStringContainsString('Cadastrar', $html);
        $this->assertStringNotContainsString('Área do Aluno', $html);
    }

    public function testStudentLogoutRedirectsToHome(): void
    {
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->expects($this->atLeastOnce())->method('remove');
        $sessionMock->expects($this->once())->method('setFlash')->with('success', 'Você saiu da sua conta.');

        $controller = new StudentAuthController($sessionMock, $this->pdo);
        $response = $controller->logout(new Request());

        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeader('Location');
        $this->assertTrue($location === '/' || str_ends_with($location, '/'));
    }

    public function testStudentRegistrationCreatesUserAndAutoLogsIn(): void
    {
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('getCsrfToken')->willReturn('VALID_CSRF_TOKEN');

        $sessionValues = [];
        $sessionMock->method('set')->willReturnCallback(function ($key, $val) use (&$sessionValues) {
            $sessionValues[$key] = $val;
        });

        $controller = new StudentAuthController($sessionMock, $this->pdo);

        $req = new Request([], [
            'csrf_token'            => 'VALID_CSRF_TOKEN',
            'name'                  => 'HADEL RACHID DAHER JUNIOR',
            'username'              => 'hadelrachid',
            'email'                 => 'fernanda@aluna.com',
            'password'              => 'segredo123',
            'password_confirmation' => 'segredo123',
        ]);

        $response = $controller->register($req);

        // Deve redirecionar para o painel do aluno
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/aluno/painel', $response->getHeader('Location'));

        // Usuário deve ter sido gravado no banco de dados com hash seguro
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['fernanda@aluna.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($user);
        $this->assertSame('HADEL RACHID DAHER JUNIOR', $user['name']);
        $this->assertSame('hadelrachid', $user['username']);
        $this->assertTrue(password_verify('segredo123', $user['password']));

        // Sessão do aluno deve ter sido preenchida no escopo isolado
        $this->assertSame((int)$user['id'], $sessionValues['academy_student_id']);
        $this->assertSame('HADEL RACHID DAHER JUNIOR', $sessionValues['academy_student_name']);
        $this->assertSame('hadelrachid', $sessionValues['academy_student_username']);
        $this->assertSame('fernanda@aluna.com', $sessionValues['academy_student_email']);
    }

    public function testStudentAuthenticationWithValidAndInvalidCredentials(): void
    {
        // 1. Cria usuário prévio
        $hash = password_hash('alunoSenha123', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (name, email, password, role)
            VALUES ('Marcos Aluno', 'marcos@teste.com', '{$hash}', 'user')
        ");

        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('getCsrfToken')->willReturn('VALID_CSRF_TOKEN');

        $sessionValues = [];
        $sessionMock->method('set')->willReturnCallback(function ($key, $val) use (&$sessionValues) {
            $sessionValues[$key] = $val;
        });

        $controller = new StudentAuthController($sessionMock, $this->pdo);

        // Tentativa inválida (senha errada)
        $invalidReq = new Request([], [
            'csrf_token' => 'VALID_CSRF_TOKEN',
            'email'      => 'marcos@teste.com',
            'password'   => 'senhaErrada',
        ]);
        $responseFail = $controller->authenticate($invalidReq);
        $this->assertSame(302, $responseFail->getStatusCode());
        $this->assertStringContainsString('/aluno/login', $responseFail->getHeader('Location'));
        $this->assertArrayNotHasKey('academy_student_id', $sessionValues);

        // Tentativa válida
        $validReq = new Request([], [
            'csrf_token' => 'VALID_CSRF_TOKEN',
            'email'      => 'marcos@teste.com',
            'password'   => 'alunoSenha123',
        ]);
        $responseSuccess = $controller->authenticate($validReq);
        $this->assertSame(302, $responseSuccess->getStatusCode());
        $this->assertStringContainsString('/aluno/painel', $responseSuccess->getHeader('Location'));
        $this->assertSame('Marcos Aluno', $sessionValues['academy_student_name']);
    }

    public function testStudentDashboardDisplaysPurchasableCoursesWithInstallmentPrices(): void
    {
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('has')->willReturnCallback(function ($key) {
            return $key === 'academy_student_id';
        });
        $sessionMock->method('get')->willReturnCallback(function ($key, $default = null) {
            if ($key === 'academy_student_id') return 42;
            if ($key === 'academy_student_name') return 'Aluno Teste';
            return $default;
        });

        $enrollmentMock = $this->createMock(CourseEnrollmentInterface::class);
        $enrollmentMock->method('getActiveCourseIds')->with(42)->willReturn([10]);

        $courseRepoMock = $this->createMock(CourseRepositoryInterface::class);
        $courseRepoMock->method('findById')->with(10)->willReturn([
            'id'          => 10,
            'title'       => 'Curso Já Comprado',
            'slug'        => 'curso-ja-comprado',
            'status'      => 'published',
            'description' => 'Acesso liberado',
        ]);
        $courseRepoMock->method('findPublished')->willReturn([
            [
                'id'                    => 10,
                'title'                 => 'Curso Já Comprado',
                'slug'                  => 'curso-ja-comprado',
                'status'                => 'published',
            ],
            [
                'id'                    => 20,
                'title'                 => 'Novo Curso de Hotmart',
                'slug'                  => 'novo-curso-hotmart',
                'status'                => 'published',
                'description'           => 'Disponível para compra',
                'price'                 => 197.00,
                'is_free'               => 0,
                'max_installments'      => 12,
                'external_platform'     => 'hotmart',
                'external_checkout_url' => 'https://pay.hotmart.com/HOT123',
            ],
        ]);

        $structureRepoMock = $this->createMock(CourseStructureRepositoryInterface::class);
        $structureRepoMock->method('getLessons')->willReturn([]);

        $progressMock = $this->createMock(StudentProgressInterface::class);
        $progressMock->method('getCourseProgressPercentage')->willReturn(50.0);
        $progressMock->method('getCompletedLessonIds')->willReturn([]);

        $storageMock = $this->createMock(StudentStorageInterface::class);
        $storageMock->method('listStudentFiles')->willReturn([]);

        $dashboardController = new StudentDashboardController(
            $sessionMock,
            $enrollmentMock,
            $courseRepoMock,
            $structureRepoMock,
            $progressMock,
            $storageMock
        );

        $response = $dashboardController->index(new Request());
        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();

        // Deve conter o curso matriculado
        $this->assertStringContainsString('Curso Já Comprado', $content);

        // Deve conter a seção de cursos disponíveis para compra
        $this->assertStringContainsString('Cursos Disponíveis para Compra', $content);
        $this->assertStringContainsString('Novo Curso de Hotmart', $content);
        $this->assertStringContainsString('R$ 197,00', $content);
        $this->assertStringContainsString('12x de R$ 16,42', $content);
        $this->assertStringContainsString('Comprar via Hotmart', $content);
        $this->assertStringContainsString('https://pay.hotmart.com/HOT123', $content);
    }

    public function testRouteBoundaryGuardStrictlyRestrictsAdminWorkspace(): void
    {
        $guard = new \DomainSystem\Core\Http\RouteBoundaryGuard();
        $viewResponse = new \DomainSystem\Core\Http\Responses\ViewResponse('content', true);

        // Rotas administrativas autorizadas para envelope
        $this->assertTrue($guard->canWrapInAdminWorkspace('/admin', $viewResponse));
        $this->assertTrue($guard->canWrapInAdminWorkspace('/admin/settings', $viewResponse));
        $this->assertTrue($guard->canWrapInAdminWorkspace('/admin/academy', $viewResponse));

        // Rotas públicas e de alunos NUNCA podem ser envolvidas pelo admin workspace
        $this->assertFalse($guard->canWrapInAdminWorkspace('/aluno/login', $viewResponse));
        $this->assertFalse($guard->canWrapInAdminWorkspace('/aluno/cadastro', $viewResponse));
        $this->assertFalse($guard->canWrapInAdminWorkspace('/aluno/painel', $viewResponse));
        $this->assertFalse($guard->canWrapInAdminWorkspace('/cursos', $viewResponse));
        $this->assertFalse($guard->canWrapInAdminWorkspace('/', $viewResponse));
    }

    public function testStudentAuthFormsRenderAsStandaloneHtml5Documents(): void
    {
        $sessionMock = $this->createMock(SessionManagerInterface::class);
        $sessionMock->method('getCsrfToken')->willReturn('MOCK_CSRF');
        $sessionMock->method('has')->willReturn(false);

        $controller = new StudentAuthController($sessionMock, $this->pdo);

        // 1. Tela de Login do Aluno (2 Cards Lado a Lado: Login + Cadastro + Olhinho de Senha)
        $loginResponse = $controller->showLoginForm(new Request(['redirect' => '/checkout/curso/8']));
        $this->assertSame(200, $loginResponse->getStatusCode());
        $this->assertFalse($loginResponse instanceof \DomainSystem\Core\Http\Responses\ViewResponse);
        $loginHtml = $loginResponse->getContent();
        $this->assertStringContainsString('<!DOCTYPE html>', $loginHtml);
        $this->assertStringContainsString('<title>Área do Aluno | Entrar na Sala de Aula</title>', $loginHtml);
        $this->assertStringContainsString('action="/aluno/login"', $loginHtml);
        $this->assertStringContainsString('action="/aluno/cadastro"', $loginHtml);
        $this->assertStringContainsString("togglePassVisibility('student-password'", $loginHtml);
        $this->assertStringContainsString("togglePassVisibility('student-pass'", $loginHtml);
        $this->assertStringContainsString("togglePassVisibility('student-pass-conf'", $loginHtml);
        $this->assertStringContainsString('value="/checkout/curso/8"', $loginHtml);
        $this->assertStringNotContainsString('workspace-sidebar', $loginHtml);

        // 2. Tela de Cadastro do Aluno (2 Cards Lado a Lado: Login + Cadastro + Olhinho de Senha)
        $registerResponse = $controller->showRegisterForm(new Request(['redirect' => '/checkout/curso/8']));
        $this->assertSame(200, $registerResponse->getStatusCode());
        $this->assertFalse($registerResponse instanceof \DomainSystem\Core\Http\Responses\ViewResponse);
        $registerHtml = $registerResponse->getContent();
        $this->assertStringContainsString('<!DOCTYPE html>', $registerHtml);
        $this->assertStringContainsString('<title>Criar Conta de Aluno | Academy LMS</title>', $registerHtml);
        $this->assertStringContainsString('action="/aluno/login"', $registerHtml);
        $this->assertStringContainsString('action="/aluno/cadastro"', $registerHtml);
        $this->assertStringContainsString("togglePassVisibility('student-password'", $registerHtml);
        $this->assertStringContainsString("togglePassVisibility('student-pass'", $registerHtml);
        $this->assertStringContainsString("togglePassVisibility('student-pass-conf'", $registerHtml);
        $this->assertStringContainsString('value="/checkout/curso/8"', $registerHtml);
        $this->assertStringNotContainsString('workspace-sidebar', $registerHtml);
    }

    public function testCourseCheckoutRequiresStudentLoginEvenWhenAdminSessionIsActiveAndRedirectsBackAfterAuth(): void
    {
        // Simula Admin logado no painel (user_id = 1), mas SEM sessão de Aluno da Academy
        $adminOnlySession = $this->createMock(SessionManagerInterface::class);
        $adminOnlySession->method('getCsrfToken')->willReturn('CSRF_123');
        $adminOnlySession->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
            return match ($key) {
                'user_id'            => 1,
                'user_role'          => 'admin',
                'academy_student_id' => 0,
                default              => $default,
            };
        });

        $orderRepo = $this->createMock(\DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface::class);
        $registry = new \DomainSystem\Plugins\commerce\Services\PaymentGatewayRegistry();
        $resolver = new \DomainSystem\Plugins\commerce\Services\PurchasableItemResolver();
        $resolver->registerResolver('course', function (int $id) {
            return new \DomainSystem\Plugins\academy\Adapters\CoursePurchasableAdapter([
                'id'               => $id,
                'title'            => 'Curso de Arquitetura SOLID',
                'slug'             => 'curso-de-arquitetura-solid',
                'price'            => 297.00,
                'status'           => 'published',
                'is_free'          => 0,
                'max_installments' => 12,
            ]);
        });

        $checkoutController = new \DomainSystem\Plugins\commerce\Controllers\CheckoutController(
            session: $adminOnlySession,
            orderRepo: $orderRepo,
            gatewayRegistry: $registry,
            itemResolver: $resolver
        );

        // 1. Pressionar "Comprar" sem login de aluno DEVE redirecionar para /aluno/login?redirect=/checkout/curso/8
        $unauthResp = $checkoutController->showCheckout(
            new Request([], [], ['REQUEST_URI' => '/checkout/curso/8']),
            8
        );
        $this->assertSame(302, $unauthResp->getStatusCode());
        $this->assertSame('/aluno/login?redirect=' . urlencode('/checkout/curso/8'), $unauthResp->getHeader('Location'));

        // 2. Cadastrar novo aluno no card da direita com redirect=/checkout/curso/8 redireciona direto para a página de venda
        $sessionValues = [];
        $authSession = $this->createMock(SessionManagerInterface::class);
        $authSession->method('getCsrfToken')->willReturn('CSRF_123');
        $authSession->method('set')->willReturnCallback(function (string $k, mixed $v) use (&$sessionValues) {
            $sessionValues[$k] = $v;
        });
        $authSession->method('get')->willReturnCallback(function (string $k, mixed $default = null) use (&$sessionValues) {
            return $sessionValues[$k] ?? $default;
        });

        $studentAuth = new StudentAuthController($authSession, $this->pdo);
        $regResp = $studentAuth->register(new Request([], [
            'csrf_token'            => 'CSRF_123',
            'name'                  => 'Rachid Aluno SOLID',
            'username'              => 'rachidsolid',
            'email'                 => 'rachid.solid@academy.com',
            'password'              => 'senhaForte123',
            'password_confirmation' => 'senhaForte123',
            'redirect'              => '/checkout/curso/8',
        ]));

        $this->assertSame(302, $regResp->getStatusCode());
        $this->assertSame('/checkout/curso/8', $regResp->getHeader('Location'));

        // 3. Agora autenticado como aluno, a página de venda do curso abre e exibe a Conta da Área de Estudos
        $authedCheckout = new \DomainSystem\Plugins\commerce\Controllers\CheckoutController(
            session: $authSession,
            orderRepo: $orderRepo,
            gatewayRegistry: $registry,
            itemResolver: $resolver
        );
        $checkoutResp = $authedCheckout->showCheckout(
            new Request([], [], ['REQUEST_URI' => '/checkout/curso/8']),
            8
        );
        $this->assertSame(200, $checkoutResp->getStatusCode());
        $checkoutHtml = $checkoutResp->getContent();
        $this->assertStringContainsString('Conta da Área de Estudos (Sala de Aula)', $checkoutHtml);
        $this->assertStringContainsString('Rachid Aluno SOLID', $checkoutHtml);
        $this->assertStringContainsString('@rachidsolid', $checkoutHtml);
        $this->assertStringContainsString('rachid.solid@academy.com', $checkoutHtml);
        $this->assertStringContainsString('id="payer_email" name="payer_email" value="rachid.solid@academy.com"', $checkoutHtml);
        $this->assertStringContainsString('id="payer_name" name="payer_name" value="Rachid Aluno SOLID"', $checkoutHtml);
        $this->assertStringContainsString('Minha Área de Estudos (Meus Cursos)', $checkoutHtml);

        // 4. Processar pagamento Pix deve encaminhar o e-mail da sessão do aluno ao gateway mesmo se payer_email vier vazio no JSON
        $capturedOrderData = null;
        $mockGateway = $this->createMock(\DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface::class);
        $mockGateway->method('getId')->willReturn('mercadopago');
        $mockGateway->method('isConfigured')->willReturn(true);
        $mockGateway->method('processPayment')->willReturnCallback(function (array $orderData, array $paymentInput) use (&$capturedOrderData) {
            $capturedOrderData = $orderData;
            return \DomainSystem\Plugins\commerce\DTO\PaymentResult::pendingPix(
                (string)$orderData['order_ref'],
                '00020126580014BR.GOV.BCB.PIX',
                null,
                'TX_999'
            );
        });
        $registry->register($mockGateway);

        $processResp = $authedCheckout->process(new Request([], [
            'csrf_token'     => 'CSRF_123',
            'course_id'      => 8,
            'gateway'        => 'mercadopago',
            'payment_method' => 'pix',
            'payer_cpf'      => '12345678909',
            'payer_name'     => '',
            'payer_email'    => '',
        ]));

        $this->assertSame(200, $processResp->getStatusCode());
        $this->assertNotNull($capturedOrderData);
        $this->assertSame('rachid.solid@academy.com', $capturedOrderData['student_email']);
        $this->assertSame('Rachid Aluno SOLID', $capturedOrderData['student_name']);
    }
}
