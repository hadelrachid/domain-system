<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMediaSyncInterface;
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
        private readonly ?ProductMediaSyncInterface $mediaSync = null,
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
        $cycleInfo = $this->miningService->getDailyCycleInfo();

        $flash = $this->session->getFlash();
        $flashMsg = $flash['msg'] ?? null;
        $flashType = $flash['type'] ?? null;

        ob_start();
        $csrfToken = (string)$this->session->getCsrfToken();
        include dirname(__DIR__) . '/views/admin/index.php';
        $content = ob_get_clean();

        return new ViewResponse($content ?: '');
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
        $sort = (string)$request->input('sort', 'volume_desc');

        try {
            $mined = $this->miningService->mineTrendingProducts($category, $limit, true, $sort);
            $count = count($mined);
            $this->session->setFlash('success', "🚀 Mineração concluída com sucesso! {$count} produtos com alta reputação minerados e adicionados ao catálogo.");
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

        return new ViewResponse($content ?: '');
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
        $newAccessToken = trim((string)$request->input('access_token', ''));
        if ($newAccessToken !== '' && !str_starts_with($newAccessToken, '***')) {
            $config['access_token'] = $newAccessToken;
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

        return new ViewResponse($content ?: '');
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

    public function syncImages(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        if ($this->mediaSync === null) {
            $this->session->setFlash('error', 'Serviço de sincronização de mídia indisponível.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        try {
            $updated = $this->mediaSync->syncProductMedia((int)$id);
            $imgCount = count($updated->images);
            $this->session->setFlash('success', "🔄 Produto '{$updated->title}' sincronizado com sucesso direto do link oficial! {$imgCount} fotos em alta resolução carregadas.");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Falha ao sincronizar imagens: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/dropship');
    }

    public function syncAllImages(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        if ($this->mediaSync === null) {
            $this->session->setFlash('error', 'Serviço de sincronização de mídia indisponível.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        try {
            $res = $this->mediaSync->syncAllCatalogMedia();
            $this->session->setFlash('success', "🔄 Varredura concluída: {$res['synced']} produtos sincronizados com as imagens oficiais dos seus links.");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Falha na sincronização em lote: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/dropship');
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

    public function updatePrice(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            if ($this->isAjaxOrJson($request)) {
                return new JsonResponse(['success' => false, 'message' => 'Token de segurança expirado.'], 403);
            }
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $productId = (int)$id;
        $product = $this->catalogRepo->findById($productId);
        if ($product === null) {
            if ($this->isAjaxOrJson($request)) {
                return new JsonResponse(['success' => false, 'message' => 'Produto não encontrado.'], 404);
            }
            $this->session->setFlash('error', 'Produto não encontrado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $rate = $this->currencyConverter->getExchangeRate('USD', 'BRL');
        if ($rate <= 0) {
            $rate = 5.50;
        }

        // Preço de venda em BRL
        $salePriceBrl = max(0.01, $this->parseCurrency($request->input('sale_price_brl')));

        // Custo pode ser informado em BRL ou USD
        $costPriceUsd = null;
        if ($request->input('cost_price_brl') !== null && $request->input('cost_price_brl') !== '') {
            $costBrl = max(0.0, $this->parseCurrency($request->input('cost_price_brl')));
            $costPriceUsd = round($costBrl / $rate, 2);
        } elseif ($request->input('cost_price_usd') !== null && $request->input('cost_price_usd') !== '') {
            $costPriceUsd = max(0.0, $this->parseCurrency($request->input('cost_price_usd')));
        }

        $this->catalogRepo->updatePrice($productId, $salePriceBrl, $costPriceUsd);

        $finalCostUsd = $costPriceUsd ?? $product->costPriceUsd;
        $finalCostBrl = $finalCostUsd * $rate;
        $profitBrl = $salePriceBrl - $finalCostBrl;
        $marginPercent = ($salePriceBrl > 0) ? round(($profitBrl / $salePriceBrl) * 100, 1) : 0;

        $msg = "💰 Preço de '{$product->title}' atualizado com sucesso! Venda: R$ " . number_format($salePriceBrl, 2, ',', '.') . " (Margem: {$marginPercent}% / R$ " . number_format($profitBrl, 2, ',', '.') . ")";

        if ($this->isAjaxOrJson($request)) {
            return new JsonResponse([
                'success'        => true,
                'message'        => $msg,
                'sale_price_brl' => $salePriceBrl,
                'sale_formatted' => 'R$ ' . number_format($salePriceBrl, 2, ',', '.'),
                'cost_price_usd' => $finalCostUsd,
                'cost_price_brl' => round($finalCostBrl, 2),
                'cost_formatted' => 'R$ ' . number_format($finalCostBrl, 2, ',', '.'),
                'profit_brl'     => round($profitBrl, 2),
                'margin_percent' => $marginPercent,
            ]);
        }

        $this->session->setFlash('success', $msg);
        return Response::redirect($baseUrl . '/admin/dropship');
    }

    private function isAjaxOrJson(Request $request): bool
    {
        return str_contains((string)$request->header('Accept', ''), 'application/json')
            || (bool)$request->input('ajax', false)
            || ($request->header('X-Requested-With') === 'XMLHttpRequest');
    }

    private function parseCurrency(string|float|int|null $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        $str = trim((string)$value);
        $str = str_replace(['R$', '$', ' '], '', $str);

        if (str_contains($str, ',')) {
            // Formato brasileiro: 1.234,56 -> remove pontos, substitui vírgula por ponto
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        }

        return (float)$str;
    }

    public function runDailyCycle(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            if ($this->isAjaxOrJson($request)) {
                return new Response(json_encode(['success' => false, 'error' => 'Token CSRF inválido']), 403, ['Content-Type' => 'application/json']);
            }
            $this->session->setFlash('Token de segurança expirado. Tente novamente.', 'error');
            return new Response('', 302, ['Location' => $baseUrl . '/admin/dropship']);
        }

        try {
            $itemsPerCategory = max(1, min(10, (int)$request->input('items_per_category', 3)));
            $result = $this->miningService->runDailyAutomatedRotation($itemsPerCategory);
            $cycleInfo = $this->miningService->getDailyCycleInfo();

            if ($this->isAjaxOrJson($request)) {
                return new Response(json_encode([
                    'success' => true,
                    'message' => "Ciclo diário 24h finalizado com sucesso! Total minerado: {$result['total_mined']} produtos.",
                    'result'  => $result,
                    'cycle'   => $cycleInfo,
                ]), 200, ['Content-Type' => 'application/json']);
            }

            $this->session->setFlash("Ciclo diário 24h concluído com sucesso! {$result['total_mined']} novos produtos minerados nas 6 categorias.", 'success');
        } catch (Throwable $e) {
            if ($this->isAjaxOrJson($request)) {
                return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
            }
            $this->session->setFlash("Falha ao executar ciclo diário: " . $e->getMessage(), 'error');
        }

        return new Response('', 302, ['Location' => $baseUrl . '/admin/dropship']);
    }

    private function verifyCsrf(Request $request): bool
    {
        $token = (string)$request->input('csrf_token', (string)$request->input('_csrf', (string)($request->server['HTTP_X_CSRF_TOKEN'] ?? '')));
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
