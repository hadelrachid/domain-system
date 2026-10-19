<?php

namespace DomainSystem\Core\Http;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\RouterInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Monitoring\RequestMetricsRecorder;
use DomainSystem\Core\Workspace\WorkspaceManager;

/**
 * Orquestrador HTTP do Kernel (SRP & Testabilidade E2E).
 * Centraliza o despacho da requisição, política de manutenção, normalização de Response,
 * envelopamento de Workspace, CachePolicy e telemetria.
 */
class HttpKernel
{
    private RouterInterface $router;
    private ContainerInterface $container;
    private ?WorkspaceManager $workspaceManager;
    private CachePolicy $cachePolicy;

    public function __construct(
        RouterInterface $router,
        ContainerInterface $container,
        ?WorkspaceManager $workspaceManager = null,
        ?CachePolicy $cachePolicy = null
    ) {
        $this->router = $router;
        $this->container = $container;
        $this->workspaceManager = $workspaceManager;
        $this->cachePolicy = $cachePolicy ?? new CachePolicy();
    }

    public function handle(
        Request $request,
        bool $maintenanceFileExists = false,
        ?string $maintenancePagePath = null
    ): Response {
        $uri = strtok($request->uri(), '?') ?: '/';

        $siteState = null;
        $userRole = '';
        try {
            if ($this->container->has(SiteStateInterface::class)) {
                $siteState = $this->container->make(SiteStateInterface::class);
            }
        } catch (\Throwable $e) {
            $siteState = null;
        }

        try {
            if ($this->container->has(SessionManagerInterface::class)) {
                $sessionForState = $this->container->make(SessionManagerInterface::class);
                $userRole = (string)$sessionForState->get('user_role', '');
            }
        } catch (\Throwable $e) {
            $userRole = '';
        }

        if (MaintenancePolicy::shouldBlock($uri, $maintenanceFileExists, $siteState, $userRole)) {
            $maintHtml = ($maintenancePagePath !== null && file_exists($maintenancePagePath))
                ? (string)file_get_contents($maintenancePagePath)
                : "<h1 style='text-align:center; font-family:sans-serif; margin-top:50px;'>Manutenção do Sistema. Voltamos em breve.</h1>";
            $response = new Response($maintHtml, 503, ['Retry-After' => '3600']);
        } else {
            $response = $this->router->dispatch($request);
        }

        if (!$response instanceof Response) {
            if (is_array($response) || is_object($response)) {
                $response = new JsonResponse($response);
            } else {
                $wrap = false;
                if (strpos($uri, '/admin') === 0 && !isset($request->query['raw']) && !isset($_GET['raw']) && !str_starts_with($uri, '/admin/themes/preview')) {
                    $wrap = true;
                }
                $response = new ViewResponse((string)$response, $wrap);
            }
        }

        if ($response instanceof ViewResponse && $response->shouldWrapLayout()) {
            $workspaceManager = $this->workspaceManager;
            if ($workspaceManager === null) {
                try {
                    $workspaceManager = $this->container->make(WorkspaceManager::class);
                } catch (\Throwable $e) {
                    $workspaceManager = null;
                }
            }

            if ($workspaceManager !== null) {
                $role = $userRole !== '' ? $userRole : 'admin';
                $workspace = $workspaceManager->getWorkspace($role);
                $wrappedContent = $workspace->wrap($response->getContent());
                $response->setContent($wrappedContent);
            }
        }

        $response = $this->cachePolicy->apply($request, $response);

        try {
            if ($this->container->has(RequestMetricsRecorder::class)) {
                $metricsRecorder = $this->container->make(RequestMetricsRecorder::class);
                $metricsRecorder->record($uri, $request->method());
            }
        } catch (\Throwable $e) {
            // Não interrompe a resposta caso a gravação de métricas falhe
        }

        return $response;
    }
}
