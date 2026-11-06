<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Contracts;

use DomainSystem\Core\Contracts\RequestInterface;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;

/**
 * Contrato Fundamental de Provedores de Pagamento (DIP / SOLID).
 * 
 * Permite que Mercado Pago, PayPal ou outros gateways funcionem de forma intercambiável,
 * sem nenhum acoplamento do sistema a APIs bancárias específicas.
 */
interface PaymentGatewayInterface
{
    /**
     * Identificador único do gateway (ex: 'mercadopago', 'paypal').
     */
    public function getId(): string;

    /**
     * Título exibido na interface pública de pagamento.
     */
    public function getTitle(): string;

    /**
     * Verifica se as credenciais de API do gateway estão configuradas no sistema (.env ou settings).
     */
    public function isConfigured(): bool;

    /**
     * Processa a cobrança (Pix, Cartão, Boleto ou redirecionamento).
     *
     * @param array<string, mixed> $orderData Dados do pedido (order_ref, total, description, student_name, student_email)
     * @param array<string, mixed> $paymentInput Dados da forma de pagamento (method, card_token, installments)
     */
    public function processPayment(array $orderData, array $paymentInput): PaymentResult;

    /**
     * Trata o webhook enviado assincronamente pelo gateway.
     * Retorna array com ['order_ref' => ..., 'status' => 'approved'|'refunded'|'pending', 'transaction_id' => ...] ou null se inválido.
     *
     * @return array<string, mixed>|null
     */
    public function handleWebhook(RequestInterface $request): ?array;
}
