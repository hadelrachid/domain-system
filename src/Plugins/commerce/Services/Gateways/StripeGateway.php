<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services\Gateways;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;
use DomainSystem\Plugins\commerce\Services\StripeSignatureVerifier;
use Throwable;

/**
 * Driver de Integração com o Stripe (Cartão de Crédito Global / Internacional e Stripe Checkout Oficial).
 * Compatível com SOLID / DIP.
 */
class StripeGateway implements PaymentGatewayInterface
{
    private ?string $secretKey;
    private ?string $publishableKey;
    private ?CommerceSettingsInterface $settings;
    private string $apiBaseUrl = 'https://api.stripe.com';

    public function __construct(
        ?string $secretKey = null,
        ?string $publishableKey = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->settings = $settings;
        $this->secretKey = $secretKey
            ?: ($this->settings?->get('stripe_secret_key')
                ?: (getenv('STRIPE_SECRET_KEY') ?: null));

        $this->publishableKey = $publishableKey
            ?: ($this->settings?->get('stripe_publishable_key')
                ?: (getenv('STRIPE_PUBLISHABLE_KEY') ?: null));
    }

    public function getId(): string
    {
        return 'stripe';
    }

    public function getTitle(): string
    {
        return 'Stripe (Cartão de Crédito Global, Apple Pay & Google Pay)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    public function isSandbox(): bool
    {
        if ($this->settings && (bool)$this->settings->get('stripe_sandbox', false)) {
            return true;
        }
        return str_starts_with((string)$this->secretKey, 'sk_test_');
    }

    private function resolveAbsoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (str_starts_with($baseUrl, 'http://') || str_starts_with($baseUrl, 'https://')) {
            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        }

        return $scheme . '://' . $host . $baseUrl . '/' . ltrim($path, '/');
    }

    public function processPayment(array $orderData, array $paymentInput): PaymentResult
    {
        $orderRef = (string)($orderData['order_ref'] ?? ('ORD_' . uniqid('', true)));
        $total = (float)($orderData['total'] ?? 0.0);
        $studentEmail = (string)($orderData['student_email'] ?? 'aluno@exemplo.com');
        $currency = strtolower((string)($this->settings?->get('active_currency', 'BRL') ?: 'BRL'));

        if ($total <= 0.0) {
            return PaymentResult::failure($orderRef, 'Valor do pedido inválido para cobrança no Stripe.', 'stripe');
        }

        // Se estiver em modo de produção/configurado com chaves ativas
        if ($this->isConfigured()) {
            try {
                $successUrl = $this->resolveAbsoluteUrl('/checkout/stripe/success?session_id={CHECKOUT_SESSION_ID}&order_ref=' . urlencode($orderRef));
                $cancelUrl = $this->resolveAbsoluteUrl('/checkout/curso/' . (int)($orderData['item_id'] ?? 0) . '?cancelled=1');

                $params = [
                    'mode'                 => 'payment',
                    'customer_email'       => $studentEmail,
                    'client_reference_id'  => $orderRef,
                    'metadata'             => [
                        'order_ref' => $orderRef,
                        'item_id'   => (string)($orderData['item_id'] ?? ''),
                    ],
                    'line_items'           => [
                        [
                            'quantity'   => 1,
                            'price_data' => [
                                'currency'     => $currency,
                                'unit_amount'  => (int)round($total * 100),
                                'product_data' => [
                                    'name' => (string)($orderData['description'] ?? "Curso Academy - Pedido {$orderRef}"),
                                ],
                            ],
                        ],
                    ],
                    'success_url'          => $successUrl,
                    'cancel_url'           => $cancelUrl,
                ];

                $session = $this->sendHttpRequest('POST', '/v1/checkout/sessions', $params);

                if (!empty($session['url'])) {
                    return PaymentResult::redirect(
                        $orderRef,
                        (string)$session['url'],
                        'stripe',
                        'Redirecionando para o Stripe Checkout seguro...'
                    );
                }

                $errMsg = (string)($session['error']['message'] ?? 'Falha ao criar sessão de pagamento no Stripe.');
                return PaymentResult::failure($orderRef, 'Stripe: ' . $errMsg, 'stripe');
            } catch (Throwable $e) {
                return PaymentResult::failure($orderRef, 'Erro de comunicação com o Stripe: ' . $e->getMessage(), 'stripe');
            }
        }

        // Modo Simulação / Sandbox (somente com commerce_demo_mode=true)
        if (!(bool)($this->settings?->get('commerce_demo_mode', false))) {
            return PaymentResult::failure($orderRef, 'Gateway Stripe não configurado.', 'stripe');
        }

        return PaymentResult::approved(
            $orderRef,
            'stripe',
            'STRIPE_DEMO_' . time(),
            'Modo Demonstração: Pagamento Stripe aprovado instantaneamente!'
        );
    }

    /**
     * Consulta e valida o status de uma sessão do Stripe Checkout após o retorno do aluno.
     */
    public function verifySession(string $sessionId): ?array
    {
        if (!$this->isConfigured() || empty($sessionId)) {
            return null;
        }

        try {
            $session = $this->sendHttpRequest('GET', '/v1/checkout/sessions/' . urlencode($sessionId));
            if (($session['payment_status'] ?? '') === 'paid') {
                return [
                    'order_ref'      => (string)($session['client_reference_id'] ?? ($session['metadata']['order_ref'] ?? '')),
                    'status'         => 'approved',
                    'transaction_id' => (string)($session['payment_intent'] ?? $sessionId),
                    'total'          => isset($session['amount_total']) ? ((float)$session['amount_total'] / 100.0) : 0.0,
                    'gateway'        => $this->getId(),
                ];
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Validação em tempo real das credenciais do Stripe contra a API oficial.
     *
     * @return array{ok: bool, message: string}
     */
    public function testCredentials(?string $secretKey = null): array
    {
        $key = trim((string)($secretKey ?: $this->secretKey));
        if ($key === '') {
            return ['ok' => false, 'message' => 'Nenhuma Secret Key do Stripe informada para teste.'];
        }

        try {
            $ch = curl_init($this->apiBaseUrl . '/v1/balance');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $key,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT        => 8,
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode((string)$res, true);
            if ($code === 200 && is_array($data) && isset($data['object'])) {
                $env = str_starts_with($key, 'sk_test_') ? 'Modo Teste (Sandbox)' : 'Produção Real (Live)';
                return [
                    'ok'      => true,
                    'message' => "Stripe Conectado com Sucesso! Chave Secreta Válida em {$env}.",
                ];
            }

            $detail = is_array($data) ? (string)($data['error']['message'] ?? 'Credencial inválida') : "HTTP {$code}";
            return [
                'ok'      => false,
                'message' => "Falha no Stripe: {$detail} (Código HTTP {$code}).",
            ];
        } catch (Throwable $e) {
            return [
                'ok'      => false,
                'message' => 'Erro ao comunicar com a API do Stripe: ' . $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(RequestInterface $request): ?array
    {
        $secret = (string)($this->settings?->get('stripe_webhook_secret') ?: (getenv('STRIPE_WEBHOOK_SECRET') ?: ''));
        $raw = method_exists($request, 'rawBody') ? $request->rawBody() : '';
        if ($raw === '' && !empty($request->json())) {
            $raw = (string)json_encode($request->json());
        }

        $sigHeader = (string)($request->header('Stripe-Signature') ?? ($request->server['HTTP_STRIPE_SIGNATURE'] ?? ''));
        $verifier = new StripeSignatureVerifier($secret);
        if (!$verifier->verify($raw, ['stripe-signature' => $sigHeader])) {
            return null; // Assinatura ausente ou inválida => ignora
        }

        $payload = $request->json();
        $eventType = (string)($payload['type'] ?? '');

        if (!in_array($eventType, ['checkout.session.completed', 'payment_intent.succeeded'], true)) {
            return null;
        }

        $dataObj = $payload['data']['object'] ?? [];
        $orderRef = (string)($dataObj['client_reference_id'] ?? ($dataObj['metadata']['order_ref'] ?? ''));
        $txId = (string)($dataObj['id'] ?? '');

        if ($orderRef === '') {
            return null;
        }

        return [
            'order_ref'      => $orderRef,
            'status'         => 'approved',
            'transaction_id' => $txId,
            'gateway'        => 'stripe',
        ];
    }

    private function sendHttpRequest(string $method, string $path, array $data = []): array
    {
        $ch = curl_init($this->apiBaseUrl . $path);
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $httpCode >= 500) {
            throw new \RuntimeException("Falha de conexão com a API do Stripe (HTTP {$httpCode}).");
        }

        $decoded = json_decode((string)$res, true);
        return is_array($decoded) ? $decoded : [];
    }
}
