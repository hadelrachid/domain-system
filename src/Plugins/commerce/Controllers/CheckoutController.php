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
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface;

/**
 * Controlador de Checkout e Finalização de Matrículas (SRP / DIP / Sem Superglobais).
 */
class CheckoutController
{
    private SessionManagerInterface $session;
    private OrderRepositoryInterface $orderRepo;
    private PaymentGatewayRegistryInterface $gatewayRegistry;
    private ?CourseRepositoryInterface $courseRepo;
    private ?EventDispatcherInterface $dispatcher;
    private ?PurchasableItemResolverInterface $itemResolver;
    private ?CommerceSettingsInterface $settings;

    public function __construct(
        SessionManagerInterface $session,
        OrderRepositoryInterface $orderRepo,
        PaymentGatewayRegistryInterface $gatewayRegistry,
        ?CourseRepositoryInterface $courseRepo = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?PurchasableItemResolverInterface $itemResolver = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->session = $session;
        $this->orderRepo = $orderRepo;
        $this->gatewayRegistry = $gatewayRegistry;
        $this->courseRepo = $courseRepo;
        $this->dispatcher = $dispatcher;
        $this->itemResolver = $itemResolver;
        $this->settings = $settings;
    }

    private function isDemoModeEnabled(): bool
    {
        return $this->settings !== null && (bool)$this->settings->get('commerce_demo_mode', false);
    }

    /**
     * Tela de Checkout: GET /checkout/curso/{id}
     */
    public function showCheckout(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $courseId = (int)$id;

        $course = null;
        if ($this->courseRepo !== null) {
            $c = $this->courseRepo->findById($courseId);
            if ($c && ($c['status'] ?? '') === 'published') {
                $course = $c;
            }
        }

        if ($course === null && $this->itemResolver !== null) {
            $item = $this->itemResolver->resolve('course', $courseId)
                 ?? $this->itemResolver->resolve('dropship_product', $courseId)
                 ?? $this->itemResolver->resolve('product', $courseId);
            if ($item && $item->isAvailable()) {
                $course = [
                    'id'          => $item->getId(),
                    'title'       => $item->getTitle(),
                    'price'       => $item->getPrice(),
                    'status'      => 'published',
                    'description' => $item->getDescription(),
                    'image'       => $item->getThumbnailUrl(),
                    'type'        => $item->getItemType(),
                ];
            }
        }

        if (!$course) {
            return new Response("<h1>404 - Item não encontrado</h1>", 404);
        }

        $userId = (int)$this->session->get('academy_student_id', 0);
        if ($userId <= 0) {
            $userId = (int)$this->session->get('user_id', 0);
        }

        if ($userId <= 0) {
            $checkoutUri = $baseUrl . '/checkout/curso/' . $courseId;
            return Response::redirect($baseUrl . '/aluno/login?redirect=' . urlencode($checkoutUri));
        }

        // REGRA DE INJEÇÃO E DISPONIBILIDADE:
        // Só disponibiliza gateways que possuem credenciais/chaves REALMENTE configuradas.
        $allConfiguredGateways = array_values(array_filter(
            $this->gatewayRegistry->getAll(),
            function ($g) {
                $id = $g->getId();
                // Exclui webhooks de afiliados externos (Hotmart, Eduzz, etc.)
                if (in_array($id, ['hotmart', 'eduzz', 'monetizze', 'clickbank'], true)) {
                    return false;
                }
                // REGRA CRÍTICA: Se não há chave/credencial configurada, não pode ficar disponível
                if (!$g->isConfigured()) {
                    return false;
                }
                if ($this->settings !== null) {
                    $enabledKey = $id . '_enabled';
                    $isEnabled = (bool)$this->settings->get($enabledKey, $id === 'mercadopago');
                    if (!$isEnabled) {
                        return false;
                    }
                }
                return true;
            }
        ));

        // Modalidade configurada especificamente para este curso (ex: 'paypal', 'mercadopago', 'stripe')
        $coursePlatform = strtolower(trim((string)($course['external_platform'] ?? '')));
        $gateways = $allConfiguredGateways;

        if ($coursePlatform !== '' && in_array($coursePlatform, ['paypal', 'stripe', 'mercadopago'], true)) {
            $specificGateways = array_values(array_filter($allConfiguredGateways, function ($g) use ($coursePlatform) {
                return $g->getId() === $coursePlatform;
            }));
            // Se o gateway específico escolhido para o curso estiver configurado com chave, usa ele exclusivamente!
            if (!empty($specificGateways)) {
                $gateways = $specificGateways;
            }
        }
        $csrfToken = (string)$this->session->getCsrfToken();
        $userEmail = (string)$this->session->get('academy_student_email', (string)$this->session->get('user_email', ''));
        $userName = (string)$this->session->get('academy_student_name', (string)$this->session->get('user_name', 'Aluno'));
        $userCpf = (string)$this->session->get('user_cpf', (string)$this->session->get('academy_student_cpf', ''));

        $courseImage = '';
        if (!empty($course['image'])) {
            $courseImage = (string)$course['image'];
        } elseif (!empty($course['thumbnail'])) {
            $thumb = (string)$course['thumbnail'];
            if (str_starts_with($thumb, 'http://') || str_starts_with($thumb, 'https://') || str_starts_with($thumb, 'data:')) {
                $courseImage = $thumb;
            } else {
                $courseImage = $baseUrl . '/' . ltrim($thumb, '/');
            }
        }

        $html = $this->renderView('front/checkout', [
            'baseUrl'        => $baseUrl,
            'course'         => $course,
            'courseImage'    => $courseImage,
            'gateways'       => $gateways,
            'coursePlatform' => $coursePlatform,
            'csrfToken'      => $csrfToken,
            'userEmail'      => $userEmail,
            'userName'       => $userName,
            'userCpf'        => $userCpf,
            'total'          => (float)($course['price'] ?? 0.0),
        ]);

        return new ViewResponse($html);
    }

    /**
     * Tela de Checkout de Produtos Físicos / Dropshipping: GET /checkout/produto/{id}
     */
    public function showProductCheckout(Request $request, $id): ResponseInterface
    {
        return $this->showCheckout($request, $id);
    }

    /**
     * Processamento de Cobrança: POST /checkout/process
     */
    public function process(Request $request): ResponseInterface
    {
        $userId = (int)$this->session->get('academy_student_id', 0);
        if ($userId <= 0) {
            $userId = (int)$this->session->get('user_id', 0);
        }

        if ($userId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Faça login para concluir o pagamento.'], 401);
        }

        $token = (string)($request->input('csrf_token') ?? ($request->server['HTTP_X_CSRF_TOKEN'] ?? ''));
        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $courseId = (int)($request->input('item_id') ?? $request->input('course_id'));
        $itemType = (string)($request->input('item_type') ?? '');
        $course = null;

        if ($this->itemResolver !== null) {
            $resolvedType = $itemType !== '' ? $itemType : 'course';
            $item = $this->itemResolver->resolve($resolvedType, $courseId)
                 ?? $this->itemResolver->resolve('dropship_product', $courseId)
                 ?? $this->itemResolver->resolve('course', $courseId);
            if ($item && $item->isAvailable()) {
                $course = [
                    'id'    => $item->getId(),
                    'title' => $item->getTitle(),
                    'price' => $item->getPrice(),
                    'type'  => $item->getItemType(),
                ];
                $itemType = $item->getItemType();
            }
        }

        if ($course === null && $this->courseRepo !== null) {
            $c = $this->courseRepo->findById($courseId);
            if ($c) {
                $course = $c;
                $itemType = 'course';
            }
        }

        if (!$course) {
            return new JsonResponse(['success' => false, 'message' => 'Item não encontrado.'], 404);
        }

        if ($itemType === '') {
            $itemType = 'course';
        }

        $total = (float)($course['price'] ?? 0.0);
        $gatewayId = (string)($request->input('gateway') ?? 'mercadopago');
        $paymentMethod = (string)($request->input('payment_method') ?? 'pix');
        $payerCpf = preg_replace('/\D/', '', (string)($request->input('payer_cpf') ?? $this->session->get('user_cpf', '')));

        if ($payerCpf !== '') {
            $this->session->set('user_cpf', $payerCpf);
            $this->session->set('academy_student_cpf', $payerCpf);
        }

        $gateway = $this->gatewayRegistry->get($gatewayId);
        if (!$gateway) {
            return new JsonResponse(['success' => false, 'message' => 'Meio de pagamento inválido.'], 422);
        }

        // Só cobra por gateway com credenciais reais. Modo demonstração exige flag explícita.
        if (!$gateway->isConfigured() && !$this->isDemoModeEnabled()) {
            return new JsonResponse(['success' => false, 'message' => 'Este meio de pagamento não está disponível no momento.'], 409);
        }

        // Para Pix e Boleto em ambiente real do Mercado Pago, CPF com 11 dígitos é obrigatório
        if (in_array($paymentMethod, ['pix', 'boleto'], true) && strlen($payerCpf) !== 11 && $gateway->getId() === 'mercadopago') {
            if ($gateway instanceof \DomainSystem\Plugins\commerce\Services\Gateways\MercadoPagoGateway && !$gateway->isSandbox() && !$this->isDemoModeEnabled()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Por favor, informe seu CPF (11 dígitos) para emitir o ' . ($paymentMethod === 'pix' ? 'Pix' : 'Boleto Bancário') . '.',
                ], 422);
            }
        }

        $orderRef = 'ORD_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
        $orderId = $this->orderRepo->createOrder([
            'user_id'        => $userId,
            'item_type'      => $itemType,
            'item_id'        => $courseId,
            'total'          => $total,
            'status'         => 'pending',
            'gateway'        => $gateway->getId(),
            'payment_method' => $paymentMethod,
            'order_ref'      => $orderRef,
        ]);

        $studentName = (string)$this->session->get('academy_student_name', (string)$this->session->get('user_name', 'Aluno'));
        $studentEmail = (string)$this->session->get('academy_student_email', (string)$this->session->get('user_email', 'aluno@exemplo.com'));

        $orderData = [
            'order_ref'     => $orderRef,
            'total'         => $total,
            'description'   => "Matrícula no Curso: " . $course['title'],
            'student_name'  => $studentName,
            'student_email' => $studentEmail,
            'payer_cpf'     => $payerCpf,
        ];

        $paymentResult = $gateway->processPayment($orderData, [
            'method'            => $paymentMethod,
            'card_token'        => $request->input('card_token'),
            'installments'      => $request->input('installments', 1),
            'payment_method_id' => $request->input('payment_method_id'),
            'payer_cpf'         => $payerCpf,
        ]);

        if ($paymentResult->status === 'approved') {
            $this->orderRepo->updateStatus($orderId, 'paid', $paymentResult->transactionId);

            // Dispara evento reativo do Kernel para liberar a matrícula no Academy imediatamente
            if ($this->dispatcher !== null) {
                $this->dispatcher->dispatch('payment.approved', [
                    'user_id'   => $userId,
                    'course_id' => $courseId,
                    'item_id'   => $courseId,
                    'item_type' => $itemType,
                    'order_ref' => $orderRef,
                ]);
            }

            return new JsonResponse([
                'success'      => true,
                'status'       => 'approved',
                'order_ref'    => $orderRef,
                'redirect_url' => (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/aluno/painel',
                'message'      => 'Pagamento aprovado com sucesso! Sua matrícula foi liberada.',
            ]);
        }

        if ($paymentResult->status === 'pending') {
            return new JsonResponse([
                'success'           => true,
                'status'            => 'pending',
                'order_ref'         => $orderRef,
                'payment_method'    => $paymentResult->paymentMethod,
                'pix_copia_e_cola'  => $paymentResult->pixCopiaECola,
                'pix_qr_code'       => $paymentResult->pixQrCode,
                'boleto_url'        => $paymentResult->boletoUrl,
                'redirect_url'      => $paymentResult->redirectUrl,
                'message'           => $paymentResult->message,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'status'  => 'failed',
            'message' => $paymentResult->message ?: 'Falha ao processar o pagamento.',
        ], 422);
    }

    /**
     * Consulta status do pedido para polling do Pix em tempo real: GET /checkout/status/{orderRef}
     */
    public function checkStatus(Request $request, string $orderRef): ResponseInterface
    {
        $order = $this->orderRepo->findByOrderRef($orderRef);
        if (!$order) {
            return new JsonResponse(['success' => false, 'paid' => false, 'message' => 'Pedido não encontrado.'], 404);
        }

        $status = (string)($order['status'] ?? 'pending');
        $isPaid = $status === 'paid';
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        return new JsonResponse([
            'success'      => true,
            'status'       => $status,
            'paid'         => $isPaid,
            'order_ref'    => $orderRef,
            'redirect_url' => $baseUrl . '/aluno/painel',
        ]);
    }

    /**
     * Retorno após checkout no Stripe: GET /checkout/stripe/success
     */
    public function stripeSuccess(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $sessionId = (string)($request->input('session_id') ?? '');
        $orderRef = (string)($request->input('order_ref') ?? '');

        $order = $this->orderRepo->findByOrderRef($orderRef);
        if (!$order) {
            return Response::redirect($baseUrl . '/aluno/painel?payment_status=error&message=' . urlencode('Pedido não localizado.'));
        }

        $gateway = $this->gatewayRegistry->get('stripe');
        if ($gateway instanceof \DomainSystem\Plugins\commerce\Services\Gateways\StripeGateway && !empty($sessionId)) {
            $verified = $gateway->verifySession($sessionId);
            if ($verified && ($verified['status'] ?? '') === 'approved') {
                $this->orderRepo->updateStatus((int)$order['id'], 'paid', (string)($verified['transaction_id'] ?? $sessionId));

                if ($this->dispatcher !== null) {
                    $this->dispatcher->dispatch('payment.approved', [
                        'user_id'   => (int)$order['user_id'],
                        'course_id' => (int)$order['item_id'],
                        'item_id'   => (int)$order['item_id'],
                        'item_type' => (string)($order['item_type'] ?? 'course'),
                        'order_ref' => $orderRef,
                    ]);
                }

                return Response::redirect($baseUrl . '/aluno/painel?payment_status=approved&gateway=stripe&order_ref=' . urlencode($orderRef));
            }
        }

        if ($this->isDemoModeEnabled()) {
            $this->orderRepo->updateStatus((int)$order['id'], 'paid', 'STRIPE_DEMO_' . time());
            if ($this->dispatcher !== null) {
                $this->dispatcher->dispatch('payment.approved', [
                    'user_id'   => (int)$order['user_id'],
                    'course_id' => (int)$order['item_id'],
                    'item_id'   => (int)$order['item_id'],
                    'item_type' => (string)($order['item_type'] ?? 'course'),
                    'order_ref' => $orderRef,
                ]);
            }
            return Response::redirect($baseUrl . '/aluno/painel?payment_status=approved&gateway=stripe&order_ref=' . urlencode($orderRef));
        }

        return Response::redirect($baseUrl . '/aluno/painel?payment_status=pending&gateway=stripe&order_ref=' . urlencode($orderRef));
    }

    /**
     * Retorno após aprovação no PayPal: GET /checkout/paypal/success
     */
    public function paypalSuccess(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $token = (string)($request->input('token') ?? ''); // PayPal order ID
        $orderRef = (string)($request->input('order_ref') ?? '');

        $order = $this->orderRepo->findByOrderRef($orderRef);
        if (!$order) {
            return Response::redirect($baseUrl . '/aluno/painel?payment_status=error&message=' . urlencode('Pedido não localizado.'));
        }

        $gateway = $this->gatewayRegistry->get('paypal');
        if ($gateway instanceof \DomainSystem\Plugins\commerce\Services\Gateways\PayPalGateway && !empty($token)) {
            $captured = $gateway->captureOrder($token);
            if ($captured && ($captured['status'] ?? '') === 'approved') {
                $this->orderRepo->updateStatus((int)$order['id'], 'paid', (string)($captured['transaction_id'] ?? $token));

                if ($this->dispatcher !== null) {
                    $this->dispatcher->dispatch('payment.approved', [
                        'user_id'   => (int)$order['user_id'],
                        'course_id' => (int)$order['item_id'],
                        'item_id'   => (int)$order['item_id'],
                        'item_type' => (string)($order['item_type'] ?? 'course'),
                        'order_ref' => $orderRef,
                    ]);
                }

                return Response::redirect($baseUrl . '/aluno/painel?payment_status=approved&gateway=paypal&order_ref=' . urlencode($orderRef));
            }
        }

        if ($this->isDemoModeEnabled()) {
            $this->orderRepo->updateStatus((int)$order['id'], 'paid', 'PAYPAL_DEMO_' . time());
            if ($this->dispatcher !== null) {
                $this->dispatcher->dispatch('payment.approved', [
                    'user_id'   => (int)$order['user_id'],
                    'course_id' => (int)$order['item_id'],
                    'item_id'   => (int)$order['item_id'],
                    'item_type' => (string)($order['item_type'] ?? 'course'),
                    'order_ref' => $orderRef,
                ]);
            }
            return Response::redirect($baseUrl . '/aluno/painel?payment_status=approved&gateway=paypal&order_ref=' . urlencode($orderRef));
        }

        return Response::redirect($baseUrl . '/aluno/painel?payment_status=pending&gateway=paypal&order_ref=' . urlencode($orderRef));
    }

    private function renderView(string $viewName, array $data = []): string
    {
        $viewFile = dirname(__DIR__) . '/views/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($viewFile)) {
            return "<h1>Checkout</h1><p>Visualização em configuração.</p>";
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewFile;
        return (string)ob_get_clean();
    }
}
