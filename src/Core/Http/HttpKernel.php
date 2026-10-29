<?php

declare(strict_types=1);

namespace DomainSystem\Core\Http;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\RouterInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Error\CircuitBreaker;
use DomainSystem\Core\Exceptions\HttpResponseException;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Monitoring\RequestMetricsRecorder;
use DomainSystem\Core\Workspace\WorkspaceManager;
use Throwable;

/**
 * Orquestrador HTTP do Kernel (SRP & Testabilidade E2E).
 * Centraliza o despacho da requisição, política de manutenção, normalização de Response,
 * envelopamento de Workspace, CachePolicy, telemetria e Circuit Breaker de Rotas (Pilar 2 v2.4).
 */
class HttpKernel
{
    private RouterInterface $router;
    private ContainerInterface $container;
    private ?WorkspaceManager $workspaceManager;
    private CachePolicy $cachePolicy;
    private int $routeFailureThreshold;

    public function __construct(
        RouterInterface $router,
        ContainerInterface $container,
        ?WorkspaceManager $workspaceManager = null,
        ?CachePolicy $cachePolicy = null,
        int $routeFailureThreshold = 3
    ) {
        $this->router = $router;
        $this->container = $container;
        $this->workspaceManager = $workspaceManager;
        $this->cachePolicy = $cachePolicy ?? new CachePolicy();
        $this->routeFailureThreshold = max(1, $routeFailureThreshold);
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
        } catch (Throwable $e) {
            $siteState = null;
        }

        try {
            if ($this->container->has(SessionManagerInterface::class)) {
                $sessionForState = $this->container->make(SessionManagerInterface::class);
                $userRole = (string)$sessionForState->get('user_role', '');
            }
        } catch (Throwable $e) {
            $userRole = '';
        }

        if (MaintenancePolicy::shouldBlock($uri, $maintenanceFileExists, $siteState, $userRole)) {
            $maintHtml = ($maintenancePagePath !== null && file_exists($maintenancePagePath))
                ? (string)file_get_contents($maintenancePagePath)
                : "<h1 style='text-align:center; font-family:sans-serif; margin-top:50px;'>Manutenção do Sistema. Voltamos em breve.</h1>";
            $response = new Response($maintHtml, 503, ['Retry-After' => '3600']);
        } else {
            try {
                $response = $this->router->dispatch($request);
            } catch (HttpResponseException $e) {
                throw $e;
            } catch (Throwable $e) {
                try {
                    if ($this->container->has(CircuitBreaker::class)) {
                        /** @var CircuitBreaker $breaker */
                        $breaker = $this->container->make(CircuitBreaker::class);
                        $breaker->recordRouteException($e, $this->routeFailureThreshold);
                    }
                } catch (Throwable $inner) {
                    // Ignora erro interno do registrador de falhas
                }
                throw $e;
            }
        }

        if (!$response instanceof Response) {
            if (is_array($response) || is_object($response)) {
                $response = new JsonResponse($response);
            } else {
                $wrap = false;
                if (strpos($uri, '/admin') === 0 && !isset($request->query['raw']) && !str_starts_with($uri, '/admin/themes/preview')) {
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
                } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            // Não interrompe a resposta caso a gravação de métricas falhe
        }

        return $response;
    }
}
