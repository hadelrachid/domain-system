<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCustomerRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipDisputeRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMediaSyncInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use DomainSystem\Plugins\domain_drop\Services\OrderSlaProgressService;
use DomainSystem\Plugins\domain_drop\Services\SellerMediationService;
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
        ?string $basePath = null,
        private readonly ?DropshipCustomerRepositoryInterface $customerRepo = null,
        private readonly ?DropshipDisputeRepositoryInterface $disputeRepo = null,
        private readonly ?OrderSlaProgressService $slaService = null,
        private readonly ?SellerMediationService $mediationService = null,
        private readonly ?AliExpressApiClientInterface $apiClient = null
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

    public function importBrowserSource(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $rawSource = trim((string)$request->input('raw_source', ''));
        $category = trim((string)$request->input('category', ''));
        $category = $category !== '' ? $category : null;

        if ($rawSource === '') {
            $this->session->setFlash('error', 'Cole o código-fonte HTML ou JSON do produto na caixa de texto.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        try {
            $product = $this->miningService->importFromBrowserSource($rawSource, $category);
            $this->session->setFlash('success', "✅ Produto '{$product->title}' importado com sucesso a partir do navegador!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Falha ao processar código do produto: " . $e->getMessage());
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

    public function testConnection(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            if ($this->isAjaxOrJson($request)) {
                return new JsonResponse(['success' => false, 'status' => 'error', 'message' => 'Token de segurança expirado. Atualize a página e tente novamente.'], 403);
            }
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship/settings');
        }

        $config = $this->loadConfig();
        $appKey = trim((string)$request->input('app_key', $config['app_key'] ?? ''));
        $appSecret = trim((string)$request->input('app_secret', ''));
        if ($appSecret === '' || str_starts_with($appSecret, '***')) {
            $appSecret = (string)($config['app_secret'] ?? '');
        }
        $accessToken = trim((string)$request->input('access_token', ''));
        if ($accessToken === '' || str_starts_with($accessToken, '***')) {
            $accessToken = (string)($config['access_token'] ?? '');
        }
        $gatewayUrl = (string)($config['gateway_url'] ?? 'https://api-sg.aliexpress.com/sync');

        if ($appKey === '' || $appSecret === '') {
            $msg = '❌ O AppKey e o AppSecret são obrigatórios para testar a conexão com o servidor do AliExpress.';
            if ($this->isAjaxOrJson($request)) {
                return new JsonResponse(['success' => false, 'status' => 'error', 'message' => $msg], 400);
            }
            $this->session->setFlash('error', $msg);
            return Response::redirect($baseUrl . '/admin/dropship/settings');
        }

        try {
            $client = new \DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient(
                appKey: $appKey,
                appSecret: $appSecret,
                gatewayUrl: $gatewayUrl,
                timeout: 10,
                accessToken: $accessToken !== '' ? $accessToken : null
            );

            // Testa endpoint de detalhes de produto no gateway oficial do AliExpress
            $res = $client->getProductDetails('1005006093867086');

            if (isset($res['error_response'])) {
                $err = $res['error_response'];
                $errCode = (string)($err['code'] ?? '');
                $errMsg = (string)($err['msg'] ?? '');

                if (str_contains(strtolower($errMsg), 'access_token') || str_contains($errCode, 'MissingParameter') || str_contains($errCode, 'IllegalAccessToken') || str_contains($errCode, 'InvalidAccessToken')) {
                    $status = 'warning';
                    $msg = "⚠️ Conexão física OK com o servidor do AliExpress (api-sg.aliexpress.com), mas a aplicação está em modo TEST ou o Access Token não foi fornecido. Clique no botão 'Apply Online' no portal do AliExpress para ir para Produção e cole o Access Token gerado.";
                } elseif (str_contains($errCode, 'InvalidAppKey') || str_contains($errMsg, 'AppKey') || str_contains($errCode, 'IncompleteSignature') || str_contains($errCode, 'Signature')) {
                    $status = 'error';
                    $msg = "❌ Credenciais inválidas. O servidor do AliExpress rejeitou o AppKey ou AppSecret informados. Verifique se foram digitados corretamente ou gere novas credenciais no portal openservice.aliexpress.com.";
                } else {
                    $status = 'warning';
                    $msg = "⚠️ Resposta do Servidor AliExpress: [{$errCode}] {$errMsg}. Verifique as permissões da aplicação no painel de desenvolvedores.";
                }

                if ($this->isAjaxOrJson($request)) {
                    return new JsonResponse([
                        'success' => false,
                        'status'  => $status,
                        'message' => $msg,
                        'code'    => $errCode,
                    ]);
                }

                $this->session->setFlash($status === 'warning' ? 'warning' : 'error', $msg);
                return Response::redirect($baseUrl . '/admin/dropship/settings');
            }

            // Sucesso Total
            $msg = "✔️ Conexão e sincronização 100% estabelecidas com o servidor do AliExpress! Protocolo HMAC-SHA256 validado e credenciais operantes.";
            if ($this->isAjaxOrJson($request)) {
                return new JsonResponse([
                    'success' => true,
                    'status'  => 'ok',
                    'message' => $msg,
                ]);
            }

            $this->session->setFlash('success', $msg);
            return Response::redirect($baseUrl . '/admin/dropship/settings');

        } catch (Throwable $e) {
            $msg = "❌ Falha de rede ao conectar com o servidor do AliExpress: " . $e->getMessage() . ". Verifique se o servidor possui saída HTTPS para api-sg.aliexpress.com.";
            if ($this->isAjaxOrJson($request)) {
                return new JsonResponse(['success' => false, 'status' => 'error', 'message' => $msg], 500);
            }
            $this->session->setFlash('error', $msg);
            return Response::redirect($baseUrl . '/admin/dropship/settings');
        }
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
        $registeredCustomers = $this->customerRepo !== null ? $this->customerRepo->listAll(200, 0) : [];
        $customersByEmail = [];
        foreach ($registeredCustomers as $cust) {
            $em = strtolower(trim((string)($cust['email'] ?? '')));
            if ($em !== '') {
                $customersByEmail[$em] = $cust;
            }
        }

        $orderRefs = array_values(array_filter(array_map(static fn (array $o): string => (string)($o['order_ref'] ?? ''), $orders)));
        $disputesByOrder = $this->disputeRepo !== null ? $this->disputeRepo->mapByOrderRefs($orderRefs) : [];
        $slaCalc = $this->slaService ?? new OrderSlaProgressService();

        foreach ($orders as &$ord) {
            $ref = (string)($ord['order_ref'] ?? '');
            $em = strtolower(trim((string)($ord['customer_email'] ?? '')));
            $ordDisputes = $disputesByOrder[$ref] ?? [];
            $ord['disputes'] = $ordDisputes;
            $ord['is_registered_customer'] = isset($customersByEmail[$em]);
            $ord['sla'] = $slaCalc->calculate($ord, $ordDisputes);
        }
        unset($ord);

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
        $sellerOrigin = strtoupper(trim((string)$request->input('seller_origin', '')));
        $expectedDays = (int)$request->input('expected_delivery_days', 0);
        $sellerEmail = trim((string)$request->input('seller_email', ''));

        if ($this->orderRepo !== null && $orderRef !== '') {
            $this->orderRepo->updateFulfillment(
                $orderRef,
                $status,
                $aeOrderId !== '' ? $aeOrderId : null,
                $tracking !== '' ? $tracking : null
            );

            if ($sellerOrigin !== '') {
                $days = $expectedDays > 0 ? $expectedDays : ($sellerOrigin === 'CN' ? 25 : 7);
                $this->orderRepo->updateSellerAndSla(
                    $orderRef,
                    $sellerOrigin,
                    $days,
                    null,
                    $sellerEmail !== '' ? $sellerEmail : null
                );
            }

            $this->session->setFlash('success', 'Status de envio do pedido atualizado com sucesso!');
        }

        return Response::redirect($baseUrl . '/admin/dropship/orders');
    }

    /**
     * Consulta o SDK oficial do AliExpress para verificar se o item chegou ao destino.
     */
    public function checkOrderSdk(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $isAjax = $this->isAjaxOrJson($request);

        if (!$this->verifyCsrf($request)) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Token de segurança expirado.'], 403);
            }
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship/orders');
        }

        $orderRef = trim((string)$request->input('order_ref', ''));
        $order = ($this->orderRepo !== null && $orderRef !== '') ? $this->orderRepo->findByOrderRef($orderRef) : null;
        if ($order === null) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
            }
            $this->session->setFlash('error', 'Pedido não encontrado.');
            return Response::redirect($baseUrl . '/admin/dropship/orders');
        }

        $trackingCode = trim((string)($order['tracking_code'] ?? ''));
        $aeOrderId = trim((string)($order['aliexpress_order_id'] ?? ''));

        if ($trackingCode === '' || $this->apiClient === null) {
            $msg = 'Informe o código de rastreio do pedido antes de consultar o SDK logístico.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $msg], 422);
            }
            $this->session->setFlash('error', $msg);
            return Response::redirect($baseUrl . '/admin/dropship/orders');
        }

        try {
            $sdkRes = $this->apiClient->queryTrackingInfo(
                logisticsNo: $trackingCode,
                origin: 'ESCROW',
                outRef: $aeOrderId,
                serviceName: str_starts_with(strtoupper($trackingCode), 'BR') ? 'CORREIOS' : 'CAINIAO',
                toArea: 'BR'
            );

            $slaCalc = $this->slaService ?? new OrderSlaProgressService();
            $parsed = $slaCalc->parseSdkTracking($sdkRes);
            $summaryJson = json_encode($sdkRes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

            if ($parsed['delivered']) {
                $this->orderRepo->updateSdkTracking(
                    $orderRef,
                    date('Y-m-d H:i:s'),
                    $summaryJson,
                    'delivered'
                );
                $msg = 'SDK AliExpress confirmou: item entregue no local! Pedido atualizado para "Tudo Ok".';
            } else {
                $this->orderRepo->updateSdkTracking($orderRef, null, $summaryJson, null);
                $msg = 'SDK consultado: encomenda ainda em trânsito (' . ($parsed['last_event'] !== '' ? $parsed['last_event'] : $parsed['status_code']) . ').';
            }

            if ($isAjax) {
                return new JsonResponse(['success' => true, 'delivered' => $parsed['delivered'], 'message' => $msg, 'tracking' => $parsed]);
            }
            $this->session->setFlash('success', $msg);
        } catch (Throwable $e) {
            $msg = 'Falha ao consultar SDK logístico: ' . $e->getMessage();
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $msg], 500);
            }
            $this->session->setFlash('error', $msg);
        }

        return Response::redirect($baseUrl . '/admin/dropship/orders');
    }

    /**
     * Botão único de intermediação com o fornecedor (PT-BR se Brasil, traduzido para Chinês se China).
     */
    public function mediateDispute(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $isAjax = $this->isAjaxOrJson($request);

        if (!$this->verifyCsrf($request)) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Token de segurança expirado.'], 403);
            }
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship/orders');
        }

        $orderRef = trim((string)$request->input('order_ref', ''));
        $disputeId = (int)$request->input('dispute_id', 0);

        $order = ($this->orderRepo !== null && $orderRef !== '') ? $this->orderRepo->findByOrderRef($orderRef) : null;
        if ($order === null) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
            }
            $this->session->setFlash('error', 'Pedido não encontrado.');
            return Response::redirect($baseUrl . '/admin/dropship/orders');
        }

        $dispute = null;
        if ($this->disputeRepo !== null) {
            $dispute = $disputeId > 0 ? $this->disputeRepo->findById($disputeId) : null;
            if ($dispute === null) {
                $list = $this->disputeRepo->findByOrderRef($orderRef);
                $dispute = $list[0] ?? null;
            }
        }

        if ($dispute === null || $this->mediationService === null) {
            $msg = 'Nenhuma reclamação aberta encontrada para intermediar.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $msg], 404);
            }
            $this->session->setFlash('error', $msg);
            return Response::redirect($baseUrl . '/admin/dropship/orders');
        }

        $overrideOrigin = strtoupper(trim((string)$request->input('seller_origin', '')));
        if (in_array($overrideOrigin, ['BR', 'CN'], true)) {
            $dispute['seller_origin'] = $overrideOrigin;
        }

        $result = $this->mediationService->sendMediation($order, $dispute, $baseUrl);
        $langLabel = ($result['seller_language'] ?? '') === 'zh-CN'
            ? 'Chinês Simplificado (zh-CN)'
            : 'Português (pt-BR)';

        $msg = "Intermediação enviada ao fornecedor em {$langLabel}!";
        if ($isAjax) {
            return new JsonResponse([
                'success'            => true,
                'message'            => $msg,
                'seller_language'    => $result['seller_language'] ?? 'zh-CN',
                'channel'            => $result['channel'] ?? 'aliexpress_mediation_log',
                'translated_message' => $result['translated_message'] ?? '',
            ]);
        }

        $this->session->setFlash('success', $msg);
        return Response::redirect($baseUrl . '/admin/dropship/orders');
    }

    public function syncImages(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $isAjax = $this->isAjaxOrJson($request);

        if (!$this->verifyCsrf($request)) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Token de segurança expirado.'], 403);
            }
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        if ($this->mediaSync === null) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Serviço de sincronização de mídia indisponível.'], 503);
            }
            $this->session->setFlash('error', 'Serviço de sincronização de mídia indisponível.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        try {
            $updated = $this->mediaSync->syncProductMedia((int)$id);
            $images = $this->filterImageUrls(array_merge([$updated->mainImageUrl], $updated->images));
            $mainImageUrl = $updated->mainImageUrl ?: ($images[0] ?? '');
            $imgCount = count($images);
            $msg = "🔄 Produto '{$updated->title}' sincronizado com sucesso! {$imgCount} foto(s) em alta resolução carregada(s).";

            if ($isAjax) {
                $rate = $this->currencyConverter->getExchangeRate('USD', 'BRL');
                if ($rate <= 0) {
                    $rate = 5.50;
                }
                return new JsonResponse([
                    'success'        => true,
                    'message'        => $msg,
                    'title'          => $updated->title,
                    'main_image_url' => $mainImageUrl,
                    'images'         => $images,
                    'count'          => $imgCount,
                    'sale_formatted' => 'R$ ' . number_format($updated->salePriceBrl, 2, ',', '.'),
                    'cost_formatted' => 'R$ ' . number_format($updated->costPriceUsd * $rate, 2, ',', '.'),
                    'cost_price_usd' => $updated->costPriceUsd,
                ]);
            }

            $this->session->setFlash('success', $msg);
        } catch (Throwable $e) {
            $errMsg = 'Falha ao sincronizar imagens: ' . $e->getMessage();
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $errMsg], 422);
            }
            $this->session->setFlash('error', $errMsg);
        }

        return Response::redirect($baseUrl . '/admin/dropship');
    }

    public function updateImages(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $isAjax = $this->isAjaxOrJson($request);

        if (!$this->verifyCsrf($request)) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Token de segurança expirado.'], 403);
            }
            $this->session->setFlash('error', 'Token de segurança expirado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $product = $this->catalogRepo->findById((int)$id);
        if ($product === null) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Produto não encontrado.'], 404);
            }
            $this->session->setFlash('error', 'Produto não encontrado.');
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        $rawImages = $request->input('images_json');
        $parsedImages = [];
        if (is_string($rawImages) && trim($rawImages) !== '') {
            $decoded = json_decode($rawImages, true);
            if (is_array($decoded)) {
                $parsedImages = $decoded;
            }
        } elseif (is_array($request->input('images'))) {
            $parsedImages = $request->input('images');
        }

        $mainImageInput = trim((string)$request->input('main_image_url', ''));
        $validImages = $this->filterImageUrls($parsedImages);
        $validMain = $this->filterImageUrls([$mainImageInput])[0] ?? ($validImages[0] ?? '');

        if ($validMain === '' && empty($validImages)) {
            $errMsg = 'Informe pelo menos 1 URL de imagem HTTP/HTTPS válida.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $errMsg], 422);
            }
            $this->session->setFlash('error', $errMsg);
            return Response::redirect($baseUrl . '/admin/dropship');
        }

        // Garante que a imagem principal esteja na lista (em primeiro lugar)
        $orderedImages = $this->filterImageUrls(array_merge([$validMain], $validImages));

        $updatedDto = new DropshipProductDto(
            aliexpressId: $product->aliexpressId,
            title: $product->title,
            slug: $product->slug,
            costPriceUsd: $product->costPriceUsd,
            salePriceBrl: $product->salePriceBrl,
            mainImageUrl: $validMain,
            images: $orderedImages,
            category: $product->category,
            description: $product->description,
            rating: $product->rating,
            ordersCount: $product->ordersCount,
            attributes: $product->attributes,
            status: $product->status,
            id: $product->id
        );

        $this->catalogRepo->save($updatedDto);

        $count = count($orderedImages);
        $msg = "🖼️ Galeria de '{$product->title}' atualizada! {$count} foto(s) salva(s).";

        if ($isAjax) {
            return new JsonResponse([
                'success'        => true,
                'message'        => $msg,
                'main_image_url' => $validMain,
                'images'         => $orderedImages,
                'count'          => $count,
            ]);
        }

        $this->session->setFlash('success', $msg);
        return Response::redirect($baseUrl . '/admin/dropship');
    }

    /**
     * @param array<int, mixed> $urls
     * @return array<int, string>
     */
    private function filterImageUrls(array $urls): array
    {
        $clean = [];
        foreach ($urls as $raw) {
            if (!is_string($raw)) {
                continue;
            }
            $url = trim($raw);
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
            if ($scheme !== 'http' && $scheme !== 'https') {
                continue;
            }
            if (!in_array($url, $clean, true)) {
                $clean[] = $url;
            }
        }
        return $clean;
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

            $this->session->setFlash("Ciclo diário 24h concluído com sucesso! {$result['total_mined']} novos produtos minerados nas 9 categorias oficiais.", 'success');
        } catch (Throwable $e) {
            if ($this->isAjaxOrJson($request)) {
                return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
            }
            $this->session->setFlash("Falha ao executar ciclo diário: " . $e->getMessage(), 'error');
        }

        return new Response('', 302, ['Location' => $baseUrl . '/admin/dropship']);
    }

    /**
     * Gestão Administrativa de Clientes Cadastrados (semelhante a Alunos & Matrículas do Academy).
     */
    public function customers(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $search = trim((string)$request->input('q', ''));
        $status = trim((string)$request->input('status', 'all'));
        $page = max(1, (int)$request->input('page', 1));
        $perPage = 15;

        $customers = [];
        $total = 0;
        $metrics = [
            'total_customers'     => 0,
            'active_customers'    => 0,
            'suspended_customers' => 0,
            'linked_orders'       => 0,
        ];

        if ($this->customerRepo !== null) {
            if (method_exists($this->customerRepo, 'listCustomers')) {
                $res = $this->customerRepo->listCustomers(
                    $page,
                    $perPage,
                    $search !== '' ? $search : null,
                    $status !== 'all' ? $status : null
                );
                $customers = $res['items'] ?? [];
                $total = (int)($res['total'] ?? count($customers));
            } else {
                $customers = $this->customerRepo->listAll($perPage, ($page - 1) * $perPage);
                $total = count($customers);
            }

            if (method_exists($this->customerRepo, 'getCustomerMetrics')) {
                $metrics = $this->customerRepo->getCustomerMetrics();
            }
        }

        $totalPages = max(1, (int)ceil($total / $perPage));
        $csrfToken = (string)$this->session->getCsrfToken();
        $flash = $this->session->getFlash();
        $currentSearch = $search;
        $currentStatus = $status;
        $recentDisputes = ($this->disputeRepo !== null && method_exists($this->disputeRepo, 'listRecent'))
            ? $this->disputeRepo->listRecent(30)
            : [];

        ob_start();
        include dirname(__DIR__) . '/views/admin/customers.php';
        $content = (string)ob_get_clean();

        return new ViewResponse($content, true);
    }

    /**
     * Ativa ou suspende a conta de um cliente cadastrado no DomainDrop.
     */
    public function updateCustomerStatus(Request $request, string $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return Response::redirect($baseUrl . '/admin/dropship/customers');
        }

        $customerId = (int)$id;
        $newStatus = trim((string)$request->input('status', 'active'));
        if (!in_array($newStatus, ['active', 'suspended', 'blocked'], true)) {
            $newStatus = 'active';
        }

        if ($this->customerRepo !== null && method_exists($this->customerRepo, 'updateStatus')) {
            $this->customerRepo->updateStatus($customerId, $newStatus);
            $label = $newStatus === 'active' ? 'ativado' : 'suspenso';
            $this->session->setFlash('success', "Status do cliente #{$customerId} atualizado ({$label}).");
        } else {
            $this->session->setFlash('error', 'Repositório de clientes não disponível.');
        }

        return Response::redirect($baseUrl . '/admin/dropship/customers');
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
