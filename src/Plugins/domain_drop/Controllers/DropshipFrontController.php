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

            $event = \DomainSystem\Plugins\domain_drop\DTO\DemandEvent::fromArray(
                payload: $payload,
                sessionId: $identity['sess_id'],
                customerId: $identity['id'],
                customerName: $identity['name'],
                customerEmail: $identity['email']
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
     * Vitrine Completa da Loja: GET /loja
     */
    public function storefront(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $selectedCategory = trim((string)$request->input('cat', ''));
        $search = trim((string)$request->input('q', ''));

        // Metadados do Ciclo Diário de 24 Horas (somente leitura na vitrine; execução via CLI dropship:cycle ou Admin)
        $cycleInfo = $this->miningService ? $this->miningService->getDailyCycleInfo() : [
            'formatted_remaining' => '24:00:00',
            'seconds_remaining'   => 86400,
            'is_due'              => false,
            'auto_sync_enabled'   => true,
        ];

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

        // Rastreamento Server-Side Garantido de 'Ver Detalhes'
        $this->recordDemandForProduct($product, 'view_details');

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
                $res = $this->miningService->runDailyAutomatedRotation(3);
                $rotated = !empty($res['success']);
            } catch (Throwable $e) {
                $rotated = false;
            }
            $info = $this->miningService->getDailyCycleInfo();
        }

        return new Response(json_encode([
            'success'             => true,
            'rotated'             => $rotated,
            'seconds_remaining'   => (int)($info['seconds_remaining'] ?? 86400),
            'formatted_remaining' => (string)($info['formatted_remaining'] ?? '24:00:00'),
            'last_cycle_date'     => (string)($info['last_cycle_date'] ?? ''),
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
