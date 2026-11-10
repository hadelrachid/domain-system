<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use Throwable;

/**
 * Controlador Administrativo do Hub DomainDrop (SRP / DIP).
 */
class DropshipAdminController
{
    private string $configFile;

    public function __construct(
        private readonly DropshipCatalogRepositoryInterface $catalogRepo,
        private readonly ProductMiningServiceInterface $miningService,
        private readonly CurrencyConverterInterface $currencyConverter,
        private readonly SessionManagerInterface $session,
        private readonly ?\DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface $orderRepo = null,
        ?string $basePath = null
    ) {
        $root = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
        $this->configFile = $root . '/config/plugins/domain_drop.json';
    }

    public function index(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $category = (string)$request->input('category', '');
        $search = (string)$request->input('q', '');

        $criteria = [];
        if ($category !== '') {
            $criteria['category'] = $category;
        }
        if ($search !== '') {
            $criteria['search'] = $search;
        }

        $products = $this->catalogRepo->list($criteria, 50, 0);
        $totalCount = $this->catalogRepo->count($criteria);
        $categories = $this->catalogRepo->getCategories();
        $rate = $this->currencyConverter->getExchangeRate('USD', 'BRL');
        $config = $this->loadConfig();

        $flash = $this->session->getFlash();
        $flashMsg = $flash['msg'] ?? null;
        $flashType = $flash['type'] ?? null;

        ob_start();
        $csrfToken = (string)$this->session->getCsrfToken();
        include dirname(__DIR__) . '/views/admin/index.php';
        $content = ob_get_clean();

        return new Response($content ?: '');
    }

    public function mine(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $category = (string)$request->input('category', 'Eletrônicos');
        $limit = max(1, min(30, (int)$request->input('limit', 10)));

        try {
            $mined = $this->miningService->mineTrendingProducts($category, $limit, true);
            $count = count($mined);
            $this->session->setFlash('success', "🚀 Mineração concluída com sucesso! {$count} produtos em alta minerados e adicionados ao catálogo.");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Falha ao minerar produtos: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/dropship');
    }

    public function importUrl(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $urlOrId = trim((string)$request->input('product_url_or_id', ''));
        if ($urlOrId === '') {
            $this->session->setFlash('error', 'Informe a URL ou ID do produto no AliExpress.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        try {
            $product = $this->miningService->importSingleProduct($urlOrId);
            $this->session->setFlash('success', "✅ Produto '{$product->title}' importado com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Falha na importação: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/dropship');
    }

    public function settings(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $config = $this->loadConfig();
        $csrfToken = (string)$this->session->getCsrfToken();
        $rate = $this->currencyConverter->getExchangeRate('USD', 'BRL');

        $flash = $this->session->getFlash();
        $flashMsg = $flash['msg'] ?? null;
        $flashType = $flash['type'] ?? null;

        ob_start();
        include dirname(__DIR__) . '/views/admin/settings.php';
        $content = ob_get_clean();

        return new Response($content ?: '');
    }

    public function saveSettings(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship/settings');
        }

        $config = $this->loadConfig();
        $config['app_key'] = trim((string)$request->input('app_key', $config['app_key'] ?? ''));
        $newSecret = trim((string)$request->input('app_secret', ''));
        if ($newSecret !== '' && !str_starts_with($newSecret, '***')) {
            $config['app_secret'] = $newSecret;
        }
        $config['default_markup_percent'] = max(10.0, (float)$request->input('default_markup_percent', 100.0));
        $config['min_profit_margin_brl'] = max(0.0, (float)$request->input('min_profit_margin_brl', 30.0));

        @file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->session->setFlash('success', 'Configurações do DomainDrop salvas com sucesso!');

        return Response::redirect($baseUrl . '/admin/dropship/settings');
    }

    public function orders(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $csrfToken = (string)$this->session->getCsrfToken();
        $status = (string)$request->input('status', '');
        $search = (string)$request->input('q', '');

        $filters = [];
        if ($status !== '') {
            $filters['status'] = $status;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }

        $orders = $this->orderRepo !== null ? $this->orderRepo->list($filters, 50, 0) : [];

        $flash = $this->session->getFlash();
        $flashMsg = $flash['msg'] ?? null;
        $flashType = $flash['type'] ?? null;

        ob_start();
        include dirname(__DIR__) . '/views/admin/orders.php';
        $content = ob_get_clean();

        return new Response($content ?: '');
    }

    public function updateFulfillment(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship/orders');
        }

        $orderRef = (string)$request->input('order_ref', '');
        $status = (string)$request->input('status', 'paid');
        $aeOrderId = trim((string)$request->input('aliexpress_order_id', ''));
        $tracking = trim((string)$request->input('tracking_code', ''));

        if ($this->orderRepo !== null && $orderRef !== '') {
            $this->orderRepo->updateFulfillment(
                $orderRef,
                $status,
                $aeOrderId !== '' ? $aeOrderId : null,
                $tracking !== '' ? $tracking : null
            );
            $this->session->setFlash('success', 'Status de envio do pedido atualizado com sucesso!');
        }

        return Response::redirect($baseUrl . '/admin/dropship/orders');
    }

    public function delete(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $this->catalogRepo->delete((int)$id);
        $this->session->setFlash('success', 'Produto removido do catálogo.');

        return Response::redirect($baseUrl . '/admin/dropship');
    }

    private function verifyCsrf(Request $request): bool
    {
        $token = (string)$request->input('csrf_token', '');
        $expected = (string)$this->session->getCsrfToken();
        return $token !== '' && hash_equals($expected, $token);
    }

    private function loadConfig(): array
    {
        if (file_exists($this->configFile)) {
            $json = @file_get_contents($this->configFile);
            if ($json !== false) {
                return json_decode($json, true) ?: [];
            }
        }
        return [
            'app_key' => '558628',
            'app_secret' => '',
            'default_markup_percent' => 100.0,
            'min_profit_margin_brl' => 30.0,
        ];
    }
}
