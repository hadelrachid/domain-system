<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services\Gateways;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;

/**
 * Driver de Integração com a Eduzz (Webhooks e Provisionamento de Alunos) (SRP / DIP).
 */
class EduzzGateway implements PaymentGatewayInterface
{
    private ?string $token;
    private ?CommerceSettingsInterface $settings;

    public function __construct(
        ?string $token = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->settings = $settings;
        $this->token = $token
            ?: ($this->settings?->get('eduzz_token')
                ?: (getenv('EDUZZ_TOKEN') ?: null));
    }

    public function getId(): string
    {
        return 'eduzz';
    }

    public function getTitle(): string
    {
        return 'Eduzz (Plataforma de Afiliados)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    public function processPayment(array $orderData, array $paymentInput): PaymentResult
    {
        $orderRef = (string)($orderData['order_ref'] ?? ('ORD_' . uniqid('', true)));
        return PaymentResult::pendingPix(
            $orderRef,
            '',
            null,
            null,
            'O pagamento deste curso é processado diretamente na página de checkout da Eduzz.'
        );
    }

    public function handleWebhook(RequestInterface $request): ?array
    {
        $payload = $request->json();
        if (empty($payload)) {
            $payload = $request->all();
        }

        // Validação de Segurança da Eduzz (api_key ou token)
        $receivedToken = (string)(
            $payload['api_key']
            ?? ($payload['token']
            ?? ($payload['secret']
            ?? ($request->header('X-Eduzz-Token') ?? '')))
        );

        if ($this->isConfigured()) {
            if ($receivedToken === '' || !hash_equals((string)$this->token, $receivedToken)) {
                return null;
            }
        }

        // Eduzz: trans_status = 3 (Paga) ou 4 (Disponível) ou 'pago' / 'approved'
        $rawStatus = (string)($payload['trans_status'] ?? ($payload['status'] ?? ''));
        $isApproved = in_array($rawStatus, ['3', '4', 'pago', 'paid', 'approved'], true);
        $isRefunded = in_array($rawStatus, ['6', '7', 'reembolsado', 'refunded'], true);

        if (!$isApproved && !$isRefunded) {
            return null;
        }

        $studentEmail = (string)(
            $payload['cus_email']
            ?? ($payload['email']
            ?? ($payload['cliente_email'] ?? ''))
        );

        $studentName = (string)(
            $payload['cus_name']
            ?? ($payload['name']
            ?? ($payload['cliente_nome'] ?? 'Aluno Eduzz'))
        );

        $productId = (string)(
            $payload['pro_id']
            ?? ($payload['pro_cod']
            ?? ($payload['product_id'] ?? ''))
        );

        $txId = (string)(
            $payload['trans_id']
            ?? ($payload['trans_cod']
            ?? ($payload['transaction'] ?? uniqid('EDZ_', true)))
        );

        $total = (float)(
            $payload['val_liquid']
            ?? ($payload['val_total']
            ?? ($payload['value'] ?? 0.0))
        );

        return [
            'order_ref'      => 'EDUZZ_' . $txId,
            'status'         => $isApproved ? 'approved' : 'refunded',
            'transaction_id' => $txId,
            'gateway'        => $this->getId(),
            'student_email'  => strtolower(trim($studentEmail)),
            'student_name'   => trim($studentName),
            'product_id'     => $productId,
            'total'          => $total,
        ];
    }
}
