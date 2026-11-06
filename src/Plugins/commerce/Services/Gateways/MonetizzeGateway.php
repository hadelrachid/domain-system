<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Services\Gateways;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;

/**
 * Driver de Integração com a Monetizze (Webhooks e Provisionamento de Alunos) (SRP / DIP).
 */
class MonetizzeGateway implements PaymentGatewayInterface
{
    private ?string $chaveUnica;
    private ?CommerceSettingsInterface $settings;

    public function __construct(
        ?string $chaveUnica = null,
        ?CommerceSettingsInterface $settings = null
    ) {
        $this->settings = $settings;
        $this->chaveUnica = $chaveUnica
            ?: ($this->settings?->get('monetizze_chave_unica')
                ?: (getenv('MONETIZZE_CHAVE_UNICA') ?: null));
    }

    public function getId(): string
    {
        return 'monetizze';
    }

    public function getTitle(): string
    {
        return 'Monetizze (Plataforma de Afiliados)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->chaveUnica);
    }

    public function processPayment(array $orderData, array $paymentInput): PaymentResult
    {
        $orderRef = (string)($orderData['order_ref'] ?? ('ORD_' . uniqid('', true)));
        return PaymentResult::pendingPix(
            $orderRef,
            '',
            null,
            null,
            'O pagamento deste curso é processado diretamente na página de checkout da Monetizze.'
        );
    }

    public function handleWebhook(RequestInterface $request): ?array
    {
        $payload = $request->json();
        if (empty($payload)) {
            $payload = $request->all();
        }

        // Validação de Chave Única da Monetizze
        $receivedChave = (string)(
            $payload['chave_unica']
            ?? ($payload['token']
            ?? ($request->header('X-Monetizze-Token') ?? ''))
        );

        if ($this->isConfigured()) {
            if ($receivedChave === '' || !hash_equals((string)$this->chaveUnica, $receivedChave)) {
                return null;
            }
        }

        // Monetizze: tipoEvento ou status = 'Finalizada' ou 'Completa'
        $statusStr = (string)(
            $payload['tipoEvento']
            ?? ($payload['status']
            ?? ($payload['dados']['status'] ?? ''))
        );

        $isApproved = in_array(strtolower($statusStr), ['finalizada', 'completa', 'aprovada', 'approved'], true);
        $isRefunded = in_array(strtolower($statusStr), ['reembolsada', 'cancelada', 'refunded'], true);

        if (!$isApproved && !$isRefunded) {
            return null;
        }

        $comprador = isset($payload['comprador']) && is_array($payload['comprador'])
            ? $payload['comprador']
            : ($payload['dados']['comprador'] ?? $payload);

        $studentEmail = (string)(
            $comprador['email']
            ?? ($payload['email'] ?? '')
        );

        $studentName = (string)(
            $comprador['nome']
            ?? ($payload['nome'] ?? 'Aluno Monetizze')
        );

        $produto = isset($payload['produto']) && is_array($payload['produto'])
            ? $payload['produto']
            : ($payload['dados']['produto'] ?? []);

        $productId = (string)(
            $produto['codigo']
            ?? ($produto['id']
            ?? ($payload['cod_produto'] ?? ''))
        );

        $transacao = isset($payload['transacao']) && is_array($payload['transacao'])
            ? $payload['transacao']
            : ($payload['dados']['transacao'] ?? []);

        $txId = (string)(
            $transacao['codigo']
            ?? ($payload['cod_transacao']
            ?? ($payload['chave_unica'] ?? uniqid('MNZ_', true)))
        );

        $total = (float)(
            $transacao['valor']
            ?? ($payload['valor'] ?? 0.0)
        );

        return [
            'order_ref'      => 'MONETIZZE_' . $txId,
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
