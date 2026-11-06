<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services\Gateways;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;
use Throwable;

/**
 * Driver de Integração com o PayPal (Vendas Globais / Moeda Estrangeira e Checkout Oficial v2).
 * Compatível com SOLID / DIP.
 */
class PayPalGateway implements PaymentGatewayInterface
{
    private ?string $clientId;
    private ?string $clientSecret;
    private ?CommerceSettingsInterface $settings;

    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->settings = $settings;
        $this->clientId = $clientId 
            ?: ($this->settings?->get('paypal_client_id')
                ?: (getenv('PAYPAL_CLIENT_ID') ?: null));
        $this->clientSecret = $clientSecret 
            ?: ($this->settings?->get('paypal_client_secret')
                ?: (getenv('PAYPAL_CLIENT_SECRET') ?: null));
    }

    public function getId(): string
    {
        return 'paypal';
    }

    public function getTitle(): string
    {
        return 'PayPal (Cartões Globais & Saldo PayPal)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    public function isSandbox(): bool
    {
        if ($this->settings && $this->settings->get('paypal_sandbox') !== null) {
            return (bool)$this->settings->get('paypal_sandbox');
        }
        return true; // Padrão seguro é Sandbox a menos que explicitamente desligado
    }

    private function getApiBaseUrl(): string
    {
        return $this->isSandbox() ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
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
        $currency = strtoupper((string)($this->settings?->get('active_currency', 'BRL') ?: 'BRL'));

        if ($total <= 0.0) {
            return PaymentResult::failure($orderRef, 'Valor do pedido inválido para cobrança no PayPal.', 'paypal');
        }

        // Se estiver configurado, cria ordem oficial via API REST v2 do PayPal
        if ($this->isConfigured()) {
            try {
                $token = $this->getAccessToken();
                if (!$token) {
                    return PaymentResult::failure($orderRef, 'Falha de autenticação com o PayPal. Verifique o Client ID e Client Secret.', 'paypal');
                }

                $returnUrl = $this->resolveAbsoluteUrl('/checkout/paypal/success?order_ref=' . urlencode($orderRef));
                $cancelUrl = $this->resolveAbsoluteUrl('/checkout/curso/' . (int)($orderData['item_id'] ?? 0) . '?cancelled=1');

                $orderPayload = [
                    'intent'         => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => $orderRef,
                            'description'  => (string)($orderData['description'] ?? "Curso Academy - Pedido {$orderRef}"),
                            'amount'       => [
                                'currency_code' => $currency,
                                'value'         => number_format($total, 2, '.', ''),
                            ],
                        ],
                    ],
                    'application_context' => [
                        'brand_name'          => 'Academy',
                        'landing_page'        => 'BILLING',
                        'user_action'         => 'PAY_NOW',
                        'return_url'          => $returnUrl,
                        'cancel_url'          => $cancelUrl,
                    ],
                ];

                $response = $this->sendHttpRequest('POST', '/v2/checkout/orders', $orderPayload, $token);
                $links = $response['links'] ?? [];
                $approveUrl = null;

                foreach ($links as $link) {
                    if (($link['rel'] ?? '') === 'approve') {
                        $approveUrl = (string)($link['href'] ?? '');
                        break;
                    }
                }

                if (!empty($approveUrl)) {
                    return PaymentResult::redirect(
                        $orderRef,
                        $approveUrl,
                        'paypal',
                        'Redirecionando para o ambiente seguro do PayPal...'
                    );
                }

                $errMsg = (string)($response['message'] ?? 'Não foi possível gerar o link de pagamento do PayPal.');
                if (!empty($response['details']) && is_array($response['details'])) {
                    $details = [];
                    foreach ($response['details'] as $detail) {
                        if (!empty($detail['description'])) {
                            $details[] = $detail['description'];
                        }
                    }
                    if (!empty($details)) {
                        $errMsg .= ' (' . implode('; ', $details) . ')';
                    }
                }
                return PaymentResult::failure($orderRef, 'PayPal: ' . $errMsg, 'paypal');
            } catch (Throwable $e) {
                return PaymentResult::failure($orderRef, 'Erro de comunicação com o PayPal: ' . $e->getMessage(), 'paypal');
            }
        }

        // Modo Simulação Local (somente com commerce_demo_mode=true)
        if (!(bool)($this->settings?->get('commerce_demo_mode', false))) {
            return PaymentResult::failure($orderRef, 'Gateway PayPal não configurado.', 'paypal');
        }

        return PaymentResult::approved(
            $orderRef,
            'paypal',
            'PAYPAL_DEMO_' . time(),
            'Modo Demonstração: Pagamento PayPal aprovado!'
        );
    }

    /**
     * Captura o pagamento após o aluno retornar do PayPal aprovado.
     */
    public function captureOrder(string $paypalOrderId): ?array
    {
        if (!$this->isConfigured() || empty($paypalOrderId)) {
            return null;
        }

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            $response = $this->sendHttpRequest('POST', '/v2/checkout/orders/' . urlencode($paypalOrderId) . '/capture', [], $token);
            $status = (string)($response['status'] ?? '');

            if ($status === 'COMPLETED') {
                $pu = $response['purchase_units'][0] ?? [];
                $capture = $pu['payments']['captures'][0] ?? [];
                return [
                    'order_ref'      => (string)($pu['reference_id'] ?? ''),
                    'status'         => 'approved',
                    'transaction_id' => (string)($capture['id'] ?? $paypalOrderId),
                    'total'          => (float)($capture['amount']['value'] ?? 0.0),
                    'gateway'        => $this->getId(),
                ];
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Obtém o Access Token de autorização OAuth2 da API v1 do PayPal.
     */
    public function getAccessToken(?string $clientId = null, ?string $clientSecret = null): ?string
    {
        $id = trim((string)($clientId ?: $this->clientId));
        $sec = trim((string)($clientSecret ?: $this->clientSecret));

        if ($id === '' || $sec === '') {
            return null;
        }

        try {
            $ch = curl_init($this->getApiBaseUrl() . '/v1/oauth2/token');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Basic ' . base64_encode($id . ':' . $sec),
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT        => 10,
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode((string)$res, true);
            if ($code === 200 && is_array($data) && !empty($data['access_token'])) {
                return (string)$data['access_token'];
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Validação em tempo real das credenciais do PayPal.
     *
     * @return array{ok: bool, message: string}
     */
    public function testCredentials(?string $clientId = null, ?string $clientSecret = null): array
    {
        $id = trim((string)($clientId ?: $this->clientId));
        $sec = trim((string)($clientSecret ?: $this->clientSecret));

        if ($id === '' || $sec === '') {
            return ['ok' => false, 'message' => 'Client ID e Client Secret do PayPal são obrigatórios para teste.'];
        }

        $token = $this->getAccessToken($id, $sec);
        if ($token !== null) {
            $env = $this->isSandbox() ? 'Sandbox (Testes)' : 'Produção (Live)';
            return [
                'ok'      => true,
                'message' => "PayPal Conectado com Sucesso! Autenticação OAuth2 aprovada em {$env}.",
            ];
        }

        return [
            'ok'      => false,
            'message' => 'Falha de autenticação no PayPal: Client ID ou Client Secret inválidos.',
        ];
    }

    public function handleWebhook(RequestInterface $request): ?array
    {
        $payload = $request->json();
        $eventType = (string)($payload['event_type'] ?? '');

        if ($eventType !== 'CHECKOUT.ORDER.APPROVED' && $eventType !== 'PAYMENT.CAPTURE.COMPLETED') {
            return null;
        }

        $resource = $payload['resource'] ?? [];
        $orderRef = (string)($resource['purchase_units'][0]['reference_id'] ?? ($resource['custom_id'] ?? ''));
        $txId = (string)($resource['id'] ?? '');

        if ($orderRef === '') {
            return null;
        }

        return [
            'order_ref'      => $orderRef,
            'status'         => 'approved',
            'transaction_id' => $txId,
            'gateway'        => 'paypal',
        ];
    }

    private function sendHttpRequest(string $method, string $path, array $data = [], ?string $accessToken = null): array
    {
        $ch = curl_init($this->getApiBaseUrl() . $path);
        $headers = [
            'Authorization: Bearer ' . ($accessToken ?: ''),
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $httpCode >= 500) {
            throw new \RuntimeException("Falha de conexão com a API do PayPal (HTTP {$httpCode}).");
        }

        $decoded = json_decode((string)$res, true);
        return is_array($decoded) ? $decoded : [];
    }
}
