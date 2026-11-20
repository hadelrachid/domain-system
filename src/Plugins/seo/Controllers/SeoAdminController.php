<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo\Controllers;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\seo\Contracts\PageSpeedAuditInterface;
use DomainSystem\Plugins\seo\Repositories\PdoPageSpeedAuditRepository;
use Throwable;

/**
 * Controlador Administrativo do DomainSEO & Performance Hub.
 */
class SeoAdminController
{
    public function __construct(
        private readonly PageSpeedAuditInterface $auditService,
        private readonly PdoPageSpeedAuditRepository $auditRepo,
        private readonly ?SessionManagerInterface $session = null
    ) {}

    public function index(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $latestPair = $this->auditRepo->getLatestPair();
        $history = $this->auditRepo->getHistory(15);
        $defaultUrl = 'https://rachidd.com';

        $flash = $this->session ? $this->session->getFlash() : [];
        $flashMsg = $flash['msg'] ?? null;
        $flashType = $flash['type'] ?? null;

        $content = $this->renderView('admin/performance', [
            'baseUrl'           => $baseUrl,
            'latestMobile'      => $latestPair['mobile'],
            'latestDesktop'     => $latestPair['desktop'],
            'history'           => $history,
            'defaultUrl'        => $defaultUrl,
            'publicTestUrl'     => $this->auditService->getPublicTestUrl($defaultUrl),
            'flashMsg'          => $flashMsg,
            'flashType'         => $flashType,
            'csrfToken'         => $this->session ? (string)$this->session->getCsrfToken() : '',
        ]);

        return new ViewResponse($content);
    }

    public function runAudit(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $targetUrl = trim((string)$request->input('url', 'https://rachidd.com'));
        if ($targetUrl === '') {
            $targetUrl = 'https://rachidd.com';
        }

        $strategy = strtolower(trim((string)$request->input('strategy', 'mobile')));
        $runBoth = (bool)$request->input('both', false) || $strategy === 'both';

        $results = [];

        try {
            if ($runBoth) {
                $mob = $this->auditService->audit($targetUrl, 'mobile');
                $this->auditRepo->save($mob);
                $results['mobile'] = $mob;

                $desk = $this->auditService->audit($targetUrl, 'desktop');
                $this->auditRepo->save($desk);
                $results['desktop'] = $desk;
            } else {
                $strat = in_array($strategy, ['mobile', 'desktop'], true) ? $strategy : 'mobile';
                $audit = $this->auditService->audit($targetUrl, $strat);
                $this->auditRepo->save($audit);
                $results[$strat] = $audit;
            }

            if ($this->isAjax($request)) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Auditoria de performance executada com sucesso!',
                    'results' => $results,
                ]);
            }

            if ($this->session) {
                $this->session->setFlash('success', 'Auditoria Google PageSpeed executada com sucesso!');
            }
        } catch (Throwable $e) {
            if ($this->isAjax($request)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Falha ao executar auditoria: ' . $e->getMessage(),
                ], 500);
            }

            if ($this->session) {
                $this->session->setFlash('error', 'Falha ao executar auditoria: ' . $e->getMessage());
            }
        }

        return Response::redirect($baseUrl . '/admin/seo');
    }

    public function getAiFeed(Request $request): ResponseInterface
    {
        $summary = $this->auditRepo->getSummaryForAi();
        return new JsonResponse($summary);
    }

    private function renderView(string $viewName, array $data = []): string
    {
        $viewFile = dirname(__DIR__) . '/views/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($viewFile)) {
            return "<div style='padding:24px; color:#fff;'><h1>DomainSEO</h1><p>Visualização {$viewName} não encontrada.</p></div>";
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewFile;
        return (string)ob_get_clean();
    }

    private function isAjax(Request $request): bool
    {
        return str_contains(strtolower((string)$request->header('Accept')), 'application/json')
            || str_contains(strtolower((string)$request->header('X-Requested-With')), 'xmlhttprequest');
    }
}
