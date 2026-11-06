<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Controllers;

use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;

/**
 * Painel Administrativo do DomainCommerce (SRP / DIP / Sem Superglobais).
 */
class OrdersAdminController
{
    private SessionManagerInterface $session;
    private OrderRepositoryInterface $orderRepo;
    private PaymentGatewayRegistryInterface $gatewayRegistry;
    private ?CommerceSettingsInterface $settings;
    private ?EventDispatcherInterface $dispatcher;

    public function __construct(
        SessionManagerInterface $session,
        OrderRepositoryInterface $orderRepo,
        PaymentGatewayRegistryInterface $gatewayRegistry,
        ?CommerceSettingsInterface $settings = null,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->session = $session;
        $this->orderRepo = $orderRepo;
        $this->gatewayRegistry = $gatewayRegistry;
        $this->settings = $settings;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dashboard de Vendas e Pedidos: GET /admin/commerce
     */
    public function index(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $gateways = $this->gatewayRegistry->getAll();

        $rawPeriod = trim((string)$request->input('period', ''));
        $filterPeriod = (preg_match('/^\d{4}-\d{2}$/', $rawPeriod)) ? $rawPeriod : null;

        $availablePeriods = $this->orderRepo->getAvailablePeriods();
        $orders = $this->orderRepo->getAllOrders(100, 0, $filterPeriod);
        $metrics = $this->orderRepo->getMetrics($filterPeriod);
        $salesTimeline = $this->orderRepo->getSalesTimeline($filterPeriod);
        $paymentBreakdown = $this->orderRepo->getPaymentMethodBreakdown($filterPeriod);

        $html = $this->renderView('admin/dashboard', [
            'baseUrl'          => $baseUrl,
            'gateways'         => $gateways,
            'orders'           => $orders,
            'metrics'          => $metrics,
            'salesTimeline'    => $salesTimeline,
            'paymentBreakdown' => $paymentBreakdown,
            'csrfToken'        => (string)$this->session->getCsrfToken(),
            'flash'            => $this->session->getFlash('success'),
            'error'            => $this->session->getFlash('error'),
            'settings'         => $this->settings ? $this->settings->all() : [],
            'selectedPeriod'   => $filterPeriod,
            'availablePeriods' => $availablePeriods,
        ]);

        return new ViewResponse($html);
    }

    /**
     * Tela de Configurações de Gateways: GET /admin/commerce/settings
     */
    public function settings(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $gateways = $this->gatewayRegistry->getAll();
        $currentSettings = $this->settings ? $this->settings->all() : [];

        $html = $this->renderView('admin/settings', [
            'baseUrl'   => $baseUrl,
            'gateways'  => $gateways,
            'settings'  => $currentSettings,
            'csrfToken' => (string)$this->session->getCsrfToken(),
            'flash'     => $this->session->getFlash('success'),
            'error'     => $this->session->getFlash('error'),
        ]);

        return new ViewResponse($html);
    }

    /**
     * Salva as Configurações de Gateways: POST /admin/commerce/settings
     */
    public function saveSettings(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $token = (string)$request->input('csrf_token');
        $expectedToken = (string)$this->session->getCsrfToken();

        if ($token === '' || !hash_equals($expectedToken, $token)) {
            $this->session->setFlash('error', 'Token CSRF inválido.');
            return Response::redirect($baseUrl . '/admin/commerce/settings');
        }

        if ($this->settings !== null) {
            $data = [
                'mercadopago_enabled'      => (bool)$request->input('mercadopago_enabled'),
                'mercadopago_access_token' => trim((string)$request->input('mercadopago_access_token', '')),
                'mercadopago_public_key'   => trim((string)$request->input('mercadopago_public_key', '')),
                'mercadopago_sandbox'      => (bool)$request->input('mercadopago_sandbox'),
                'paypal_enabled'           => (bool)$request->input('paypal_enabled'),
                'paypal_client_id'         => trim((string)$request->input('paypal_client_id', '')),
                'paypal_client_secret'     => trim((string)$request->input('paypal_client_secret', '')),
                'paypal_sandbox'           => (bool)$request->input('paypal_sandbox'),
                'stripe_enabled'           => (bool)$request->input('stripe_enabled'),
                'stripe_secret_key'        => trim((string)$request->input('stripe_secret_key', '')),
                'stripe_publishable_key'   => trim((string)$request->input('stripe_publishable_key', '')),
                'stripe_webhook_secret'    => trim((string)$request->input('stripe_webhook_secret', '')),
                'hotmart_enabled'          => (bool)$request->input('hotmart_enabled'),
                'hotmart_hottok'           => trim((string)$request->input('hotmart_hottok', '')),
                'eduzz_enabled'            => (bool)$request->input('eduzz_enabled'),
                'eduzz_token'              => trim((string)$request->input('eduzz_token', '')),
                'monetizze_enabled'        => (bool)$request->input('monetizze_enabled'),
                'monetizze_chave_unica'    => trim((string)$request->input('monetizze_chave_unica', '')),
                'clickbank_enabled'        => (bool)$request->input('clickbank_enabled'),
                'clickbank_secret'         => trim((string)$request->input('clickbank_secret', '')),
                'active_currency'          => (string)($request->input('active_currency') ?: 'BRL'),
            ];

            $this->settings->save($data);
            $this->session->setFlash('success', 'Configurações do DomainCommerce salvas com sucesso!');
        }

        return Response::redirect($baseUrl . '/admin/commerce/settings');
    }

    /**
     * Validação em tempo real das credenciais do Mercado Pago: POST /admin/commerce/test-mercadopago
     */
    public function testMercadoPago(Request $request): ResponseInterface
    {
        $token = (string)($request->input('csrf_token') ?? ($request->server['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $accessToken = trim((string)$request->input('access_token', ''));
        $publicKey = trim((string)$request->input('public_key', ''));

        /** @var \DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway|null $mpGateway */
        $mpGateway = $this->gatewayRegistry->get('mercadopago');
        if (!($mpGateway instanceof \DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway)) {
            $mpGateway = new \DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway($accessToken, $publicKey, $this->settings);
        }

        $results = $mpGateway->testCredentials($accessToken, $publicKey);

        return new JsonResponse([
            'success'      => true,
            'access_token' => $results['access_token'],
            'public_key'   => $results['public_key'],
        ]);
    }

    /**
     * Validação em tempo real das credenciais do Stripe: POST /admin/commerce/test-stripe
     */
    public function testStripe(Request $request): ResponseInterface
    {
        $token = (string)($request->input('csrf_token') ?? ($request->server['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $secretKey = trim((string)$request->input('secret_key', ''));

        /** @var \DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway|null $stripeGateway */
        $stripeGateway = $this->gatewayRegistry->get('stripe');
        if (!($stripeGateway instanceof \DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway)) {
            $stripeGateway = new \DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway($secretKey, null, $this->settings);
        }

        $result = $stripeGateway->testCredentials($secretKey);

        return new JsonResponse([
            'success' => true,
            'result'  => $result,
        ]);
    }

    /**
     * Validação em tempo real das credenciais do PayPal: POST /admin/commerce/test-paypal
     */
    public function testPayPal(Request $request): ResponseInterface
    {
        $token = (string)($request->input('csrf_token') ?? ($request->server['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $clientId = trim((string)$request->input('client_id', ''));
        $clientSecret = trim((string)$request->input('client_secret', ''));

        /** @var \DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway|null $ppGateway */
        $ppGateway = $this->gatewayRegistry->get('paypal');
        if (!($ppGateway instanceof \DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway)) {
            $ppGateway = new \DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway($clientId, $clientSecret, $this->settings);
        }

        $result = $ppGateway->testCredentials($clientId, $clientSecret);

        return new JsonResponse([
            'success' => true,
            'result'  => $result,
        ]);
    }

    /**
     * Altera o Status de um Pedido Manualmente: POST /admin/commerce/orders/{id}/status
     */
    public function updateOrderStatus(Request $request, $id): ResponseInterface
    {
        $orderId = (int)$id;
        $status = (string)$request->input('status', 'paid');
        $token = (string)$request->input('csrf_token');
        $expectedToken = (string)$this->session->getCsrfToken();

        if ($token === '' || !hash_equals($expectedToken, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            return new JsonResponse(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
        }

        $updated = $this->orderRepo->updateStatus($orderId, $status);
        if ($updated && $status === 'paid' && $this->dispatcher !== null) {
            $this->dispatcher->dispatch('payment.approved', [
                'user_id'   => (int)$order['user_id'],
                'course_id' => (int)$order['item_id'],
                'order_ref' => (string)$order['order_ref'],
            ]);
        } elseif ($updated && $status === 'refunded' && $this->dispatcher !== null) {
            $this->dispatcher->dispatch('payment.refunded', [
                'user_id'   => (int)$order['user_id'],
                'course_id' => (int)$order['item_id'],
                'order_ref' => (string)$order['order_ref'],
            ]);
        }

        return new JsonResponse([
            'success'   => $updated,
            'message'   => 'Status do pedido alterado para ' . $status,
            'order_id'  => $orderId,
            'status'    => $status,
        ]);
    }

    /**
     * Exporta os Pedidos para Planilha CSV compatível com Microsoft Excel: GET /admin/commerce/export
     */
    public function export(Request $request): ResponseInterface
    {
        $rawPeriod = trim((string)$request->input('period', ''));
        $rawYear = trim((string)$request->input('year', ''));

        $period = (preg_match('/^\d{4}-\d{2}$/', $rawPeriod)) ? $rawPeriod : null;
        $year = (preg_match('/^\d{4}$/', $rawYear)) ? $rawYear : null;

        $orders = $this->orderRepo->getOrdersForExport($period, $year);

        if ($period) {
            $filename = "balanco-vendas-{$period}.csv";
        } elseif ($year) {
            $filename = "balanco-anual-vendas-{$year}.csv";
        } else {
            $filename = "balanco-geral-vendas-" . date('Y-m-d') . ".csv";
        }

        // Gera arquivo CSV com UTF-8 BOM para garantir acentuação correta no Excel
        $output = "\xEF\xBB\xBF";
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'ID Pedido',
            'Referência',
            'Data / Hora',
            'ID Aluno',
            'Nome do Aluno',
            'E-mail do Aluno',
            'Item',
            'Valor (R$)',
            'Gateway',
            'Forma de Pagamento',
            'Status da Venda',
            'ID Transação Gateway',
            'Última Atualização',
        ], ';');

        $statusLabels = [
            'paid'      => 'Pago / Aprovado',
            'pending'   => 'Aguardando Pagamento',
            'refunded'  => 'Reembolsado',
            'cancelled' => 'Cancelado',
        ];

        $methodLabels = [
            'pix'          => 'Pix Instantâneo',
            'credit_card'  => 'Cartão de Crédito',
            'boleto'       => 'Boleto Bancário',
            'checkout_pro' => 'Checkout Pro',
        ];

        foreach ($orders as $o) {
            $st = (string)($o['status'] ?? 'pending');
            $pm = (string)($o['payment_method'] ?? '');
            
            fputcsv($handle, [
                (int)$o['id'],
                (string)$o['order_ref'],
                (string)$o['created_at'],
                (int)$o['user_id'],
                (string)($o['user_name'] ?? ('Aluno #' . $o['user_id'])),
                (string)($o['user_email'] ?? ''),
                ucfirst((string)$o['item_type']) . ' #' . (int)$o['item_id'],
                number_format((float)$o['total'], 2, ',', '.'),
                ucfirst((string)$o['gateway']),
                $methodLabels[$pm] ?? ucfirst($pm),
                $statusLabels[$st] ?? ucfirst($st),
                (string)($o['transaction_id'] ?? ''),
                (string)($o['updated_at'] ?? ''),
            ], ';');
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $output .= $csvContent;

        return new Response(
            $output,
            200,
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]
        );
    }

    private function renderView(string $viewName, array $data = []): string
    {
        $viewFile = dirname(__DIR__) . '/views/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($viewFile)) {
            return "<div style='padding:20px; color:#fff;'><h1>DomainCommerce</h1><p>Visualização não encontrada.</p></div>";
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewFile;
        return (string)ob_get_clean();
    }
}
