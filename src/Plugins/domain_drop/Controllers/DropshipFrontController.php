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
        private readonly ?AliExpressApiClientInterface $apiClient = null,
        private readonly ?\DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface $demandTracker = null,
        private readonly ?\DomainSystem\Core\Contracts\SessionManagerInterface $session = null,
        private readonly ?\DomainSystem\Plugins\domain_drop\Contracts\DropshipCustomerRepositoryInterface $customerRepo = null
    ) {
    }

    /**
     * Resolve a identidade do usuário da sessão atual de forma unificada:
     * 1. Cliente da loja DomainDrop (customer_id / customer_email / customer_name)
     * 2. Aluno da Academy (academy_student_id / academy_student_email / academy_student_name)
     * 3. Administrador ou usuário do sistema (user_id / user_name / user_email)
     */
    private function resolveSessionIdentity(): array
    {
        $session = $this->session;
        if ($session === null && isset($GLOBALS['app'])) {
            try {
                $session = $GLOBALS['app']->getContainer()->make(\DomainSystem\Core\Contracts\SessionManagerInterface::class);
            } catch (\Throwable $e) {}
        }

        $customerId = null;
        $customerName = null;
        $customerEmail = null;
        $userType = 'externo';

        if ($session instanceof \DomainSystem\Core\Contracts\SessionManagerInterface) {
            // 1. Cliente da Loja DomainDrop
            $cId = (int)($session->get('customer_id') ?: 0);
            $cEmail = strtolower(trim((string)($session->get('customer_email') ?: '')));
            $cName = trim((string)($session->get('customer_name') ?: ''));

            if ($cId > 0 && ($cEmail === '' || $cName === '')) {
                if ($this->customerRepo !== null) {
                    try {
                        $cust = $this->customerRepo->findById($cId);
                        if ($cust) {
                            if ($cEmail === '' && !empty($cust['email'])) {
                                $cEmail = strtolower(trim((string)$cust['email']));
                            }
                            if ($cName === '' && !empty($cust['name'])) {
                                $cName = trim((string)$cust['name']);
                            }
                        }
                    } catch (\Throwable $e) {}
                }
            }

            // 2. Aluno da Academy
            if ($cId <= 0 && $cEmail === '') {
                $cId = (int)($session->get('academy_student_id') ?: 0);
                $cEmail = strtolower(trim((string)($session->get('academy_student_email') ?: '')));
                $cName = trim((string)($session->get('academy_student_name') ?: ''));
            }

            // 3. Usuário / Administrador logado no sistema
            if ($cId <= 0 && $cEmail === '') {
                $uId = (int)($session->get('user_id') ?: 0);
                if ($uId > 0) {
                    $cId = $uId;
                    $cName = trim((string)($session->get('user_name') ?: ''));
                    $cEmail = strtolower(trim((string)($session->get('user_email') ?: $session->get('email') ?: '')));
                }
            }

            if ($cId > 0 || $cEmail !== '') {
                $customerId = $cId > 0 ? $cId : null;
                $customerName = $cName !== '' ? $cName : 'Cliente Cadastrado';
                $customerEmail = $cEmail !== '' ? $cEmail : null;
                $userType = 'cliente_logado';
            }
        }

        return [
            'id'        => $customerId,
            'name'      => $customerName,
            'email'     => $customerEmail,
            'user_type' => $userType,
            'sess_id'   => session_id() ?: null,
        ];
    }

    /**
     * Endpoint Assíncrono de Rastreamento de Demanda: POST /api/drop/track-demand
     */
    public function trackDemand(Request $request): ResponseInterface
    {
        $payload = $request->json();
        if (empty($payload) || !is_array($payload)) {
            $payload = $request->all();
        }

        if ($this->demandTracker !== null && !empty($payload)) {
            $identity = $this->resolveSessionIdentity();
            // Remove campos de identidade/conversão forjáveis do payload público
            unset($payload['customer_id'], $payload['customer_name'], $payload['customer_email'], $payload['ip_address']);
            $rawAct = strtolower(trim((string)($payload['action'] ?? ($payload['event_type'] ?? 'view_details'))));
            if (in_array($rawAct, ['conversion', 'purchase'], true)) {
                $payload['action'] = 'view_details';
                $payload['event_type'] = 'view_details';
            }

            $event = \DomainSystem\Plugins\domain_drop\DTO\DemandEvent::fromArray(
                payload: $payload,
                sessionId: $identity['sess_id'],
                customerId: $identity['id'],
                customerName: $identity['name'],
                customerEmail: $identity['email'],
                ipAddress: \DomainSystem\Plugins\domain_drop\DTO\DemandEvent::resolveClientIp()
            );
            $tracked = $this->demandTracker->recordInteraction($event);
            return Response::json([
                'success'   => true,
                'tracked'   => $tracked,
                'user_type' => $event->userType,
                'identity'  => [
                    'id'    => $event->customerId,
                    'name'  => $event->customerName,
                    'email' => $event->customerEmail,
                ],
            ]);
        }

        return Response::json(['success' => false, 'message' => 'Rastreador de demanda indisponível'], 400);
    }

    /**
     * Endpoint de Verificação ao Vivo no Clique de Compra: POST /api/drop/check-availability
     */
    public function checkAvailability(Request $request): ResponseInterface
    {
        $payload = $request->json();
        if (empty($payload) || !is_array($payload)) {
            $payload = $request->all();
        }

        $productId = (int)($payload['product_id'] ?? 0);
        $slug = trim((string)($payload['slug'] ?? ''));
        $skuId = trim((string)($payload['sku'] ?? ''));

        $product = null;
        if ($productId > 0) {
            $product = $this->catalogRepo->findById($productId);
        } elseif ($slug !== '') {
            $product = $this->catalogRepo->findBySlug($slug);
        }

        if ($product === null || $product->status !== 'published') {
            return Response::json([
                'success'      => true,
                'available'    => false,
                'reason'       => 'not_found',
                'message'      => 'O estoque deste produto já acabou e ele não está mais disponível.',
                'stock'        => 0,
                'stock_source' => 'catalog',
            ]);
        }

        if ($this->miningService !== null && method_exists($this->miningService, 'checkLiveProductAvailability')) {
            $check = $this->miningService->checkLiveProductAvailability($product, $skuId);
            return Response::json(array_merge(['success' => true], $check));
        }

        return Response::json([
            'success'      => true,
            'available'    => true,
            'reason'       => 'available',
            'message'      => 'Produto disponível para compra.',
            'stock'        => null,
            'stock_source' => 'catalog',
        ]);
    }

    /**
     * Vitrine Completa da Loja: GET /loja
     */
    public function storefront(Request $request): ResponseInterface
    {
        return $this->renderCatalogPage($request, [
            'dedicatedSection' => '',
            'pageBaseRoute'    => '/loja',
            'pageTitle'        => 'Loja Oficial - Produtos Selecionados',
        ]);
    }

    /**
     * Página Dedicada: Moda, Roupas, Lingeries, Meias-Calça, Espartilhos & Íntima Masculina:
     * GET /moda-intima (e alias GET /roupas)
     */
    public function fashionAndLingerie(Request $request): ResponseInterface
    {
        $sub = strtolower(trim((string)$request->input('sub', '')));
        $forcedCategory = match ($sub) {
            'roupas'    => 'Roupas, Bolsas & Calçados',
            'intima'    => 'Roupa Íntima & Pijamas',
            'masculina' => 'Roupa Íntima & Pijamas',
            default     => ['Roupas, Bolsas & Calçados', 'Roupa Íntima & Pijamas'],
        };

        $forcedSearch = '';
        if ($sub === 'masculina') {
            $forcedSearch = 'cueca';
        } elseif ($sub === 'meias') {
            $forcedSearch = 'meia';
        }

        $displayCategory = is_array($forcedCategory)
            ? 'Roupas, Bolsas & Calçados + Roupa Íntima & Pijamas'
            : $forcedCategory;

        return $this->renderCatalogPage($request, [
            'dedicatedSection' => 'fashion',
            'dedicatedSub'     => $sub,
            'pageBaseRoute'    => '/moda-intima',
            'pageTitle'        => 'Moda, Roupas & Moda Íntima - Loja Oficial',
            'forcedCategory'   => $forcedCategory,
            'displayCategory'  => $displayCategory,
            'forcedSearch'     => $forcedSearch,
        ]);
    }

    /**
     * Página Dedicada: Sex Shop & Bem-Estar Íntimo (+18):
     * GET /sexshop
     */
    public function sexShop(Request $request): ResponseInterface
    {
        return $this->renderCatalogPage($request, [
            'dedicatedSection' => 'sexshop',
            'dedicatedSub'     => '',
            'pageBaseRoute'    => '/sexshop',
            'pageTitle'        => 'Sex Shop & Bem-Estar Íntimo (+18) - Loja Oficial',
            'forcedCategory'   => 'Novidades & Uso Especial',
            'displayCategory'  => 'Novidades & Uso Especial',
            'forcedSearch'     => '',
        ]);
    }

    /**
     * Renderizador compartilhado da vitrine principal e das páginas dedicadas (SRP / DRY).
     *
     * @param array<string, mixed> $options
     */
    private function renderCatalogPage(Request $request, array $options): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $dedicatedSection = (string)($options['dedicatedSection'] ?? '');
        $dedicatedSub = (string)($options['dedicatedSub'] ?? '');
        $pageBaseRoute = (string)($options['pageBaseRoute'] ?? '/loja');
        $pageTitle = (string)($options['pageTitle'] ?? 'Loja Oficial - Produtos Selecionados');

        $selectedCategory = isset($options['displayCategory'])
            ? (string)$options['displayCategory']
            : trim((string)$request->input('cat', ''));
        $search = trim((string)$request->input('q', ''));
        if ($search === '' && !empty($options['forcedSearch'])) {
            $search = (string)$options['forcedSearch'];
        }

        // Metadados do Ciclo Diário de 24 Horas (somente leitura na vitrine; execução via CLI dropship:cycle ou Admin)
        $cycleInfo = $this->miningService ? $this->miningService->getDailyCycleInfo() : [
            'formatted_remaining' => '24:00:00',
            'seconds_remaining'   => 86400,
            'is_due'              => false,
            'auto_sync_enabled'   => true,
        ];

        $sort = trim((string)$request->input('sort', ''));
        $page = max(1, (int)$request->input('page', 1));
        $perPage = 48; // 48 produtos por página (grade equilibrada para 2, 3 e 4 colunas)

        $criteria = ['status' => 'published'];
        if (isset($options['forcedCategory'])) {
            $criteria['category'] = $options['forcedCategory'];
        } elseif ($selectedCategory !== '') {
            $criteria['category'] = $selectedCategory;
        }
        if ($search !== '') {
            $criteria['search'] = $search;
        }
        if ($sort !== '') {
            $criteria['sort'] = $sort;
        }

        $totalCount = $this->catalogRepo->count($criteria);

        // Se uma busca na vitrine geral com categoria restrita não encontrou nada, expande para busca global na loja
        if ($totalCount === 0 && $dedicatedSection === '' && $selectedCategory !== '' && $search !== '') {
            $fallbackCriteria = $criteria;
            unset($fallbackCriteria['category']);
            $fallbackCount = $this->catalogRepo->count($fallbackCriteria);
            if ($fallbackCount > 0) {
                $criteria = $fallbackCriteria;
                $totalCount = $fallbackCount;
                $selectedCategory = ''; // Expande visualmente para exibir o resultado global
            }
        }

        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        if ($page > $totalPages && $totalCount > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $products = $this->catalogRepo->list($criteria, $perPage, $offset);
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
                'page'             => $page,
                'totalPages'       => $totalPages,
                'selectedCategory' => $selectedCategory,
                'search'           => $search,
                'sort'             => $sort,
                'html'             => $stageHtml ?: '',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        ob_start();
        include dirname(__DIR__) . '/views/front/storefront.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, $pageTitle);
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

        // Rastreamento Server-Side Garantido de 'Ver Detalhes'
        $this->recordDemandForProduct($product, 'view_details');

        $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$product->id;

        // Busca ofertas alternativas / outros vendedores (estilo AliExpress, Amazon, Shopee)
        $similarOffers = $this->catalogRepo->findSimilarOffers(
            excludeId: (int)$product->id,
            category: (string)$product->category,
            title: (string)$product->title,
            limit: 4
        );

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
            // Apenas busca exata por referência do pedido (impede enumeração pública via LIKE)
            $exact = $this->orderRepo->findByOrderRef($searchCode);
            if ($exact !== null) {
                $matchedOrders[] = $exact;
            }

            foreach ($matchedOrders as &$ord) {
                if (!empty($ord['customer_email'])) {
                    $ord['customer_email_masked'] = $this->maskEmail((string)$ord['customer_email']);
                    unset($ord['customer_email']);
                }
                if (!empty($ord['customer_name'])) {
                    $parts = preg_split('/\s+/', trim((string)$ord['customer_name'])) ?: [];
                    $ord['customer_name'] = ($parts[0] ?? 'Cliente') . (count($parts) > 1 ? ' ***' : '');
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
     * Gatilho assíncrono em background (auto-tick de 24h quando o cronômetro zera): POST /dropship/cron-tick
     * Protegido por lock exclusivo (flock) dentro de runDailyAutomatedRotation() e só executa quando is_due === true.
     */
    public function cronTick(Request $request): ResponseInterface
    {
        if ($this->miningService === null) {
            return new Response(json_encode(['success' => false, 'reason' => 'service_unavailable']), 200, ['Content-Type' => 'application/json']);
        }

        $info = $this->miningService->getDailyCycleInfo();
        $rotated = false;
        if (!empty($info['auto_sync_enabled']) && !empty($info['is_due'])) {
            try {
                $res = $this->miningService->runDailyAutomatedRotation(10);
                $rotated = !empty($res['success']);
            } catch (Throwable $e) {
                $rotated = false;
            }
            $info = $this->miningService->getDailyCycleInfo();
        }

        // 2. Sentinela de Preços, Estoque & Volatilidade de 6h
        $priceInfo = method_exists($this->miningService, 'getPriceSyncInfo') ? $this->miningService->getPriceSyncInfo() : null;
        $pricesSynced = false;
        if ($priceInfo !== null && !empty($priceInfo['auto_sync_enabled']) && !empty($priceInfo['is_due'])) {
            try {
                $syncRes = $this->miningService->runPriceAndStockSync(50);
                $pricesSynced = !empty($syncRes['success']);
            } catch (Throwable $e) {
                $pricesSynced = false;
            }
            $priceInfo = $this->miningService->getPriceSyncInfo();
        }

        return new Response(json_encode([
            'success'               => true,
            'rotated'               => $rotated,
            'seconds_remaining'     => (int)($info['seconds_remaining'] ?? 86400),
            'formatted_remaining'   => (string)($info['formatted_remaining'] ?? '24:00:00'),
            'last_cycle_date'       => (string)($info['last_cycle_date'] ?? ''),
            'prices_synced'         => $pricesSynced,
            'price_sync_remaining'  => (int)($priceInfo['seconds_remaining'] ?? 21600),
            'price_sync_formatted'  => (string)($priceInfo['formatted_remaining'] ?? '06:00:00'),
            'last_price_sync_date'  => (string)($priceInfo['last_sync_date'] ?? ''),
            'volatility_applied'    => (float)($priceInfo['volatility_percent'] ?? 12.0),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type'  => 'application/json',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
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
        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Thu, 01 Jan 1970 00:00:00 GMT',
            'Vary'          => 'Cookie, Accept-Encoding',
        ];

        if ($this->themeManager !== null) {
            try {
                $wrapped = $this->themeManager->render('page', [
                    'title'   => $title,
                    'content' => $content,
                ]);
                return new Response($wrapped, 200, $headers);
            } catch (Throwable $e) {
                // Fallback para SeoMiddleware ou entrega direta
            }
        }

        return new Response($content ?: '', 200, $headers);
    }

    /**
     * Registra evento de demanda diretamente no servidor (resiliente a ad-blockers e navegadores sem JS).
     */
    private function recordDemandForProduct(object $product, string $actionType): void
    {
        if ($this->demandTracker === null) {
            return;
        }

        try {
            $identity = $this->resolveSessionIdentity();
            $productId = (string)($product->aliexpressId ?? ($product->aliProductId ?? ($product->id ?? '')));
            if ($productId === '') {
                $productId = (string)($product->id ?? '');
            }

            $event = new \DomainSystem\Plugins\domain_drop\DTO\DemandEvent(
                productId: $productId,
                productTitle: (string)($product->title ?? 'Produto Sem Nome'),
                categorySlug: (string)($product->category ?? 'geral'),
                actionType: $actionType,
                sessionId: $identity['sess_id'],
                customerId: $identity['id'],
                customerName: $identity['name'],
                customerEmail: $identity['email'],
                userType: $identity['user_type']
            );

            $this->demandTracker->recordInteraction($event);
        } catch (\Throwable $e) {
            // Silencioso para não interromper navegação do usuário
        }
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
