<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use Throwable;

/**
 * Controlador Público da Loja Virtual de Dropshipping (SRP / DIP).
 */
class DropshipFrontController
{
    public function __construct(
        private readonly DropshipCatalogRepositoryInterface $catalogRepo,
        private readonly ?ThemeManagerInterface $themeManager = null,
        private readonly ?ProductMiningServiceInterface $miningService = null,
        private readonly ?DropshipOrderRepositoryInterface $orderRepo = null,
        private readonly ?AliExpressApiClientInterface $apiClient = null
    ) {
    }

    /**
     * Vitrine Completa da Loja: GET /loja
     */
    public function storefront(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $selectedCategory = trim((string)$request->input('cat', ''));
        $search = trim((string)$request->input('q', ''));

        // Metadados do Ciclo Diário de 24 Horas
        $cycleInfo = $this->miningService ? $this->miningService->getDailyCycleInfo() : [
            'formatted_remaining' => '24:00:00',
            'seconds_remaining'   => 86400,
            'is_due'              => false,
            'auto_sync_enabled'   => true,
        ];

        // Sincronização Autônoma Diária Lazy-Cron (Se o tempo zerou 00:00:00)
        if (!empty($cycleInfo['is_due']) && !empty($cycleInfo['auto_sync_enabled']) && $this->miningService !== null) {
            try {
                $this->miningService->runDailyAutomatedRotation(3);
                $cycleInfo = $this->miningService->getDailyCycleInfo();
            } catch (Throwable $e) {
                // Silencioso para manter a renderização da loja
            }
        }

        $sort = trim((string)$request->input('sort', ''));

        $criteria = ['status' => 'published'];
        if ($selectedCategory !== '') {
            $criteria['category'] = $selectedCategory;
        }
        if ($search !== '') {
            $criteria['search'] = $search;
        }
        if ($sort !== '') {
            $criteria['sort'] = $sort;
        }

        $products = $this->catalogRepo->list($criteria, 48, 0);
        $totalCount = $this->catalogRepo->count($criteria);

        // Se uma busca com categoria restrita não encontrou nada, expande para busca global na loja
        if ($totalCount === 0 && $selectedCategory !== '' && $search !== '') {
            $fallbackCriteria = $criteria;
            unset($fallbackCriteria['category']);
            $fallbackProducts = $this->catalogRepo->list($fallbackCriteria, 48, 0);
            if (!empty($fallbackProducts)) {
                $products = $fallbackProducts;
                $totalCount = $this->catalogRepo->count($fallbackCriteria);
                $selectedCategory = ''; // Expande visualmente para exibir o resultado global
            }
        }

        $categories = $this->catalogRepo->getCategories();

        // Resposta ultra-rápida de AJAX para o Palco Dinâmico (Sem recarregar página nem pular para cima!)
        if ($request->input('ajax') === '1' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            ob_start();
            include dirname(__DIR__) . '/views/front/stage.php';
            $stageHtml = ob_get_clean();

            return new Response(json_encode([
                'success'          => true,
                'totalCount'       => $totalCount,
                'count'            => count($products),
                'selectedCategory' => $selectedCategory,
                'search'           => $search,
                'sort'             => $sort,
                'html'             => $stageHtml ?: '',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        ob_start();
        include dirname(__DIR__) . '/views/front/storefront.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, 'Loja Oficial - Produtos Selecionados');
    }

    /**
     * Página Dedicada do Produto: GET /produto/{slug}
     */
    public function singleProduct(Request $request, string $slug): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        // Tenta buscar por ID numérico ou pelo slug amigável do produto
        $product = null;
        if (is_numeric($slug)) {
            $product = $this->catalogRepo->findById((int)$slug);
        } else {
            $product = $this->catalogRepo->findBySlug($slug);
        }

        if (!$product) {
            return new Response("<h1>404 - Produto não encontrado na loja</h1>", 404);
        }

        $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$product->id;

        ob_start();
        include dirname(__DIR__) . '/views/front/product.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, $product->title . ' - Loja Oficial');
    }

    /**
     * Rastreamento Público de Pedidos: GET /rastrear
     */
    public function trackOrder(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $searchCode = trim((string)($request->input('codigo', '') ?: $request->input('q', '')));
        $matchedOrders = [];
        $trackingCodeToInspect = '';
        $aliExpressOrderId = '';
        $sdkEvents = [];
        $sdkOfficialUrl = '';

        if ($searchCode !== '' && $this->orderRepo !== null) {
            $exact = $this->orderRepo->findByOrderRef($searchCode);
            if ($exact !== null) {
                $matchedOrders[] = $exact;
            } else {
                $matchedOrders = $this->orderRepo->list(['search' => $searchCode], 10, 0);
            }

            foreach ($matchedOrders as &$ord) {
                if (!empty($ord['customer_email'])) {
                    $ord['customer_email_masked'] = $this->maskEmail((string)$ord['customer_email']);
                }
                if ($trackingCodeToInspect === '' && !empty($ord['tracking_code'])) {
                    $trackingCodeToInspect = trim((string)$ord['tracking_code']);
                    $aliExpressOrderId = trim((string)($ord['aliexpress_order_id'] ?? ''));
                }
            }
            unset($ord);
        }

        // Se o visitante digitou diretamente um código de objeto (ex: BR123456789CN ou NM123456789BR)
        if ($trackingCodeToInspect === '' && $searchCode !== '' && !str_contains($searchCode, '@')) {
            if (preg_match('/^[A-Z]{2}\d{8,12}[A-Z]{2}$/i', $searchCode) || str_starts_with(strtoupper($searchCode), 'LP') || str_starts_with(strtoupper($searchCode), 'CN')) {
                $trackingCodeToInspect = strtoupper($searchCode);
            }
        }

        if ($trackingCodeToInspect !== '' && $this->apiClient !== null) {
            try {
                $serviceName = preg_match('/BR$/i', $trackingCodeToInspect) ? 'CORREIOS' : 'CAINIAO';
                $sdkRes = $this->apiClient->queryTrackingInfo(
                    logisticsNo: $trackingCodeToInspect,
                    origin: 'ESCROW',
                    outRef: $aliExpressOrderId,
                    serviceName: $serviceName,
                    toArea: 'BR'
                );
                if (!empty($sdkRes['details']) && is_array($sdkRes['details'])) {
                    $sdkEvents = $sdkRes['details'];
                }
                if (!empty($sdkRes['official_website'])) {
                    $sdkOfficialUrl = (string)$sdkRes['official_website'];
                }
            } catch (Throwable $e) {
                // Silencioso: fallback para links oficiais Correios/Cainiao e linha do tempo do pedido
            }
        }

        ob_start();
        include dirname(__DIR__) . '/views/front/track_order.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, 'Rastrear Meu Pedido - RachidD.com');
    }

    /**
     * Página Oficial de Política de Envio & Prazos: GET /politica-de-envio
     */
    public function shippingPolicy(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        ob_start();
        include dirname(__DIR__) . '/views/front/shipping_policy.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, 'Política de Envio & Prazos - RachidD.com');
    }

    private function wrapWithThemeOrRaw(string $content, string $title): ResponseInterface
    {
        if ($this->themeManager !== null) {
            try {
                $wrapped = $this->themeManager->render('page', [
                    'title'   => $title,
                    'content' => $content,
                ]);
                return new Response($wrapped);
            } catch (Throwable $e) {
                // Fallback para SeoMiddleware ou entrega direta
            }
        }

        return new Response($content ?: '');
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', trim($email));
        if (count($parts) !== 2) {
            return '';
        }
        $local = $parts[0];
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));
        return $visible . '***@' . $parts[1];
    }
}
