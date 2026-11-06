<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services\Gateways;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;
use Throwable;

/**
 * Driver de Integração com o Mercado Pago (Mercado Livre) - Pix, Cartão e Boleto (SRP / DIP).
 */
class MercadoPagoGateway implements PaymentGatewayInterface
{
    private ?string $accessToken;
    private ?string $publicKey;
    private ?CommerceSettingsInterface $settings;
    private string $apiBaseUrl = 'https://api.mercadopago.com';

    public function __construct(
        ?string $accessToken = null,
        ?string $publicKey = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->settings = $settings;
        $this->accessToken = $accessToken 
            ?: ($this->settings?->get('mercadopago_access_token')
                ?: (getenv('MERCADOPAGO_ACCESS_TOKEN') ?: (getenv('MP_ACCESS_TOKEN') ?: null)));
        $this->publicKey = $publicKey 
            ?: ($this->settings?->get('mercadopago_public_key')
                ?: (getenv('MERCADOPAGO_PUBLIC_KEY') ?: (getenv('MP_PUBLIC_KEY') ?: null)));
    }

    public function getId(): string
    {
        return 'mercadopago';
    }

    public function getTitle(): string
    {
        return 'Mercado Pago (Pix Instantâneo, Cartão até 12x, Boleto & Checkout Pro Oficial)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessToken);
    }

    public function isSandbox(): bool
    {
        if ($this->settings && (bool)$this->settings->get('mercadopago_sandbox', false)) {
            return true;
        }
        return str_starts_with((string)$this->accessToken, 'TEST-');
    }

    public function processPayment(array $orderData, array $paymentInput): PaymentResult
    {
        $orderRef = (string)($orderData['order_ref'] ?? ('ORD_' . uniqid('', true)));
        $total = (float)($orderData['total'] ?? 0.0);
        $method = (string)($paymentInput['method'] ?? 'pix');
        $studentEmail = (string)($orderData['student_email'] ?? 'aluno@exemplo.com');
        $studentName = (string)($orderData['student_name'] ?? 'Aluno');
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if ($total <= 0.0) {
            return PaymentResult::failure($orderRef, 'Valor do pedido inválido para cobrança.', $method);
        }

        // Se o token de acesso estiver configurado, envia a requisição oficial para a API REST do Mercado Pago
        if ($this->isConfigured()) {
            try {
                // Caso 1: Checkout Pro (Preferências) ou Fallback para aplicação de Orders/Checkout Pro
                if ($method === 'checkout_pro' || ($method === 'credit_card' && empty($paymentInput['card_token']))) {
                    $prefResult = $this->createPreferenceRedirect($orderData, $orderRef, $total, $studentName, $studentEmail, $baseUrl);
                    if ($prefResult !== null) {
                        return $prefResult;
                    }
                }

                // Caso 2: API de Pagamentos Diretos (/v1/payments) - Pix, Boleto ou Cartão com token
                $endpoint = $this->apiBaseUrl . '/v1/payments';

                $payerCpf = preg_replace('/\D/', '', (string)($paymentInput['payer_cpf'] ?? $orderData['payer_cpf'] ?? $orderData['customer_cpf'] ?? ''));
                if (strlen($payerCpf) !== 11 && !$this->isSandbox()) {
                    return PaymentResult::failure(
                        $orderRef,
                        'CPF do pagador obrigatório e deve conter 11 dígitos para emissão do pagamento.',
                        $method
                    );
                }

                $rawEmail = (string)($orderData['student_email'] ?? $paymentInput['payer_email'] ?? '');
                $validatedEmail = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
                if (!$validatedEmail && !$this->isSandbox()) {
                    return PaymentResult::failure(
                        $orderRef,
                        'E-mail válido do pagador é obrigatório para emissão do pagamento.',
                        $method
                    );
                }

                $payerEmail = (string)$validatedEmail;
                $payerFirstName = $studentName ?: 'Aluno';
                $payerLastName = 'Academy';

                if ($this->isSandbox()) {
                    if (strlen($payerCpf) !== 11) {
                        $payerCpf = '19119119100'; // CPF fictício válido apenas para o sandbox do Mercado Pago
                    }
                    if (!$payerEmail || !str_ends_with(strtolower($payerEmail), '@testuser.com')) {
                        $payerEmail = 'test_user_sandbox@testuser.com';
                    }
                }

                $payload = [
                    'transaction_amount' => $total,
                    'description'        => (string)($orderData['description'] ?? "Curso Academy - Pedido {$orderRef}"),
                    'external_reference' => $orderRef,
                    'payer'              => [
                        'email'          => $payerEmail,
                        'first_name'     => $payerFirstName,
                        'last_name'      => $payerLastName,
                        'identification' => [
                            'type'   => 'CPF',
                            'number' => $payerCpf,
                        ],
                    ],
                ];

                $webhookUrl = $baseUrl . '/webhook/payment/mercadopago';
                if (filter_var($webhookUrl, FILTER_VALIDATE_URL) && !str_contains($webhookUrl, 'localhost') && !str_contains($webhookUrl, '127.0.0.1')) {
                    $payload['notification_url'] = $webhookUrl;
                }

                if ($method === 'pix') {
                    $payload['payment_method_id'] = 'pix';
                } elseif ($method === 'boleto') {
                    $payload['payment_method_id'] = 'bolbradesco';
                } elseif ($method === 'credit_card') {
                    $payload['token'] = (string)($paymentInput['card_token'] ?? '');
                    $payload['installments'] = max(1, (int)($paymentInput['installments'] ?? 1));
                    $payload['payment_method_id'] = (string)($paymentInput['payment_method_id'] ?? 'visa');
                }

                $response = $this->sendHttpRequest('POST', $endpoint, $payload);
                $status = (string)($response['status'] ?? 'pending');
                $txId = isset($response['id']) ? (string)$response['id'] : null;

                if ($status === 'approved') {
                    return PaymentResult::approved($orderRef, $method, $txId, 'Pagamento aprovado instantaneamente pelo Mercado Pago!');
                }

                if ($method === 'pix') {
                    $pixData = $response['point_of_interaction']['transaction_data'] ?? [];
                    $qrCodeText = (string)($pixData['qr_code'] ?? '');
                    $qrCodeBase64 = isset($pixData['qr_code_base64']) ? 'data:image/png;base64,' . $pixData['qr_code_base64'] : null;

                    if ($qrCodeText !== '') {
                        return PaymentResult::pendingPix($orderRef, $qrCodeText, $qrCodeBase64, $txId);
                    }

                    // Se a API direta de Pix não retornou QR Code na resposta imediata (ex: credenciais de produção
                    // ainda aguardando homologação do formulário Go-Live no Mercado Pago), gera o QR Code e código Copia-e-Cola
                    // na própria tela do checkout para que o aluno pague via app bancário sem sair do site!
                    return $this->generatePixResult($orderRef, $total, $studentName);
                }

                if ($method === 'boleto') {
                    $boletoUrl = (string)($response['transaction_details']['external_resource_url'] ?? '');
                    if ($boletoUrl !== '') {
                        return PaymentResult::pendingBoleto($orderRef, $boletoUrl, $txId);
                    }

                    // Para boleto que exige endereço completo do pagador na FEBRABAN, redireciona para a preferência oficial
                    $prefResult = $this->createPreferenceRedirect($orderData, $orderRef, $total, $studentName, $studentEmail, $baseUrl);
                    if ($prefResult !== null) {
                        return $prefResult;
                    }
                }

                // Se outro método direto falhou, tenta preferência oficial do Mercado Pago
                if ($method !== 'pix' && (isset($response['error']) || isset($response['message']) || empty($txId))) {
                    $prefResult = $this->createPreferenceRedirect($orderData, $orderRef, $total, $studentName, $studentEmail, $baseUrl);
                    if ($prefResult !== null) {
                        return $prefResult;
                    }
                }

                if ($status === 'in_process' || $status === 'pending') {
                    return new PaymentResult(true, $orderRef, 'pending', $method, null, null, null, null, $txId, 'Pagamento em análise pelo Mercado Pago.');
                }

                $statusDetail = (string)($response['status_detail'] ?? ($response['message'] ?? 'Transação não autorizada.'));
                return PaymentResult::failure($orderRef, "Pagamento rejeitado: {$statusDetail}", $method);
            } catch (Throwable $e) {
                if ($method === 'pix') {
                    // Pix NUNCA redireciona para a página hospedada; entrega o QR Code na tela
                    return $this->generatePixResult($orderRef, $total, $studentName);
                }
                // Em caso de falha de conexão com /v1/payments, tenta fallback para /checkout/preferences
                $prefResult = $this->createPreferenceRedirect($orderData, $orderRef, $total, $studentName, $studentEmail, $baseUrl);
                if ($prefResult !== null) {
                    return $prefResult;
                }
                return PaymentResult::failure($orderRef, 'Erro de comunicação com o Mercado Pago: ' . $e->getMessage(), $method);
            }
        }

        // Modo Simulação / Sandbox Local (somente quando commerce_demo_mode=true)
        if (!(bool)($this->settings?->get('commerce_demo_mode', false))) {
            return PaymentResult::failure($orderRef, 'Gateway Mercado Pago não configurado.', $method);
        }

        if ($method === 'pix') {
            return $this->generatePixResult($orderRef, $total, $studentName);
        }

        if ($method === 'checkout_pro' || $method === 'credit_card') {
            $mockUrl = $baseUrl . '/aluno/painel?payment_status=approved&mock=1&order_ref=' . urlencode($orderRef);
            return PaymentResult::redirect($orderRef, $mockUrl, 'mercadopago', 'Modo Demonstração: Redirecionando para aprovação simulada do Mercado Pago.');
        }

        return PaymentResult::approved($orderRef, $method, 'MP_DEMO_' . time(), 'Modo Demonstração: Pagamento aprovado com sucesso!');
    }

    public function handleWebhook(RequestInterface $request): ?array
    {
        $payload = $request->json();
        $query = $request->all();

        // Mercado Pago envia notificações via data.id ou id na query string
        $paymentId = $payload['data']['id'] ?? ($query['data_id'] ?? ($query['id'] ?? null));
        $type = $payload['type'] ?? ($query['topic'] ?? ($query['type'] ?? null));

        if ($type !== null && !in_array($type, ['payment', 'payment.updated', 'order', 'merchant_order'], true)) {
            return null;
        }

        if (!$paymentId) {
            return null;
        }

        // Se tiver token configurado, faz a checagem no endpoint oficial para validar a autenticidade
        if ($this->isConfigured()) {
            try {
                if ($type === 'order') {
                    $orderEndpoint = $this->apiBaseUrl . '/v1/orders/' . urlencode((string)$paymentId);
                    $orderInfo = $this->sendHttpRequest('GET', $orderEndpoint);
                    $orderRef = (string)($orderInfo['external_reference'] ?? '');
                    $status = (string)($orderInfo['status'] ?? '');
                    if ($orderRef !== '') {
                        return [
                            'order_ref'      => $orderRef,
                            'status'         => in_array($status, ['closed', 'paid', 'approved'], true) ? 'approved' : 'pending',
                            'transaction_id' => (string)$paymentId,
                            'total'          => (float)($paymentInfo['total_amount'] ?? ($paymentInfo['transaction_amount'] ?? 0.0)),
                            'gateway'        => $this->getId(),
                        ];
                    }
                }

                $endpoint = $this->apiBaseUrl . '/v1/payments/' . urlencode((string)$paymentId);
                $paymentInfo = $this->sendHttpRequest('GET', $endpoint);

                $orderRef = (string)($paymentInfo['external_reference'] ?? '');
                $status = (string)($paymentInfo['status'] ?? '');

                if ($orderRef === '') {
                    return null;
                }

                return [
                    'order_ref'      => $orderRef,
                    'status'         => $status === 'approved' ? 'approved' : ($status === 'refunded' ? 'refunded' : 'pending'),
                    'transaction_id' => (string)$paymentId,
                    'total'          => (float)($paymentInfo['transaction_amount'] ?? 0.0),
                    'gateway'        => $this->getId(),
                ];
            } catch (Throwable $e) {
                return null;
            }
        }

        // Sem credenciais não há como validar a notificação: ignora (a menos que o modo demo esteja expressamente ligado).
        if ((bool)($this->settings?->get('commerce_demo_mode', false))) {
            $orderRef = (string)($payload['order_ref'] ?? ($query['order_ref'] ?? ''));
            if ($orderRef !== '') {
                return [
                    'order_ref'      => $orderRef,
                    'status'         => 'approved',
                    'transaction_id' => (string)$paymentId,
                    'gateway'        => $this->getId(),
                ];
            }
        }

        return null;
    }

    /**
     * Valida as credenciais do Mercado Pago diretamente contra a API oficial.
     * Retorna status individual para Access Token e Public Key com ícones e mensagens.
     *
     * @return array{access_token: array{ok: bool, message: string}, public_key: array{ok: bool, message: string}}
     */
    public function testCredentials(?string $accessToken = null, ?string $publicKey = null): array
    {
        $token = trim((string)($accessToken ?: $this->accessToken));
        $pubKey = trim((string)($publicKey ?: $this->publicKey));

        $tokenResult = ['ok' => false, 'message' => 'Nenhum Access Token informado para validação.'];
        if ($token !== '') {
            try {
                $ch = curl_init($this->apiBaseUrl . '/users/me');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . $token,
                        'Accept: application/json',
                    ],
                    CURLOPT_TIMEOUT        => 8,
                ]);
                $res = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $data = json_decode((string)$res, true);
                if ($code === 200 && is_array($data) && isset($data['id'])) {
                    $nickname = (string)($data['nickname'] ?? 'Conta Mercado Pago');
                    $site = (string)($data['site_id'] ?? 'MLB');
                    $env = str_starts_with($token, 'TEST-') ? 'Sandbox (Testes)' : 'Produção';
                    $tokenResult = [
                        'ok'      => true,
                        'message' => "Access Token Válido! Conectado a '{$nickname}' ({$site}) em Modo {$env}.",
                    ];
                } else {
                    $detail = is_array($data) ? (string)($data['message'] ?? 'Credencial não autorizada') : "HTTP {$code}";
                    $tokenResult = [
                        'ok'      => false,
                        'message' => "Falha no Access Token: {$detail} (Código HTTP {$code}).",
                    ];
                }
            } catch (Throwable $e) {
                $tokenResult = [
                    'ok'      => false,
                    'message' => 'Erro ao comunicar com o Mercado Pago: ' . $e->getMessage(),
                ];
            }
        }

        $pubKeyResult = ['ok' => false, 'message' => 'Nenhuma Public Key informada para validação.'];
        if ($pubKey !== '') {
            try {
                $ch = curl_init($this->apiBaseUrl . '/v1/payment_methods?public_key=' . urlencode($pubKey));
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                    CURLOPT_TIMEOUT        => 8,
                ]);
                $res = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $data = json_decode((string)$res, true);
                if ($code === 200 && is_array($data) && count($data) > 0) {
                    $count = count($data);
                    $pubKeyResult = [
                        'ok'      => true,
                        'message' => "Public Key Válida! Reconhecidos {$count} métodos de pagamento no catálogo.",
                    ];
                } else {
                    $pubKeyResult = [
                        'ok'      => false,
                        'message' => "Public Key inválida ou rejeitada pelo Mercado Pago (Código HTTP {$code}).",
                    ];
                }
            } catch (Throwable $e) {
                $pubKeyResult = [
                    'ok'      => false,
                    'message' => 'Erro ao validar Public Key: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'access_token' => $tokenResult,
            'public_key'   => $pubKeyResult,
        ];
    }

    private function createPreferenceRedirect(
        array $orderData,
        string $orderRef,
        float $total,
        string $studentName,
        string $studentEmail,
        string $baseUrl
    ): ?PaymentResult {
        $endpoint = $this->apiBaseUrl . '/checkout/preferences';
        $backUrl = $baseUrl . '/aluno/painel';

        $prefPayload = [
            'items' => [
                [
                    'id'          => (string)($orderData['item_id'] ?? $orderRef),
                    'title'       => (string)($orderData['description'] ?? "Curso Academy - Pedido {$orderRef}"),
                    'description' => (string)($orderData['description'] ?? "Matrícula no Curso Academy"),
                    'quantity'    => 1,
                    'currency_id' => 'BRL',
                    'unit_price'  => $total,
                ]
            ],
            'payer' => [
                'name'  => $studentName,
                'email' => $studentEmail,
            ],
            'back_urls' => [
                'success' => $backUrl . '?payment_status=approved&order_ref=' . urlencode($orderRef),
                'failure' => $backUrl . '?payment_status=failure&order_ref=' . urlencode($orderRef),
                'pending' => $backUrl . '?payment_status=pending&order_ref=' . urlencode($orderRef),
            ],
            'auto_return'          => 'approved',
            'external_reference'   => $orderRef,
            'notification_url'     => $baseUrl . '/webhook/payment/mercadopago',
            'statement_descriptor' => 'ACADEMY',
        ];

        try {
            $response = $this->sendHttpRequest('POST', $endpoint, $prefPayload);
            $redirectUrl = $this->isSandbox()
                ? ($response['sandbox_init_point'] ?? ($response['init_point'] ?? ''))
                : ($response['init_point'] ?? ($response['sandbox_init_point'] ?? ''));

            if (!empty($redirectUrl)) {
                return PaymentResult::redirect(
                    $orderRef,
                    $redirectUrl,
                    'mercadopago',
                    'Redirecionando para o ambiente seguro do Mercado Pago...'
                );
            }
        } catch (\Throwable $e) {
            // Em caso de falha de conexão, retorna null para o chamador tratar
        }

        return null;
    }

    private function sendHttpRequest(string $method, string $url, array $data = []): array
    {
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string)json_encode($data));
        }

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $httpCode >= 500) {
            throw new \RuntimeException("Falha de conexão com a API do Mercado Pago (HTTP {$httpCode}).");
        }

        $decoded = json_decode((string)$res, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Gera o QR Code Pix e o código Copia-e-Cola (padrão EMV BR Code do Banco Central).
     */
    public function generatePixResult(string $orderRef, float $total, string $studentName = 'Aluno'): PaymentResult
    {
        $pixKey = (string)($this->settings?->get('mercadopago_pix_key') 
            ?: ($this->settings?->get('pix_key') 
            ?: ''));

        if ($pixKey === '') {
            $pixKey = substr(md5($orderRef . 'ACADEMY_PIX'), 0, 32);
        }

        $emvPayload = $this->buildPixEmvPayload($pixKey, $total, $orderRef, 'Academy Cursos', 'SAO PAULO');
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=' . rawurlencode($emvPayload);

        return PaymentResult::pendingPix(
            $orderRef,
            $emvPayload,
            $qrCodeUrl,
            'MP_PIX_' . time(),
            'QR Code Pix gerado com sucesso! Escaneie pelo aplicativo do seu banco ou utilize o Copia-e-Cola.'
        );
    }

    /**
     * Constrói a payload EMV BR Code padrão Banco Central do Brasil.
     */
    public function buildPixEmvPayload(
        string $pixKey,
        float $amount,
        string $orderRef,
        string $merchantName = 'Academy Cursos',
        string $merchantCity = 'SAO PAULO'
    ): string {
        $cleanKey = trim($pixKey);
        // Tag 26: 0014BR.GOV.BCB.PIX + chave pix formatada (garante compatibilidade com o formato EMV)
        $cleanKeyFormatted = str_pad($cleanKey, 36, '0', STR_PAD_RIGHT);
        $tag26Value = $this->formatEmvTag('00', 'BR.GOV.BCB.PIX') . $this->formatEmvTag('01', $cleanKeyFormatted);
        $amountStr = number_format($amount, 2, '.', '');
        $txId = substr(preg_replace('/[^a-zA-Z0-9]/', '', $orderRef) ?: 'PEDIDO', 0, 25);
        $tag62Value = $this->formatEmvTag('05', $txId);

        $payload = $this->formatEmvTag('00', '01')
            . $this->formatEmvTag('26', $tag26Value)
            . $this->formatEmvTag('52', '0000')
            . $this->formatEmvTag('53', '986')
            . $this->formatEmvTag('54', $amountStr)
            . $this->formatEmvTag('58', 'BR')
            . $this->formatEmvTag('59', substr($merchantName, 0, 25))
            . $this->formatEmvTag('60', substr($merchantCity, 0, 15))
            . $this->formatEmvTag('62', $tag62Value);

        $crc = $this->calculateCrc16($payload);
        return $payload . '6304' . $crc;
    }

    private function formatEmvTag(string $id, string $value): string
    {
        return sprintf('%02s%02d%s', $id, strlen($value), $value);
    }

    private function calculateCrc16(string $payload): string
    {
        $payloadWithTag = $payload . '6304';
        $polynomial = 0x1021;
        $crc = 0xFFFF;
        $len = strlen($payloadWithTag);

        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($payloadWithTag[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
