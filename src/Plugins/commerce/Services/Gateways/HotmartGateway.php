<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services\Gateways;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;

/**
 * Driver de Integração com Hotmart (Webhooks e Provisionamento de Alunos) (SRP / DIP).
 */
class HotmartGateway implements PaymentGatewayInterface
{
    private ?string $hottok;
    private ?CommerceSettingsInterface $settings;

    public function __construct(
        ?string $hottok = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->settings = $settings;
        $this->hottok = $hottok
            ?: ($this->settings?->get('hotmart_hottok')
                ?: (getenv('HOTMART_HOTTOK') ?: null));
    }

    public function getId(): string
    {
        return 'hotmart';
    }

    public function getTitle(): string
    {
        return 'Hotmart (Plataforma de Afiliados)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->hottok);
    }

    public function processPayment(array $orderData, array $paymentInput): PaymentResult
    {
        $orderRef = (string)($orderData['order_ref'] ?? ('ORD_' . uniqid('', true)));
        return PaymentResult::pendingPix(
            $orderRef,
            '',
            null,
            null,
            'O pagamento deste curso é processado diretamente na página de checkout da Hotmart.'
        );
    }

    public function handleWebhook(RequestInterface $request): ?array
    {
        $payload = $request->json();
        if (empty($payload)) {
            $payload = $request->all();
        }

        // Validação de Segurança (Hottok)
        $receivedHottok = (string)(
            $payload['hottok']
            ?? ($payload['data']['hottok']
            ?? ($request->header('X-Hotmart-Hottok')
            ?? ($request->header('x-hotmart-hottok') ?? '')))
        );

        if ($this->isConfigured()) {
            if ($receivedHottok === '' || !hash_equals((string)$this->hottok, $receivedHottok)) {
                return null;
            }
        }

        // Suporte aos formatos v2 e v1 da Hotmart
        $event = (string)($payload['event'] ?? ($payload['status'] ?? ''));
        $isApproved = in_array(strtoupper($event), ['PURCHASE_APPROVED', 'PURCHASE_COMPLETE', 'APPROVED', 'COMPLETE'], true);
        $isRefunded = in_array(strtoupper($event), ['PURCHASE_REFUNDED', 'PURCHASE_CANCELED', 'REFUNDED'], true);

        if (!$isApproved && !$isRefunded) {
            return null;
        }

        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

        $studentEmail = (string)(
            $data['buyer']['email']
            ?? ($data['email']
            ?? ($payload['email'] ?? ''))
        );

        $studentName = (string)(
            $data['buyer']['name']
            ?? ($data['name']
            ?? ($data['first_name']
            ?? ($payload['name'] ?? 'Aluno Hotmart')))
        );

        $productId = (string)(
            $data['product']['id']
            ?? ($data['product']['ucode']
            ?? ($data['prod']
            ?? ($payload['prod'] ?? '')))
        );

        $txId = (string)(
            $data['purchase']['transaction']
            ?? ($data['transaction']
            ?? ($payload['transaction'] ?? uniqid('HTM_', true)))
        );

        $total = (float)(
            $data['purchase']['price']['value']
            ?? ($data['price']
            ?? ($payload['price'] ?? 0.0))
        );

        return [
            'order_ref'      => 'HOTMART_' . $txId,
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
