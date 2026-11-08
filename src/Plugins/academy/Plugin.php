<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy;

use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\academy\Adapters\CoursePurchasableAdapter;
use DomainSystem\Plugins\academy\Contracts\CourseCatalogRendererInterface;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentInterface;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseImageStorageInterface;
use DomainSystem\Plugins\academy\Contracts\CourseNavigationInterface;
use DomainSystem\Plugins\academy\Contracts\CoursePackageServiceInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\LessonBlockRendererInterface;
use DomainSystem\Plugins\academy\Contracts\LessonFileScaffolderInterface;
use DomainSystem\Plugins\academy\Contracts\LessonPdfStorageInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAuthServiceInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentStorageInterface;
use DomainSystem\Plugins\academy\Contracts\StudentViewRendererInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminServiceInterface;
use DomainSystem\Plugins\academy\Contracts\ThemePageResolverInterface;
use DomainSystem\Plugins\academy\Controllers\CourseAdminController;
use DomainSystem\Plugins\academy\Controllers\CourseFrontController;
use DomainSystem\Plugins\academy\Controllers\LessonAdminController;
use DomainSystem\Plugins\academy\Controllers\StudentAdminController;
use DomainSystem\Plugins\academy\Controllers\StudentAuthController;
use DomainSystem\Plugins\academy\Controllers\StudentDashboardController;
use DomainSystem\Plugins\academy\Repositories\PdoCourseEnrollmentRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseRepository;
use DomainSystem\Plugins\academy\Repositories\PdoCourseStructureRepository;
use DomainSystem\Plugins\academy\Repositories\PdoStudentAdminRepository;
use DomainSystem\Plugins\academy\Repositories\PdoStudentProgressRepository;
use DomainSystem\Plugins\academy\Services\AcademyCourseFulfillment;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\academy\Services\CourseEnrollmentService;
use DomainSystem\Plugins\academy\Services\CourseNavigationService;
use DomainSystem\Plugins\academy\Services\CoursePackageService;
use DomainSystem\Plugins\academy\Services\CourseService;
use DomainSystem\Plugins\academy\Services\LessonBlockRenderer;
use DomainSystem\Plugins\academy\Services\LocalCourseImageStorage;
use DomainSystem\Plugins\academy\Services\LocalLessonPdfStorage;
use DomainSystem\Plugins\academy\Services\LocalStudentStorage;
use DomainSystem\Plugins\academy\Services\StudentAdminService;
use DomainSystem\Plugins\academy\Services\StudentAuthService;
use DomainSystem\Plugins\academy\Services\StudentProgressService;
use DomainSystem\Plugins\academy\Services\StudentViewRenderer;
use DomainSystem\Plugins\academy\Services\ThemeLessonFileScaffolder;
use DomainSystem\Plugins\academy\Services\ThemePageResolver;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface;
use Throwable;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db.schema');
        $os->requireLink('core.db');
        $os->requireLink('core.storage');
        $os->requireLink('core.session');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
        $os->listenHook('payment.approved');
        $os->listenHook('payment.refunded');
        $os->listenHook('commerce.register_item_resolvers');
        $os->listenHook('theme.blocks.register');
        $os->listenHook('flexbuilder.register_blocks');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Injeção de Dependências no Container via Contratos (DIP)
        $runtime->singleton(CourseRepositoryInterface::class, function () use ($runtime) {
            return new PdoCourseRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(CourseStructureRepositoryInterface::class, function () use ($runtime) {
            return new PdoCourseStructureRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(CourseEnrollmentRepositoryInterface::class, function () use ($runtime) {
            return new PdoCourseEnrollmentRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(CourseEnrollmentInterface::class, function () use ($runtime) {
            return new CourseEnrollmentService(
                $runtime->make(CourseEnrollmentRepositoryInterface::class),
                $runtime->make(CourseRepositoryInterface::class)
            );
        });

        $runtime->singleton(StudentStorageInterface::class, function () {
            return new LocalStudentStorage();
        });

        $runtime->singleton(StudentAuthServiceInterface::class, function () use ($runtime) {
            return new StudentAuthService($runtime->make(SessionManagerInterface::class));
        });

        $runtime->singleton(StudentViewRendererInterface::class, function () {
            return new StudentViewRenderer();
        });

        $runtime->singleton(StudentProgressRepositoryInterface::class, function () use ($runtime) {
            return new PdoStudentProgressRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(StudentProgressInterface::class, function () use ($runtime) {
            return new StudentProgressService(
                $runtime->make(StudentProgressRepositoryInterface::class),
                $runtime->make(CourseStructureRepositoryInterface::class)
            );
        });

        $runtime->singleton(StudentAdminRepositoryInterface::class, function () use ($runtime) {
            return new PdoStudentAdminRepository($runtime->getLink('core.db'));
        });

        $runtime->singleton(StudentAdminServiceInterface::class, function () use ($runtime) {
            return new StudentAdminService(
                $runtime->make(StudentAdminRepositoryInterface::class),
                $runtime->make(CourseEnrollmentRepositoryInterface::class),
                $runtime->make(CourseRepositoryInterface::class),
                $runtime->make(StudentProgressRepositoryInterface::class),
                $runtime->getLink('core.db')
            );
        });

        $runtime->singleton(CourseImageStorageInterface::class, function () use ($runtime) {
            return new LocalCourseImageStorage($runtime->getLink('core.storage'));
        });

        $runtime->singleton(CourseService::class, function () use ($runtime) {
            return new CourseService(
                $runtime->make(CourseRepositoryInterface::class),
                $runtime->make(CourseImageStorageInterface::class)
            );
        });

        $runtime->singleton(CourseNavigationInterface::class, function () use ($runtime) {
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            return new CourseNavigationService(
                $runtime->make(CourseRepositoryInterface::class),
                $runtime->make(CourseStructureRepositoryInterface::class),
                $baseUrl
            );
        });

        $runtime->singleton(CourseCatalogRendererInterface::class, function () use ($runtime) {
            return new CourseCatalogRenderer(
                $runtime->make(CourseRepositoryInterface::class),
                $runtime->getLink('core.storage')
            );
        });

        $runtime->singleton(CourseCatalogRenderer::class, function () use ($runtime) {
            return $runtime->make(CourseCatalogRendererInterface::class);
        });

        $runtime->singleton(LessonPdfStorageInterface::class, function () use ($runtime) {
            return new LocalLessonPdfStorage($runtime->getLink('core.storage'));
        });

        $runtime->singleton(LessonBlockRendererInterface::class, function () use ($runtime) {
            return new LessonBlockRenderer($runtime->make(LessonPdfStorageInterface::class));
        });

        $runtime->singleton(LessonBlockRenderer::class, function () use ($runtime) {
            return $runtime->make(LessonBlockRendererInterface::class);
        });

        $runtime->singleton(ThemePageResolverInterface::class, function () use ($runtime) {
            $themeManager = null;
            $themeResolver = null;
            $siteState = null;
            try {
                if ($runtime->has(ThemeManagerInterface::class)) {
                    $themeManager = $runtime->make(ThemeManagerInterface::class);
                }
                if ($runtime->has(\DomainSystem\Core\Contracts\ThemePathResolverInterface::class)) {
                    $themeResolver = $runtime->make(\DomainSystem\Core\Contracts\ThemePathResolverInterface::class);
                }
                if ($runtime->has(\DomainSystem\Core\Contracts\SiteStateInterface::class)) {
                    $siteState = $runtime->make(\DomainSystem\Core\Contracts\SiteStateInterface::class);
                }
            } catch (Throwable $e) {
                // Fallback seguro
            }
            return new ThemePageResolver($themeManager, $themeResolver, $siteState);
        });

        $runtime->singleton(LessonFileScaffolderInterface::class, function () use ($runtime) {
            return new ThemeLessonFileScaffolder(
                $runtime->make(ThemePageResolverInterface::class),
                $runtime->make(LessonBlockRendererInterface::class)
            );
        });

        $runtime->singleton(CoursePackageServiceInterface::class, function () use ($runtime) {
            return new CoursePackageService(
                $runtime->make(CourseRepositoryInterface::class),
                $runtime->make(CourseStructureRepositoryInterface::class),
                $runtime->make(ThemePageResolverInterface::class),
                $runtime->make(CourseService::class),
                $runtime->make(LessonFileScaffolderInterface::class)
            );
        });

        // 2. Menu Administrativo
        $runtime->onHook('admin.menu', function ($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title'   => 'Academy (Cursos)',
                    'url'     => '#',
                    'icon'    => '🎓',
                    'submenu' => [
                        [
                            'title' => 'Cursos & Aulas',
                            'url'   => '/admin/academy',
                            'icon'  => '📚',
                        ],
                        [
                            'title' => 'Alunos & Matrículas',
                            'url'   => '/admin/academy/students',
                            'icon'  => '👨‍🎓',
                        ],
                    ],
                ];
            }
            return $menus;
        });

        // 3. Rotas Administrativas e Públicas
        $runtime->onHook('router.register', function (Router $router) {
            // Administrativas - Cursos & Fábrica de Pastas/Arquivos
            $router->addRoute('GET', '/admin/academy', [CourseAdminController::class, 'index'], 'academy', ['admin']);
            $router->addRoute('GET', '/admin/academy/edit/{id}', [CourseAdminController::class, 'edit'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/store', [CourseAdminController::class, 'store'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/update/{id}', [CourseAdminController::class, 'update'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/structure/{id}', [CourseAdminController::class, 'updateStructure'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/scaffold/folder/{id}', [CourseAdminController::class, 'createThemeFolder'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/delete/{id}', [CourseAdminController::class, 'delete'], 'academy', ['admin']);
            $router->addRoute('GET', '/admin/academy/export/{id}', [CourseAdminController::class, 'exportZip'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/import-zip', [CourseAdminController::class, 'importZip'], 'academy', ['admin']);

            // Administrativas - Gestão de Alunos & Matrículas
            $router->addRoute('GET', '/admin/academy/students', [StudentAdminController::class, 'index'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/students/{id}/status', [StudentAdminController::class, 'changeStatus'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/students/{id}/enroll', [StudentAdminController::class, 'enroll'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/students/{id}/revoke', [StudentAdminController::class, 'revoke'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/students/{id}/reset-password', [StudentAdminController::class, 'resetPassword'], 'academy', ['admin']);

            // Administrativas - Editor em Blocos de Aulas
            $router->addRoute('GET', '/admin/academy/lessons/{id}/editor', [LessonAdminController::class, 'editor'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/lessons/{id}/update-content', [LessonAdminController::class, 'updateContent'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/lessons/{id}/upload-pdf', [LessonAdminController::class, 'uploadPdf'], 'academy', ['admin']);

            // Públicas
            $router->addRoute('GET', '/cursos', [CourseFrontController::class, 'index'], 'academy');
            $router->addRoute('GET', '/cursos/{courseSlug}', [CourseFrontController::class, 'showCourse'], 'academy');
            $router->addRoute('GET', '/cursos/{courseSlug}/{lessonSlug}', [CourseFrontController::class, 'showLesson'], 'academy');

            // Autenticação e Cadastro da Sala de Aula / Alunos
            $router->addRoute('GET', '/aluno/login', [StudentAuthController::class, 'showLoginForm'], 'academy');
            $router->addRoute('POST', '/aluno/login', [StudentAuthController::class, 'authenticate'], 'academy');
            $router->addRoute('GET', '/aluno/cadastro', [StudentAuthController::class, 'showRegisterForm'], 'academy');
            $router->addRoute('POST', '/aluno/cadastro', [StudentAuthController::class, 'register'], 'academy');
            $router->addRoute('GET', '/aluno/logout', [StudentAuthController::class, 'logout'], 'academy');
            $router->addRoute('POST', '/aluno/logout', [StudentAuthController::class, 'logout'], 'academy');

            // Cockpit / Espaço Individual do Aluno
            $router->addRoute('GET', '/aluno/painel', [StudentDashboardController::class, 'index'], 'academy');
            $router->addRoute('POST', '/aluno/aulas/{id}/toggle-complete', [StudentDashboardController::class, 'toggleLessonProgress'], 'academy');
            $router->addRoute('POST', '/aluno/aulas/{id}/notes', [StudentDashboardController::class, 'saveLessonNote'], 'academy');
            $router->addRoute('GET', '/aluno/aulas/{id}/notes', [StudentDashboardController::class, 'getLessonNote'], 'academy');
        });

        // 4. Shortcode: [hub_de_cursos]
        $runtime->onHook('shortcodes.register', function (\DomainSystem\Core\Theme\ShortcodeManager $manager) use ($runtime) {
            $manager->add('hub_de_cursos', function ($attrs = []) use ($runtime) {
                try {
                    /** @var CourseCatalogRendererInterface $renderer */
                    $renderer = $runtime->make(CourseCatalogRendererInterface::class);
                    return $renderer->render(is_array($attrs) ? $attrs : []);
                } catch (Throwable $e) {
                    return "<div style='text-align:center; padding:30px; background:rgba(255,255,255,0.03); border-radius:8px; color:var(--text-muted, #8c8f94);'>Hub de Cursos em configuração.</div>";
                }
            });
        });

        // 5. Integração com Pagamentos / Webhooks (DIP & Event-driven): ativa matrícula automaticamente
        $runtime->onHook('payment.approved', function ($payload) use ($runtime) {
            if (!is_array($payload)) {
                return;
            }
            $userId = (int)($payload['user_id'] ?? 0);
            $courseId = (int)($payload['course_id'] ?? ($payload['item_id'] ?? 0));
            $orderRef = isset($payload['order_ref']) ? (string)$payload['order_ref'] : null;
            $expiresAt = isset($payload['expires_at']) && $payload['expires_at'] instanceof \DateTimeImmutable
                ? $payload['expires_at']
                : null;

            if ($userId > 0 && $courseId > 0) {
                try {
                    /** @var CourseEnrollmentInterface $enrollmentService */
                    $enrollmentService = $runtime->make(CourseEnrollmentInterface::class);
                    $enrollmentService->enroll($userId, $courseId, $expiresAt, $orderRef);
                } catch (Throwable $e) {
                    // Proteção de isolamento de falhas
                }
            }
        });

        // 5b. Revogação de Matrícula por Reembolso (T13):
        $runtime->onHook('payment.refunded', function ($payload) use ($runtime) {
            if (!is_array($payload)) {
                return;
            }
            $userId = (int)($payload['user_id'] ?? 0);
            $courseId = (int)($payload['course_id'] ?? ($payload['item_id'] ?? 0));

            if ($userId > 0 && $courseId > 0) {
                try {
                    if ($runtime->has(CourseEnrollmentRepositoryInterface::class)) {
                        /** @var CourseEnrollmentRepositoryInterface $enrollmentRepo */
                        $enrollmentRepo = $runtime->make(CourseEnrollmentRepositoryInterface::class);
                        $enrollmentRepo->updateStatus($userId, $courseId, 'cancelled');
                    }
                } catch (Throwable $e) {
                    error_log('[Academy] Falha ao revogar matrícula por reembolso: ' . $e->getMessage());
                }
            }
        });

        // 6. Conexão com o Commerce (Padrão Adapter): expõe cursos como itens vendíveis
        $runtime->onHook('commerce.register_item_resolvers', function ($resolver) use ($runtime) {
            if ($resolver instanceof PurchasableItemResolverInterface) {
                $resolver->registerResolver('course', function (int $id) use ($runtime) {
                    try {
                        /** @var CourseRepositoryInterface $repo */
                        $repo = $runtime->make(CourseRepositoryInterface::class);
                        $course = $repo->findById($id);
                        return $course ? new CoursePurchasableAdapter($course) : null;
                    } catch (Throwable $e) {
                        return null;
                    }
                });
            }
        });

        // 7. Registro de Blocos Semânticos no Flex Builder via Contrato (SOLID - OCP / DIP)
        $registerAcademyBlocks = function ($registry) use ($runtime) {
            if (is_object($registry) && method_exists($registry, 'has') && !$registry->has('user_auth')) {
                $session = null;
                try {
                    $session = $runtime->getLink('core.session');
                } catch (\Throwable $e) {}

                $provider = new \DomainSystem\Plugins\academy\Blocks\AcademyBlockProvider(
                    null,
                    null,
                    null,
                    $session
                );
                $provider->registerBlocks($registry);
            }
        };

        $runtime->onHook('theme.blocks.register', $registerAcademyBlocks);
        $runtime->onHook('flexbuilder.register_blocks', $registerAcademyBlocks);
    }

    public function activate(OsRuntimeInterface $runtime): void
    {
        $db = $runtime->getLink('core.db');
        new PdoCourseRepository($db);
        $structureRepo = new PdoCourseStructureRepository($db);
        $structureRepo->ensureTables();
        $enrollmentRepo = new PdoCourseEnrollmentRepository($db);
        $enrollmentRepo->ensureTable();
        $progressRepo = new PdoStudentProgressRepository($db);
        $progressRepo->ensureTable();
    }
}
