<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services\Gateways;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;

/**
 * Driver de Integração com ClickBank (Instant Notification Service - INS) (SRP / DIP).
 */
class ClickBankGateway implements PaymentGatewayInterface
{
    private ?string $secretKey;
    private ?CommerceSettingsInterface $settings;

    public function __construct(
        ?string $secretKey = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->settings = $settings;
        $this->secretKey = $secretKey
            ?: ($this->settings?->get('clickbank_secret')
                ?: (getenv('CLICKBANK_SECRET') ?: null));
    }

    public function getId(): string
    {
        return 'clickbank';
    }

    public function getTitle(): string
    {
        return 'ClickBank (Plataforma Global de Afiliados)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    public function processPayment(array $orderData, array $paymentInput): PaymentResult
    {
        $orderRef = (string)($orderData['order_ref'] ?? ('ORD_' . uniqid('', true)));
        return PaymentResult::pendingPix(
            $orderRef,
            '',
            null,
            null,
            'O pagamento deste curso é processado diretamente na página de checkout da ClickBank.'
        );
    }

    public function handleWebhook(RequestInterface $request): ?array
    {
        $payload = $request->json();
        if (empty($payload)) {
            $payload = $request->all();
        }

        // Validação de Secret Key ClickBank
        $receivedSecret = (string)(
            $payload['secret']
            ?? ($payload['verification_key']
            ?? ($request->header('X-ClickBank-Verification') ?? ''))
        );

        if ($this->isConfigured()) {
            if ($receivedSecret === '' || !hash_equals((string)$this->secretKey, $receivedSecret)) {
                return null;
            }
        }

        // ClickBank INS: transactionType = 'SALE' ou 'TEST_SALE'
        $txType = strtoupper((string)($payload['transactionType'] ?? ($payload['type'] ?? '')));
        $isApproved = in_array($txType, ['SALE', 'TEST_SALE', 'BILL', 'APPROVED'], true);
        $isRefunded = in_array($txType, ['RFND', 'CGBK', 'REFUND'], true);

        if (!$isApproved && !$isRefunded) {
            return null;
        }

        $customer = isset($payload['customer']) && is_array($payload['customer']) ? $payload['customer'] : [];
        $billing = isset($customer['billing']) && is_array($customer['billing']) ? $customer['billing'] : [];

        $studentEmail = (string)(
            $billing['email']
            ?? ($payload['email']
            ?? ($customer['email'] ?? ''))
        );

        $studentName = (string)(
            $billing['fullName']
            ?? ($billing['firstName']
            ?? ($payload['name'] ?? 'Aluno ClickBank'))
        );

        $lineItems = isset($payload['lineItems']) && is_array($payload['lineItems']) ? $payload['lineItems'] : [];
        $firstItem = !empty($lineItems[0]) && is_array($lineItems[0]) ? $lineItems[0] : [];

        $productId = (string)(
            $firstItem['itemNo']
            ?? ($payload['item']
            ?? ($payload['product_id'] ?? ''))
        );

        $receipt = (string)(
            $payload['receipt']
            ?? ($payload['transaction'] ?? uniqid('CBK_', true))
        );

        $total = (float)(
            $payload['totalOrderAmount']
            ?? ($payload['total'] ?? 0.0)
        );

        return [
            'order_ref'      => 'CLICKBANK_' . $receipt,
            'status'         => $isApproved ? 'approved' : 'refunded',
            'transaction_id' => $receipt,
            'gateway'        => $this->getId(),
            'student_email'  => strtolower(trim($studentEmail)),
            'student_name'   => trim($studentName),
            'product_id'     => $productId,
            'total'          => $total,
        ];
    }
}
